<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$stmt = $conn->prepare("SELECT a.full_name FROM admins a JOIN users u ON a.user_id = u.id WHERE u.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($admin_name);
$stmt->fetch();
$stmt->close();

$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$doctors = $conn->query("SELECT COUNT(*) FROM doctors")->fetch_row()[0];
$nurses = $conn->query("SELECT COUNT(*) FROM nurses")->fetch_row()[0];
$receptionists = $conn->query("SELECT COUNT(*) FROM receptionists")->fetch_row()[0];
$pharmacists = $conn->query("SELECT COUNT(*) FROM pharmacists")->fetch_row()[0];
$total_patients = $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];

$status_res = $conn->query("SELECT is_active FROM queue_status ORDER BY updated_at DESC LIMIT 1");
$system_active = ($status_res && $status_res->num_rows) ? $status_res->fetch_assoc()['is_active'] : 0;

$queue_preview = $conn->query("SELECT q.queue_number, p.full_name, d.name AS department_name, q.registered_at, q.checked_at, doc.full_name AS doctor_full_name, doc.room_number FROM queue q JOIN patients p ON q.patient_id = p.id JOIN departments d ON q.department_id = d.id LEFT JOIN doctors doc ON q.assigned_doctor_id = doc.user_id AND doc.department_id = q.department_id ORDER BY q.registered_at ASC LIMIT 5");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .header { background: #333; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 30px; }
        .section { background: #fff; padding: 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #eee; }
        .actions a { display: inline-block; margin-top: 10px; background: #007bff; color: #fff; padding: 8px 16px; border-radius: 5px; text-decoration: none; }
        .status-open { color: green; font-weight: bold; }
        .status-closed { color: red; font-weight: bold; }
    </style>
</head>
<body>
<div class="header">
    <div>
        <h2>Welcome, <?= htmlspecialchars($admin_name) ?> (Admin)</h2>
        <p>Status: <span class="<?= $system_active ? 'status-open' : 'status-closed' ?>">
            <?= $system_active ? 'OPEN' : 'CLOSED' ?></span></p>
    </div>
    <div>
        <span id="datetime"></span> |
        <a href="settings.php">⚙ Settings</a>
        <a href="profile.php">👤 Profile</a>
        <a href="../logout.php">🔓 Logout</a>
    </div>
</div>

<div class="container">
    <div class="section">
        <h3>👥 User Management</h3>
        <ul>
            <li>Total Users: <?= $total_users ?></li>
            <li>Doctors: <?= $doctors ?></li>
            <li>Nurses: <?= $nurses ?></li>
            <li>Receptionists: <?= $receptionists ?></li>
            <li>Pharmacists: <?= $pharmacists ?></li>
            <li>Total Patients: <?= $total_patients ?></li>
        </ul>
        <div class="actions">
            <a href="users.php">Manage Users</a>
            <a href="register_user.php">Register New User</a>
        </div>
    </div>

    <div class="section">
        <h3>🕒 Live Queue Preview (<?= $system_active ? "<span style='color:green'>OPEN</span>" : "<span style='color:red'>CLOSED</span>" ?>)</h3>
        <table>
            <tr><th>Queue No</th><th>Patient</th><th>Department</th><th>Doctor</th><th>Room</th><th>Registered</th><th>Status</th></tr>
            <?php while ($row = $queue_preview->fetch_assoc()): ?>
                <?php
                    $status = 'Waiting';
                    if ($row['checked_at']) $status = 'Checked';
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['queue_number']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['department_name']) ?></td>
                    <td><?= htmlspecialchars($row['doctor_full_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['room_number'] ?? '-') ?></td>
                    <td><?= date("h:i A", strtotime($row['registered_at'])) ?></td>
                    <td><strong><?= $status ?></strong></td>
                </tr>
            <?php endwhile; ?>
        </table>
        <div class="actions">
            <a href="live_queue.php">👁 View Full Queue</a>
        </div>
    </div>
</div>

<script>
    function updateTime() {
        const now = new Date();
        document.getElementById('datetime').textContent = now.toLocaleString();
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>
</body>
</html>
