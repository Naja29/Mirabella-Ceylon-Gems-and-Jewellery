<?php
session_start();
session_unset();
session_destroy();
setcookie('mc_admin_remember', '', time() - 3600, '/');
header('Location: login.php');
exit;
