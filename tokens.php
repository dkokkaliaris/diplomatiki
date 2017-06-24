<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}

$alert = '';
if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    if($_SESSION['level']!=1){// αν δεν είναι διαχειριστής ελέγχω να δω αν έχει πρόσβαση στο ερωτηματολόγιο
        $params = array(':id' => $id, ':u_id'=>$_SESSION['userid']);
        $sql = 'SELECT COUNT(*) as count FROM dk_lessons WHERE id = :id AND user_id = :u_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $flag = $stmt->fetchObject();
        if($flag->count>0){//
            $continue = true;
        }
    }else $continue = true;
    if($continue){
        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_tokens WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $alert .= "<div class='alert alert-success'>Η διαγραφή των κωδικών token πραγματοποιήθηκε με επιτυχία.</div>";
    }else{
        $alert .= "<div class='alert alert-danger'>Δεν έχετε δικαίωμα να διαγράψετε τους συγκεκριμένους Κωδικούς Token</div>";
    }
}
if (isset($_GET['status']) && sanitize($_GET['status']) == 1) {
    $alert .= "<div class='alert alert-success'>Η προσθήκη της νέας σειράς κωδικών token πραγματοποιήθηκε με επιτυχία.</div>";
}
$addtosql = "";
$title = isset($_REQUEST['title']) ? filter_var($_REQUEST['title'], FILTER_SANITIZE_STRING) : '';

if (!empty($title)) {
    $addtosql .= " AND title LIKE '%$title%'";
}
// φέρνω όλα τα tokens ερωτηματολογίων
if ($_SESSION['level'] == 3){
$params = array(':id' => $_SESSION['userid']);
    $sql = "SELECT title,questionnaire_id FROM dk_questionnaire join dk_tokens on dk_questionnaire.id = dk_tokens.questionnaire_id where dk_tokens.user_id = :id $addtosql group by dk_tokens.questionnaire_id,title";
}else{
    $params = array();
    $sql = "SELECT title,questionnaire_id FROM dk_questionnaire join dk_tokens on dk_questionnaire.id = dk_tokens.questionnaire_id where 1 $addtosql group by dk_tokens.questionnaire_id,title";
}
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();

get_header();
$breadcrumb=array(
    array('title'=>'Διαχείριση Κωδικών Token','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <h3>Διαχείριση Κωδικών Token
            <a class="btn btn-primary btn-sm pull-right" href="generate_tokens.php">Δημιουργία Κωδικών Token</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <form action="tokens.php" method="get">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Εκπαιδευτικό Πρόγραμμα</th>
                            <th>Σειρά Κωδικών Token</th>
                            <th>Ενέργειες</th>
                        </tr>
                        <tr>
                            <td><input type="text" class="form-control" placeholder="Εκπαιδευτικό Πρόγραμμα" name="title" id="title" value="'.$title.'"/></td>
                            <td></td>
                            <td>
                                <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                            </td>

                        </tr>
                    </thead>
                    <tbody>';
                    foreach ($results as $result) {
                        echo '<tr>
                            <td>'.$result->title.'</td>
                            <th scope="row">
                                <div class="col-sm-2">
                                    <a data-toggle="tooltip" data-placement="bottom" title="Προβολή" target="_blank" href="generate_pdf.php?id='.$result->questionnaire_id.'"><i class="fa fa-file-pdf-o" style="color: red;" aria-hidden="true"></i></a>
                                </div>
                            </th>
                            <td><a data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '.$result->title.'" onclick="return confirm(\'Διαγραφή\')" href="tokens.php?action=delete&id='.$result->questionnaire_id.'" class="btn btn-sm btn-danger"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>
                        </tr>';
                    }
                    echo '</tbody>
                </table>
            </form>
        </div>
    </div>
</div>';

get_footer();
?>
