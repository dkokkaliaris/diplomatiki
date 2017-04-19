<?php
include_once "includes/init.php";
get_header();
if (!$_SESSION['userid']) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $stmt = $dbh->prepare('DELETE FROM dk_tokens WHERE id = :id');
    $params = array(':id' => $id);
    $stmt->execute($params);
}


// φέρνω όλα τα tokens ερωτηματολογίων
$stmt = $dbh->prepare("SELECT * FROM dk_questionnaire join dk_tokens on dk_questionnaire.id = dk_tokens.questionnaire_id where dk_tokens.user_id = " . $_SESSION['userid'] . " group by dk_tokens.questionnaire_id;");
$stmt->execute();

$results = $stmt->fetchALL();

echo '<div class="container-fluid">
    <div class="row breadcrumb">
        <div class="col-sm-12">
        <a href="index.php">Αρχική Σελίδα</a> &gt; Διαχείριση Κωδικών Token
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <h3>Διαχείριση Κωδικών Token
            <a class="btn btn-primary btn-sm pull-right" href="generate_tokens.php">Δημιουργία Κωδικών Token</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Τίτλος</th>
                    <th>Δημιουργία pdf</th>
                </tr>
                </thead>
                <tbody>';
                foreach ($results as $result) {
                    echo '<tr>
                        <td>'.$result->title.'</td>
                        <th scope="row">
                            <div class="col-sm-2">
                                <a target="_blank" href="generate_pdf.php?id='.$result->questionnaire_id.'"><i class="fa fa-file-pdf-o" style="color: red;" aria-hidden="true"></i></a>
                            </div>
                        </th>
                    </tr>';
                }
                echo '</tbody>
            </table>
        </div>
    </div>
</div>';

get_footer();
?>
