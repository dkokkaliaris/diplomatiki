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
$pdf->AddPage();
//Τέλος ετοιμος κώδικας από TCPDF
$html = "";

$id = $_GET['id'];
// φέρνω όλα τα ερωτηματολόγια
$params = array(':id' => $id);
$stmt = $dbh->prepare('SELECT * FROM dk_tokens JOIN dk_questionnaire ON dk_tokens.questionnaire_id=dk_questionnaire.id where dk_tokens.questionnaire_id = :id ;');
$stmt->execute($params);
$results = $stmt->fetchALL();

$params = array(':id' => $id);
$stmt = $dbh->prepare('SELECT * FROM dk_questionnaire where id = :id ;');
$stmt->execute($params);
$questionnaire = $stmt->fetchObject();

//find professor
$params = array(':id' => $questionnaire->user_id);
$stmt = $dbh->prepare('SELECT dk_users.first_name,dk_users.last_name FROM dk_users where id = :id ;');
$stmt->execute($params);
$professor = $stmt->fetchObject();

//find lesson
$params = array(':id' => $questionnaire->lesson_id);
$stmt = $dbh->prepare('SELECT dk_lessons.title FROM dk_lessons where id = :id ;');
$stmt->execute($params);
$lesson = $stmt->fetchObject();

$row1 = 'Διδάσκων: '.$professor->first_name.' '.$professor->last_name.' | Συμπληρώστε τον <strong>Αρ.Μητρώου</strong> Φοιτητή:____________________<br />
        Εκπαιδευτικό Πρόγραμμα:  '.$lesson->title.'<br />
        Ισχύει από ['.$questionnaire->time_begins.'] εώς ['.$questionnaire->time_ends.']<br />
        <span style="font-size: 10px;">'.BASE_URL.'evaluate_questionnaire.php?id='.$id.'&token=';
$row2 = '</span><br />
        <strong>ΠΡΟΣΟΧΗ:</strong> Πριν το χρησιμοποιήσετε να αποσυνδεθείτε από το σύστημα.';
$html .= 'Ερωτηματολόγιο: <strong>' . $questionnaire->title . '</strong><br /><br/><br/><br/>';

$html .= '<table style="font-size: 8px;">';
$i = 0;
foreach ($results as $result) {$i++;
    $html .= '<tr>';
    $html .= '<td style="border-bottom:1px solid #000;">'.$row1.$result->seira . $result->token_code .$row2.'
                <h3 style="line-height:1">Σειριακός Αριθμός: ' . $result->seira . $result->token_code . '</h3>
                <br />
            </td>';
    $html .= '</tr>';
    $html .= '<br/>';
}

$html .= '</table>';

//Αρχή Ετοιμος κώδικας από TCPDF
//$pdf->writeHTML($html, true, false, true, false, '');
$pdf->writeHTMLCell(300, 0, 3, 5, $html);
$pdf->Output('Print_Tokens.pdf', 'I');
//Τέλος ετοιμος κώδικας από TCPDF