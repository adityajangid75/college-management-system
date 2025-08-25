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
    $code = $conn->real_escape_string(trim($_POST['code'] ?? ''));
    $description = $conn->real_escape_string(trim($_POST['description'] ?? ''));
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

// Delete Course
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM courses WHERE id=$id");
    $success = "Course deleted successfully!";
}

// Fetch all courses
$coursesQuery = $conn->query("SELECT c.*, f.name as faculty_name FROM courses c LEFT JOIN faculty f ON c.faculty_id=f.id ORDER BY c.id DESC");
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
table{width:100%;border-collapse:collapse;table-layout:fixed}
th, td{border:1px solid #ddd;padding:8px;text-align:left;word-wrap:break-word}
th{background:#2c3e50;color:#fff}
td:last-child{width:150px;display:flex;gap:5px}
.btn{flex:1;text-align:center;text-decoration:none;padding:5px 0;border-radius:4px;color:#fff}
.edit{background:#2980b9}
.edit:hover{background:#3498db}
.delete{background:#c0392b}
.delete:hover{background:#e74c3c}

/* Messages */
.success{color:green;margin-bottom:10px}
.error{color:red;margin-bottom:10px}

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
<textarea name="description" required><?= htmlspecialchars($course['description']) ?></textarea>
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
<table>
<tr><th>ID</th><th>Name</th><th>Code</th><th>Description</th><th>Faculty</th><th>Actions</th></tr>
<?php while($c = $coursesQuery->fetch_assoc()): ?>
<tr>
<td><?=$c['id']?></td>
<td><?=htmlspecialchars($c['name'])?></td>
<td><?=htmlspecialchars($c['code'])?></td>
<td><?=htmlspecialchars($c['description'])?></td>
<td><?=htmlspecialchars($c['faculty_name'])?></td>
<td>
<a href="?edit=<?=$c['id']?>" class="btn edit">Edit</a>
<a href="?delete=<?=$c['id']?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<script>
// Mobile sidebar toggle
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
}
</script>

</body>
</html>
