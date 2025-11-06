<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'nurse'])) {
    header("Location: ../login.php");
    exit;
}

$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
$table = $role . 's';

$stmt = $conn->prepare("SELECT department_id, full_name FROM $table WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($department_id, $staff_name);
$stmt->fetch();
$stmt->close();

$queue = $conn->prepare("SELECT q.id, q.queue_number, p.full_name, p.reason_for_visit, q.registered_at FROM queue q JOIN patients p ON q.patient_id = p.id WHERE q.assigned_doctor_id = ? AND q.department_id = ? ORDER BY q.registered_at ASC");
$queue->bind_param("ii", $uid, $department_id);
$queue->execute();
$assigned = $queue->get_result();

$history = $conn->prepare("SELECT p.full_name, p.reason_for_visit, im.illness, im.medication, im.created_at FROM illness_medications im JOIN patients p ON im.patient_id = p.id WHERE im.prescribed_by = ? ORDER BY im.created_at DESC LIMIT 10");
$history->bind_param("i", $uid);
$history->execute();
$checked = $history->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($role) ?> Dashboard | MediCare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --border-radius: 10px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info h1 {
            font-size: 1.4rem;
            font-weight: 600;
        }

        .user-info .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.3rem;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .datetime {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        nav a:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            position: relative;
        }

        .card-header h2::after {
            content: '';
            position: absolute;
            bottom: -0.75rem;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        th {
            font-weight: 600;
            color: var(--gray);
            background: rgba(59, 130, 246, 0.05);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: rgba(59, 130, 246, 0.03);
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            nav {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .datetime {
                display: none;
            }
            
            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="user-info">
            <h1>Welcome, <?= htmlspecialchars($staff_name) ?></h1>
            <span class="role-badge"><?= ucfirst($role) ?></span>
        </div>
        <nav>
            <span class="datetime" id="datetime"></span>
            <a href="profile.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Profile
            </a>
            <a href="../logout.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-header">
                <h2>Current Patient Queue</h2>
                <span class="status-badge status-pending">Live Updates</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Queue No</th>
                        <th>Patient</th>
                        <th>Reason</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $assigned->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['queue_number']) ?></strong></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['reason_for_visit']) ?></td>
                        <td><?= date("h:i A", strtotime($row['registered_at'])) ?></td>
                        <td>
                            <a href="check_patient.php?id=<?= $row['id'] ?>" class="btn btn-primary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                Check
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Recent Patient History</h2>
                <span class="status-badge status-completed">Last 10 Records</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Reason</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                        <th>Checked At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $checked->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['reason_for_visit']) ?></td>
                        <td><?= htmlspecialchars($row['illness'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($row['medication'] ?: '-') ?></td>
                        <td><?= date("M j, Y h:i A", strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updateTime() {
        const now = new Date();
        const options = { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', options);
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>
</body>
</html>