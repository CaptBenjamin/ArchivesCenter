<?php
/* people/edit.php 编辑人物 - 完整修正版 */
$config = require __DIR__ . '/../config.php'; // 假设 config.php 返回配置数组
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
}

/* 数据库连接 */
$conn = new mysqli($config['db_host'], $config['db_username'], $config['db_password'], $config['db_name']);
if ($conn->connect_error) {
    die('连接失败：' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

/* 读取历史可选值（datalist 用） */
$hist = [];
$cols = ['身份证件类型','籍贯','政治面貌','小学','初中','高中','大学'];
foreach ($cols as $c) {
    $hist[$c] = [];
    $safeCol = $conn->real_escape_string($c);
    $res = $conn->query("SELECT DISTINCT `$safeCol` FROM `people` WHERE `$safeCol` IS NOT NULL ORDER BY `$safeCol`");
    if ($res) {
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        if ($rows) {
            $hist[$c] = array_column($rows, $c);
        }
        $res->free();
    }
}

/* 读取单条人物 */
$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM `people` WHERE `id` = ? LIMIT 1");
if (!$stmt) {
    $conn->close();
    die('SQL 预处理错误：' . $conn->error);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$person = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$person) {
    $conn->close();
    header('Location: /people.php');
    exit;
}

/* 处理更新 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'put') {
    $fields = [
        '姓名','性别','身份证件类型','身份证件号码','出生日期','籍贯','政治面貌',
        '婚姻状况','住址','家庭状况','结识地点','相对身份','小学','初中','高中',
        '大学','研究生学校','就职单位','电话号码','电子邮件地址','微信ID','QQ号',
        '其他联系方式','备注'
    ];

    $binds = [];
    $types = '';
    foreach ($fields as $f) {
        // 注意：表单 name 使用中文字段名，直接读取
        $val = $_POST[$f] ?? null;
        if ($val === '') $val = null; // 空字符串视为 NULL
        $binds[] = $val;
        $types .= 's';
    }

    /* 修正：字段名前后都加反引号，确保 SQL 语法正确 */
    $set = '`' . implode('` = ?, `', $fields) . '` = ?';
    $sql = "UPDATE `people` SET $set WHERE `id` = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // 调试时可开启 error_log($sql); 记录最终 SQL
        $conn->close();
        die('SQL 预处理错误：' . $conn->error);
    }

    $types .= 'i';      // 最后一个参数是 id（整数）
    $binds[] = $id;

    // bind_param 不能接受 null 值类型信息单独传递，但传 null 会被正确绑定
    // 使用展开运算符直接绑定
    $stmt->bind_param($types, ...$binds);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: /people/detail.php?id=$id&saved=1");
        exit;
    } else {
        $msg = '<div class="alert alert-danger mt-3">更新失败：' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8') . '</div>';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>编辑人物 - LYKNS ArchivesCenter</title>
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
        /* 全宽主内容区 */
        .main-container {
            width: 100%;
            padding: 40px 20px;
        }
        textarea { white-space: pre-wrap; word-break: break-word; }
    </style>
</head>

<body class="d-flex flex-column">

    <nav class="navbar navbar-expand-sm bg-primary navbar-dark">
        <a class="navbar-brand" href="https://www.contoso.com/">
            <img src="https://www.contoso.com/logo.svg" alt="logo" style="width:40px;margin:0 0 0 10px;">
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

    <div class="flex-fill container-fluid main-container mb-3">
        <div class="container-fluid">
            <h2>编辑人物</h2>
            <?php if (isset($msg)) echo $msg; ?>

            <form method="post" autocomplete="off">
                <input type="hidden" name="_method" value="put">

                <!-- 1 姓名 -->
                <div class="mb-3">
                    <label class="form-label">姓名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="姓名"
                           value="<?= htmlspecialchars($person['姓名'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <!-- 2 性别 -->
                <div class="mb-3">
                    <label class="form-label">性别 <span class="text-danger">*</span></label>
                    <select class="form-select" name="性别" required>
                        <option value="男" <?= (isset($person['性别']) && $person['性别']=='男')?'selected':'' ?>>男</option>
                        <option value="女" <?= (isset($person['性别']) && $person['性别']=='女')?'selected':'' ?>>女</option>
                    </select>
                </div>

                <!-- 3 身份证件类型 -->
                <div class="mb-3">
                    <label class="form-label">身份证件类型</label>
                    <input list="list-身份证件类型" class="form-control" name="身份证件类型"
                           value="<?= htmlspecialchars($person['身份证件类型'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <datalist id="list-身份证件类型">
                        <option value="居民身份证"><option value="护照"><option value="通行证">
                        <?php foreach ($hist['身份证件类型'] as $v) echo '<option value="'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'">'; ?>
                    </datalist>
                </div>

                <!-- 4 身份证件号码 -->
                <div class="mb-3">
                    <label class="form-label">身份证件号码</label>
                    <input type="text" class="form-control" name="身份证件号码" maxlength="50"
                           value="<?= htmlspecialchars($person['身份证件号码'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 5 出生日期 -->
                <div class="mb-3">
                    <label class="form-label">出生日期</label>
                    <input type="date" class="form-control" name="出生日期"
                           value="<?= htmlspecialchars($person['出生日期'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 6 籍贯 -->
                <div class="mb-3">
                    <label class="form-label">籍贯</label>
                    <input list="list-籍贯" class="form-control" name="籍贯"
                           value="<?= htmlspecialchars($person['籍贯'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <datalist id="list-籍贯">
                        <?php foreach ($hist['籍贯'] as $v) echo '<option value="'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'">'; ?>
                    </datalist>
                </div>

                <!-- 7 政治面貌 -->
                <div class="mb-3">
                    <label class="form-label">政治面貌</label>
                    <input list="list-政治面貌" class="form-control" name="政治面貌"
                           value="<?= htmlspecialchars($person['政治面貌'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <datalist id="list-政治面貌">
                        <?php
                        $defaultPm = ['中共党员','中共预备党员','共青团员','民革党员','民盟盟员','民建会员','民进会员','农工党党员','致公党党员','九三学社社员','台盟盟员','无党派人士','群众'];
                        $merged = array_unique(array_merge($defaultPm, $hist['政治面貌']));
                        foreach ($merged as $v) echo '<option value="'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'">';
                        ?>
                    </datalist>
                </div>

                <!-- 8 婚姻状况 -->
                <div class="mb-3">
                    <label class="form-label">婚姻状况</label>
                    <select class="form-select" name="婚姻状况">
                        <option value=""></option>
                        <option value="未婚" <?= (isset($person['婚姻状况']) && $person['婚姻状况']=='未婚')?'selected':'' ?>>未婚</option>
                        <option value="已婚" <?= (isset($person['婚姻状况']) && $person['婚姻状况']=='已婚')?'selected':'' ?>>已婚</option>
                        <option value="初婚" <?= (isset($person['婚姻状况']) && $person['婚姻状况']=='初婚')?'selected':'' ?>>初婚</option>
                        <option value="再婚" <?= (isset($person['婚姻状况']) && $person['婚姻状况']=='再婚')?'selected':'' ?>>再婚</option>
                        <option value="丧偶" <?= (isset($person['婚姻状况']) && $person['婚姻状况']=='丧偶')?'selected':'' ?>>丧偶</option>
                        <option value="离婚" <?= (isset($person['婚姻状况']) && $person['婚姻状况']=='离婚')?'selected':'' ?>>离婚</option>
                    </select>
                </div>

                <!-- 9 住址 -->
                <div class="mb-3">
                    <label class="form-label">住址</label>
                    <textarea class="form-control" rows="2" name="住址"><?= htmlspecialchars($person['住址'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- 10 家庭状况 -->
                <div class="mb-3">
                    <label class="form-label">家庭状况</label>
                    <textarea class="form-control" rows="2" name="家庭状况"><?= htmlspecialchars($person['家庭状况'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- 11 结识地点 必填 -->
                <div class="mb-3">
                    <label class="form-label">结识地点 <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="2" name="结识地点" required><?= htmlspecialchars($person['结识地点'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- 12 相对身份 必填 -->
                <div class="mb-3">
                    <label class="form-label">相对身份 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="相对身份"
                           value="<?= htmlspecialchars($person['相对身份'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <!-- 13-16 各阶段学校 -->
                <?php foreach (['小学','初中','高中','大学'] as $s): ?>
                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?></label>
                    <input list="list-<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" class="form-control" name="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($person[$s] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <datalist id="list-<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($hist[$s] as $v) echo '<option value="'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'">'; ?>
                    </datalist>
                </div>
                <?php endforeach; ?>

                <!-- 17 研究生学校 -->
                <div class="mb-3">
                    <label class="form-label">研究生学校</label>
                    <input type="text" class="form-control" name="研究生学校" value="<?= htmlspecialchars($person['研究生学校'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 18 就职单位 -->
                <div class="mb-3">
                    <label class="form-label">就职单位</label>
                    <input type="text" class="form-control" name="就职单位" value="<?= htmlspecialchars($person['就职单位'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 19 电话号码 -->
                <div class="mb-3">
                    <label class="form-label">电话号码</label>
                    <input type="text" class="form-control" name="电话号码" pattern="[0-9]*" maxlength="20" value="<?= htmlspecialchars($person['电话号码'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 20 电子邮件地址 -->
                <div class="mb-3">
                    <label class="form-label">电子邮件地址</label>
                    <input type="email" class="form-control" name="电子邮件地址" value="<?= htmlspecialchars($person['电子邮件地址'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 21 微信ID -->
                <div class="mb-3">
                    <label class="form-label">微信ID</label>
                    <input type="text" class="form-control" name="微信ID" value="<?= htmlspecialchars($person['微信ID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 22 QQ号 -->
                <div class="mb-3">
                    <label class="form-label">QQ号</label>
                    <input type="text" class="form-control" name="QQ号" pattern="[0-9]*" maxlength="20" value="<?= htmlspecialchars($person['QQ号'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- 23 其他联系方式 -->
                <div class="mb-3">
                    <label class="form-label">其他联系方式</label>
                    <textarea class="form-control" rows="2" name="其他联系方式"><?= htmlspecialchars($person['其他联系方式'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <!-- 24 备注 -->
                <div class="mb-3">
                    <label class="form-label">备注</label>
                    <textarea class="form-control" rows="2" name="备注"><?= htmlspecialchars($person['备注'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">保存修改</button>
                <a href="/people/detail.php?id=<?= $id ?>" class="btn btn-secondary ms-2">返回详情</a>
            </form>
        </div>
    </div>

    <footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
        <p class="mb-0 text-body-secondary" style="margin: 0 0 0 10px;">&copy; 2018-2025 LYKNS 保留所有权利.</p>
    </footer>

</body>
</html>
