<?php
session_start();
require 'config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ใช้ Prepared Statement เพื่อความปลอดภัย
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['staff_id'] = $user['id'];
        $_SESSION['staff_name'] = $user['name'];
        header("Location: index.php");
        exit();
    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cafe</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <style>
        body { 
            background: #f4f1ea; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Kanit', sans-serif; 
        }
        .login-card { 
            width: 100%; 
            max-width: 380px; 
            padding: 40px; 
            border-radius: 25px; 
            background: white; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: none;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <div class="mb-4">
            <h2 class="fw-bold" style="color: #6d4c41;">CAFE</h2>
            <p class="text-muted small">กรุณาเข้าสู่ระบบพนักงาน</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="text" name="username" class="form-control border-0" placeholder="Username" required autocomplete="off">
            </div>
            <div class="mb-4">
                <input type="password" name="password" class="form-control border-0" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn btn-dark w-100 py-2 fw-bold shadow-sm">
                เข้าสู่ระบบ
            </button>
        </form>
    </div>
</body>
</html>