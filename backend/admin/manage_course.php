<?php
session_start();
require __DIR__ . '/../../config/config.php'; // DB connection

// Only admins allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Admin';

// Handle Add / Edit / Delete
$editMode = false;
$course = ['id'=>'','name'=>'','code'=>'','description'=>'','faculty_id'=>''];

// Fetch faculty list for dropdown
$facultyQuery = $conn->query("SELECT id, name FROM faculty ORDER BY name ASC");

// Add / Update Course
if(isset($_POST['save_course'])){
    $id = $_POST['id'] ?? '';
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $code = strtoupper($conn->real_escape_string(trim($_POST['code'] ?? ''))); // always uppercase
    $description = $conn->real_escape_string(substr(trim($_POST['description'] ?? ''),0,500)); // max 500 chars
    $faculty_id = intval($_POST['faculty_id'] ?? 0);

    if($name === '' || $code === '' || $faculty_id === 0){
        $error = "Please fill all required fields!";
    } else {
        // Duplicate check on code
        $dupQuery = "SELECT id FROM courses WHERE code='$code'";
        if($id !== '') $dupQuery .= " AND id != $id";
        $dupCheck = $conn->query($dupQuery);

        if($dupCheck->num_rows > 0){
            $error = "Course code already exists!";
        } else {
            if($id !== ''){
                // Update
                $conn->query("UPDATE courses SET name='$name', code='$code', description='$description', faculty_id='$faculty_id' WHERE id=$id");
                $success = "Course updated successfully!";
            } else {
                // Insert
                $conn->query("INSERT INTO courses (name, code, description, faculty_id) VALUES ('$name','$code','$description','$faculty_id')");
                $success = "Course added successfully!";
            }
        }
    }
}

// Edit Course
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM courses WHERE id=$id");
    if($res->num_rows){
        $course = $res->fetch_assoc();
        $editMode = true;
    }
}

// Delete Course with error handling
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    try{
        $conn->query("DELETE FROM courses WHERE id=$id");
        if($conn->affected_rows>0){
            $success = "Course deleted successfully!";
        } else {
            $error = "Course not found or already deleted.";
        }
    }catch(Exception $e){
        $error = "Unable to delete course. It might be linked with other records!";
    }
}

// --- Search + Pagination ---
$search = $conn->real_escape_string(trim($_GET['search'] ?? ''));
$where = "";
if($search !== ''){
    $where = "WHERE c.name LIKE '%$search%' OR c.code LIKE '%$search%' OR f.name LIKE '%$search%'";
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page-1)*$limit;

// Count total
$countRes = $conn->query("SELECT COUNT(*) as total FROM courses c LEFT JOIN faculty f ON c.faculty_id=f.id $where");
$totalRows = $countRes->fetch_assoc()['total'];
$totalPages = ceil($totalRows/$limit);

