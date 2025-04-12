<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once('includes/config.php');

    $username = $_POST['username'];
    $password = md5($_POST['password']);

    $sql = "SELECT * FROM pengguna WHERE username = :username AND password = :password";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username, 'password' => $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user['username'];
        header('Location: index.php');
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAS Biro Umum</title>

    <!-- Link Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Roboto:wght@300;400&display=swap" rel="stylesheet">

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: url('assets/img/kantor_gubernur_kendari.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .login-container {
            width: 100%;
            max-width: 500px; /* Menyesuaikan ukuran container */
            padding: 30px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.7); /* Latar belakang hitam transparan */
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            color: #fff; /* Warna teks putih agar kontras */
            text-align: center;
        }
        .login-title {
            margin-bottom: 40px;
            max-width: 100%; /* Agar gambar responsive */
            height: auto;
            width: 20%; /* Menyesuaikan ukuran logo */
        }
        .form-label {
            font-weight: 500;
            color: #fff; /* Warna label putih */
        }
        .btn-login {
            width: 100%;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo di tengah -->
        <img src="assets/img/logo sipas.png" alt="SIPAS Biro Umum" class="login-title">
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username Anda" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password Anda" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">Login</button>
        </form>
    </div>
</body>
</html>