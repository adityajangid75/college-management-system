<?php
session_start();
require __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Admin';

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Messages
$success = '';
$error = '';

// Handle Add User
if(isset($_POST['add_user'])){
    if($_POST['csrf_token'] === $_SESSION['csrf_token']){
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $full_name = $_POST['full_name'];
        $age = intval($_POST['age']);
        $phone = $_POST['phone'];
        $city = $_POST['city'];
        $profile_pic = '';

        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0){
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid().'_profile.'.$ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], __DIR__.'/../../uploads/'.$profile_pic);
        }

        // Check unique username/email
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss",$username,$email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if($count>0){
            $error = "Username or Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username,email,password,role,full_name,age,phone,city,profile_pic,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->bind_param("sssssiiss",$username,$email,$password,$role,$full_name,$age,$phone,$city,$profile_pic);
            $stmt->execute();
            $stmt->close();
            $success = "User added successfully!";
        }
    } else { $error = "Invalid CSRF token!"; }
}

// Handle Edit User
if(isset($_POST['edit_user'])){
    if($_POST['csrf_token'] === $_SESSION['csrf_token']){
        $id = intval($_POST['user_id']);
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $full_name = $_POST['full_name'];
        $age = intval($_POST['age']);
        $phone = $_POST['phone'];
        $city = $_POST['city'];

        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0){
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $profile_pic = uniqid().'_profile.'.$ext;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], __DIR__.'/../../uploads/'.$profile_pic);
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, full_name=?, age=?, phone=?, city=?, profile_pic=? WHERE id=?");
            $stmt->bind_param("ssssisssi",$username,$email,$role,$full_name,$age,$phone,$city,$profile_pic,$id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, full_name=?, age=?, phone=?, city=? WHERE id=?");
            $stmt->bind_param("ssssissi",$username,$email,$role,$full_name,$age,$phone,$city,$id);
        }
        $stmt->execute();
        $stmt->close();
        $success = "User updated successfully!";
    } else { $error = "Invalid CSRF token!"; }
}

// Handle Delete User
if(isset($_POST['delete_id'])){
    if($_POST['csrf_token'] === $_SESSION['csrf_token']){
        $delete_id = intval($_POST['delete_id']);
        if($delete_id == $_SESSION['user_id']){
            $error = "You cannot delete yourself!";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();
            $success = "User deleted successfully!";
        }
    } else { $error = "Invalid CSRF token!"; }
}

// Pagination & Search
$limit = 10;
$page = intval($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$search_sql = $search ? "WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?" : "";

if($search){
    $stmt_count = $conn->prepare("SELECT COUNT(*) FROM users $search_sql");
    $like = "%$search%";
    $stmt_count->bind_param("sss",$like,$like,$like);
    $stmt_count->execute();
    $stmt_count->bind_result($total_users);
    $stmt_count->fetch();
    $stmt_count->close();
} else { $total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0]; }

if($search){
    $stmt = $conn->prepare("SELECT * FROM users $search_sql ORDER BY id DESC LIMIT ?,?");
    $stmt->bind_param("sssii",$like,$like,$like,$offset,$limit);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT ?,?");
    $stmt->bind_param("ii",$offset,$limit);
    $stmt->execute();
    $result = $stmt->get_result();
}

