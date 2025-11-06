<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$message = "";

// Fetch current profile data
$stmt = $conn->prepare("SELECT name, email, phone, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $profile_pic);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    $new_pic = $_POST['profile_pic']; // Assume a URL or filename input for now
    $new_password = $_POST['password'];

    if (!empty($new_password)) {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, profile_pic=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $new_name, $new_email, $new_phone, $new_pic, $new_password, $admin_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, profile_pic=? WHERE id=?");
        $stmt->bind_param("ssssi", $new_name, $new_email, $new_phone, $new_pic, $admin_id);
    }

    if ($stmt->execute()) {
        $message = "Profile updated successfully.";
    } else {
        $message = "Error updating profile.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Profile Settings</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        label { display: block; margin-top: 10px; }
        input[type=text], input[type=email], input[type=password] {
            width: 300px; padding: 8px;
        }
        input[type=submit] {
            margin-top: 20px; padding: 10px 20px;
        }
        .back-link { margin-top: 20px; display: block; }
    </style>
</head>
<body>
    <h2>Admin Profile Settings</h2>

    <?php if ($message): ?>
        <p style="color: green;"><?= $message ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Full Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label>Phone:</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>">

        <label>Profile Picture Filename (e.g., admin1.jpg):</label>
        <input type="text" name="profile_pic" value="<?= htmlspecialchars($profile_pic) ?>">

        <label>New Password (leave blank to keep current):</label>
        <input type="password" name="password">

        <input type="submit" value="Update Profile">
    </form>

    <a class="back-link" href="dashboard.php">← Back to Dashboard</a>
</body>
</html>
