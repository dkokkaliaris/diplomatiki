<?php
include_once "includes/init.php";
session_start();
session_destroy();
header("Location: ".BASE_URL."login.php");
exit();
?>