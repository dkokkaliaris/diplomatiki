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
$stmt = $dbh->prepare('SELECT * FROM dk_tokens where questionnaire_id = :id and used = 0;');
$params = array(':id' => $id);
$stmt->execute($params);

$results = $stmt->fetchALL();

$stmt = $dbh->prepare('SELECT * FROM dk_questionnaire where id = :id ;');
$params = array(':id' => $id);
$stmt->execute($params);
$questionnaire = $stmt->fetchObject();

$html .= 'Ερωτηματολόγιο: <strong>' . $questionnaire->title . '</strong>';

$html .= '<table>';
$html .= '<br/><br/><br/>';
$i = 0;
foreach ($results as $result) {
//    if ($i % 3 == 0) {
//        $html .= '<tr>';
//    }
//
//    $html .= '<td>' . $result->seira . $result->token_code . '</td>';
//
//    $i++;
//    if ($i == 3) {
//        $html .= '</tr>';
//        $html .= '<br/>';
//        $i = 0;
//    }
    $html .= '<tr>';
    $html .= '<td>' . $result->seira . $result->token_code . '</td>';
    $html .= '</tr>';
    $html .= '<br/>';
}

$html .= '</table>';


//Αρχή Ετοιμος κώδικας από TCPDF
//$pdf->writeHTML($html, true, false, true, false, '');
$pdf->writeHTMLCell(300, 0, 3, 5, $html);
$pdf->Output('Print_Tokens.pdf', 'I');
//Τέλος ετοιμος κώδικας από TCPDF