<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['doctor', 'nurse'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$id = intval($_GET['id']);
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];
$table = $role . 's';

// Validate queue belongs to doctor/nurse
$check = $conn->prepare("SELECT q.id, q.queue_number, q.patient_id, p.full_name FROM queue q JOIN patients p ON q.patient_id = p.id JOIN $table d ON d.user_id = q.assigned_doctor_id WHERE q.id = ? AND d.user_id = ?");
$check->bind_param("ii", $id, $uid);
$check->execute();
$res = $check->get_result();
if ($res->num_rows == 0) {
    echo "Unauthorized access or patient not found.";
    exit;
}
$patient = $res->fetch_assoc();
$check->close();

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['defer'])) {
        $sql = "UPDATE queue SET registered_at = NOW() WHERE id = ? AND assigned_doctor_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $id, $uid);
            if ($stmt->execute()) {
                $success = "Patient deferred to be checked later.";
            } else {
                $error = "Failed to defer patient.";
            }
            $stmt->close();
        } else {
            $error = "Defer query preparation failed: " . $conn->error;
        }
    } else {
        $illness = trim($_POST['illness']);
        $medication = trim($_POST['medication']);

        $sql = "UPDATE queue SET checked_at = NOW() WHERE id = ? AND assigned_doctor_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $id, $uid);
            if ($stmt->execute()) {
                // Store illness and medication separately
                $insert = $conn->prepare("INSERT INTO illness_medications (patient_id, illness, medication, prescribed_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                $insert->bind_param("issi", $patient['patient_id'], $illness, $medication, $uid);
                if ($insert->execute()) {
                    $delete = $conn->prepare("DELETE FROM queue WHERE id = ?");
                    $delete->bind_param("i", $id);
                    $delete->execute();
                    $delete->close();
                    header("Location: doctor_nurse.php");
                    exit;
                } else {
                    $error = "Checked but failed to save prescription.";
                }
                $insert->close();
            } else {
                $error = "Failed to update queue.";
            }
            $stmt->close();
        } else {
            $error = "Check query preparation failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Check Patient</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; padding: 40px; }
        form { background: white; padding: 20px; max-width: 600px; margin: auto; border-radius: 8px; box-shadow: 0 0 6px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, textarea { width: 100%; padding: 10px; margin-top: 5px; }
        .success { color: green; margin-top: 10px; }
        .error { color: red; margin-top: 10px; }
        .btn { margin-right: 10px; }
        a { display: inline-block; margin-top: 20px; color: #3498db; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Check Patient - <?= htmlspecialchars($patient['full_name']) ?> (<?= htmlspecialchars($patient['queue_number']) ?>)</h2>

<form method="POST">
    <?php if ($success): ?><p class="success">✅ <?= $success ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error">❌ <?= $error ?></p><?php endif; ?>

    <label>Illness/Diagnosis (Optional)</label>
    <textarea name="illness" rows="3"></textarea>

    <label>Medication Prescribed (Optional)</label>
    <textarea name="medication" rows="3"></textarea>

    <button type="submit" class="btn">Mark as Checked</button>
    <button type="submit" name="defer" value="1" class="btn">Defer</button>
</form>

<a href="doctor_nurse.php">&larr; Back to Dashboard</a>
</body>
</html>
