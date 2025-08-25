<?php
session_start();
require __DIR__ . '/../../config/config.php';

// Only admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Admin';

// Add Student
if(isset($_POST['add_student'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $city = $_POST['city'];
    $gender = $_POST['gender'];
    $course = $_POST['course'];
    $semester = $_POST['semester'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO students (name,email,age,phone,city,gender,course,semester,address,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssissssiss",$name,$email,$age,$phone,$city,$gender,$course,$semester,$address,$status);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_students.php");
    exit();
}

// Update Student
if(isset($_POST['edit_student'])){
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $city = $_POST['city'];
    $gender = $_POST['gender'];
    $course = $_POST['course'];
    $semester = $_POST['semester'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE students SET name=?, email=?, age=?, phone=?, city=?, gender=?, course=?, semester=?, address=?, status=? WHERE id=?");
    $stmt->bind_param("ssissssissi",$name,$email,$age,$phone,$city,$gender,$course,$semester,$address,$status,$id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_students.php");
    exit();
}

// Fetch all students
$students = $conn->query("SELECT * FROM students ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students - Admin Dashboard</title>
<style>
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
.content h2{margin-bottom:10px;font-size:20px;color:#2c3e50;}
.table-wrapper{overflow-x:auto;}
.content table{width:100%;border-collapse:collapse;min-width:1000px;}
.content table th, .content table td{border:1px solid #ddd;padding:10px;text-align:left;white-space:nowrap;}
.content table th{background:#34495e;color:white;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;overflow-y:auto;padding:20px;}
.modal-content{background:white;padding:20px;border-radius:8px;width:90%;max-width:500px;position:relative;max-height:90vh;overflow-y:auto;}
.modal-content h3{margin-bottom:15px;color:#2c3e50;}
.modal-content label{display:block;margin:10px 0 5px;}
.modal-content input, .modal-content select, .modal-content textarea{width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:4px;}
.close{position:absolute;top:10px;right:15px;font-size:20px;cursor:pointer;color:#c0392b;}

/* Responsive */
@media(max-width:1366px){.sidebar{width:220px;}.main-content{margin-left:220px;}}
@media(max-width:1023px){.sidebar{width:200px;}.main-content{margin-left:200px;}}
@media(max-width:767px){.sidebar{width:180px;}.main-content{margin-left:180px;}.sidebar a{font-size:14px;padding:12px 15px;}}
@media(max-width:599px){body{flex-direction:column;}.sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px;}.sidebar.active{left:0;}.hamburger{display:block;}.main-content{margin-left:0;padding:15px;}.header h1{font-size:18px;}.table-wrapper{overflow-x:auto;}}

/* Buttons */
.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    display: inline-block;
    margin-right: 5px;
    font-size: 14px;
    transition: background 0.3s, transform 0.2s;
}

/* Add Button */
.btn-add {
    background: #27ae60;
    color: white;
}
.btn-add:hover {
    background: #2ecc71;
}

/* Edit Button */
.btn-edit {
    background: #2980b9;
    color: white;
}
.btn-edit:hover {
    background: #3498db;
    transform: scale(1.05);
}

/* Delete Button */
.btn-delete {
    background: #c0392b;
    color: white;
}
.btn-delete:hover {
    background: #e74c3c;
    transform: scale(1.05);
}

/* Mobile adjustments */
@media(max-width:599px){
    .btn {
        font-size: 12px;
        padding: 5px 8px;
        margin-bottom: 5px;
    }
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
<a href="report.php">📊 Reports</a>
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
<button class="btn btn-add" onclick="document.getElementById('addModal').style.display='flex'">➕ Add Student</button>

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
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['age']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['city']; ?></td>
<td><?php echo $row['gender']; ?></td>
<td><?php echo $row['course']; ?></td>
<td><?php echo $row['semester']; ?></td>
<td><?php echo $row['address']; ?></td>
<td><?php echo $row['status']; ?></td>
<td>
<button class="btn-edit" onclick='openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)'>✏️ Edit</button>
<a href="delete_student.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
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
<label>Age</label><input type="number" name="age" required>
<label>Phone</label><input type="text" name="phone" required>
<label>City</label><input type="text" name="city" required>
<label>Gender</label>
<select name="gender" required>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>
<label>Course</label><input type="text" name="course" required>
<label>Semester</label><input type="text" name="semester" required>
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

<script>
function toggleSidebar(){
document.getElementById('sidebar').classList.toggle('active');
}

// Open Edit Modal with data
function openEditModal(student){
let modal = document.getElementById('editModal');
if(!modal){
modal = document.createElement('div');
modal.id = 'editModal';
modal.className = 'modal';
modal.innerHTML = `
<div class="modal-content">
<span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
<h3>Edit Student</h3>
<form method="POST">
<input type="hidden" name="id" value="${student.id}">
<label>Name</label><input type="text" name="name" value="${student.name}" required>
<label>Email</label><input type="email" name="email" value="${student.email}" required>
<label>Age</label><input type="number" name="age" value="${student.age}" required>
<label>Phone</label><input type="text" name="phone" value="${student.phone}" required>
<label>City</label><input type="text" name="city" value="${student.city}" required>
<label>Gender</label>
<select name="gender" required>
<option value="Male" ${student.gender=='Male'?'selected':''}>Male</option>
<option value="Female" ${student.gender=='Female'?'selected':''}>Female</option>
<option value="Other" ${student.gender=='Other'?'selected':''}>Other</option>
</select>
<label>Course</label><input type="text" name="course" value="${student.course}" required>
<label>Semester</label><input type="text" name="semester" value="${student.semester}" required>
<label>Address</label><textarea name="address" rows="2" required>${student.address}</textarea>
<label>Status</label>
<select name="status" required>
<option value="Active" ${student.status=='Active'?'selected':''}>Active</option>
<option value="Inactive" ${student.status=='Inactive'?'selected':''}>Inactive</option>
</select>
<button type="submit" name="edit_student" class="btn btn-edit">Update Student</button>
</form>
</div>
`;
document.body.appendChild(modal);
}
modal.style.display = 'flex';
}

// Close modals when clicking outside content
window.onclick = function(event) {
if(event.target.classList.contains('modal')){
event.target.style.display='none';
}
}
</script>
</body>
</html>
