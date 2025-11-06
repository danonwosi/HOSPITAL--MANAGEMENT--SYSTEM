<?php
session_start();
require_once 'db.php'; // contains $conn

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password']; // plain text (intentional)

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) { // plain text check
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            switch ($user['role']) {
                case 'admin':
                    header("Location: dashboard/admin.php");
                    break;
                case 'doctor':
                case 'nurse':
                    header("Location: dashboard/doctor_nurse.php");
                    break;
                case 'receptionist':
                    header("Location: dashboard/receptionist.php");
                    break;
                case 'pharmacist':
                    header("Location: dashboard/pharmacist.php");
                    break;
                default:
                    $error = "Unknown role.";
            }
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Queue System | CuraX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #93c5fd;
            --error: #ef4444;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-dark: #1e293b;
            --text-light: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #1e40af, #2563eb, #3b82f6, #60a5fa);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            padding: 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .logo img {
            height: 60px;
            width: auto;
        }

        .login-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .form-group {
            position: relative;
            margin-bottom: 24px;
        }

        .form-group input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid transparent;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }

        .form-group label {
            position: absolute;
            left: 20px;
            top: 16px;
            font-weight: 500;
            color: var(--text-light);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: -10px;
            left: 12px;
            font-size: 12px;
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
        }

        button {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            color: white;
            font-weight: 600;
            background: var(--error);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }

        /* Responsive Adjustments */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo img {
                height: 50px;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <!-- Replace with your hospital logo -->
    <div class="logo">
        <img src="img/Untitled-2.jpg.png" alt="Hospital Logo">
    </div>
    
    <div class="login-header">
        <h2>Queue Management</h2>
        <p>Staff Login Portal</p>
    </div>
    
    <?php if ($error): ?>
        <p class="error">⚠️ <?= $error ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <input type="text" id="username" name="username" required placeholder=" ">
            <label for="username">Username</label>
        </div>
        
        <div class="form-group">
            <input type="password" id="password" name="password" required placeholder=" ">
            <label for="password">Password</label>
        </div>
        
        <button type="submit">Login →</button>
    </form>
</div>
</body>
</html>