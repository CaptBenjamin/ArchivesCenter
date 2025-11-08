<?php
/* search.php 综合搜索 - 优化版 */
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

/* ===== 搜索关键词 ===== */
$keyword = trim($_GET['keyword'] ?? '');
$hasSearched = !empty($keyword);

$people = [];
$logs = [];
$peopleCount = 0;
$logsCount = 0;

if ($hasSearched) {
    $searchPattern = "%$keyword%";
    
    /* ===== 搜索人物 ===== */
    $peopleStmt = $conn->prepare(
        "SELECT id, 姓名, 性别, 出生日期, 相对身份 
         FROM people 
         WHERE 姓名 LIKE ? 
            OR 身份证件号码 LIKE ? 
            OR 电话号码 LIKE ?
            OR 相对身份 LIKE ?
         ORDER BY 姓名 
         LIMIT 50"
    );
    $peopleStmt->bind_param('ssss', $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $peopleStmt->execute();
    $peopleResult = $peopleStmt->get_result();
    $people = $peopleResult->fetch_all(MYSQLI_ASSOC);
    $peopleCount = count($people);
    $peopleStmt->close();
    
    /* ===== 搜索日志 ===== */
    $logsStmt = $conn->prepare(
        "SELECT id, log_date, title 
         FROM logs 
         WHERE title LIKE ? OR content LIKE ? 
         ORDER BY log_date DESC 
         LIMIT 50"
    );
    $logsStmt->bind_param('ss', $searchPattern, $searchPattern);
    $logsStmt->execute();
    $logsResult = $logsStmt->get_result();
    $logs = $logsResult->fetch_all(MYSQLI_ASSOC);
    $logsCount = count($logs);
    $logsStmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>综合搜索 - ArchivesCenter</title>
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
        .search-box {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .search-input {
            font-size: 1.1rem;
        }
        .result-section {
            margin-bottom: 30px;
        }
        .result-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0d6efd;
        }
        .result-header h4 {
            margin: 0;
            color: #0d6efd;
        }
        .result-count {
            margin-left: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .result-item {
            padding: 15px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .result-item:hover {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }
        .result-item a {
            text-decoration: none;
            color: #212529;
            font-weight: 500;
        }
        .result-item a:hover {
            color: #0d6efd;
        }
        .result-meta {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state svg {
            width: 80px;
            height: 80px;
            opacity: 0.3;
            margin-bottom: 20px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
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

                <li class="nav-item"><a class="nav-link" href="/">欢迎</a></li>
                <li class="nav-item"><a class="nav-link active" href="/search.php">综合搜索</a></li>
                <li class="nav-item"><a class="nav-link" href="/people.php">人物</a></li>
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
                <h2 class="mb-4">综合搜索</h2>

                <!-- 搜索框 -->
                <div class="search-box">
                    <form method="get" autocomplete="off">
                        <div class="input-group input-group-lg">
                            <input type="text" 
                                   class="form-control search-input" 
                                   name="keyword" 
                                   placeholder="搜索人物姓名、身份证号、电话、相对身份或日志标题、内容..." 
                                   value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                                   autofocus>
                            <button class="btn btn-primary" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                                </svg>
                                搜索
                            </button>
                        </div>
                    </form>
                    <?php if ($hasSearched): ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                搜索 "<strong><?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?></strong>" 
                                找到 <strong><?= $peopleCount + $logsCount ?></strong> 条结果
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$hasSearched): ?>
                    <!-- 未搜索状态 -->
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                        </svg>
                        <h5 class="text-muted">输入关键词开始搜索</h5>
                        <p class="text-muted">可以搜索人物姓名、身份证号、电话号码、相对身份或日志内容</p>
                    </div>
                <?php elseif ($peopleCount == 0 && $logsCount == 0): ?>
                    <!-- 无结果状态 -->
                    <div class="alert alert-info">
                        <h5 class="alert-heading">未找到相关结果</h5>
                        <p class="mb-0">没有找到与 "<strong><?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?></strong>" 相关的人物或日志，请尝试其他关键词。</p>
                    </div>
                    <div class="mt-3">
                        <h6>搜索建议：</h6>
                        <ul class="text-muted">
                            <li>尝试使用更简短的关键词</li>
                            <li>检查关键词是否输入正确</li>
                            <li>尝试搜索部分姓名或标题</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- 搜索结果 -->
                    <?php if ($peopleCount > 0): ?>
                        <div class="result-section">
                            <div class="result-header">
                                <h4>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16" style="margin-right: 8px;">
                                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                                    </svg>
                                    人物
                                </h4>
                                <span class="result-count"><?= $peopleCount ?> 条结果</span>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($people as $person): ?>
                                    <div class="result-item list-group-item">
                                        <a href="/people/detail.php?id=<?= $person['id'] ?>">
                                            <?= htmlspecialchars($person['姓名'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <div class="result-meta">
                                            <?php if (!empty($person['性别'])): ?>
                                                <span><?= htmlspecialchars($person['性别'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($person['出生日期'])): ?>
                                                <span class="ms-2">出生日期：<?= htmlspecialchars($person['出生日期'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($person['相对身份'])): ?>
                                                <span class="ms-2">相对身份：<?= htmlspecialchars($person['相对身份'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($peopleCount >= 50): ?>
                                <div class="mt-2">
                                    <small class="text-muted">仅显示前 50 条结果，请使用更精确的关键词缩小范围。</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($logsCount > 0): ?>
                        <div class="result-section">
                            <div class="result-header">
                                <h4>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-journal-text" viewBox="0 0 16 16" style="margin-right: 8px;">
                                        <path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                                        <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2"/>
                                        <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z"/>
                                    </svg>
                                    日志
                                </h4>
                                <span class="result-count"><?= $logsCount ?> 条结果</span>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($logs as $log): ?>
                                    <div class="result-item list-group-item">
                                        <a href="/logs/detail.php?id=<?= $log['id'] ?>">
                                            <?= htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <div class="result-meta">
                                            <span><?= htmlspecialchars($log['log_date'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($logsCount >= 50): ?>
                                <div class="mt-2">
                                    <small class="text-muted">仅显示前 50 条结果，请使用更精确的关键词缩小范围。</small>
                                </div>
                            <?php endif; ?>
                        </div>
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