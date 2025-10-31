<?php
/* oobe.php 一站式初始化 - 优化版 */
session_start();

$success = false;
$msg = null;
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 保存表单数据用于回显
    $form_data = [
        'db_host'   => trim($_POST['db_host'] ?? ''),
        'db_name'   => trim($_POST['db_name'] ?? ''),
        'db_user'   => trim($_POST['db_username'] ?? ''),
        'init_user' => trim($_POST['initial_username'] ?? '')
    ];
    
    $db_pass   = $_POST['db_password'] ?? '';
    $init_pwd  = $_POST['initial_password'] ?? '';

    // 基础校验
    if (empty($form_data['db_host']) || empty($form_data['db_name']) || 
        empty($form_data['db_user']) || empty($form_data['init_user']) || 
        empty($init_pwd)) {
        $msg = "❌ 所有字段均为必填。";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['db_name'])) {
        $msg = "❌ 数据库名不合法，仅允许字母、数字和下划线。";
    } elseif (strlen($init_pwd) < 6) {
        $msg = "❌ 初始密码长度至少为 6 个字符。";
    } else {
        /* 1️⃣ 写入 config.php */
        $config_content = "<?php\nreturn [\n";
        $config_content .= "    'db_host' => '" . addslashes($form_data['db_host']) . "',\n";
        $config_content .= "    'db_name' => '" . addslashes($form_data['db_name']) . "',\n";
        $config_content .= "    'db_username' => '" . addslashes($form_data['db_user']) . "',\n";
        $config_content .= "    'db_password' => '" . addslashes($db_pass) . "',\n";
        $config_content .= "];\n";
        
        if (@file_put_contents(__DIR__ . '/config.php', $config_content) === false) {
            $msg = "❌ 无法写入配置文件，请检查目录权限。";
        } else {
            @chmod(__DIR__ . '/config.php', 0600);

            /* 2️⃣ 建库 + 建表 */
            $conn = @new mysqli($form_data['db_host'], $form_data['db_user'], $db_pass);
            
            if ($conn->connect_error) {
                $msg = "❌ 数据库连接失败：" . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8');
            } else {
                $conn->set_charset('utf8mb4');
                
                // 创建数据库
                $safe_db_name = $conn->real_escape_string($form_data['db_name']);
                if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$safe_db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
                    $msg = "❌ 创建数据库失败：" . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8');
                    $conn->close();
                } else {
                    $conn->select_db($safe_db_name);

                    // 创建 users 表
                    $conn->query("CREATE TABLE IF NOT EXISTS `users` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `username` VARCHAR(50) NOT NULL UNIQUE,
                        `password` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX `idx_username` (`username`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    // 创建 people 表
                    $conn->query("CREATE TABLE IF NOT EXISTS `people` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `姓名` VARCHAR(100) NOT NULL,
                        `性别` ENUM('男','女') NOT NULL,
                        `身份证件类型` VARCHAR(50) DEFAULT NULL,
                        `身份证件号码` VARCHAR(100) DEFAULT NULL,
                        `出生日期` DATE DEFAULT NULL,
                        `籍贯` VARCHAR(100) DEFAULT NULL,
                        `政治面貌` VARCHAR(50) DEFAULT NULL,
                        `婚姻状况` ENUM('未婚','已婚','初婚','再婚','丧偶','离婚') DEFAULT NULL,
                        `住址` TEXT DEFAULT NULL,
                        `家庭状况` TEXT DEFAULT NULL,
                        `结识地点` TEXT NOT NULL,
                        `相对身份` VARCHAR(100) NOT NULL,
                        `小学` VARCHAR(100) DEFAULT NULL,
                        `初中` VARCHAR(100) DEFAULT NULL,
                        `高中` VARCHAR(100) DEFAULT NULL,
                        `大学` VARCHAR(100) DEFAULT NULL,
                        `研究生学校` VARCHAR(100) DEFAULT NULL,
                        `就职单位` VARCHAR(100) DEFAULT NULL,
                        `电话号码` VARCHAR(50) DEFAULT NULL,
                        `电子邮件地址` VARCHAR(120) DEFAULT NULL,
                        `微信ID` VARCHAR(50) DEFAULT NULL,
                        `QQ号` VARCHAR(20) DEFAULT NULL,
                        `其他联系方式` TEXT DEFAULT NULL,
                        `备注` TEXT DEFAULT NULL,
                        `录入时间` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX `idx_name` (`姓名`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    // 创建 logs 表
                    $conn->query("CREATE TABLE IF NOT EXISTS `logs` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `log_date` DATE NOT NULL UNIQUE,
                        `title` VARCHAR(255) NOT NULL,
                        `content` TEXT NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX `idx_date` (`log_date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                    // 插入初始用户
                    $hashed_pwd = password_hash($init_pwd, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO `users` (`username`, `password`) VALUES (?, ?)");
                    $stmt->bind_param('ss', $form_data['init_user'], $hashed_pwd);
                    
                    if ($stmt->execute()) {
                        $success = true;
                        // 自动登录
                        $_SESSION['username'] = $form_data['init_user'];
                        $_SESSION['user_id'] = $stmt->insert_id;
                    } else {
                        $msg = "❌ 创建初始用户失败：" . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
                    }
                    
                    $stmt->close();
                    $conn->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>系统初始化 - LYKNS ArchivesCenter</title>
    <link href="https://www.contoso.com/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.contoso.com/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="https://www.contoso.com/logo.ico" type="image/x-icon">
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
        .oobe-form {
            max-width: 600px;
        }
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .form-section:last-of-type {
            border-bottom: none;
        }
        .form-section h4 {
            font-size: 1.1rem;
            color: #495057;
            margin-bottom: 15px;
        }
        .form-text {
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
<div class="d-flex flex-column">
    <nav class="navbar navbar-expand-sm bg-primary navbar-dark">
        <a class="navbar-brand" href="https://www.contoso.com/">
            <img src="https://www.contoso.com/logo.svg" alt="logo" style="width:40px;margin:0 0 0 10px;">
            LYKNS ArchivesCenter
        </a>
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link active">系统初始化</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/">返回首页</a></li>
        </ul>
    </nav>

    <div class="flex-fill">
        <div class="container-fluid">
            <div class="main-container">
                <h2 class="mb-3">首次使用初始化</h2>
                <p class="text-muted mb-4">欢迎使用 LYKNS ArchivesCenter！请填写以下信息完成系统初始化。</p>

                <form method="post" autocomplete="off" class="oobe-form">
                    <!-- 数据库配置 -->
                    <div class="form-section">
                        <h4>数据库配置</h4>
                        <div class="mb-3">
                            <label class="form-label">数据库地址</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="db_host" 
                                   value="<?= htmlspecialchars($form_data['db_host'] ?? 'localhost', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="例如：localhost 或 127.0.0.1"
                                   required>
                            <div class="form-text">MySQL 数据库服务器地址</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">数据库名</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="db_name" 
                                   value="<?= htmlspecialchars($form_data['db_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="例如：archivescenter"
                                   pattern="[a-zA-Z0-9_]+"
                                   required>
                            <div class="form-text">仅允许字母、数字和下划线</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">数据库用户名</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="db_username" 
                                   value="<?= htmlspecialchars($form_data['db_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="例如：root"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">数据库密码</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="db_password"
                                   placeholder="数据库密码">
                            <div class="form-text">如果没有密码，请留空</div>
                        </div>
                    </div>

                    <!-- 管理员账户 -->
                    <div class="form-section">
                        <h4>管理员账户</h4>
                        <div class="mb-3">
                            <label class="form-label">管理员用户名</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="initial_username" 
                                   value="<?= htmlspecialchars($form_data['init_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="设置管理员用户名"
                                   required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">管理员密码</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="initial_password"
                                   placeholder="设置管理员密码（至少 6 位）"
                                   minlength="6"
                                   required>
                            <div class="form-text">请设置一个强密码以保护系统安全</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg px-5">开始初始化</button>
                </form>

                <?php if ($success): ?>
                    <div class="alert alert-success mt-4">
                        <strong>✅ 初始化成功！</strong>
                        <p class="mb-0 mt-2">系统已成功配置，即将跳转到首页...</p>
                    </div>
                    <script>setTimeout(() => location.href = '/index.php', 2000);</script>
                <?php elseif ($msg): ?>
                    <div class="alert alert-danger mt-4">
                        <strong><?= $msg ?></strong>
                    </div>
                <?php endif; ?>

                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="mb-2">💡 提示</h6>
                    <ul class="mb-0 small text-muted">
                        <li>请确保 MySQL 数据库服务正在运行</li>
                        <li>数据库用户需要有创建数据库和表的权限</li>
                        <li>初始化完成后，配置文件将保存在系统根目录</li>
                    </ul>
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