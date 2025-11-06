<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$success = $error = "";

// Fetch current admin data
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone);
$stmt->fetch();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);
    $new_password = trim($_POST['password']);

    if ($new_name && $new_email) {
        if ($new_password) {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $new_name, $new_email, $new_phone, $new_password, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
            $stmt->bind_param("sssi", $new_name, $new_email, $new_phone, $admin_id);
        }

        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    } else {
        $error = "Name and email are required.";
    }
}

// Handle system toggle
if (isset($_POST['toggle_system'])) {
    $new_status = ($_POST['status'] === '1') ? 0 : 1;
    $stmt = $conn->prepare("INSERT INTO queue_status (is_active) VALUES (?)");
    $stmt->bind_param("i", $new_status);
    if ($stmt->execute()) {
        $success = "Queue system status updated.";
    } else {
        $error = "Failed to update queue status.";
    }
    $stmt->close();
}

// Get latest queue system status
$status_result = $conn->query("SELECT is_active FROM queue_status ORDER BY updated_at DESC LIMIT 1");
$current_status = $status_result->num_rows ? $status_result->fetch_assoc()['is_active'] : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Settings</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f4f4f4; }
        .section { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
        input, button { padding: 10px; margin: 5px 0; width: 100%; max-width: 400px; }
        label { font-weight: bold; display: block; margin-top: 10px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<h2>⚙ Admin Settings</h2>

<?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>

<!-- PROFILE SETTINGS -->
<div class="section">
    <h3>👤 Update Profile</h3>
    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>">

        <label>New Password (leave blank to keep current)</label>
        <input type="text" name="password">

        <button type="submit" name="update_profile">Update Profile</button>
    </form>
</div>

<!-- SYSTEM STATUS CONTROL -->
<div class="section">
    <h3>🟢 Queue System Control</h3>
    <form method="POST">
        <p>Current Status: <strong style="color:<?= $current_status ? 'green' : 'red' ?>">
            <?= $current_status ? 'Open' : 'Closed' ?>
        </strong></p>
        <input type="hidden" name="status" value="<?= $current_status ?>">
        <button type="submit" name="toggle_system">
            <?= $current_status ? 'Close Queue' : 'Open Queue' ?>
        </button>
    </form>
</div>

<a href="admin.php">← Back to Dashboard</a>

</body>
</html>
