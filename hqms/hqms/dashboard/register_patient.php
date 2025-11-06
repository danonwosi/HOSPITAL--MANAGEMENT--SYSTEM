<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: ../login.php");
    exit;
}

$departments = $conn->query("SELECT id, name FROM departments");
$register_success = $register_error = "";
$ticket_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $age = (int)$_POST['age'];
    $gender = $_POST['gender'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $reason = trim($_POST['reason']);
    $department_id = (int)$_POST['department_id'];

    if ($name && $age && $gender && $reason && $department_id) {
        $stmt = $conn->prepare("INSERT INTO patients (full_name, age, gender, phone, email, department_id, reason_for_visit) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssis", $name, $age, $gender, $phone, $email, $department_id, $reason);
        if ($stmt->execute()) {
            $patient_id = $conn->insert_id;
            $doctor_q = $conn->query("SELECT u.id, d.room_number, d.full_name FROM users u JOIN doctors d ON u.id = d.user_id WHERE d.department_id = $department_id ORDER BY (SELECT COUNT(*) FROM queue q WHERE q.assigned_doctor_id = u.id AND q.checked_at IS NULL) ASC LIMIT 1");
            $assigned_doctor = $doctor_q->num_rows ? $doctor_q->fetch_assoc() : null;
            $assigned_doctor_id = $assigned_doctor ? $assigned_doctor['id'] : null;
            $queue_number = 'Q' . str_pad($patient_id, 3, '0', STR_PAD_LEFT);
            $stmt2 = $conn->prepare("INSERT INTO queue (patient_id, assigned_doctor_id, department_id, queue_number) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("iiis", $patient_id, $assigned_doctor_id, $department_id, $queue_number);
            $stmt2->execute();
            $stmt2->close();
            $register_time = date("Y-m-d H:i:s");

            $stmt3 = $conn->prepare("INSERT INTO tickets (patient_id, queue_number, assigned_doctor_id, room_number, registered_at) VALUES (?, ?, ?, ?, ?)");
            $stmt3->bind_param("issis", $patient_id, $queue_number, $assigned_doctor_id, $assigned_doctor['room_number'], $register_time);
            $stmt3->execute();
            $stmt3->close();

            $register_success = "Patient registered successfully.";
            $ticket_info = [
                'queue' => $queue_number,
                'name' => $name,
                'doctor' => $assigned_doctor ? $assigned_doctor['full_name'] : 'N/A',
                'room' => $assigned_doctor ? $assigned_doctor['room_number'] : 'N/A',
                'time' => $register_time,
                'phone' => '+233 302 123456',
                'email' => 'info@hospitalghana.org',
                'hospital' => 'Ghana General Hospital'
            ];
        } else {
            $register_error = "Failed to register patient.";
        }
        $stmt->close();
    } else {
        $register_error = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital System | Register Patient</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --border-radius: 6px;
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f5f9;
            color: var(--dark);
            line-height: 1.5;
        }

        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 0.8rem 1.5rem;
            box-shadow: var(--shadow);
        }

        .nav-container {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-weight: 700;
            font-size: 1.1rem;
        }

        nav {
            display: flex;
            gap: 1.2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .main-container {
            max-width: 1000px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }

        .form-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .compact-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        input, select {
            width: 100%;
            padding: 0.5rem 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .alert {
            padding: 0.8rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
            grid-column: span 2;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn {
            padding: 0.6rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
            width: 100%;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .ticket {
            margin-top: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            border: 1px dashed var(--primary);
            grid-column: span 2;
        }

        .ticket h3 {
            margin: 0 0 0.3rem;
            font-size: 1.1rem;
        }

        .ticket p {
            margin: 0.3rem 0;
            font-size: 0.85rem;
        }

        .print-btn {
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            margin-top: 0.8rem;
            font-size: 0.85rem;
        }

        .back-link {
            display: block;
            text-align: center;
            color: var(--primary);
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .compact-group {
                grid-template-columns: 1fr;
            }
            
            .full-width, .alert {
                grid-column: span 1;
            }
            
            nav {
                gap: 0.8rem;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="nav-container">
        <div class="logo">MediCare Hospital</div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="register_patient.php" class="active">Register</a>
            <a href="queue.php">Queue</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>
</header>

<div class="main-container">
    <div class="form-card">
        <div class="form-header">
            <h2>Register New Patient</h2>
        </div>

        <form method="POST">
            <div class="form-grid">
                <?php if ($register_success): ?>
                    <div class="alert alert-success">✅ <?= $register_success ?></div>
                <?php endif; ?>
                
                <?php if ($register_error): ?>
                    <div class="alert alert-error">❌ <?= $register_error ?></div>
                <?php endif; ?>

                <!-- Left Column -->
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <!-- Right Column -->
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone">
                </div>

                <!-- Compact Row -->
                <div class="form-group full-width">
                    <div class="compact-group">
                        <div>
                            <label for="age">Age *</label>
                            <input type="number" id="age" name="age" required style="padding: 0.5rem;">
                        </div>
                        <div>
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" required style="padding: 0.5rem;">
                                <option value="">-- Select --</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Left Column -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>

                <!-- Right Column -->
                <div class="form-group">
                    <label for="department_id">Department *</label>
                    <select id="department_id" name="department_id" required>
                        <option value="">-- Select --</option>
                        <?php while ($d = $departments->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Full Width -->
                <div class="form-group full-width">
                    <label for="reason">Reason for Visit *</label>
                    <input type="text" id="reason" name="reason" required>
                </div>

                <!-- Form Actions -->
                <div class="form-group full-width">
                    <button type="submit" class="btn btn-primary">Register Patient</button>
                </div>

                <?php if ($ticket_info): ?>
                <div class="ticket" id="ticket">
                    <h3><?= $ticket_info['hospital'] ?></h3>
                    <p>📞 <?= $ticket_info['phone'] ?> | ✉ <?= $ticket_info['email'] ?></p>
                    <hr>
                    <p><strong>Queue No:</strong> <?= htmlspecialchars($ticket_info['queue']) ?></p>
                    <p><strong>Patient:</strong> <?= htmlspecialchars($ticket_info['name']) ?></p>
                    <p><strong>Doctor:</strong> <?= htmlspecialchars($ticket_info['doctor']) ?></p>
                    <p><strong>Room:</strong> <?= htmlspecialchars($ticket_info['room']) ?></p>
                    <p><strong>Registered:</strong> <?= $ticket_info['time'] ?></p>
                    <button class="print-btn" onclick="printTicket()">Print Ticket</button>
                </div>
                <script>
                    function printTicket() {
                        const ticket = document.getElementById('ticket').innerHTML;
                        const win = window.open('', '', 'height=500,width=400');
                        win.document.write(`
                            <html>
                                <head>
                                    <title>Patient Ticket</title>
                                    <style>
                                        body { font-family: Arial; padding: 15px; }
                                        h3 { color: #3b82f6; }
                                        hr { border: none; border-top: 1px dashed #ccc; }
                                    </style>
                                </head>
                                <body>${ticket}</body>
                            </html>
                        `);
                        win.document.close();
                        win.print();
                    }
                </script>
                <?php endif; ?>
            </div>
        </form>

        <a href="receptionist.php" class="back-link">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>