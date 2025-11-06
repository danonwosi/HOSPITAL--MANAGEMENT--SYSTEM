<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: ../login.php");
    exit;
}

$stmt = $conn->prepare("SELECT department_id FROM receptionists WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($department_id);
$stmt->fetch();
$stmt->close();

if (isset($_POST['toggle_queue'])) {
    $status = ($_POST['toggle_queue'] === 'pause') ? 0 : 1;
    $conn->query("INSERT INTO queue_status (is_active) VALUES ($status)");
}

$status_res = $conn->query("SELECT is_active FROM queue_status ORDER BY updated_at DESC LIMIT 1");
$queue_active = ($status_res && $status_res->num_rows) ? $status_res->fetch_assoc()['is_active'] : 0;

$queue = $conn->query("SELECT q.id, q.queue_number, p.full_name, d.name AS department, q.registered_at, q.checked_at, doc.full_name AS doctor_name, doc.room_number FROM queue q JOIN patients p ON q.patient_id = p.id JOIN departments d ON q.department_id = d.id LEFT JOIN doctors doc ON q.assigned_doctor_id = doc.user_id WHERE q.department_id = $department_id ORDER BY q.registered_at ASC LIMIT 5");
$patients = $conn->query("SELECT * FROM patients WHERE department_id = $department_id ORDER BY created_at DESC");
$total_today = $conn->query("SELECT COUNT(*) AS total FROM patients WHERE DATE(created_at) = CURDATE() AND department_id = $department_id")->fetch_assoc()['total'];
$total_waiting = $conn->query("SELECT COUNT(*) AS waiting FROM queue WHERE checked_at IS NULL AND department_id = $department_id")->fetch_assoc()['waiting'];
$total_checked = $conn->query("SELECT COUNT(*) AS checked FROM queue WHERE checked_at IS NOT NULL AND department_id = $department_id")->fetch_assoc()['checked'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Queue | Reception Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #ef233c;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --card-bg: #ffffff;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }

        header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            opacity: 0.3;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo h1 {
            font-weight: 700;
            font-size: 1.5rem;
        }

        nav {
            display: flex;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 0;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav a:hover {
            transform: translateY(-2px);
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: white;
            transition: width 0.3s ease;
            border-radius: 3px;
        }

        nav a:hover::after {
            width: 100%;
        }

        .container {
            padding: 2rem 3rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            position: relative;
            display: inline-block;
        }

        .dashboard-header h2::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 5px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 20px -5px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 1rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card p {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 40px;
            height: 40px;
            background: var(--primary);
            opacity: 0.1;
            border-radius: 0 0 0 40px;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--primary-light));
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        th, td {
            padding: 1.25rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        th {
            font-weight: 600;
            color: var(--gray);
            background: rgba(67, 97, 238, 0.05);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: rgba(67, 97, 238, 0.03);
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-waiting {
            background: #fff3bf;
            color: #e67700;
        }

        .status-checked {
            background: #d3f9d8;
            color: #2b8a3e;
        }

        .view-all {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            float: right;
        }

        .view-all:hover {
            gap: 1rem;
            color: var(--secondary);
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .container {
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1.5rem;
            }
            
            nav {
                width: 100%;
                justify-content: space-around;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            th, td {
                padding: 1rem 0.75rem;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L3 5V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V5L12 2Z" fill="white"/>
            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="white" stroke-width="2"/>
            <path d="M12 8L10 12H14L12 16" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h1>HQMS | CuraX</h1>
    </div>
    <nav>
        <a href="register_patient.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            Register Patient
        </a>
        <a href="queue_list.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            View Queue
        </a>
        <a href="../logout.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <h2>Reception Dashboard</h2>
        <button class="btn btn-primary" onclick="window.location.href='register_patient.php'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            New Patient
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Today's Registrations</h3>
            <p><?= $total_today ?></p>
        </div>
        <div class="stat-card">
            <h3>Patients Waiting</h3>
            <p><?= $total_waiting ?></p>
        </div>
        <div class="stat-card">
            <h3>Patients Checked</h3>
            <p><?= $total_checked ?></p>
        </div>
        <div class="stat-card">
            <h3>Current Department</h3>
            <p>Dept <?= htmlspecialchars($department_id) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Live Queue</h2>
            <form method="POST">
                <button class="btn <?= $queue_active ? 'btn-warning' : 'btn-primary' ?> btn-sm" type="submit" name="toggle_queue" value="<?= $queue_active ? 'pause' : 'resume' ?>">
                    <?= $queue_active ? '⏸ Pause Queue' : '▶ Resume Queue' ?>
                </button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Queue #</th>
                    <th>Patient</th>
                    <th>Department</th>
                    <th>Doctor</th>
                    <th>Room</th>
                    <th>Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $queue->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['queue_number']) ?></strong></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['doctor_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['room_number'] ?? '-') ?></td>
                    <td><?= date("h:i A", strtotime($row['registered_at'])) ?></td>
                    <td>
                        <span class="status-badge <?= $row['checked_at'] ? 'status-checked' : 'status-waiting' ?>">
                            <?= $row['checked_at'] ? 'Checked' : 'Waiting' ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!$row['checked_at']): ?>
                            <div class="action-buttons">
                                <button class="btn btn-primary btn-sm" onclick="window.location.href='edit_patient.php?id=<?= $row['id'] ?>'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="if(confirm('Delete this patient?')) window.location.href='delete_patient.php?id=<?= $row['id'] ?>'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="queue_list.php" class="view-all">
            View Full Queue
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M12 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Patient History</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Reason</th>
                    <th>Registered At</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $patients->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($p['full_name']) ?></td>
                    <td><?= $p['age'] ?></td>
                    <td><?= $p['gender'] ?></td>
                    <td><?= $p['phone'] ?></td>
                    <td><?= $p['email'] ?></td>
                    <td><?= $p['reason_for_visit'] ?></td>
                    <td><?= date("M d, Y H:i", strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>