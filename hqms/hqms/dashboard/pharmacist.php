<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    header("Location: ../login.php");
    exit;
}

$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT department_id, full_name FROM pharmacists WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($department_id, $pharmacist_name);
$stmt->fetch();
$stmt->close();

// Get patients with prescriptions in the pharmacist's department (Pending only)
$query = "SELECT im.id, p.full_name, p.reason_for_visit, im.illness, im.medication, im.created_at, im.status FROM illness_medications im JOIN patients p ON im.patient_id = p.id WHERE p.department_id = ? AND (im.status IS NULL OR im.status = '') ORDER BY im.created_at DESC";
$patients = $conn->prepare($query);
if (!$patients) {
    die("Prepare failed: " . $conn->error);
}
$patients->bind_param("i", $department_id);
$patients->execute();
$results = $patients->get_result();

// Get history (served/cancelled)
$history = $conn->prepare("SELECT p.full_name, im.illness, im.medication, im.status, im.created_at FROM illness_medications im JOIN patients p ON im.patient_id = p.id WHERE p.department_id = ? AND im.status IN ('served', 'cancelled') ORDER BY im.created_at DESC LIMIT 10");
$history->bind_param("i", $department_id);
$history->execute();
$historyResults = $history->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Dashboard | MediCare</title>
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
            --border-radius: 8px;
            --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            line-height: 1.5;
        }

        header {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
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

        .user-info h2 {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info .role-badge {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
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
            display: grid;
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
            transform: translateY(-2px);
        }

        .card-header {
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.875rem;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        th {
            font-weight: 600;
            color: var(--gray);
            background: rgba(75, 108, 183, 0.05);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: rgba(75, 108, 183, 0.03);
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

        .status-served {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-group {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: var(--success);
            color: white;
        }

        .btn-primary:hover {
            background: #0d9f6e;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        form {
            display: inline;
        }

        .pill-icon {
            width: 20px;
            height: 20px;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 0.75rem;
                text-align: center;
            }
            
            .user-info h2 {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .action-group {
                flex-direction: column;
            }
            
            th, td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="user-info">
            <h2>
                <svg class="pill-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Welcome, <?= htmlspecialchars($pharmacist_name) ?>
                <span class="role-badge">Pharmacist</span>
            </h2>
        </div>
        <nav>
            <a href="../logout.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </nav>
    </div>
</header>

<div class="container">
    <!-- Active Prescriptions Card -->
    <div class="card">
        <div class="card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Active Prescriptions
            </h2>
            <span class="status-badge status-pending"><?= $results->num_rows ?> Pending</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Reason</th>
                    <th>Medication</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['reason_for_visit']) ?></td>
                    <td><?= htmlspecialchars($row['medication']) ?></td>
                    <td><?= date("M j, h:i A", strtotime($row['created_at'])) ?></td>
                    <td>
                        <span class="status-badge 
                            <?= ($row['status'] ?? 'pending') === 'served' ? 'status-served' : 
                               (($row['status'] ?? 'pending') === 'cancelled' ? 'status-cancelled' : 'status-pending') ?>">
                            <?= ucfirst($row['status'] ?? 'pending') ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-group">
                            <form method="POST" action="serve_patient.php">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="served" class="btn btn-primary">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"></path>
                                    </svg>
                                    Mark Served
                                </button>
                            </form>
                            <form method="POST" action="serve_patient.php">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="cancelled" class="btn btn-danger">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Prescription History Card -->
    <div class="card">
        <div class="card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                Prescription History
            </h2>
            <span class="status-badge status-served">Last 30 Days</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Medication</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $historyResults->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['medication']) ?></td>
                    <td>
                        <span class="status-badge 
                            <?= $row['status'] === 'served' ? 'status-served' : 
                               ($row['status'] === 'cancelled' ? 'status-cancelled' : 'status-pending') ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                    <td><?= date("M j, Y", strtotime($row['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>