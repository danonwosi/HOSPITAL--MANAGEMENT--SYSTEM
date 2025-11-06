<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = $error = "";

// Fetch departments
$departments = $conn->query("SELECT id, name FROM departments");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $room_number = trim($_POST['room_number']);
    $department_id = (int)$_POST['department_id'];

    if ($name && $username && $password && $role && $department_id) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, email, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $password, $role, $email, $phone);
            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                $role_table = $role . 's';

                if (in_array($role, ['doctor', 'nurse'])) {
                    $stmt2 = $conn->prepare("INSERT INTO $role_table (user_id, department_id, full_name, room_number) VALUES (?, ?, ?, ?)");
                    $stmt2->bind_param("iiss", $user_id, $department_id, $name, $room_number);
                } else {
                    $stmt2 = $conn->prepare("INSERT INTO $role_table (user_id, department_id, full_name) VALUES (?, ?, ?)");
                    $stmt2->bind_param("iis", $user_id, $department_id, $name);
                }

                if ($stmt2->execute()) {
                    $success = "User registered successfully.";
                } else {
                    $error = "Failed to insert into role-based table.";
                }
                $stmt2->close();
            } else {
                $error = "Failed to register user.";
            }
            $stmt->close();
        }
    } else {
        $error = "All fields marked * are required.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register New User</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        form { background: #fff; padding: 20px; max-width: 600px; margin: auto; border-radius: 8px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, select, button { width: 100%; padding: 10px; margin-top: 5px; }
        .success { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
        a { display: block; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Register New User</h2>

<form method="POST">
    <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

    <label>Full Name *</label>
    <input type="text" name="name" required>

    <label>Username *</label>
    <input type="text" name="username" required>

    <label>Password *</label>
    <input type="text" name="password" required>

    <label>Role *</label>
    <select name="role" required>
        <option value="">-- Select Role --</option>
        <option value="admin">Admin</option>
        <option value="doctor">Doctor</option>
        <option value="nurse">Nurse</option>
        <option value="receptionist">Receptionist</option>
        <option value="pharmacist">Pharmacist</option>
    </select>

    <label>Department *</label>
    <select name="department_id" required>
        <option value="">-- Select Department --</option>
        <?php while ($dept = $departments->fetch_assoc()): ?>
            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Email</label>
    <input type="email" name="email">

    <label>Phone</label>
    <input type="text" name="phone">

    <label>Room Number (for doctors/nurses)</label>
    <input type="text" name="room_number">

    <button type="submit">Register</button>
</form>

<a href="users.php">Back to Manage Users</a>

</body>
</html>
