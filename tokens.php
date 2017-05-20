<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();

if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $params = array(':id' => $id);
    $sql = 'DELETE FROM dk_tokens WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
}


// φέρνω όλα τα tokens ερωτηματολογίων
$params = array(':id' => $_SESSION['userid']);
$sql = 'SELECT * FROM dk_questionnaire join dk_tokens on dk_questionnaire.id = dk_tokens.questionnaire_id where dk_tokens.user_id = :id group by dk_tokens.questionnaire_id;';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();
$breadcrumb=array(
    array('title'=>'Διαχείριση Κωδικών Token','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
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
                    <th>Εκπαιδευτικό Πρόγραμμα</th>
                    <th>Σειρά Κωδικών Token</th>
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
