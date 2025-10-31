<?php
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

/* ===== 获取存在的年份 ===== */
$yearStmt = $conn->prepare("SELECT DISTINCT YEAR(log_date) AS y FROM logs ORDER BY y DESC");
$yearStmt->execute();
$existYears = array_column($yearStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'y');
$yearStmt->close();

/* ===== 获取筛选参数 ===== */
$year = intval($_GET['year'] ?? 0);   // 0 表示全部年份
$month = intval($_GET['month'] ?? 0); // 0 表示全部月份

/* ===== 获取该年份下存在的月份 ===== */
$existMonths = [];
if ($year > 0) {
    $monthStmt = $conn->prepare("SELECT DISTINCT MONTH(log_date) AS m FROM logs WHERE YEAR(log_date) = ? ORDER BY m DESC");
    $monthStmt->bind_param('i', $year);
    $monthStmt->execute();
    $existMonths = array_column($monthStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'm');
    $monthStmt->close();
} else {
    // 选择全部年份时，月份显示 1~12
    $existMonths = range(1, 12);
}

/* ===== 分页参数 ===== */
$limit = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* ===== 构建 WHERE 条件 ===== */
$where = [];
$params = [];
$types = '';

if ($year > 0) {
    $where[] = "YEAR(log_date) = ?";
    $params[] = $year;
    $types .= 'i';
}
if ($month > 0) {
    $where[] = "MONTH(log_date) = ?";
    $params[] = $month;
    $types .= 'i';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* ===== 查询总数 ===== */
$countSql = "SELECT COUNT(*) FROM logs $whereSql";
$countStmt = $conn->prepare($countSql);
if ($params) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_row()[0];
$countStmt->close();

$totalPages = max(1, ceil($total / $limit));

/* ===== 查询当前页数据 ===== */
$sql = "SELECT id, log_date, title FROM logs $whereSql ORDER BY log_date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>日志 - LYKNS ArchivesCenter</title>
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
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .table tbody tr {
            transition: background-color 0.2s;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .log-date {
            white-space: nowrap;
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        .stats-text {
            font-size: 0.9rem;
            color: #6c757d;
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
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/people.php">人物</a></li>
            <li class="nav-item"><a class="nav-link active" href="https://www.contoso.com/logs.php">日志</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/settings.php">设置</a></li>
            <li class="nav-item"><a class="nav-link" href="https://www.contoso.com/logout.php">注销</a></li>
        </ul>
    </nav>

    <div class="flex-fill">
        <div class="container-fluid">
            <div class="main-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>日志</h2>
                    <a href="/logs/create.php" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16" style="margin-right: 5px;">
                            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
                        </svg>
                        新建日志
                    </a>
                </div>

                <?php if (empty($existYears)): ?>
                    <div class="alert alert-info">
                        <strong>暂无日志数据</strong>
                        <p class="mb-0 mt-2">开始创建您的第一条日志吧！</p>
                    </div>
                <?php else: ?>
                    <!-- 筛选器 -->
                    <div class="filter-section">
                        <form method="get" class="row g-2 align-items-center">
                            <div class="col-auto">
                                <label class="col-form-label fw-bold">筛选：</label>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="year" onchange="this.form.submit()">
                                    <option value="0" <?= $year == 0 ? 'selected' : '' ?>>全部年份</option>
                                    <?php foreach ($existYears as $y): ?>
                                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?> 年</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="month" onchange="this.form.submit()">
                                    <option value="0" <?= $month == 0 ? 'selected' : '' ?>>全部月份</option>
                                    <?php if ($year > 0 && !empty($existMonths)): ?>
                                        <?php foreach ($existMonths as $m): ?>
                                            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= $m ?> 月</option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= $m ?> 月</option>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <?php if ($year > 0 || $month > 0): ?>
                                <div class="col-auto">
                                    <a href="/logs.php" class="btn btn-outline-secondary btn-sm">清除筛选</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- 统计信息 -->
                    <p class="stats-text mb-3">
                        找到 <strong><?= $total ?></strong> 条日志
                        <?php if ($year > 0 || $month > 0): ?>
                            <span class="text-muted">
                                （筛选条件：
                                <?php if ($year > 0): ?><?= $year ?> 年<?php endif; ?>
                                <?php if ($month > 0): ?><?= $month ?> 月<?php endif; ?>
                                ）
                            </span>
                        <?php endif; ?>
                    </p>

                    <?php if ($total == 0): ?>
                        <div class="alert alert-warning">
                            没有符合筛选条件的日志，请调整筛选条件或 <a href="/logs.php">查看全部</a>。
                        </div>
                    <?php else: ?>
                        <!-- 日志列表 -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 150px">日期</th>
                                        <th>标题</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td class="log-date">
                                                <a href="/logs/detail.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['log_date'], ENT_QUOTES, 'UTF-8') ?></a>
                                            </td>
                                            <td>
                                                <a href="/logs/detail.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 分页 -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="日志分页" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?year=<?= $year ?>&month=<?= $month ?>&page=<?= $page - 1 ?>">上一页</a>
                                    </li>
                                    
                                    <?php
                                    // 智能分页显示
                                    $range = 2; // 当前页前后显示的页数
                                    $start = max(1, $page - $range);
                                    $end = min($totalPages, $page + $range);
                                    
                                    if ($start > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?year=<?= $year ?>&month=<?= $month ?>&page=1">1</a></li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?year=<?= $year ?>&month=<?= $month ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end < $totalPages): ?>
                                        <?php if ($end < $totalPages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item"><a class="page-link" href="?year=<?= $year ?>&month=<?= $month ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                                    <?php endif; ?>
                                    
                                    <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?year=<?= $year ?>&month=<?= $month ?>&page=<?= $page + 1 ?>">下一页</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
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