<?php
session_start();
require_once '../../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = (int) $_GET['id'];
$errors = [];
$success = "";

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    die("User not found.");
}
$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $role     = $_POST['role'];
    $password = trim($_POST['password']);

    if (!$name || !$email || !$phone || !$role) {
        $errors[] = "All fields except password are required.";
    } else {
        // Check for email conflict
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email is already used by another user.";
        } else {
            if ($password) {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=?, password=? WHERE id=?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $role, $password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=? WHERE id=?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $role, $user_id);
            }

            if ($stmt->execute()) {
                $success = "User updated successfully.";
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $user['role'] = $role;
                if ($password) $user['password'] = $password;
            } else {
                $errors[] = "Failed to update user.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Edit User</title></head>
<body>
    <h2>Edit User</h2>
    <a href="users.php">← Back to Users</a><br><br>

    <?php if (!empty($errors)): ?>
        <div style="color:red;">
            <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="color:green;"><p><?= $success ?></p></div>
    <?php endif; ?>

    <form method="POST">
        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required><br><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="receptionist" <?= $user['role'] == 'receptionist' ? 'selected' : '' ?>>Receptionist</option>
            <option value="doctor" <?= $user['role'] == 'doctor' ? 'selected' : '' ?>>Doctor</option>
            <option value="nurse" <?= $user['role'] == 'nurse' ? 'selected' : '' ?>>Nurse</option>
            <option value="pharmacist" <?= $user['role'] == 'pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
        </select><br><br>

        <label>New Password (leave blank to keep current):</label><br>
        <input type="text" name="password"><br><br>

        <button type="submit">Update User</button>
    </form>
</body>
</html>
