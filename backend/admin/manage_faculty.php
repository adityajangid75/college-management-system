<?php
session_start();
require __DIR__ . '/../../config/config.php'; // DB connection

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Add Faculty
if(isset($_POST['addFaculty'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $designation = $_POST['designation'];
    $joining_date = $_POST['joining_date'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO faculty (name,email,phone,department,designation,joining_date,address,gender,status) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssss",$name,$email,$phone,$department,$designation,$joining_date,$address,$gender,$status);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_faculty.php");
    exit();
}

// Update Faculty
if(isset($_POST['editFaculty'])){
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department = $_POST['department'];
    $designation = $_POST['designation'];
    $joining_date = $_POST['joining_date'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE faculty SET name=?, email=?, phone=?, department=?, designation=?, joining_date=?, address=?, gender=?, status=? WHERE id=?");
    $stmt->bind_param("sssssssssi",$name,$email,$phone,$department,$designation,$joining_date,$address,$gender,$status,$id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_faculty.php");
    exit();
}

// Fetch all faculty
$faculty = $conn->query("SELECT * FROM faculty ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Faculty - Admin Dashboard</title>
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
.content table{width:100%;border-collapse:collapse;min-width:900px;}
.content table th, .content table td{border:1px solid #ddd;padding:10px;text-align:left;white-space:nowrap;}
.content table th{background:#34495e;color:white;}
.btn{padding:6px 12px;border:none;border-radius:4px;cursor:pointer;display:inline-block;margin-right:5px;}
.btn-add{background:#27ae60;color:white;margin-bottom:10px;}
.btn-edit, .btn-delete {
    padding: 6px 14px;
    border: none;
    border-radius: 5px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    display: inline-block;
    margin-right: 5px;
    color: white;
    text-decoration: none;
}

.btn-edit {
    background: #3498db; /* blue */
}

.btn-delete {
    background: #e74c3c; /* red */
}
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
@media(max-width:599px){body{flex-direction:column;}.sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px;}.sidebar.active{left:0;}.hamburger{display:block;}.main-content{margin-left:0;padding:15px;}.header h1{font-size:18px;}.table-wrapper{overflow-x:auto;  .btn-edit, .btn-delete {
        font-size: 12px;
        padding: 5px 10px;
        margin-bottom: 5px;
    }  }}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
<h2>Admin Panel</h2>
<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="manage_users.php">👤 Manage Users</a>
<a href="manage_students.php">🎓 Manage Students</a>
<a href="#">📚 Courses</a>
<a href="#">📊 Reports</a>
<a href="#">⚙️ Settings</a>
<a href="../auth/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main-content" id="main">
<div class="header">
<h1>Welcome Admin, <span style="color:#2c3e50;">Mr. Admin</span> 👋</h1>
<button class="hamburger" onclick="toggleSidebar()">☰</button>
</div>

<div class="content">
<h2>Manage Faculty</h2>
<button class="btn btn-add" onclick="document.getElementById('addModal').style.display='flex'">➕ Add Faculty</button>

<div class="table-wrapper">
<table>
<thead>
<tr>
<th>Name</th><th>Email</th><th>Phone</th><th>Department</th><th>Designation</th><th>Joining Date</th><th>Gender</th><th>Status</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($row = $faculty->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['phone']; ?></td>
<td><?php echo $row['department']; ?></td>
<td><?php echo $row['designation']; ?></td>
<td><?php echo $row['joining_date']; ?></td>
<td><?php echo $row['gender']; ?></td>
<td><?php echo $row['status']; ?></td>
<td>
<button class="btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">✏️ Edit</button>
<a href="delete_faculty.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Add Faculty Modal -->
<div class="modal" id="addModal">
<div class="modal-content">
<span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
<h3>Add Faculty</h3>
<form method="post">
<label>Name</label><input type="text" name="name" required>
<label>Email</label><input type="email" name="email" required>
<label>Phone</label><input type="text" name="phone">
<label>Department</label><input type="text" name="department" required>
<label>Designation</label><input type="text" name="designation">
<label>Joining Date</label><input type="date" name="joining_date">
<label>Address</label><textarea name="address"></textarea>
<label>Gender</label>
<select name="gender">
<option value="">Select</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>
<label>Status</label>
<select name="status">
<option value="">Select</option>
<option value="Active">Active</option>
<option value="Inactive">Inactive</option>
</select>
<button type="submit" name="addFaculty" class="btn btn-add">Add Faculty</button>
</form>
</div>
</div>

<!-- Edit Faculty Modal -->
<div class="modal" id="editModal">
<div class="modal-content">
<span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
<h3>Edit Faculty</h3>
<form method="post" id="editForm">
<input type="hidden" name="id" id="edit_id">
<label>Name</label><input type="text" name="name" id="edit_name" required>
<label>Email</label><input type="email" name="email" id="edit_email" required>
<label>Phone</label><input type="text" name="phone" id="edit_phone">
<label>Department</label><input type="text" name="department" id="edit_department" required>
<label>Designation</label><input type="text" name="designation" id="edit_designation">
<label>Joining Date</label><input type="date" name="joining_date" id="edit_joining_date">
<label>Address</label><textarea name="address" id="edit_address"></textarea>
<label>Gender</label>
<select name="gender" id="edit_gender">
<option value="">Select</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>
<label>Status</label>
<select name="status" id="edit_status">
<option value="">Select</option>
<option value="Active">Active</option>
<option value="Inactive">Inactive</option>
</select>
<button type="submit" name="editFaculty" class="btn btn-add">Update Faculty</button>
</form>
</div>
</div>

<script>
function toggleSidebar(){ document.getElementById("sidebar").classList.toggle("active"); }

// Open Edit Modal and populate data
function openEditModal(data){
    document.getElementById('editModal').style.display='flex';
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_email').value = data.email;
    document.getElementById('edit_phone').value = data.phone;
    document.getElementById('edit_department').value = data.department;
    document.getElementById('edit_designation').value = data.designation;
    document.getElementById('edit_joining_date').value = data.joining_date;
    document.getElementById('edit_address').value = data.address;
    document.getElementById('edit_gender').value = data.gender;
    document.getElementById('edit_status').value = data.status;
}
</script>
</body>
</html>
