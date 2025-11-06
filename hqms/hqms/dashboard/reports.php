<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$filter = $_GET['filter'] ?? 'daily';

$condition = "";
switch ($filter) {
    case 'monthly':
        $condition = "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        break;
    case 'yearly':
        $condition = "WHERE YEAR(created_at) = YEAR(CURDATE())";
        break;
    default:
        $condition = "WHERE DATE(created_at) = CURDATE()";
}

$query = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN age < 18 THEN 1 ELSE 0 END) AS children,
        SUM(CASE WHEN age BETWEEN 18 AND 64 THEN 1 ELSE 0 END) AS adults,
        SUM(CASE WHEN age >= 65 THEN 1 ELSE 0 END) AS elderly,
        SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) AS male,
        SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) AS female,
        SUM(CASE WHEN gender = 'Other' THEN 1 ELSE 0 END) AS other_gender
    FROM patients
    $condition
";

$result = $conn->query($query);
$stats = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        .filter-btns a {
            margin-right: 10px; text-decoration: none; padding: 8px 16px;
            background: #007bff; color: white; border-radius: 5px;
        }
        table { border-collapse: collapse; margin-top: 20px; width: 50%; }
        th, td { padding: 10px; border: 1px solid #ddd; }
        .back { margin-top: 20px; display: block; }
    </style>
</head>
<body>

<h2>Reports – <?= ucfirst($filter) ?> Summary</h2>

<div class="filter-btns">
    <a href="?filter=daily">Daily</a>
    <a href="?filter=monthly">Monthly</a>
    <a href="?filter=yearly">Yearly</a>
</div>

<table>
    <tr><th>Total Patients</th><td><?= $stats['total'] ?></td></tr>
    <tr><th>Children (&lt;18)</th><td><?= $stats['children'] ?></td></tr>
    <tr><th>Adults (18–64)</th><td><?= $stats['adults'] ?></td></tr>
    <tr><th>Elderly (65+)</th><td><?= $stats['elderly'] ?></td></tr>
    <tr><th>Male</th><td><?= $stats['male'] ?></td></tr>
    <tr><th>Female</th><td><?= $stats['female'] ?></td></tr>
    <tr><th>Other</th><td><?= $stats['other_gender'] ?></td></tr>
</table>

<a class="back" href="dashboard.php">← Back to Dashboard</a>

</body>
</html>
