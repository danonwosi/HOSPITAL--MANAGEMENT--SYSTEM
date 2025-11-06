<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['receptionist', 'admin'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch all departments
$departments_result = $conn->query("SELECT id, name FROM departments ORDER BY name");
$departments = [];
while ($d = $departments_result->fetch_assoc()) {
    $departments[] = $d;
}

$sql = "SELECT q.id, q.queue_number, p.full_name, d.name AS department, q.registered_at, q.checked_at,
               doc.full_name AS doctor_name, doc.room_number
        FROM queue q
        JOIN patients p ON q.patient_id = p.id
        JOIN departments d ON q.department_id = d.id
        LEFT JOIN doctors doc ON doc.user_id = q.assigned_doctor_id
        ORDER BY q.registered_at ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Full Live Queue</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 40px; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #eee; }
        input, select { padding: 8px; margin-right: 10px; }
        .back-link { margin-top: 20px; display: inline-block; text-decoration: none; color: #3498db; }
        .back-link:hover { text-decoration: underline; }
        .filters { margin-bottom: 15px; }
    </style>
    <script>
        function liveFilter() {
            const text = document.getElementById('search').value.toLowerCase();
            const dept = document.getElementById('filterDept').value;
            const status = document.getElementById('filterStatus').value;
            const rows = document.querySelectorAll('#queueTable tbody tr');
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const rowDept = row.getAttribute('data-dept');
                const rowStatus = row.getAttribute('data-status');
                const matchesText = rowText.includes(text);
                const matchesDept = dept === '' || dept === rowDept;
                const matchesStatus = status === '' || status === rowStatus;
                row.style.display = (matchesText && matchesDept && matchesStatus) ? '' : 'none';
            });
        }
    </script>
</head>
<body>

<div class="container">
    <h2>Full Live Queue</h2>

    <div class="filters">
        <input type="text" id="search" onkeyup="liveFilter()" placeholder="Search by patient, doctor, queue...">
        <select id="filterDept" onchange="liveFilter()">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= strtolower($d['name']) ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filterStatus" onchange="liveFilter()">
            <option value="">All Status</option>
            <option value="waiting">Waiting</option>
            <option value="checked">Checked</option>
        </select>
    </div>

    <table id="queueTable">
        <thead>
            <tr>
                <th>Queue #</th>
                <th>Patient</th>
                <th>Department</th>
                <th>Doctor</th>
                <th>Room</th>
                <th>Registered At</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $status = $row['checked_at'] ? 'Checked' : 'Waiting';
                    $status_class = $row['checked_at'] ? 'checked' : 'waiting';
                ?>
                <tr data-dept="<?= strtolower($row['department']) ?>" data-status="<?= $status_class ?>">
                    <td><?= htmlspecialchars($row['queue_number']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['doctor_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['room_number'] ?? '-') ?></td>
                    <td><?= date("Y-m-d h:i A", strtotime($row['registered_at'])) ?></td>
                    <td><?= $status ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No queue records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="receptionist.php" class="back-link">&larr; Back to Dashboard</a>
</div>

</body>
</html>
