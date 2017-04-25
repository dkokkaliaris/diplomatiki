<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();
$breadcrumb=array(
    array('title'=>'Ερωτηματολόγια','href'=>'questionnaires.php'),
    array('title'=>'Προσθήκη Νέου Ερωτηματολογίου','href'=>''),
);
echo '<div class="container-fluid">
'.show_breacrumb($breadcrumb);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // ελέγχω αν ο χρήστης έστειλε κενά πεδία
    if (!isset($_POST['title'], $_POST['description'], $_POST['time_begins'], $_POST['time_ends'], $_POST['lesson']) || ($_POST['title'] == '' || $_POST['description'] == '' || $_POST['time_begins'] == '' || $_POST['time_ends'] == '' || $_POST['lesson'] == '')) {
        echo "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div></div></div>";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $date_begins = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $_POST['time_begins'])));
        $date_ends = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $_POST['time_ends'])));
        $lesson = sanitize($_POST['lesson']);

        // Δημιουργούμε το ερωτηματολόγιο
        $stmt = $dbh->prepare('INSERT INTO dk_questionnaire (title, description, time_begins, time_ends, template, user_id, lesson_id, last_edit_time, last_editor) VALUES (:title, :description, :time_begins, :time_ends, :template, :user_id, :lesson_id, :last_edit_time, :last_editor)');
        //$params = array(':title' => $title, ':description' => $description, ':time_begins' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_begins))), ':time_ends' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_ends))), ':template' => 0, ':user_id' => $_SESSION['userid'], ':lesson_id' => $lesson, ':last_edit_time' => date('Y-m-d H:i:s'), ':last_editor' => $_SESSION['userid']);
        $params = array(':title' => $title, ':description' => $description, ':time_begins' => $date_begins, ':time_ends' => $date_ends, ':template' => 0, ':user_id' => $_SESSION['userid'], ':lesson_id' => $lesson, ':last_edit_time' => date('Y-m-d H:i:s'), ':last_editor' => $_SESSION['userid']);
        $stmt->execute($params);

        // Η βάση μας γνωστοποιεί το ID του ερωτηματολογίου που μόλις δημιούργησε και το αποθηκευύομε.
        $new_id = $dbh->lastInsertId();
        if ($new_id > 0) {
            // Εφόσον δημιουργηθεί το ερωτηματολόγιο βάζω τα κανάλια στον πίνακα (σύνδεση 1 προς πολλά)
            foreach ($_POST['channel'] as $channel) {
                $stmt = $dbh->prepare('INSERT INTO dk_questionnaire_channel (id_questionnaire, id_channel) VALUES (:id_questionnaire, :id_channel)');
                $params = array(':id_questionnaire' => $new_id, ':id_channel' => $channel);
                $stmt->execute($params);
            }
            header("Location: edit_questionnaire.php?id=$new_id");
            exit;
        } else {
            echo "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Η εκχώρηση δεν πραγματοποιήθηκε. Δοκιμάστε ξανά.</div></div></div>";
        }
    }
}
echo '<div class="row">
        <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
            <div class="box">
            <div class="row">
                <div class="col-sm-12">
                    <h3>Προσθήκη Νέου Ερωτηματολογίου</h3>
                </div>
            </div>
            <form action="add_questionnaire.php" method="post">
                <div class="form-group">
                    <label class="form-control-label" for="title">Τίτλος: </label>
                    <input type="text" class="form-control" name="title" id="title"/>
                </div>

                <div class="form-group">
                    <label for="lesson" class="form-control-label">Μάθημα: </label>
                    <select name="lesson" id="lesson"
                            class="form-control type" style="width: auto;">
                        <option value="0">Επιλογή Μαθήματος</option>';

                        if ($_SESSION['level'] == 3)
                            $stmt = $dbh->prepare("SELECT * FROM dk_lessons where user_id = " . $_SESSION['userid']);
                        else
                            // αν ειμαι διαχειριστής ή ΟΜΕΑ φέρνω όλα τα μαθήματα με το όνομα του διδάσκοντα
                            $stmt = $dbh->prepare('SELECT dk_lessons.id,dk_lessons.title,dk_users.first_name,dk_users.last_name FROM dk_lessons JOIN dk_users ON dk_users.id=dk_lessons.user_id');
                        $stmt->execute();

                        $results = $stmt->fetchALL();
                        foreach ($results as $result) {
                            // echo "<option value=\"$result->id\">$result->title</option>";
                            echo "<option value='$result->id'>$result->title".
                                (!empty($result->last_name)?$result->first_name .' '. $result->last_name:'').
                                "</option>";
                        }
                    echo '
                    </select>
                </div>';
                echo '
                <div class="form-group">
                    <label for="description" class="form-control-label">Μικρή Περιγραφή: </label>
                    <textarea rows="5" class="form-control" name="description" id="description"></textarea>
                </div>

                <div class="form-group" id="date_start_layout">
                    <label for="time_begins" class="form-control-label">Ημερομηνία Έναρξης: </label>
                    <input type="text" class="form-control" name="time_begins" id="time_begins" autocomplete="off"/>
                </div>

                <div class="form-group" id="date_ends_layout">
                    <label for="time_ends" class="form-control-label">Ημερομηνία Λήξης: </label>
                    <input type="text" class="form-control" name="time_ends" id="time_ends" autocomplete="off"/>
                </div>';


                // Φέρουμε την λίστα με τα κανάλια
                $stmt = $dbh->prepare('SELECT * FROM dk_channel');
                $stmt->execute();
                $results = $stmt->fetchAll();
                $total = $stmt->rowCount();
                if ($total > 0) {
                    echo '<div class="form-group">
                        <label for="date_ends" class="form-control-label">Επιλογή Καναλιού </label><br/>';

                        foreach ($results as $result) {
                            echo "
                            <label for='channel_$result->id'><input type='checkbox' name='channel[]' value='$result->id' id='channel_$result->id'/>$result->title
                            </label><br/>";
                        }
                    echo '</div>';
                }
                echo '<button class="btn btn-primary btn-sm full-width" type="submit">Δημιουργία</button>
            </form>
            </div>
        </div>
    </div>
</div>';
?>
<script>
    jQuery('#time_begins').datetimepicker({
        lang: 'el',
        timepicker: true,
        format: 'd/m/Y H:i'
    });
    jQuery('#time_ends').datetimepicker({
        lang: 'el',
        timepicker: true,
        format: 'd/m/Y H:i'
    });
</script>

<?php
get_footer();
?>