<?php
/* people.php 人物列表 - 优化版 */
require_once __DIR__ . '/config.php';
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
}

$config = require __DIR__ . '/config.php';
$conn = new mysqli(
    $config['db_host'],
    $config['db_username'],
    $config['db_password'],
    $config['db_name']
);

if ($conn->connect_error) {
    die('连接失败：' . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8'));
}

$conn->set_charset('utf8mb4');

/* ===== 分页参数 ===== */
$limit = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ===== 查询总数 ===== */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM people");
$countStmt->execute();
$total = $countStmt->get_result()->fetch_row()[0];
$totalPages = max(1, ceil($total / $limit));
$countStmt->close();

/* ===== 定义字段列表 ===== */
$fields = [
    'id', '姓名', '性别', '身份证件类型', '身份证件号码', '出生日期', '籍贯', '政治面貌',
    '婚姻状况', '住址', '家庭状况', '结识地点', '相对身份', '小学', '初中', '高中', '大学',
    '研究生学校', '就职单位', '电话号码', '电子邮件地址', '微信ID', 'QQ号', '其他联系方式', 
    '备注', '录入时间'
];

$fieldList = '`' . implode('`, `', $fields) . '`';

/* ===== 查询当前页数据 ===== */
$sql = "SELECT $fieldList FROM people ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

/* ===== 定义长文本字段（需要截断的） ===== */
$longTextFields = ['住址', '家庭状况', '结识地点', '其他联系方式', '备注'];
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>人物 - LYKNS ArchivesCenter</title>
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
            max-width: 100%;
            padding: 40px 20px;
        }
        .table-container {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .table-scroll {
            overflow-x: auto;
            white-space: nowrap;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
            white-space: nowrap;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody tr {
            transition: background-color 0.2s;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        th.sticky-col, td.sticky-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 5;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        th.sticky-col {
            z-index: 15;
            background: #f8f9fa;
        }
        .table tbody tr:hover td.sticky-col {
            background-color: #f8f9fa;
        }
        .stats-text {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .truncate {
            display: inline-block;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
        }
    </style>
</head>

<body>
<div class="d-flex flex-column">
    <nav class="navbar navbar-expand-sm bg-primary navbar-dark">
        <a class="navbar-brand" href="https://www.contoso.com/">
            <img src="https://www.contoso.com/logo.svg" alt="logo" style="width:40px;margin: 0 0 0 10px;">
            LYKNS ArchivesCenter
        </a>
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/">欢迎</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/search.php">综合搜索</a></li>
            <li class="nav-item"><a class="nav-link active" href="https://www.contoso.com/people.php">人物</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logs.php">日志</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/settings.php">设置</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logout.php">注销</a></li>
        </ul>
    </nav>

    <div class="flex-fill">
        <div class="container-fluid">
            <div class="main-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>人物</h2>
                    <a href="/people/create.php" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16" style="margin-right: 5px;">
                            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                        </svg>
                        创建新人物
                    </a>
                </div>

                <?php if ($total == 0): ?>
                    <div class="alert alert-info">
                        <strong>暂无人物数据</strong>
                        <p class="mb-0 mt-2">开始创建您的第一个人物档案吧！</p>
                    </div>
                <?php else: ?>
                    <!-- 统计信息 -->
                    <p class="stats-text mb-3">
                        共有 <strong><?= $total ?></strong> 条人物记录，当前显示第 <strong><?= $page ?></strong> 页，共 <strong><?= $totalPages ?></strong> 页
                    </p>

                    <!-- 表格容器 -->
                    <div class="table-container">
                        <div class="table-scroll">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="sticky-col">姓名</th>
                                        <?php foreach (array_slice($fields, 2) as $field): ?>
                                            <th><?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td class="sticky-col">
                                                <a href="/people/detail.php?id=<?= $row['id'] ?>" class="fw-bold text-decoration-none">
                                                    <?= htmlspecialchars($row['姓名'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </td>
                                            <?php foreach (array_slice($fields, 2) as $field): 
                                                $value = $row[$field] ?? '';
                                                $displayValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                                                
                                                // 长文本字段截断显示
                                                if (in_array($field, $longTextFields) && mb_strlen($value) > 30) {
                                                    echo '<td><span class="truncate" title="' . $displayValue . '">' . $displayValue . '</span></td>';
                                                } else {
                                                    echo '<td>' . ($displayValue ?: '<span class="text-muted">—</span>') . '</td>';
                                                }
                                            endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="人物分页" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">上一页</a>
                                </li>
                                
                                <?php
                                // 智能分页显示
                                $range = 2;
                                $start = max(1, $page - $range);
                                $end = min($totalPages, $page + $range);
                                
                                if ($start > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($end < $totalPages): ?>
                                    <?php if ($end < $totalPages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                                <?php endif; ?>
                                
                                <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">下一页</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                    <!-- 操作提示 -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <strong>💡 提示：</strong>表格可以左右滚动查看更多字段，点击姓名可查看详细信息。
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018-2025 LYKNS 保留所有权利.</p>
    </footer>
</div>
</body>
</html>