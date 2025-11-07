<?php
/* logs/detail.php 日志详情 + 上一条/下一条 + 删除 */
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

/* 读取当前日志 */
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

/* 上一条 / 下一条（按日期相邻） */
$prev = $conn->prepare("SELECT id FROM logs WHERE log_date < ? ORDER BY log_date DESC LIMIT 1");
$prev->bind_param('s', $log['log_date']);
$prev->execute();
$prevId = $prev->get_result()->fetch_column();

$next = $conn->prepare("SELECT id FROM logs WHERE log_date > ? ORDER BY log_date ASC LIMIT 1");
$next->bind_param('s', $log['log_date']);
$next->execute();
$nextId = $next->get_result()->fetch_column();

/* 处理删除 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $del = $conn->prepare("DELETE FROM logs WHERE id = ? LIMIT 1");
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
    $conn->close();
    header('Location: /logs.php?del=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>日志详情 - ArchivesCenter</title>
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
        /* 主容器：全宽 + 适度边距 */
        .main-container {
            width: 100%;
            padding: 40px 20px;
        }
        /* 文本折行 */
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

    <!-- ✅ 改为双层结构 -->
    <div class="flex-fill container-fluid main-container mb-3" style="margin: 50px 0 0 0">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>日志详情</h2>
                <div>
                    <?php if ($prevId): ?>
                        <a href="/logs/detail.php?id=<?= $prevId ?>" class="btn btn-outline-primary me-2">上一条</a>
                    <?php endif; ?>
                    <?php if ($nextId): ?>
                        <a href="/logs/detail.php?id=<?= $nextId ?>" class="btn btn-outline-primary me-2">下一条</a>
                    <?php endif; ?>
                    <a href="/logs/edit.php?id=<?= $log['id'] ?>" class="btn btn-warning me-2">编辑</a>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delModal">删除</button>
                </div>
            </div>

            <!-- 内容堆叠 -->
            <?php
            $fields = ['log_date' => '日期', 'title' => '标题', 'content' => '内容'];
            foreach ($fields as $dbCol => $label):
                $v = $log[$dbCol] ?? '';
                $display = $v === '' ? '<span class="text-muted">缺省</span>' : htmlspecialchars($v);
                echo '<h5 class="fw-bold mb-1">' . $label . '</h5>';
                echo in_array($dbCol, ['content'])
                    ? '<p class="text-content mb-3">' . $display . '</p>'
                    : '<p class="mb-3">' . $display . '</p>';
            endforeach;
            ?>

            <!-- 删除确认模态框 -->
            <div class="modal fade" id="delModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">确认删除</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            确定要删除「<?= htmlspecialchars($log['title']) ?>」？此操作不可恢复。
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <form method="post" class="d-inline">
                                <button type="submit" name="delete" class="btn btn-danger">删除</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 页脚 -->
    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018-2025 LYKNS 保留所有权利.</p>
    </footer>

</body>
</html>
