<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
//Όταν θέλω να εμφανίσω ένα alert μετά το POST...
$alert = '';
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (!isset($_POST['title'], $_POST['description']) || ($_POST['title'] == '' || $_POST['description'] == '')) {
        $alert .= "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);

        // Δημιουργούμε το template
        $params = array(':title' => $title, ':description' => $description, ':template' => 1, ':user_id' => $_SESSION['userid'], ':last_edit_time' => date('Y-m-d H:i:s'), ':last_editor' => $_SESSION['userid']);
        $sql = 'INSERT INTO dk_questionnaire (title, description, template, user_id, last_edit_time, last_editor) VALUES (:title, :description, :template, :user_id, :last_edit_time, :last_editor)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        // Η βάση μας γνωστοποιεί το ID του ερωτηματολογίου που μόλις δημιούργησε και το αποθηκευύομε.
        $new_id = $dbh->lastInsertId();

        if ($new_id > 0) {
            header("Location: edit_template.php?id=$new_id&status=1");
            exit;
        } else {
            //Αποθηκευω τον html κωδικα σε μία μεταβλητή...
            $alert .= "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Η δημιουργία νέου πρότυπου ερωτηματολογίου δεν πραγματοποιήθηκε με επιτυχία. Παρακαλούμε δοκιμάστε ξανά.</div></div></div>";
        }
    }
}
get_header();
$breadcrumb=array(
    array('title'=>'Πρότυπα Ερωτηματολόγια','href'=>'templates.php'),
    array('title'=>'Προσθήκη Νέου Πρότυπου Ερωτηματολογίου','href'=>''),
);

echo '<div class="container-fluid">
'.show_breacrumb($breadcrumb).
' <!-- και εμφανίζω την μεταβλητη αν εχει τιμη -->
'.$alert.'
<div class="row">
            <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
                <div class="box">
                <div class="row">
                    <div class="col-sm-12">
                        <h4>Προσθήκη Νέου Πρότυπου Ερωτηματολογίου</h4>
                    </div>
                </div>
                <form action="add_template.php" method="post" id="add_template_form" novalidate="">
                    <div class="form-group">
                        <label class="form-control-label" for="title">Τίτλος: </label>
                        <input type="text" class="form-control" name="title" id="title" required=""/>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-control-label">Συνοπτική Περιγραφή: </label>
                        <textarea rows="5" class="form-control" name="description" id="description" required=""></textarea>
                    </div>';

                    // Φέρουμε την λίστα με τα κανάλια
                    $sql = 'SELECT * FROM dk_channel';
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                    $total = $stmt->rowCount();
                    echo '<button class="btn btn-primary btn-sm full-width" type="submit">Δημιουργία Ερωτηματολογίου</button>
                </form>
            </div>
            </div>
        </div>
    </div>';

get_footer();
?>