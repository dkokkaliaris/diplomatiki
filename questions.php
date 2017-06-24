<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();
?>
<?php
if ($_SERVER['REQUEST_METHOD']=="GET") {
    if(sanitize($_GET['action'])=="delete"){
        $id = sanitize($_GET['id']);
        $stmt = $dbh->prepare('DELETE FROM dk_questionnaire_channel WHERE id_questionnaire = :id');
        $params=array(':id'=> $id);
        $stmt->execute($params);//διαγραφουμε πρωτα από τον πίνακα dk_questionnaire_channel, διότι υπάρχει foreign key
        $stmt = $dbh->prepare('DELETE FROM dk_questionnaire WHERE id = :id');
        $params=array(':id'=> $id);
        $stmt->execute($params);
    }
}
$breadcrumb=array(
    array('title'=>'Ερωτηματολόγια','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">
                <h3>Ερωτηματολόγια<a class="btn btn-primary btn-sm pull-right" href="add_question.php">Προσθήκη Νέου</a></h3>
            </div>
        </div>
        <div class="row">
        <div class="col-sm-12">
        <table class="table table-striped">
            <thead>
                <tr>
                  <th>ID</th>
                  <th>Σύντομος Τίτλος</th>
                  <th>Ηημερομηνία Έναρξης</th>
                  <th>Ηημερομηνία Λήξης</th>
                  <th>Κλειδωμένο</th>
                  <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>';
                $stmt = $dbh->prepare('SELECT * FROM dk_questionnaire');
                $stmt->execute();
                $results =$stmt->fetchALL();
                foreach($results as $result){
                    echo '<tr>
                      <th scope="row">'.$result->id.'</th>
                      <td>'.$result->title.'</td>
                      <td>'.date('d/m/Y H:i', strtotime($result->time_begins)).'</td>
                      <td>'.date('d/m/Y H:i', strtotime($result->time_ends)).'</td>
                      <td>'.($result->is_locked?"Ναι":"Όχι").'</td>
                      <td><a href="edit_question.php/?id='.$result->id.'" type="button" class="btn btn-sm btn-success"><span class="fa fa-pencil" aria-hidden="true"></span></a> <a onclick=\'return confirm("Διαγραφή")\' class="btn btn-sm btn-danger" href="questions.php?id='.$result->id.'&action=delete" type="button"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>
                    </tr>';
                }
            echo '</tbody>
        </table>
        </div>
    </div>
</div>';
get_footer();
?>
