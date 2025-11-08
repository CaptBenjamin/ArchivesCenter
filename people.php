<?php
/* people.php 人物列表 - 完整增强版：分页 + 每页显示 + 可排序 */
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

/* ===== 每页显示设置 ===== */
$defaultLimit = 50;
if (isset($_GET['limit'])) {
    $limit = $_GET['limit'] === 'all' ? 'all' : intval($_GET['limit']);
    $_SESSION['limit'] = $limit;
} elseif (isset($_SESSION['limit'])) {
    $limit = $_SESSION['limit'];
} else {
    $limit = $defaultLimit;
}

/* ===== 排序参数 ===== */
$allowedSortFields = [
    '姓名', '性别', '出生日期', '籍贯', '政治面貌', '婚姻状况', '录入时间', '就职单位'
];
$sort = $_GET['sort'] ?? 'id';
$order = strtoupper($_GET['order'] ?? 'DESC');
if (!in_array($sort, array_merge(['id'], $allowedSortFields))) $sort = 'id';
if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

/* ===== 分页参数 ===== */
$page = max(1, intval($_GET['page'] ?? 1));

/* ===== 查询总数 ===== */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM people");
$countStmt->execute();
$total = $countStmt->get_result()->fetch_row()[0];
$countStmt->close();

/* ===== 计算分页 ===== */
if ($limit === 'all') {
    $totalPages = 1;
    $offset = 0;
    $queryLimit = $total;
} else {
    $limit = max(1, $limit);
    $totalPages = max(1, ceil($total / $limit));
    $offset = ($page - 1) * $limit;
    $queryLimit = $limit;
}

/* ===== 字段列表 ===== */
$fields = [
    'id', '姓名', '性别', '身份证件类型', '身份证件号码', '出生日期', '籍贯', '政治面貌',
    '婚姻状况', '住址', '家庭状况', '结识地点', '相对身份', '小学', '初中', '高中', '大学',
    '研究生学校', '就职单位', '电话号码', '电子邮件地址', '微信ID', 'QQ号', '其他联系方式', 
    '备注', '录入时间'
];

$fieldList = '`' . implode('`, `', $fields) . '`';

/* ===== 查询数据 ===== */
$orderBy = "`$sort` $order";
if ($limit === 'all') {
    $sql = "SELECT $fieldList FROM people ORDER BY $orderBy";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT $fieldList FROM people ORDER BY $orderBy LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $queryLimit, $offset);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

/* ===== 长文本字段（截断显示） ===== */
$longTextFields = ['住址', '家庭状况', '结识地点', '其他联系方式', '备注'];

