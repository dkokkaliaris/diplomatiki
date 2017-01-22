<?php
session_start();
session_destroy();
header("Location: /questionnaire/login.php");
exit();
?>