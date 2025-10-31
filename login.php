<?php
/* login.php 登录页面 - 优化版 */
session_start();

/* 如果已登录，直接跳转首页 */
if (isset($_SESSION['username'])) {
    header('Location: /index.php');
    exit;
}

/* 加载配置文件 */
$config = require __DIR__ . '/config.php';

$msg = '';

/* 处理登录表单 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $msg = '<div class="alert alert-warning mt-3">请输入用户名和密码。</div>';
    } else {
        /* 数据库连接 */
        $conn = new mysqli(
            $config['db_host'],
            $config['db_username'],
            $config['db_password'],
            $config['db_name']
        );

        if ($conn->connect_error) {
            $msg = '<div class="alert alert-danger mt-3">数据库连接失败，请稍后再试。</div>';
        } else {
            $conn->set_charset('utf8mb4');

            /* 使用预处理语句防止 SQL 注入 */
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                /* 验证加密密码 */
                if (password_verify($password, $row['password'])) {
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['user_id'] = $row['id'];
                    $stmt->close();
                    $conn->close();
                    header('Location: /index.php');
                    exit;
                } else {
                    $msg = '<div class="alert alert-danger mt-3">密码错误，请重试。</div>';
                }
            } else {
                $msg = '<div class="alert alert-danger mt-3">用户不存在。</div>';
            }

            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="https://www.contoso.com/logo.ico" type="image/x-icon">
    <title>登录 - LYKNS ArchivesCenter</title>
    <link href="https://www.contoso.com/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.contoso.com/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: #fff;
        }
        .d-flex.flex-column {
            min-height: 100vh;
        }
        .flex-fill {
            flex: 1;
        }
        .main-container {
            max-width: 600px;
            padding: 40px 20px;
        }
        .login-form {
            max-width: 600px;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>

<body>
<div class="d-flex flex-column">
    <nav class="navbar navbar-expand-sm bg-primary navbar-dark">
        <a class="navbar-brand" href="https://www.contoso.com/">
            <img src="https://www.contoso.com/logo.svg" alt="logo"
                 style="width:40px;margin:0 0 0 10px;">
            LYKNS ArchivesCenter
        </a>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link active" href="https://www.contoso.com/login.php">登录</a>
            </li>
        </ul>
    </nav>

    <div class="flex-fill">
        <div class="container-fluid">
            <div class="main-container">
                <h2 class="mb-4">登录 LYKNS ArchivesCenter</h2>
                
                <form method="post" autocomplete="off" class="login-form">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="username" 
                               name="username" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required 
                               autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" 
                               class="form-control form-control-lg" 
                               id="password" 
                               name="password" 
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg px-5">登录</button>
                </form>

                <?= $msg ?>

                <div class="mt-4 text-muted">
                    <small>首次使用？请确保已完成系统初始化。</small>
                </div>
            </div>
        </div>
    </div>

    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin:0 0 0 10px;">
            &copy; 2018-2025 LYKNS 保留所有权利.
        </p>
    </footer>
</div>
</body>
</html>