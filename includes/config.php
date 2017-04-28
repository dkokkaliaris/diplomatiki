<?php
$localhost=array('127.0.0.1','::1');
if(in_array($_SERVER['REMOTE_ADDR'], $localhost)){
	define('BASE_URL', 'http://localhost/questionnaire/');
	define('DBHOST', 'localhost');
	define('DBUSER', 'root');
	define('DBPASS', '');
	define('DBNAME', 'questionnaire');
}else if($_SERVER['HTTP_HOST']="www.dmkokkaliaris.gr"){
	define('BASE_URL', 'http://www.dmkokkaliaris.gr/questionnaire/');
	define('DBHOST', 'localhost');
	define('DBUSER', 'dmkokkal_sms');
	define('DBPASS', 'smssms');
	define('DBNAME', 'dmkokkal_sms');
}else{
	define('BASE_URL', 'http://zafora.icte.uowm.gr/~ictest00516/');
	define('DBHOST', '/zstorage/home/ictest00516/mysql/run/mysql.sock');
	define('DBUSER', 'user');
	define('DBPASS', 'user');
	define('DBNAME', 'dk_questionnaire');
}

$pdo_options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4");

try {
    $dbh = new PDO('mysql:host='.DBHOST.';dbname='.DBNAME, DBUSER, DBPASS, $pdo_options);
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>
