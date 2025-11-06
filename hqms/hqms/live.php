<?php
require_once 'db.php';

$queue = $conn->query("SELECT q.queue_number, p.full_name, d.name AS department_name, q.registered_at, doc.full_name AS doctor_name, doc.room_number FROM queue q JOIN patients p ON q.patient_id = p.id JOIN departments d ON q.department_id = d.id LEFT JOIN doctors doc ON q.assigned_doctor_id = doc.user_id ORDER BY q.registered_at ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hospital Live Queue Display</title>
    <meta http-equiv="refresh" content="10">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f8ff; margin: 0; padding: 0; }
        header { background: #003366; color: white; padding: 20px; text-align: center; }
        .container { padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 12px; text-align: center; font-size: 18px; }
        th { background: #0059b3; color: white; }
        .up-next { background: #fff3cd; padding: 15px; margin-bottom: 20px; border-left: 5px solid #ffc107; font-size: 20px; }
        .ads { background: #e0e0e0; padding: 15px; text-align: center; font-size: 18px; color: #555; animation: scrollText 20s linear infinite; white-space: nowrap; overflow: hidden; }
        @keyframes scrollText {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        .clock { font-size: 20px; text-align: right; margin-bottom: 10px; color: #333; }
    </style>
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        window.onload = updateClock;
    </script>
</head>
<body>
<header>
    <h1>🏥 Hospital Live Queue Display</h1>
    <div class="clock" id="clock"></div>
</header>

<div class="container">
    <!-- <div class="ads">Visit our pharmacy for discounted prescriptions! | Health checkup packages available every Friday | Dial *123# for mobile appointment scheduling</div> -->

    <?php if ($queue && $queue->num_rows > 0): ?>
        <?php $first = $queue->fetch_assoc(); ?>
        <div class="up-next">🔊 Up Next: <?= htmlspecialchars($first['queue_number']) ?> - <?= htmlspecialchars($first['full_name']) ?> (Room <?= htmlspecialchars($first['room_number'] ?? '-') ?>)</div>

        <table>
            <tr><th>Queue #</th><th>Patient</th><th>Department</th><th>Doctor</th><th>Room</th><th>Registered At</th></tr>
            <tr>
                <td><?= htmlspecialchars($first['queue_number']) ?></td>
                <td><?= htmlspecialchars($first['full_name']) ?></td>
                <td><?= htmlspecialchars($first['department_name']) ?></td>
                <td><?= htmlspecialchars($first['doctor_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($first['room_number'] ?? '-') ?></td>
                <td><?= date("h:i A", strtotime($first['registered_at'])) ?></td>
            </tr>
            <?php while ($row = $queue->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['queue_number']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['department_name']) ?></td>
                    <td><?= htmlspecialchars($row['doctor_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['room_number'] ?? '-') ?></td>
                    <td><?= date("h:i A", strtotime($row['registered_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div style="text-align:center; font-size: 18px; color: #555;">No patients currently in the queue.</div>
    <?php endif; ?>
</div>
</body>
</html>
