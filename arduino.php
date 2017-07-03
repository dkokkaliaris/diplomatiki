<?php
include_once "includes/init.php";

//παιρνω το arduino id και το κουμπι που πατηθηκε
// https://zafora.icte.uowm.gr/~ictest00516/arduino.php?arduinoid=25&button=1

$arduino_id = sanitize($_REQUEST['arduinoid']);
$button = (int)sanitize($_REQUEST['button']) - 1;//ο πίνακας ξεκινάει από 0 και το button από 1 άρα κάνουμε αφαίρεση

//ελεγχω αν η συσκευη υπάρχει και π
$params = array(':id' => $arduino_id);
$sql = 'SELECT * FROM dk_arduino WHERE arduino_id = :id';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$arduino = $stmt->fetchObject();

//εφοσον το arduino δεν ειναι αδειο
if(!empty($arduino)){ // ελέγχω αν εχει απαντησει καποια ερωτηση στο προηγουμενο λεπτο.
    $params = array(':id' => $arduino->question_id);
    $sql = 'SELECT A.questionnaire_id, A.question_id, B.pick AS answer, C.type FROM dk_questionnaire_questions AS A JOIN dk_question_options AS B ON A.question_id = B.question_id JOIN dk_question AS C ON A.question_id = C.id WHERE A.id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll();

    $before = $arduino->last_active;
    $today = date("Y-m-d H:i:s");

    $diff =  strtotime($today) - strtotime($before);

    if ($diff > 60 ) {
        $params = array(':questionnaire_id' => $result[$button]->questionnaire_id, ':question_id' => $result[$button]->question_id, ':answer' => $result[$button]->answer, ':time' => date('Y-m-d H:i:s'), ':type' => $result[$button]->type);
        //καταχωρεί την απαντηση στην βαση.
        $sql = 'INSERT INTO dk_answers (questionnaire_id, question_id, answer, user_id, time, type, filename, hashname) VALUES (:questionnaire_id, :question_id, :answer, NULL, :time, :type, NULL, NULL)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

// αποθηκευει την διευθυνση του arduino
        if(empty($arduino->ip)){
            $ip = $_SERVER['REMOTE_ADDR'];
            $params = array(':last_active' => date('Y-m-d H:i:s'), ':ip' => $ip, ':arduino_id' => $arduino_id);
            $sql = 'UPDATE dk_arduino SET last_active = :last_active, ip = :ip where arduino_id = :arduino_id ;';
        }else{
            $params = array(':last_active' => date('Y-m-d H:i:s'), ':arduino_id' => $arduino_id);
            $sql = 'UPDATE dk_arduino SET last_active = :last_active where arduino_id = :arduino_id ;';
        }
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
    }
}