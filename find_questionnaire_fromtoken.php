<?php
include_once "includes/init.php";
$token = $_POST['token'];

$start = substr($token, 0, 6);
$end = substr($token, 6);

$stmt = $dbh->prepare("SELECT * FROM dk_tokens where seira = '$start' and token_code = '$end' and from_date < NOW() and to_date > NOW();");
$stmt->execute();
$tokenResult = $stmt->fetchObject();

$questionnaireID = $tokenResult->questionnaire_id;

$stmt = $dbh->prepare("SELECT * FROM dk_questionnaire where id = $questionnaireID;");
$stmt->execute();
$questionnaire = $stmt->fetchObject();

if ($questionnaire != null && $tokenResult->used == 0)
    header("Location: /questionnaire/evaluate_questionnaire.php?id=$questionnaire->id&token=$token");
else header("Location: /questionnaire/login.php");
exit();