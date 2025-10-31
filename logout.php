<?php
// 启动会话
session_start();

// 销毁所有会话变量
session_unset();

// 销毁会话
session_destroy();

// 重定向到登录页面
header("Location: login.php");
exit();
?>