/* ===== 辅助函数：生成排序链接 ===== */
function sortLink($field, $currentSort, $currentOrder, $limit, $page) {
    $icon = '';
    if ($currentSort === $field) {
        $icon = $currentOrder === 'ASC' ? '▲' : '▼';
    }
    $newOrder = ($currentSort === $field && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    $params = http_build_query([
        'sort' => $field,
        'order' => $newOrder,
        'limit' => $limit,
        'page' => $page
    ]);
    return "<a href=\"?$params\" class=\"text-decoration-none\">"
         . htmlspecialchars($field, ENT_QUOTES, 'UTF-8')
         . " <small>$icon</small></a>";
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>人物 - ArchivesCenter</title>
    <link href="/bootstrap.min.css" rel="stylesheet">
    <script src="/bootstrap.bundle.min.js"></script>
    <link rel="icon" href="/pinwheel.ico" type="image/x-icon">
    <style>
        body { background: #fff; }
        .d-flex.flex-column { min-height: 100vh; }
        .flex-fill { flex: 1; }
        .main-container { max-width: 100%; padding: 40px 20px; }
        .table-container { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table-scroll { overflow-x: auto; white-space: nowrap; }
        .table { margin-bottom: 0; }
        .table thead th {
            top: 0; background: #f8f9fa; z-index: 10;
            white-space: nowrap; font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody tr { transition: background-color 0.2s; }
        .table tbody tr:hover { background-color: #f8f9fa; }
        th.sticky-col, td.sticky-col {
            left: 0; background: #fff; z-index: 5;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        th.sticky-col { z-index: 15; background: #f8f9fa; }
        .table tbody tr:hover td.sticky-col { background-color: #f8f9fa; }
        .stats-text { font-size: 0.9rem; color: #6c757d; }
        .truncate {
            display: inline-block; max-width: 200px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: bottom;
        }
        th a { color: inherit; }
        th a:hover { text-decoration: underline; }
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

                <li class="nav-item"><a class="nav-link" href="/">欢迎</a></li>
                <li class="nav-item"><a class="nav-link" href="/search.php">综合搜索</a></li>
                <li class="nav-item"><a class="nav-link active" href="/people.php">人物</a></li>
                <li class="nav-item"><a class="nav-link" href="/logs.php">日志</a></li>
                <li class="nav-item"><a class="nav-link" href="/settings.php">设置</a></li>
                <li class="nav-item"><a class="nav-link" href="/logout.php">注销</a></li>

            </ul>
        </div>
    </div>
    </nav>

    <div class="flex-fill" style="margin: 50px 0 0 0">
        <div class="container-fluid">
            <div class="main-container">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <h2 class="mb-2 mb-sm-0">人物</h2>
                    <div class="d-flex align-items-center">
                        <form method="get" class="me-2">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                            <label for="limit" class="me-1 text-muted">每页显示：</label>
                            <select name="limit" id="limit" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                <?php
                                $options = [25, 50, 100, 200, 'all'];
                                foreach ($options as $opt) {
                                    $text = $opt === 'all' ? '全部' : "$opt 条";
                                    $selected = ($opt == $limit) ? 'selected' : '';
                                    echo "<option value='$opt' $selected>$text</option>";
                                }
                                ?>
                            </select>
                        </form>
                        <a href="/people/create.php" class="btn btn-primary">＋ 创建人物</a>
                    </div>
                </div>

                <?php if ($total == 0): ?>
                    <div class="alert alert-info"><strong>暂无人物数据</strong></div>
                <?php else: ?>
                    <p class="stats-text mb-3">
                        共 <strong><?= $total ?></strong> 条记录，
                        当前显示 <strong><?= $limit === 'all' ? '全部' : $limit ?></strong> 条，
                        第 <strong><?= $page ?></strong> / <strong><?= $totalPages ?></strong> 页
                    </p>

                    <div class="table-container">
                        <div class="table-scroll">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="sticky-col"><?= sortLink('姓名', $sort, $order, $limit, $page) ?></th>
                                        <?php foreach (array_slice($fields, 2) as $field): ?>
                                            <th><?= in_array($field, $allowedSortFields) ? sortLink($field, $sort, $order, $limit, $page) : htmlspecialchars($field) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td class="sticky-col">
                                                <a href="/people/detail.php?id=<?= $row['id'] ?>" class="fw-bold text-decoration-none">
                                                    <?= htmlspecialchars($row['姓名']) ?>
                                                </a>
                                            </td>
                                            <?php foreach (array_slice($fields, 2) as $field): 
                                                $value = $row[$field] ?? '';
                                                $displayValue = htmlspecialchars($value);
                                                if (in_array($field, $longTextFields) && mb_strlen($value) > 30) {
                                                    echo "<td><span class='truncate' title='$displayValue'>$displayValue</span></td>";
                                                } else {
                                                    echo "<td>" . ($displayValue ?: "<span class='text-muted'>—</span>") . "</td>";
                                                }
                                            endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($totalPages > 1 && $limit !== 'all'): ?>
                        <nav aria-label="分页" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                $base = "sort=$sort&order=$order&limit=$limit";
                                ?>
                                <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= $base ?>&page=<?= $page-1 ?>">上一页</a>
                                </li>
                                <?php
                                $range = 2;
                                $start = max(1, $page - $range);
                                $end = min($totalPages, $page + $range);
                                if ($start > 1) {
                                    echo "<li class='page-item'><a class='page-link' href='?$base&page=1'>1</a></li>";
                                    if ($start > 2) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                                }
                                for ($i = $start; $i <= $end; $i++) {
                                    $active = $i == $page ? 'active' : '';
                                    echo "<li class='page-item $active'><a class='page-link' href='?$base&page=$i'>$i</a></li>";
                                }
                                if ($end < $totalPages) {
                                    if ($end < $totalPages - 1)
                                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                                    echo "<li class='page-item'><a class='page-link' href='?$base&page=$totalPages'>$totalPages</a></li>";
                                }
                                ?>
                                <li class="page-item <?= $page == $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= $base ?>&page=<?= $page+1 ?>">下一页</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018–2025 LYKNS 保留所有权利.</p>
    </footer>
</div>
</body>
</html>
