<?php
/* people/create.php 创建人物 → 成功跳转到 people.php */
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

/* 读取历史可选值（datalist 用） */
$hist = [];
$cols = ['身份证件类型','籍贯','政治面貌','小学','初中','高中','大学'];
foreach ($cols as $c) {
    $res = $conn->query("SELECT DISTINCT `$c` FROM people WHERE `$c` IS NOT NULL ORDER BY `$c`");
    $hist[$c] = array_column($res->fetch_all(MYSQLI_ASSOC), $c);
}

/* 处理提交 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        '姓名','性别','身份证件类型','身份证件号码','出生日期','籍贯','政治面貌',
        '婚姻状况','住址','家庭状况','结识地点','相对身份','小学','初中','高中',
        '大学','研究生学校','就职单位','电话号码','电子邮件地址','微信ID','QQ号',
        '其他联系方式','备注'
    ];
    $binds  = []; $types = '';
    foreach ($fields as $f) {
        $val = $_POST[$f] ?? null;
        if ($val === '') $val = null;
        $binds[] = $val;
        $types  .= 's';                  // 统一字符串绑定
    }

    $sql = "INSERT INTO people (`".implode('`,`',$fields)."`) 
            VALUES (".implode(',',array_fill(0,count($fields),'?')).")";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$binds);
    if ($stmt->execute()) {
        $stmt->close(); $conn->close();
        header('Location: /people.php');
        exit;
    } else {
        $msg = '<div class="alert alert-danger mt-3">创建失败：' . $stmt->error . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>创建人物 - ArchivesCenter</title>
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
            padding: 40px 20px; /* 留3%的左右间距以防太贴边 */
        }
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
    <h2>创建新人物</h2>
    <form method="post" autocomplete="off">
        <!-- 1 姓名 -->
        <div class="mb-3">
            <label class="form-label">姓名 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="姓名" required>
        </div>

        <!-- 2 性别 -->
        <div class="mb-3">
            <label class="form-label">性别 <span class="text-danger">*</span></label>
            <select class="form-select" name="性别" required>
                <option value="男">男</option>
                <option value="女">女</option>
            </select>
        </div>

        <!-- 3 身份证件类型 -->
        <div class="mb-3">
            <label class="form-label">身份证件类型</label>
            <input list="list-身份证件类型" class="form-control" name="身份证件类型">
            <datalist id="list-身份证件类型">
                <option value="居民身份证"><option value="护照"><option value="通行证">
                <?php foreach ($hist['身份证件类型'] as $v) echo "<option value='$v'>"; ?>
            </datalist>
        </div>

        <!-- 4 身份证件号码 -->
        <div class="mb-3">
            <label class="form-label">身份证件号码</label>
            <input type="text" class="form-control" name="身份证件号码" maxlength="50">
        </div>

        <!-- 5 出生日期 -->
        <div class="mb-3">
            <label class="form-label">出生日期</label>
            <input type="date" class="form-control" name="出生日期">
        </div>

        <!-- 6 籍贯 -->
        <div class="mb-3">
            <label class="form-label">籍贯</label>
            <input list="list-籍贯" class="form-control" name="籍贯">
            <datalist id="list-籍贯">
                <?php foreach ($hist['籍贯'] as $v) echo "<option value='$v'>"; ?>
            </datalist>
        </div>

        <!-- 7 政治面貌 -->
        <div class="mb-3">
            <label class="form-label">政治面貌</label>
            <input list="list-政治面貌" class="form-control" name="政治面貌">
            <datalist id="list-政治面貌">
                <?php
                $defaultPm = ['中共党员','中共预备党员','共青团员','民革党员','民盟盟员','民建会员','民进会员','农工党党员','致公党党员','九三学社社员','台盟盟员','无党派人士','群众'];
                foreach (array_unique(array_merge($defaultPm, $hist['政治面貌'])) as $v) echo "<option value='$v'>";
                ?>
            </datalist>
        </div>

        <!-- 8 婚姻状况 -->
        <div class="mb-3">
            <label class="form-label">婚姻状况</label>
            <select class="form-select" name="婚姻状况">
                <option value=""></option>
                <option>未婚</option><option>已婚</option><option>初婚</option>
                <option>再婚</option><option>丧偶</option><option>离婚</option>
            </select>
        </div>

        <!-- 9 住址 -->
        <div class="mb-3"><label class="form-label">住址</label><textarea class="form-control" rows="2" name="住址"></textarea></div>

        <!-- 10 家庭状况 -->
        <div class="mb-3"><label class="form-label">家庭状况</label><textarea class="form-control" rows="2" name="家庭状况"></textarea></div>

        <!-- 11 结识地点 -->
        <div class="mb-3"><label class="form-label">结识地点 <span class="text-danger">*</span></label><textarea class="form-control" rows="2" name="结识地点" required></textarea></div>

        <!-- 12 相对身份 -->
        <div class="mb-3"><label class="form-label">相对身份 <span class="text-danger">*</span></label><input type="text" class="form-control" name="相对身份" required></div>

        <!-- 13-16 各阶段学校 -->
        <?php foreach (['小学','初中','高中','大学'] as $s): ?>
        <div class="mb-3">
            <label class="form-label"><?= $s ?></label>
            <input list="list-<?= $s ?>" class="form-control" name="<?= $s ?>">
            <datalist id="list-<?= $s ?>">
                <?php foreach ($hist[$s] as $v) echo "<option value='$v'>"; ?>
            </datalist>
        </div>
        <?php endforeach; ?>

        <!-- 17 研究生学校 -->
        <div class="mb-3"><label class="form-label">研究生学校</label><input type="text" class="form-control" name="研究生学校"></div>

        <!-- 18 就职单位 -->
        <div class="mb-3"><label class="form-label">就职单位</label><input type="text" class="form-control" name="就职单位"></div>

        <!-- 19 电话号码 -->
        <div class="mb-3"><label class="form-label">电话号码</label><input type="text" class="form-control" name="电话号码" pattern="[0-9]*" maxlength="20"></div>

        <!-- 20 电子邮件地址 -->
        <div class="mb-3"><label class="form-label">电子邮件地址</label><input type="email" class="form-control" name="电子邮件地址"></div>

        <!-- 21 微信ID -->
        <div class="mb-3"><label class="form-label">微信ID</label><input type="text" class="form-control" name="微信ID"></div>

        <!-- 22 QQ号 -->
        <div class="mb-3"><label class="form-label">QQ号</label><input type="text" class="form-control" name="QQ号" pattern="[0-9]*" maxlength="20"></div>

        <!-- 23 其他联系方式 -->
        <div class="mb-3"><label class="form-label">其他联系方式</label><textarea class="form-control" rows="2" name="其他联系方式"></textarea></div>

        <!-- 24 备注 -->
        <div class="mb-3"><label class="form-label">备注</label><textarea class="form-control" rows="2" name="备注"></textarea></div>

        <button type="submit" class="btn btn-primary">保存人物</button>
        <?php if(isset($msg)) echo $msg; ?>
    </form>
    </div>
</div>

<footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
    <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018-2025 LYKNS 保留所有权利.</p>
</footer>
</body>
</html>