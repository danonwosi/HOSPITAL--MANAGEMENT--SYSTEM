<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if (!in_array($action, ['served', 'cancelled'])) {
        die("Invalid action.");
    }

    $stmt = $conn->prepare("UPDATE illness_medications SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $action, $id);
    if ($stmt->execute()) {
        header("Location: pharmacist.php");
        exit;
    } else {
        echo "Update failed.";
    }
    $stmt->close();
}
?>
<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if (!in_array($action, ['served', 'cancelled'])) {
        die("Invalid action.");
    }

    $stmt = $conn->prepare("UPDATE illness_medications SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $action, $id);
    if ($stmt->execute()) {
        header("Location: pharmacist.php");
        exit;
    } else {
        echo "Update failed.";
    }
    $stmt->close();
}
?>
