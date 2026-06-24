<?php
session_start();
session_unset();
session_destroy();
header("Location: auth_login.php");
exit;
?>
