<?php
include_once "includes/init.php";
$token = $_POST['token'];

$start = substr($token, 0, 6);
$end = substr($token, 6);

$params = array(':start' => $start, ':end'=>$end );
$sql = 'SELECT * FROM dk_tokens where seira = :start and token_code = :end and from_date < NOW() and to_date > NOW();';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$tokenResult = $stmt->fetchObject();

$questionnaireID = $tokenResult->questionnaire_id;

$params = array(':id' => $questionnaireID );
$sql = "SELECT * FROM dk_questionnaire where id = :id;";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$questionnaire = $stmt->fetchObject();

if ($questionnaire != null && $tokenResult->used == 0)
    header("Location: /questionnaire/evaluate_questionnaire.php?id=$questionnaire->id&token=$token");
else header("Location: /questionnaire/login.php");
exit();