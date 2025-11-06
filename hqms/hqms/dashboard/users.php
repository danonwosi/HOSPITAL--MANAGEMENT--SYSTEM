<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $room_number = $_POST['room_number'];

    $stmt = $conn->prepare("UPDATE users SET email=?, phone=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $email, $phone, $role, $id);
    $stmt->execute();
    $stmt->close();

    // Update room_number in role-specific table
    if (in_array($role, ['doctor', 'nurse'])) {
        $table = $role . 's';
        $check = $conn->query("SELECT id FROM $table WHERE user_id = $id");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE $table SET room_number = '$room_number' WHERE user_id = $id");
        }
    }

    $success = "User updated successfully.";
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $success = "User deleted.";
    } else {
        $error = "You cannot delete yourself.";
    }
}

$result = $conn->query("SELECT u.id, u.username, u.email, u.phone, u.role, 
    COALESCE(d.room_number, n.room_number, '') AS room_number
    FROM users u
    LEFT JOIN doctors d ON u.id = d.user_id
    LEFT JOIN nurses n ON u.id = n.user_id
    ORDER BY u.id DESC");

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 30px; }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ccc; padding: 10px; }
        th { background: #eee; }
        input, select { width: 100%; padding: 6px; }
        .actions { text-align: center; margin-top: 20px; }
        .success { color: green; }
        .error { color: red; }
        a.button {
            background: #007bff; color: white;
            padding: 8px 14px; text-decoration: none;
            border-radius: 4px;
        }
        a.button:hover { background: #0056b3; }
    </style>
</head>
<body>

<h2>Manage Users</h2>

<?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
<?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>

<table>
    <tr>
        <th>ID</th><th>Email</th><th>Phone</th><th>Username</th><th>Role</th><th>Room No</th><th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <form method="POST">
            <td><?= $row['id'] ?></td>
            <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>"></td>
            <td><input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>"></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td>
                <select name="role">
                    <?php
                    $roles = ['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist'];
                    foreach ($roles as $role_option) {
                        $selected = ($row['role'] == $role_option) ? 'selected' : '';
                        echo "<option value='$role_option' $selected>$role_option</option>";
                    }
                    ?>
                </select>
            </td>
            <td><input type="text" name="room_number" value="<?= htmlspecialchars($row['room_number']) ?>"></td>
            <td>
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit" name="update_user">Update</button>
                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')" class="button" style="background: red;">Delete</a>
                <?php endif; ?>
            </td>
        </form>
    </tr>
    <?php endwhile; ?>
</table>

<div class="actions">
    <a href="register_user.php" class="button">Register New User</a>
    <a href="admin.php" class="button">Back to Dashboard</a>
</div>

</body>
</html>
