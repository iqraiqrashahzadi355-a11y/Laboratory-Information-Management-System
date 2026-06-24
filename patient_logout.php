<?php
session_start();
session_unset();
session_destroy();
header("Location: /LIMS/login.php");
exit;
?>
