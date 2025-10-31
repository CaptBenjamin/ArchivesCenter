<?php
/* logs/edit.php 编辑日志 - 日期只读 */
require_once __DIR__ . '/../config.php';
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
}

$config = require __DIR__ . '/../config.php';
$conn   = new mysqli($config['db_host'], $config['db_username'],
                     $config['db_password'], $config['db_name']);
if ($conn->connect_error) die('连接失败：' . $conn->connect_error);
$conn->set_charset('utf8mb4');

/* 读取单条日志 */
$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM logs WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$log = $res->fetch_assoc();
if (!$log) {
    header('Location: /logs.php');
    exit;
}

/* 处理更新（日期只读） */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'put') {
    $title   = $_POST['title']   ?? '';
    $content = $_POST['content'] ?? '';

    $stmt = $conn->prepare("UPDATE logs SET title = ?, content = ? WHERE id = ?");
    $stmt->bind_param('ssi', $title, $content, $id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: /logs/detail.php?id=$id&saved=1");
        exit;
    } else {
        $msg = '<div class="alert alert-danger mt-3">更新失败：' . $stmt->error . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>编辑日志 - LYKNS ArchivesCenter</title>
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
        /* 主容器：全宽 + 上下留白 */
        .main-container {
            width: 100%;
            padding: 40px 20px;
        }
        /* 长文本折行 */
        .text-content {
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>

<body class="d-flex flex-column">

    <!-- 顶部导航栏 -->
    <nav class="navbar navbar-expand-sm bg-primary navbar-dark">
        <a class="navbar-brand" href="https://www.contoso.com/">
            <img src="https://www.contoso.com/logo.svg" alt="logo"
                 style="width:40px;margin:0 0 0 10px;">
            LYKNS ArchivesCenter
        </a>
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/">欢迎</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/search.php">综合搜索</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/people.php">人物</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logs.php">日志</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/settings.php">设置</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logout.php">注销</a></li>
        </ul>
    </nav>

    <!-- ✅ 修改为 container-fluid + main-container -->
    <div class="flex-fill container-fluid main-container mb-3">
        <div class="container-fluid">

            <h2>编辑日志</h2>
            <?php if (isset($msg)) echo $msg; ?>

            <form method="post" autocomplete="off">
                <input type="hidden" name="_method" value="put">

                <!-- 1 日期：只读 -->
                <div class="mb-3">
                    <label class="form-label">日期</label>
                    <input type="date" class="form-control" value="<?= $log['log_date'] ?>" readonly>
                </div>

                <!-- 2 标题 -->
                <div class="mb-3">
                    <label class="form-label">标题 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="title" required value="<?= htmlspecialchars($log['title']) ?>">
                </div>

                <!-- 3 内容 -->
                <div class="mb-3">
                    <label class="form-label">内容 <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="8" name="content" required><?= htmlspecialchars($log['content']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">保存修改</button>
                <a href="/logs/detail.php?id=<?= $id ?>" class="btn btn-secondary ms-2">返回详情</a>
            </form>

        </div>
    </div>

    <!-- 页脚 -->
    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018-2025 LYKNS 保留所有权利.</p>
    </footer>

</body>
</html>
