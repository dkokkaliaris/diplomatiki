<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once dirname(__FILE__) ."/config.php";
require_once dirname(__FILE__) ."/functions.php";
require_once dirname(__FILE__) ."/tcpdf/tcpdf.php";

date_default_timezone_set('Europe/Athens');
?>
