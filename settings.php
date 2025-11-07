<?php
session_start();

/* 若未登录则跳转 */
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

/* 加载配置文件 */
$config = require __DIR__ . '/config.php';

/* 处理更新逻辑 */
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    if ($new_username === '' || $new_password === '') {
        $message = "<div class='alert alert-warning mt-3'>请填写完整信息。</div>";
    } else {
        $conn = new mysqli(
            $config['db_host'],
            $config['db_username'],
            $config['db_password'],
            $config['db_name']
        );
        if ($conn->connect_error) {
            die("数据库连接失败：" . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
        }

        /* 加密密码 */
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        /* 更新用户信息（安全预处理） */
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE username = ?");
        $stmt->bind_param('sss', $new_username, $hashed_password, $_SESSION['username']);

        if ($stmt->execute()) {
            $_SESSION['username'] = $new_username;
            $message = "<div class='alert alert-success mt-3'>用户信息更新成功。</div>";
        } else {
            $message = "<div class='alert alert-danger mt-3'>更新用户信息失败：" . htmlspecialchars($stmt->error) . "</div>";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <title>设置 - ArchivesCenter</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://www.contoso.com/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.contoso.com/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="https://www.contoso.com/pinwheel.ico" type="image/x-icon">
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
        .settings-container {
            max-width: 600px;
            padding: 40px 20px;
        }
    </style>
</head>

<body>
<div class="d-flex flex-column">
    <nav class="navbar navbar-expand-sm bg-primary navbar-dark" style="position: fixed;width: 100%;">
        <a class="navbar-brand" href="https://www.contoso.com/">
            <img src="https://www.contoso.com/pinwheel-wf.svg" alt="logo"
                 style="width:40px;margin:0 0 0 10px;">
            ArchivesCenter
        </a>
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/">欢迎</a></li>
        <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/search.php">综合搜索</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/people.php">人物</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logs.php">日志</a></li>
            <li class="nav-item"><a class="nav-link active" href="https://www.contoso.com/settings.php">设置</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logout.php">注销</a></li>
        </ul>
    </nav>

    <div class="flex-fill" style="margin: 50px 0 0 0">
        <div class="container-fluid">
            <div class="settings-container">
                <h2 class="mb-4">用户设置</h2>

                <form method="post" autocomplete="off">
                    <div class="mb-3">
                        <label for="new_username" class="form-label">新的用户名</label>
                        <input type="text" class="form-control form-control-lg" id="new_username" name="new_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">新的密码</label>
                        <input type="password" class="form-control form-control-lg" id="new_password" name="new_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg px-5">更新信息</button>
                </form>

                <?= $message ?>
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
