<?php
/* index.php 首页 - 优化版 */
session_start();

/* ✅ 未初始化系统则跳转到 oobe */
if (!file_exists(__DIR__ . '/config.php')) {
    header("Location: /oobe.php");
    exit;
}

$config = include __DIR__ . '/config.php';
$conn = @new mysqli(
    $config['db_host'],
    $config['db_username'],
    $config['db_password'],
    $config['db_name']
);

if ($conn && !$conn->connect_error) {
    $conn->set_charset('utf8mb4');

    // 检查 users 表是否存在 & 是否已有管理员
    $check = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$check || $check->num_rows === 0) {
        header("Location: /oobe.php");
        exit;
    }

    $result = $conn->query("SELECT COUNT(*) AS c FROM users");
    $count = $result->fetch_assoc()['c'];

    if ($count == 0) {
        header("Location: /oobe.php");
        exit;
    }
}


$people_count = 0;
$logs_count   = 0;
$is_logged_in = isset($_SESSION['username']);

/* 若已登录，查询统计数据 */
if ($is_logged_in) {
    $config = require __DIR__ . '/config.php';
    $conn = new mysqli(
        $config['db_host'],
        $config['db_username'],
        $config['db_password'],
        $config['db_name']
    );
    
    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
        
        // 查询人物数量
        $stmt = $conn->prepare("SELECT COUNT(*) FROM people");
        $stmt->execute();
        $people_count = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        
        // 查询日志数量
        $stmt = $conn->prepare("SELECT COUNT(*) FROM logs");
        $stmt->execute();
        $logs_count = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>首页 - ArchivesCenter</title>
    <link href="/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap.bundle.min.js"></script>
  <link rel="icon" href="/pinwheel.ico" type="image/x-icon">
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
            max-width: 100%;
            padding: 40px 20px;
        }
        .stats-box {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stats-box h5 {
            margin-bottom: 15px;
            color: #495057;
        }
        .stats-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .stats-item:last-child {
            border-bottom: none;
        }
        .stats-label {
            font-size: 1rem;
            color: #6c757d;
        }
        .stats-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>

<body>
<div class="d-flex flex-column">
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="/pinwheel-wf.svg" alt="logo"
                 style="width:32px;margin-right:8px;">
            ArchivesCenter
        </a>

        <!-- 折叠按钮（移动端汉堡按钮） -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="切换导航">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- 折叠菜单区域 -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item"><a class="nav-link active" href="/">欢迎</a></li>

                <?php if ($is_logged_in): ?>
                    <li class="nav-item"><a class="nav-link" href="/search.php">综合搜索</a></li>
                    <li class="nav-item"><a class="nav-link" href="/people.php">人物</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logs.php">日志</a></li>
                    <li class="nav-item"><a class="nav-link" href="/settings.php">设置</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout.php">注销</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login.php">登录</a></li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
    </nav>


    <!-- 主体内容 -->
    <div class="flex-fill" style="margin: 50px 0 0 0">
        <div class="container-fluid">
            <div class="main-container">
                <?php if ($is_logged_in): ?>
                    <h1 class="mb-3">真实客观 有序推进</h1>
                    <p>已作为 <strong><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong> 登录。</p>
                    <p class="text-muted">请选择上方导航栏的功能以继续操作。</p>

                    <!-- 统计信息 -->
                    <div class="stats-box">
                        <h5>系统统计</h5>
                        <div class="stats-item">
                            <span class="stats-label">已保存的人物数量</span>
                            <span class="stats-value"><?= $people_count ?></span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-label">已保存的日志数量</span>
                            <span class="stats-value"><?= $logs_count ?></span>
                        </div>
                    </div>

                    <!-- 快速操作 -->
                    <div class="mt-4">
                        <h5 class="mb-3">快速操作</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="/people/create.php" class="btn btn-primary">创建新人物</a>
                            <a href="/logs/create.php" class="btn btn-primary">创建新日志</a>
                            <a href="/search.php" class="btn btn-outline-primary">搜索</a>
                        </div>
                    </div>
                <?php else: ?>
                    <h1 class="mb-3">欢迎访问 ArchivesCenter</h1>
                    <div class="alert alert-danger">
                        <strong>未授权的访问！</strong> 请先 <a href="/login.php" class="alert-link">登录</a>。
                    </div>
                    <p class="mt-4 text-muted">ArchivesCenter 是一个用于管理人物信息和日志的档案管理系统。</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin:0 0 0 10px;">
            &copy; 2018-2025 LYKNS 保留所有权利.
        </p>
    </footer>
</div>
</body>
</html>