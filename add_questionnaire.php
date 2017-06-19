<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
$alert = '';
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // ελέγχω αν ο χρήστης έστειλε κενά πεδία
    if (!isset($_POST['title'], $_POST['description'], $_POST['time_begins'], $_POST['time_ends'], $_POST['lesson']) || ($_POST['title'] == '' || $_POST['description'] == '' || $_POST['time_begins'] == '' || $_POST['time_ends'] == '' || $_POST['lesson'] == '')) {
        $alert = "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div></div></div>";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $date_begins = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $_POST['time_begins'])));
        $date_ends = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $_POST['time_ends'])));
        $lesson = sanitize($_POST['lesson']);

        // Δημιουργούμε το ερωτηματολόγιο
        $params = array(':title' => $title, ':description' => $description, ':time_begins' => $date_begins, ':time_ends' => $date_ends, ':template' => 0, ':user_id' => $_SESSION['userid'], ':lesson_id' => $lesson, ':last_edit_time' => date('Y-m-d H:i:s'), ':last_editor' => $_SESSION['userid']);
        $sql = 'INSERT INTO dk_questionnaire (title, description, time_begins, time_ends, template, user_id, lesson_id, last_edit_time, last_editor) VALUES (:title, :description, :time_begins, :time_ends, :template, :user_id, :lesson_id, :last_edit_time, :last_editor)';
        $stmt = $dbh->prepare($sql);

        $stmt->execute($params);

        // Η βάση μας γνωστοποιεί το ID του ερωτηματολογίου που μόλις δημιούργησε και το αποθηκευύομε.
        $new_id = $dbh->lastInsertId();
        if ($new_id > 0) {
            // Εφόσον δημιουργηθεί το ερωτηματολόγιο βάζω τα κανάλια στον πίνακα (σύνδεση 1 προς πολλά)
            foreach ($_POST['channel'] as $channel) {
                $params = array(':id_questionnaire' => $new_id, ':id_channel' => $channel);
                $sql = 'INSERT INTO dk_questionnaire_channel (id_questionnaire, id_channel) VALUES (:id_questionnaire, :id_channel)';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
            }
            header("Location: edit_questionnaire.php?id=$new_id&status=1");
            exit;
        } else {
            $alert = "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Η δημιουργία του ερωτηματολογίου δεν πραγματοποιήθηκε με επιτυχία. Παρακαλούμε δοκιμάστε ξανά.</div></div></div>";
        }
    }
}

get_header();
$breadcrumb=array(
    array('title'=>'Ερωτηματολόγια','href'=>'questionnaires.php'),
    array('title'=>'Προσθήκη Νέου Ερωτηματολογίου','href'=>''),
);
echo '<div class="container-fluid">
'.show_breacrumb($breadcrumb);

echo $alert;
echo '<div class="row">
        <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
            <div class="box">
            <div class="row">
                <div class="col-sm-12">
                    <h4>Προσθήκη Νέου Ερωτηματολογίου</h4>
                </div>
            </div>
            <form action="add_questionnaire.php" method="post" id="add_questionnaire_form" novalidate="">
                <div class="form-group">
                    <label class="form-control-label" for="title">Τίτλος: </label>
                    <input type="text" class="form-control" name="title" id="title" required=""/>
                </div>

                <div class="form-group">
                    <label for="lesson" class="form-control-label">Εκπαιδευτικό Πρόγραμμα: </label>
                    <select name="lesson" id="lesson"
                            class="form-control type" style="width: auto;" required="">
                        <option value="">Επιλογή Εκπαιδευτικού Προγράμματος</option>';
                        $params = array();
                        if ($_SESSION['level'] == 3){
                            $params= array(':id' => $_SESSION['userid']);
                            $sql = 'SELECT * FROM dk_lessons where user_id = :id';
                        }else
                            // αν ειμαι διαχειριστής ή ΟΜΕΑ φέρνω όλα τα μαθήματα με το όνομα του διδάσκοντα
                            $sql = 'SELECT dk_lessons.id,dk_lessons.title,dk_users.first_name,dk_users.last_name FROM dk_lessons JOIN dk_users ON dk_users.id=dk_lessons.user_id';

                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);

                        $results = $stmt->fetchALL();
                        foreach ($results as $result) {
                            echo "<option value='$result->id'>$result->title".
                                (!empty($result->last_name)?' ('.$result->first_name .' '. $result->last_name.')':'').
                                "</option>";
                        }
                    echo '
                    </select>
                </div>';
                echo '
                <div class="form-group">
                    <label for="description" class="form-control-label">Συνοπτική Περιγραφή: </label>
                    <textarea rows="5" class="form-control" name="description" id="description"></textarea>
                </div>

                <div class="form-group" id="date_start_layout">
                    <label for="time_begins" class="form-control-label">Ημερομηνία Έναρξης: </label>
                    <input type="text" class="form-control" name="time_begins" id="time_begins" autocomplete="off" required=""/>
                </div>

                <div class="form-group" id="date_ends_layout">
                    <label for="time_ends" class="form-control-label">Ημερομηνία Λήξης: </label>
                    <input type="text" class="form-control" name="time_ends" id="time_ends" autocomplete="off" required=""/>
                </div>';


                // Φέρουμε την λίστα με τα κανάλια
                $sql = 'SELECT * FROM dk_channel';
                $stmt = $dbh->prepare($sql);
                $stmt->execute();
                $results = $stmt->fetchAll();
                $total = $stmt->rowCount();
                if ($total > 0) {
                    echo '<div class="form-group">
                        <label for="date_ends" class="form-control-label">Επιλογή Καναλιών Εισόδου:</label><br/>';

                        foreach ($results as $result) {
                            echo "
                            <label for='channel_$result->id'><input type='checkbox' name='channel[]' value='$result->id' id='channel_$result->id'/>$result->title
                            </label><br/>";
                        }
                    echo '</div>';
                }
                echo '<button class="btn btn-primary btn-sm full-width" type="submit">Δημιουργία Ερωτηματολογίου</button>
            </form>
            </div>
        </div>
    </div>
</div>';
?>
<script>
//καλει το datepicker και εφαρμοζει το πεδίο της ημερομηνιας
    jQuery('#time_begins').datetimepicker({
        minDate: new Date(),
        lang: 'el',
        timepicker: true,
        format: 'd/m/Y H:i',
        onSelectDate: function( ct ){
            //οταν επιλεξεις ημερομηνια πηγαινε στην ημερομηνια ληξης και βαλε ελεχιστη ημερομηνια αυτη που επιλέχθηκε και κανει έλεγχο
            jQuery('#time_ends').datetimepicker({minDate: new Date(ct)});
        },
        onSelectTime: function( ct ){
            jQuery('#time_ends').datetimepicker({minDate: new Date(ct)});
        }
    });
    jQuery('#time_ends').datetimepicker({
        minDate: new Date(),
        lang: 'el',
        timepicker: true,
        format: 'd/m/Y H:i',
        onSelectDate: function( ct ){
            jQuery('#time_begins').datetimepicker({maxDate: new Date(ct)});
        },
        onSelectTime: function( ct ){
            jQuery('#time_begins').datetimepicker({maxDate: new Date(ct)});
        }

    });
    jQuery(document).ready(function () {
        jQuery('#add_questionnaire_form').validate();
    });
</script>

<?php
get_footer();
?>