$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Admin Dashboard</title>
<style>
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
.content{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
.content h2{margin-bottom:15px;font-size:20px;color:#2c3e50}
table{width:100%;border-collapse:collapse;overflow-x:auto;display:block}
th, td{padding:10px;text-align:left;border-bottom:1px solid #ccc;white-space:nowrap}
th{background:#ecf0f1}
td.actions button{margin-right:5px;padding:5px 8px;border-radius:4px;font-size:13px;border:none;cursor:pointer;color:#fff}
button.edit-btn{background:#3498db}button.edit-btn:hover{background:#2980b9}
button.delete-btn{background:#e74c3c}button.delete-btn:hover{background:#c0392b}
.add-btn{display:inline-block;margin-bottom:15px;padding:8px 15px;background:#2ecc71;color:#fff;border-radius:5px;text-decoration:none;cursor:pointer}
.add-btn:hover{background:#27ae60}
.modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.5);justify-content:center;align-items:center}
.modal-content{background:#fff;padding:20px;border-radius:8px;width:400px;max-width:90%;position:relative}
.modal-content h3{margin-bottom:15px}
.modal-content input,.modal-content select{width:100%;padding:7px;margin-bottom:10px;border-radius:4px;border:1px solid #ccc}
.modal-content button[type=submit]{padding:8px 15px;background:#2ecc71;color:#fff;border:none;border-radius:5px;cursor:pointer}
.modal-content button[type=submit]:hover{background:#27ae60}
.modal-content .close{position:absolute;top:10px;right:15px;font-size:20px;border:none;background:none;cursor:pointer}
/* Messages */
.success{color:#2ecc71;margin-bottom:10px}
.error{color:#e74c3c;margin-bottom:10px}
/* Pagination */
.pagination a{padding:5px 10px;margin-right:5px;background:#3498db;color:#fff;border-radius:4px;text-decoration:none}
.pagination a:hover{background:#2980b9}
/* Profile picture */
td img{border-radius:50%;width:40px;height:40px;object-fit:cover}
/* Media Queries */
@media (max-width:1366px){.sidebar{width:220px}.main-content{margin-left:220px}.header h1{font-size:22px}.content h2{font-size:18px}}
@media (max-width:1023px){.sidebar{width:200px}.main-content{margin-left:200px}.header h1{font-size:20px}.content h2{font-size:17px}table,th,td{font-size:13px;padding:8px}}
@media (max-width:767px){.sidebar{width:180px}.main-content{margin-left:180px}.header h1{font-size:18px}.content h2{font-size:16px}table,th,td{font-size:12px;padding:6px}}
@media (max-width:599px){body{flex-direction:column}.sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px}.sidebar.active{left:0}.hamburger{display:block}.main-content{margin-left:0;padding:15px}.header h1{font-size:16px}.content h2{font-size:15px}table{display:block;overflow-x:auto;font-size:11px}}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
<h2>Admin Panel</h2>
<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="manage_faculty.php">🏫 Manage Faculty</a>
<a href="#">🎓 Manage Students</a>
<a href="#">📚 Courses</a>
<a href="#">📊 Reports</a>
<a href="settings.php">⚙️ Settings</a>
<a href="../auth/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main-content" id="main">
<div class="header">
<h1>Welcome Admin, <span style="color:#2c3e50;"><?= htmlspecialchars($adminName ?? 'Admin') ?></span> 👋</h1>
<button class="hamburger" onclick="toggleSidebar()">☰</button>
</div>

<div class="content">
<h2>Manage Users</h2>

<?php if($success) echo '<p class="success">'.$success.'</p>'; ?>
<?php if($error) echo '<p class="error">'.$error.'</p>'; ?>

<form method="GET" style="margin-bottom:10px;">
<input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">Search</button>
</form>

<button class="add-btn" onclick="openAddModal()">➕ Add User</button>

<table>
<tr>
<th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Age</th><th>Phone</th><th>City</th><th>Role</th><th>Actions</th>
</tr>
<?php while($user = $result->fetch_assoc()): ?>
<tr>
<td><?= $user['id'] ?></td>
<td><?= htmlspecialchars($user['full_name'] ?? '') ?></td>
<td><?= htmlspecialchars($user['username'] ?? '') ?></td>
<td><?= htmlspecialchars($user['email']?? '') ?></td>
<td><?= $user['age'] ?></td>
<td><?= htmlspecialchars($user['phone'] ?? '') ?></td>
<td><?= htmlspecialchars($user['city'] ?? '') ?></td>
<td style="color:<?= $user['role']=='admin'?'#e74c3c':'#000' ?>"><?= $user['role'] ?></td>
<td class="actions">
<button class="edit-btn"
  data-id="<?= $user['id'] ?>"
  data-username="<?= htmlspecialchars($user['username'] ?? '') ?>"
  data-email="<?= htmlspecialchars($user['email']?? '') ?>"
  data-role="<?= htmlspecialchars($user['role']?? '') ?>"
  data-full_name="<?= htmlspecialchars($user['full_name']?? '') ?>"
  data-age="<?= $user['age'] ?>"
  data-phone="<?= htmlspecialchars($user['phone']?? '') ?>"
  data-city="<?= htmlspecialchars($user['city']?? '') ?>">Edit</button>
<button class="delete-btn" data-id="<?= $user['id'] ?>">Delete</button>
</td>
</tr>
<?php endwhile; ?>
</table>

<div class="pagination">
<?php for($i=1;$i<=$total_pages;$i++): ?>
<a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
<?php endfor; ?>
</div>
</div>
</div>

<!-- Modals -->
<div class="modal" id="addModal">
<div class="modal-content">
<button class="close" onclick="closeAddModal()">×</button>
<h3>Add User</h3>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
<label>Full Name:</label><input type="text" name="full_name" required>
<label>Username:</label><input type="text" name="username" required>
<label>Email:</label><input type="email" name="email" required>
<label>Password:</label><input type="password" name="password" required>
<label>Role:</label>
<select name="role"><option value="user" disabled>User</option><option value="admin">Admin</option><option value="faculty">Faculty</option><option value="student">Student</option></select>
<label>Age:</label><input type="number" name="age">
<label>Phone:</label><input type="text" name="phone">
<label>City:</label><input type="text" name="city">
<label>Profile Picture:</label><input type="file" name="profile_pic">
<br><br><button type="submit" name="add_user">Add User</button>
</form>
</div>
</div>

<div class="modal" id="editModal">
<div class="modal-content">
<button class="close" onclick="closeEditModal()">×</button>
<h3>Edit User</h3>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
<input type="hidden" name="user_id" id="edit_user_id">
<label>Full Name:</label><input type="text" name="full_name" id="edit_full_name" required>
<label>Username:</label><input type="text" name="username" id="edit_username" required>
<label>Email:</label><input type="email" name="email" id="edit_email" required>
<label>Role:</label>
<select name="role" id="edit_role"><option value="user">User</option><option value="admin">Admin</option></select>
<label>Age:</label><input type="number" name="age" id="edit_age">
<label>Phone:</label><input type="text" name="phone" id="edit_phone">
<label>City:</label><input type="text" name="city" id="edit_city">
<label>Profile Picture:</label><input type="file" name="profile_pic">
<br><br><button type="submit" name="edit_user">Update User</button>
</form>
</div>
</div>

<div class="modal" id="deleteModal">
<div class="modal-content">
<button class="close" onclick="closeDeleteModal()">×</button>
<h3>Are you sure to delete?</h3>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
<input type="hidden" name="delete_id" id="delete_id">
<button type="submit">Delete</button>
</form>
</div>
</div>

<script>
function toggleSidebar(){ document.getElementById("sidebar").classList.toggle("active"); }

// Add Modal
function openAddModal(){ document.getElementById("addModal").style.display="flex"; }
function closeAddModal(){ document.getElementById("addModal").style.display="none"; }

// Edit Modal
const editBtns = document.querySelectorAll(".edit-btn");
const editModal = document.getElementById("editModal");
editBtns.forEach(btn=>{
btn.addEventListener("click",()=>{
document.getElementById("edit_user_id").value = btn.dataset.id;
document.getElementById("edit_username").value = btn.dataset.username;
document.getElementById("edit_email").value = btn.dataset.email;
document.getElementById("edit_role").value = btn.dataset.role;
document.getElementById("edit_full_name").value = btn.dataset.full_name;
document.getElementById("edit_age").value = btn.dataset.age;
document.getElementById("edit_phone").value = btn.dataset.phone;
document.getElementById("edit_city").value = btn.dataset.city;
editModal.style.display="flex";
});
});
function closeEditModal(){ editModal.style.display="none"; }

// Delete Modal
const deleteBtns = document.querySelectorAll(".delete-btn");
const deleteModal = document.getElementById("deleteModal");
const deleteInput = document.getElementById("delete_id");
deleteBtns.forEach(btn=>{
btn.addEventListener("click",()=>{
deleteInput.value = btn.getAttribute("data-id");
deleteModal.style.display="flex";
});
});
function closeDeleteModal(){ deleteModal.style.display="none"; }

// Close modal on outside click
window.onclick=function(e){
if(e.target==editModal) closeEditModal();
if(e.target==deleteModal) closeDeleteModal();
if(e.target==document.getElementById("addModal")) closeAddModal();
}
</script>
</body>
</html>
