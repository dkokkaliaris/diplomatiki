<?php
include_once "includes/init.php";
include_once("includes/tcpdf/tcpdf.php");

//Αρχή Ετοιμος κώδικας από TCPDF
ob_end_clean();
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetFont('dejavusans', '', 10);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->AddPage();
$id = $_GET['id'];

$params = array(':id' => $id);
$sql = 'SELECT title, lesson_id, user_id, time_begins, time_ends FROM dk_questionnaire WHERE id = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchObject();

$pdf->SetFont('dejavusans', '', 12);
$pdf->Cell(0, 5, 'Ερωτηματολόγιο: '.$result->title, 0, 1, 'C');//τιτλος
$pdf->Ln(4);
//Τέλος ετοιμος κώδικας από TCPDF
//$html = "";
$params = array(':id' => $result->lesson_id);//μαθημα
$sql = 'SELECT title, department_id FROM dk_lessons WHERE id = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$lesson = $stmt->fetchObject();
$pdf->Cell(0, 5, 'Περιγραφή: '.$lesson->title, 0, 1, 'C');
$pdf->Ln(4);

$params = array(':id' => $result->user_id);//διδάσκων
$sql = 'SELECT first_name, last_name FROM dk_users WHERE id = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$user = $stmt->fetchObject();
$pdf->Cell(0, 5, 'Διδάσκων: '.$user->first_name. ' '.$user->last_name , 0, 1, 'C');
$pdf->Ln(2);
$params = array(':id' => $lesson->department_id);//Τμήμα
$sql = 'SELECT name FROM dk_departments WHERE id = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$department = $stmt->fetchObject();
$pdf->Cell(0, 5, 'Τμήμα: '.$department->name , 0, 1, 'C');
$pdf->Ln(2);

//$pdf->Cell(0, 5, date("d/m/Y H:i", strtotime($result->time_begins)). ' - '. date("d/m/Y H:i", strtotime($result->time_ends)) , 0, 1, 'C');//ημερομηνία
$pdf->Cell(0, 5, 'Ημερομηνία Εκτύπωσης: '.date("m/Y") , 0, 1, 'C');//ημερομηνία
$pdf->Ln(10);

$pdf->SetFont('dejavusans', '', 10);

// φέρνω όλα τα ερωτηματολόγια
$params = array(':id' => $id);
$sql = 'SELECT dk_question.* FROM dk_question INNER JOIN dk_questionnaire_questions ON dk_questionnaire_questions.question_id=dk_question.id WHERE dk_questionnaire_questions.questionnaire_id = :id ORDER BY order_by';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$result_questions = $stmt->fetchALL();
$total_questions = $stmt->rowCount();

$pos_i = 0;
foreach ($result_questions as $q) {$pos_i++;
    $pdf->Cell('', 5, $pos_i.'. '.$q->question);
    $pdf->Ln(6);
    if($q->type=='radio'||$q->type=='check'){
        $params = array(':id' => $q->id);
        $sql = 'SELECT dk_question_options.pick FROM dk_question_options INNER JOIN dk_question ON dk_question.id=dk_question_options.question_id WHERE dk_question.id = :id ';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $options = $stmt->fetchALL();
        //$x = 0;
        foreach ($options as $op) {
            /*if($x!=0){
                $pdf->Cell(100, 5, '');
            }*/
            if($q->type=='radio'){
                $pdf->RadioButton('id_'.$q->ID, 5,  array('readonly' => 'true'), array(), $op->pick);
            }else{
                $pdf->CheckBox('id_'.$q->ID, 5, false,  array('readonly' => 'true'), array(), $op->pick);
            }
            $pdf->Cell(20, 5, $op->pick);
            //$pdf->Ln(6);
            //$x++;
        }
    }else{
        $pdf->TextField('id_'.$q->ID, '', '', array('multiline'=>true,'readonly' => 'true'), array());
        $pdf->Ln(10);
    }
    $pdf->Ln(10);
}


//Αρχή Ετοιμος κώδικας από TCPDF
//$pdf->writeHTML($html, true, false, true, false, '');
//$pdf->writeHTMLCell(300, 0, 3, 5, $html);
$pdf->Output('Questionnaire.pdf', 'I');
//Τέλος ετοιμος κώδικας από TCPDF