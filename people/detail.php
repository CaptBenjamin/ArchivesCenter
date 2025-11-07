<?php
/* people/detail.php 人物详情 - 空值显示“缺省” */
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

$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM people WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$person = $res->fetch_assoc();
if (!$person) {
    header('Location: /people.php');
    exit;
}

/* 处理删除 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $del = $conn->prepare("DELETE FROM people WHERE id = ? LIMIT 1");
    $del->bind_param('i', $id);
    $del->execute();
    $del->close();
    $conn->close();
    header('Location: /people.php?del=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>人物详情 - ArchivesCenter</title>
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
        /* 全宽主内容区 */
        .main-container {
            width: 100%;
            padding: 40px 20px;
        }
        .text-content{white-space:pre-wrap;word-break:break-word;}
    </style>
</head>

<body class="d-flex flex-column">
<nav class="navbar navbar-expand-sm bg-primary navbar-dark" style="position: fixed;width: 100%;">
    <a class="navbar-brand" href="https://www.contoso.com/">
        <img src="https://www.contoso.com/pinwheel-wf.svg" alt="logo" style="width:40px;margin:0 0 0 10px;">
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

<div class="flex-fill container-fluid main-container mb-3" style="margin: 50px 0 0 0">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>人物详情</h2>
            <div>
                <a href="/people/edit.php?id=<?= $person['id'] ?>" class="btn btn-warning me-2">编辑</a>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#delModal">删除</button>
            </div>
        </div>

        <!-- 内容堆叠：空值显示“缺省” -->
        <?php
        $fields = [
            '姓名','性别','身份证件类型','身份证件号码','出生日期','籍贯','政治面貌',
            '婚姻状况','住址','家庭状况','结识地点','相对身份','小学','初中','高中',
            '大学','研究生学校','就职单位','电话号码','电子邮件地址','微信ID','QQ号',
            '其他联系方式','备注','录入时间'
        ];
        foreach ($fields as $f) {
            $v = $person[$f] ?? '';
            // 空值显示“缺省”
            $display = $v === '' ? '<span class="text-muted">缺省</span>' : htmlspecialchars($v);
            // 长文本字段
            $isLong = in_array($f, ['住址','家庭状况','结识地点','其他联系方式','备注']);
            echo '<h5 class="fw-bold mb-1">'.$f.'</h5>';
            echo $isLong
                ? '<p class="text-content mb-3">'.$display.'</p>'
                : '<p class="mb-3">'.$display.'</p>';
        }
        ?>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal fade" id="delModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    确定要删除「<?= htmlspecialchars($person['姓名']) ?>」？此操作不可恢复。
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

<footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
    <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018-2025 LYKNS 保留所有权利.</p>
</footer>
</body>
</html>
