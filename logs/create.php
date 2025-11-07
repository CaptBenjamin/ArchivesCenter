<?php
/* logs/create.php 创建日志 */
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

/* 已存在日期数组（JS 禁止选择 + 后端二次检查） */
$existRes = $conn->query("SELECT log_date FROM logs");
$existDates = array_column($existRes->fetch_all(MYSQLI_ASSOC), 'log_date');

/* 处理提交 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date    = $_POST['log_date'] ?? '';
    $title   = $_POST['title']   ?? '';
    $content = $_POST['content'] ?? '';

    /* 二次检查：日期是否已存在 */
    $stmt = $conn->prepare("SELECT 1 FROM logs WHERE log_date = ? LIMIT 1");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $msg = '<div class="alert alert-danger mt-3">该日期已存在日志，请勿重复创建。</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO logs (log_date, title, content) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $date, $title, $content);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header('Location: /logs.php?created=1');
            exit;
        } else {
            $msg = '<div class="alert alert-danger mt-3">保存失败：' . $stmt->error . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>创建日志 - ArchivesCenter</title>
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
        /* 外层主容器 */
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
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/settings.php">设置</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logout.php">注销</a></li>
        </ul>
    </nav>

    <!-- ✅ 双层结构：main-container + 内层 container-fluid -->
    <div class="flex-fill container-fluid main-container mb-3" style="margin: 50px 0 0 0">
        <div class="container-fluid">
            <h2>创建日志</h2>
            <?php if (isset($msg)) echo $msg; ?>

            <form method="post" autocomplete="off" onsubmit="return checkDate()">

                <!-- 1 日期 -->
                <div class="mb-3">
                    <label class="form-label">日期 <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="log_date" id="logDate" required
                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>

                <!-- 2 标题 -->
                <div class="mb-3">
                    <label class="form-label">标题 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="title" required>
                </div>

                <!-- 3 内容 -->
                <div class="mb-3">
                    <label class="form-label">内容 <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="8" name="content" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">保存日志</button>
                <a href="/logs.php" class="btn btn-secondary ms-2">返回列表</a>
            </form>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018-2025 LYKNS 保留所有权利.</p>
    </footer>

    <script>
    // 禁止选择已存在的日期
    const existDates = <?= json_encode($existDates) ?>;
    function checkDate() {
        const sel = document.getElementById('logDate').value;
        if (existDates.includes(sel)) {
            alert('该日期已存在日志，请选择其他日期。');
            return false;
        }
        return true;
    }
    </script>

</body>
</html>
