<?php
session_start();
require __DIR__ . '/../../config/config.php';

// Only admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Admin';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search/filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = $search ? "WHERE name LIKE ? OR email LIKE ?" : "";

// Add Student
if(isset($_POST['add_student'])){
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $age = (int)$_POST['age'];
    $phone = htmlspecialchars(trim($_POST['phone']));
    $city = htmlspecialchars(trim($_POST['city']));
    $gender = $_POST['gender'];
    $course = $_POST['course'];
    $semester = $_POST['semester'];
    $address = htmlspecialchars(trim($_POST['address']));
    $status = $_POST['status'];

    $stmt_check = $conn->prepare("SELECT id FROM students WHERE email=?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    if($stmt_check->num_rows > 0){
        $stmt_check->close();
        echo '<script>alert("Email already exists!");window.location.href="manage_students.php";</script>';
        exit();
    }
    $stmt_check->close();

    $stmt = $conn->prepare("INSERT INTO students (name,email,age,phone,city,gender,course,semester,address,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssissssiss",$name,$email,$age,$phone,$city,$gender,$course,$semester,$address,$status);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_students.php");
    exit();
}

// Update Student
if(isset($_POST['edit_student'])){
    $id = (int)$_POST['id'];
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $age = (int)$_POST['age'];
    $phone = htmlspecialchars(trim($_POST['phone']));
    $city = htmlspecialchars(trim($_POST['city']));
    $gender = $_POST['gender'];
    $course = $_POST['course'];
    $semester = $_POST['semester'];
    $address = htmlspecialchars(trim($_POST['address']));
    $status = $_POST['status'];

    $stmt_check = $conn->prepare("SELECT id FROM students WHERE email=? AND id<>?");
    $stmt_check->bind_param("si", $email, $id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if($stmt_check->num_rows > 0){
        $stmt_check->close();
        echo '<script>alert("Email already exists!");window.location.href="manage_students.php";</script>';
        exit();
    }
    $stmt_check->close();

    $stmt = $conn->prepare("UPDATE students SET name=?, email=?, age=?, phone=?, city=?, gender=?, course=?, semester=?, address=?, status=? WHERE id=?");
    $stmt->bind_param("ssissssissi",$name,$email,$age,$phone,$city,$gender,$course,$semester,$address,$status,$id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_students.php");
    exit();
}

// Delete Student via POST
if(isset($_POST['delete_student'])){
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_students.php");
    exit();
}

// Fetch students with search & pagination
if($search){
    $stmt = $conn->prepare("SELECT * FROM students WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ?,?");
    $like_search = "%$search%";
    $stmt->bind_param("ssii", $like_search, $like_search, $start, $limit);
    $stmt->execute();
    $students = $stmt->get_result();
    $stmt->close();

    $total_result = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE name LIKE ? OR email LIKE ?");
    $total_result->bind_param("ss", $like_search, $like_search);
    $total_result->execute();
    $total_students = $total_result->get_result()->fetch_assoc()['count'];
}else{
    $students = $conn->query("SELECT * FROM students ORDER BY id DESC LIMIT $start,$limit");
    $total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
}

$total_pages = ceil($total_students / $limit);

$courses_list = ['B.Tech','M.Tech','B.Sc','M.Sc','MBA','BCA','MCA'];
$semesters_list = ['1','2','3','4','5','6','7','8'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students - Admin Dashboard</title>
<style>
/* ===== Base (Desktop first) ===== */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
body{display:flex;min-height:100vh;background:#f5f7fa;}
.sidebar{width:250px;background:#2c3e50;color:white;display:flex;flex-direction:column;padding:20px 0;position:fixed;height:100%;transition:all 0.3s ease;z-index:1000;}
.sidebar h2{text-align:center;margin-bottom:30px;font-size:22px;}
.sidebar a{text-decoration:none;color:white;padding:15px 20px;display:block;transition:background 0.2s;font-size:16px;}
.sidebar a:hover{background:#34495e;}
.sidebar a.logout{margin-top:auto;background:#c0392b;}
.sidebar a.logout:hover{background:#e74c3c;}
.main-content{margin-left:250px;flex:1;padding:20px;transition:margin-left 0.3s ease;width:100%;}
.header{background:white;padding:15px 20px;box-shadow:0 2px 5px rgba(0,0,0,0.1);margin-bottom:20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;}
.header h1{font-size:24px;color:#333;}
.hamburger{display:none;font-size:26px;cursor:pointer;background:none;border:none;color:#2c3e50;}

.content{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.1);} 
.content h2{margin-bottom:12px;font-size:20px;color:#2c3e50;}

.actions-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;}
.search-box{display:flex;gap:8px;align-items:center;}
.search-box input[type="text"]{padding:8px 10px;border:1px solid #ccc;border-radius:6px;min-width:260px;}
.search-box button{padding:8px 12px;border:none;border-radius:6px;background:#2980b9;color:#fff;cursor:pointer;}
.search-box button:hover{background:#3498db;}

.table-wrapper{overflow-x:auto;}
.content table{width:100%;border-collapse:collapse;min-width:1000px;}
.content table th, .content table td{border:1px solid #ddd;padding:10px;text-align:left;white-space:nowrap;}
.content table th{background:#34495e;color:white;}

.pagination{display:flex;gap:6px;align-items:center;justify-content:flex-end;margin-top:14px;flex-wrap:wrap;}
.pagination a, .pagination span{padding:6px 10px;border:1px solid #ccc;border-radius:6px;text-decoration:none;color:#2c3e50;}
.pagination a.active{background:#2c3e50;color:#fff;border-color:#2c3e50;}
.pagination a:hover{background:#ecf0f1;}

.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;overflow-y:auto;padding:20px;}
.modal-content{background:white;padding:20px;border-radius:8px;width:90%;max-width:520px;position:relative;max-height:90vh;overflow-y:auto;}
.modal-content h3{margin-bottom:15px;color:#2c3e50;}
.modal-content label{display:block;margin:10px 0 5px;}
.modal-content input, .modal-content select, .modal-content textarea{width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;}
.close{position:absolute;top:10px;right:15px;font-size:20px;cursor:pointer;color:#c0392b;}

/* Buttons */
.btn{padding:6px 12px;border:none;border-radius:6px;cursor:pointer;display:inline-block;margin-right:6px;font-size:14px;transition:background 0.25s, transform 0.15s;}
.btn-add{background:#27ae60;color:white;}
.btn-add:hover{background:#2ecc71;}
.btn-edit{background:#2980b9;color:white;}
.btn-edit:hover{background:#3498db;transform:scale(1.04);} 
.btn-delete{background:#c0392b;color:white;}
.btn-delete:hover{background:#e74c3c;transform:scale(1.04);} 

/* ====== Media Queries ====== */
/* Laptop (<= 1366px) */
@media(max-width:1366px){.sidebar{width:220px;}.main-content{margin-left:220px;}}
/* Tablet Landscape (<= 1023px) */
@media(max-width:1023px){.sidebar{width:200px;}.main-content{margin-left:200px;}.search-box input[type="text"]{min-width:220px;}}
/* Tablet Portrait (<= 768px) */
@media(max-width:768px){.sidebar{width:180px;}.main-content{margin-left:180px;}.header h1{font-size:20px;}.search-box input[type="text"]{min-width:200px;}}
/* Mobile (<= 599px) */
@media(max-width:599px){body{flex-direction:column;}.sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px;}.sidebar.active{left:0;}.hamburger{display:block;}.main-content{margin-left:0;padding:15px;}
.header h1{font-size:18px;}
.table-wrapper{overflow-x:auto;}
.btn{font-size:12px;padding:5px 8px;margin-bottom:5px;}
.actions-bar{flex-direction:column;align-items:stretch;}
.search-box{width:100%;}
.search-box input[type="text"]{min-width:unset;width:100%;}
}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
<h2>Admin Panel</h2>
<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="manage_users.php">👤 Manage Users</a>
<a href="manage_faculty.php">🏫 Manage Faculty</a>
<a href="manage_course.php">📚 Courses</a>
<a href="reports.php">📊 Reports</a>
<a href="settings.php">⚙️ Settings</a>
<a href="../auth/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main-content" id="main">
  <div class="header">
    <h1>Welcome Admin, <span style="color:#2c3e50;"><?= htmlspecialchars($adminName) ?></span> 👋</h1>
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
  </div>

  <div class="content">
    <h2>Manage Students</h2>

    <div class="actions-bar">
      <button class="btn btn-add" onclick="document.getElementById('addModal').style.display='flex'">➕ Add Student</button>
      <form class="search-box" method="GET" action="">
        <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
      </form>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Name</th><th>Email</th><th>Age</th><th>Phone</th><th>City</th><th>Gender</th><th>Course</th><th>Semester</th><th>Address</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $students->fetch_assoc()){ ?>
          <tr>
            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['email']?? '') ?></td>
            <td><?= (int)$row['age'] ?></td>
            <td><?= htmlspecialchars($row['phone']?? '') ?></td>
            <td><?= htmlspecialchars($row['city']?? '') ?></td>
            <td><?= htmlspecialchars($row['gender']?? '') ?></td>
            <td><?= htmlspecialchars($row['course']?? '') ?></td>
            <td><?= htmlspecialchars($row['semester']?? '') ?></td>
            <td><?= htmlspecialchars($row['address']?? '') ?></td>
            <td><?= htmlspecialchars($row['status']?? '') ?></td>
            <td>
              <button class="btn btn-edit" onclick='openEditModal(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>✏️ Edit</button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" name="delete_student" class="btn btn-delete">🗑️ Delete</button>
              </form>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="pagination">
      <?php 
        $qs = $search ? ('&search='.urlencode($search)) : '';
        if($page>1){ echo '<a href="?page='.($page-1).$qs.'">Prev</a>'; }
        for($i=1;$i<=$total_pages;$i++){
            $active = $i===$page ? 'class="active"' : '';
            echo '<a '.$active.' href="?page='.$i.$qs.'">'.$i.'</a>';
        }
        if($page<$total_pages){ echo '<a href="?page='.($page+1).$qs.'">Next</a>'; }
      ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Add Modal -->
<div class="modal" id="addModal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
    <h3>Add Student</h3>
    <form method="POST">
      <label>Name</label><input type="text" name="name" required>
      <label>Email</label><input type="email" name="email" required>
      <label>Age</label><input type="number" name="age" min="1" required>
      <label>Phone</label><input type="text" name="phone" required>
      <label>City</label><input type="text" name="city" required>
      <label>Gender</label>
      <select name="gender" required>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
      <label>Course</label>
      <select name="course" required>
        <?php foreach($courses_list as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Semester</label>
      <select name="semester" required>
        <?php foreach($semesters_list as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Address</label><textarea name="address" rows="2" required></textarea>
      <label>Status</label>
      <select name="status" required>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
      </select>
      <button type="submit" name="add_student" class="btn btn-add">Add Student</button>
    </form>
  </div>
</div>

<!-- Reusable Edit Modal (empty -> populated via JS) -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
    <h3>Edit Student</h3>
    <form method="POST" id="editForm">
      <input type="hidden" name="id" id="edit_id">
      <label>Name</label><input type="text" name="name" id="edit_name" required>
      <label>Email</label><input type="email" name="email" id="edit_email" required>
      <label>Age</label><input type="number" name="age" id="edit_age" min="1" required>
      <label>Phone</label><input type="text" name="phone" id="edit_phone" required>
      <label>City</label><input type="text" name="city" id="edit_city" required>
      <label>Gender</label>
      <select name="gender" id="edit_gender" required>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>
      <label>Course</label>
      <select name="course" id="edit_course" required>
        <?php foreach($courses_list as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Semester</label>
      <select name="semester" id="edit_semester" required>
        <?php foreach($semesters_list as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Address</label><textarea name="address" id="edit_address" rows="2" required></textarea>
      <label>Status</label>
      <select name="status" id="edit_status" required>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
      </select>
      <button type="submit" name="edit_student" class="btn btn-edit">Update Student</button>
    </form>
  </div>
</div>

<script>
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('active');
}

// Open & populate reusable edit modal
function openEditModal(student){
  try{
    const data = typeof student === 'string' ? JSON.parse(student) : student;
    document.getElementById('edit_id').value = data.id || '';
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_age').value = data.age || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_city').value = data.city || '';
    document.getElementById('edit_gender').value = data.gender || '';
    document.getElementById('edit_course').value = data.course || '';
    document.getElementById('edit_semester').value = data.semester || '';
    document.getElementById('edit_address').value = data.address || '';
    document.getElementById('edit_status').value = data.status || '';
    document.getElementById('editModal').style.display='flex';
  }catch(e){
    alert('Unable to open edit modal.');
    console.error(e);
  }
}

// Close modals when clicking outside content
window.onclick = function(event) {
  if(event.target.classList && event.target.classList.contains('modal')){
    event.target.style.display='none';
  }
}
</script>
</body>
</html>