// Fetch courses with search + pagination
$coursesQuery = $conn->query("SELECT c.*, f.name as faculty_name 
    FROM courses c 
    LEFT JOIN faculty f ON c.faculty_id=f.id 
    $where 
    ORDER BY c.id DESC 
    LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Courses - Admin Dashboard</title>
<style>
/* === Base styles === */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
body{display:flex;min-height:100vh;background:#f5f7fa}
.sidebar{width:250px;background:#2c3e50;color:#fff;display:flex;flex-direction:column;padding:20px 0;position:fixed;height:100%;transition:.3s;z-index:1000}
.sidebar h2{text-align:center;margin-bottom:30px;font-size:22px}
.sidebar a{text-decoration:none;color:#fff;padding:15px 20px;display:block;transition:.2s;font-size:16px}
.sidebar a:hover{background:#34495e}
.sidebar a.logout{margin-top:auto;background:#c0392b}
.sidebar a.logout:hover{background:#e74c3c}
.main-content{margin-left:250px;flex:1;padding:20px;transition:margin-left .3s;width:100%}
.header{background:#fff;padding:15px 20px;box-shadow:0 2px 5px rgba(0,0,0,.1);margin-bottom:20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
.header h1{font-size:24px;color:#333}
.hamburger{display:none;font-size:26px;cursor:pointer;background:none;border:none;color:#2c3e50}
.content{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1);margin-bottom:20px}
.content h2{margin-bottom:10px;font-size:20px;color:#2c3e50}
.content p{font-size:16px;line-height:1.6;color:#555}

/* Form */
.form-container{margin-bottom:20px}
.form-group{margin-bottom:12px}
label{display:block;margin-bottom:5px;color:#333}
input, select, textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;font-size:16px}
textarea{resize:vertical}
button{padding:10px 20px;background:#2c3e50;color:#fff;border:none;border-radius:4px;font-size:16px;cursor:pointer}
button:hover{background:#34495e}

/* Table */
.table-container{width:100%;overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:800px}
th, td{border:1px solid #ddd;padding:8px;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
th{background:#2c3e50;color:#fff}
td:last-child{width:150px;display:flex;gap:5px}
.btn{flex:1;text-align:center;text-decoration:none;padding:6px 0;border-radius:6px;color:#fff;font-size:14px}
.edit{background:#2980b9}
.edit:hover{background:#3498db}
.delete{background:#c0392b}
.delete:hover{background:#e74c3c}

/* Messages */
.success{color:green;margin-bottom:10px}
.error{color:red;margin-bottom:10px}

/* Search */
.search-bar{margin-bottom:15px;display:flex;gap:10px}
.search-bar input{flex:1;padding:8px;border:1px solid #ccc;border-radius:4px;font-size:15px}
.search-bar button{padding:8px 15px;background:#27ae60;border:none;color:#fff;border-radius:4px;cursor:pointer}
.search-bar button:hover{background:#2ecc71}

/* Pagination */
.pagination{margin-top:15px;display:flex;gap:8px;flex-wrap:wrap}
.pagination a{padding:6px 12px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:4px;font-size:14px}
.pagination a.active{background:#27ae60}
.pagination a:hover{background:#34495e}

/* === Responsive === */
@media (max-width:1366px){.sidebar{width:220px}.main-content{margin-left:220px}.header h1{font-size:22px}}
@media (max-width:1023px){.sidebar{width:200px}.main-content{margin-left:200px}.header h1{font-size:20px}.content h2{font-size:18px}}
@media (max-width:767px){.sidebar{width:180px}.main-content{margin-left:180px}.sidebar a{font-size:14px;padding:12px 15px}.header h1{font-size:18px}.content h2{font-size:17px}.content p{font-size:14px}}
@media (max-width:599px){
  body{flex-direction:column}
  .sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px}
  .sidebar.active{left:0}
  .hamburger{display:block}
  .main-content{margin-left:0;padding:15px}
  .header h1{font-size:16px}
  .content h2{font-size:16px}
  .content p{font-size:13px}
}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
<h2>Admin Panel</h2>
<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="manage_users.php">👤 Manage Users</a>
<a href="manage_faculty.php">🏫 Manage Faculty</a>
<a href="manage_student.php">🎓 Manage Students</a>
<a href="manage_course.php">📚 Courses</a>
<a href="reports.php">📊 Reports</a>
<a href="settings.php">⚙️ Settings</a>
<a href="../auth/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main-content" id="main">
<div class="header">
<h1>Welcome Admin, <span style="color:#2c3e50;"><?=htmlspecialchars($adminName)?></span> 👋</h1>
<button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('active')">☰</button>
</div>

<div class="content">
<h2><?= $editMode ? "Edit Course" : "Add New Course" ?></h2>

<?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>
<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

<div class="form-container">
<form method="POST">
<input type="hidden" name="id" value="<?= $course['id'] ?>">
<div class="form-group">
<label>Course Name</label>
<input type="text" name="name" value="<?= htmlspecialchars($course['name']) ?>" required>
</div>
<div class="form-group">
<label>Course Code</label>
<input type="text" name="code" value="<?= htmlspecialchars($course['code']) ?>" required>
</div>
<div class="form-group">
<label>Description</label>
<textarea name="description" maxlength="500" required><?= htmlspecialchars($course['description']) ?></textarea>
</div>
<div class="form-group">
<label>Assigned Faculty</label>
<select name="faculty_id" required>
<option value="">Select Faculty</option>
<?php
$facultyQuery->data_seek(0); // reset pointer
while($f = $facultyQuery->fetch_assoc()):
?>
<option value="<?=$f['id']?>" <?=($f['id']==$course['faculty_id'])?'selected':''?>><?=htmlspecialchars($f['name'])?></option>
<?php endwhile; ?>
</select>
</div>
<button type="submit" name="save_course"><?= $editMode ? "Update Course" : "Add Course" ?></button>
</form>
</div>
</div>

<div class="content">
<h2>All Courses</h2>

<!-- Search bar -->
<form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Search by name, code or faculty..." value="<?=htmlspecialchars($search)?>">
    <button type="submit">Search</button>
</form>

<div class="table-container">
<table>
<tr><th>ID</th><th>Name</th><th>Code</th><th>Description</th><th>Faculty</th><th>Actions</th></tr>
<?php if($coursesQuery->num_rows>0): ?>
<?php while($c = $coursesQuery->fetch_assoc()): ?>
<tr>
<td><?=$c['id']?></td>
<td><?=htmlspecialchars($c['name'] ?? '')?></td>
<td><?=htmlspecialchars($c['code'] ?? '')?></td>
<td><?=htmlspecialchars($c['description'] ?? '')?></td>
<td><?=htmlspecialchars($c['faculty_name'] ?? '')?></td>
<td>
<a href="?edit=<?=$c['id']?>" class="btn edit">Edit</a>
<a href="?delete=<?=$c['id']?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">No courses found.</td></tr>
<?php endif; ?>
</table>
</div>

<!-- Pagination -->
<?php if($totalPages>1): ?>
<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="?page=<?=$i?>&search=<?=urlencode($search)?>" class="<?=$i==$page?'active':''?>"><?=$i?></a>
<?php endfor; ?>
</div>
<?php endif; ?>

</div>

<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
}
</script>

</body>
</html>
