<?php
include_once "includes/init.php";
get_header();
?>
<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // ελέγχω αν ο χρήστης έστειλε κενά πεδία
    if (!isset($_POST['title'], $_POST['description'], $_POST['time_begins'], $_POST['time_ends'], $_POST['lesson']) || ($_POST['title'] == '' || $_POST['description'] == '' || $_POST['time_begins'] == '' || $_POST['time_ends'] == '' || $_POST['lesson'] == '')) {
        echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $date_begins = sanitize($_POST['time_begins']);
        $date_ends = sanitize($_POST['time_ends']);
        $lesson = sanitize($_POST['lesson']);

        // Δημιουργούμε το ερωτηματολόγιο
        $stmt = $dbh->prepare('INSERT INTO dk_questionnaire (title, description, time_begins, time_ends, template, user_id, lesson_id, last_edit_time, last_editor) VALUES (:title, :description, :time_begins, :time_ends, :template, :user_id, :lesson_id, :last_edit_time, :last_editor)');
        $params = array(':title' => $title, ':description' => $description, ':time_begins' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_begins))), ':time_ends' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_ends))), ':template' => 0, ':user_id' => $_SESSION['userid'], ':lesson_id' => $lesson, ':last_edit_time' => date('Y-m-d H:i:s'), ':last_editor' => $_SESSION['userid']);
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
            echo "<div class='alert alert-danger'>Η εκχώρηση δεν πραγματοποιήθηκε. Δοκιμάστε ξανά.</div>";
        }
    }
}
?>
<div class="container">
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-8">
                <h2>Προσθήκη Νέου Ερωτηματολογίου</h2>
            </div>
        </div>
        <hr/>
        <div class="row">
            <form action="add_questionnaire.php" method="post">
                <div class="form-group">
                    <label class="form-control-label" for="title">Τίτλος: </label>
                    <input type="text" class="form-control" name="title" id="title"/>
                </div>

                <div class="form-group">
                    <label for="lesson" class="form-control-label">Μάθημα: </label>
                    <select name="lesson" id="lesson"
                            class="form-control type" style="width: auto;">
                        <option value="0">Επιλογή Μαθήματος</option>

                        <?php
                        if ($_SESSION['level'] == 3)
                            $stmt = $dbh->prepare("SELECT * FROM dk_lessons where user_id = " . $_SESSION['userid']);
                        else
                            // αν ειμαι διαχειριστής ή ΟΜΕΑ φέρνω όλα τα μαθήματα με το όνομα του διδάσκοντα
                            $stmt = $dbh->prepare('SELECT dk_lessons.id,dk_lessons.title,dk_users.first_name,dk_users.last_name FROM dk_lessons JOIN dk_users ON dk_users.id=dk_lessons.id');
                        $stmt->execute();

                        $results = $stmt->fetchALL();
                        foreach ($results as $result) {
                            // echo "<option value=\"$result->id\">$result->title</option>";
                            ?>
                            <option value="<?php echo $result->id; ?>"><?php echo $result->title; ?>
                            <?php if (1) { ?>
                                (<?php echo $result->first_name; echo $result->last_name; ?>)
                            <?php } ?></option>
                        <?php } ?>
                    </select>
                </div>

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
                </div>

                <?php
                // Φέρουμε την λίστα με τα κανάλια
                $stmt = $dbh->prepare('SELECT * FROM dk_channel');
                $stmt->execute();
                $results = $stmt->fetchAll();
                $total = $stmt->rowCount();
                if ($total > 0) {
                    ?>

                    <div class="form-group">
                        <label for="date_ends" class="form-control-label">Επιλογή Καναλιού </label><br/>
                        <?php

                        foreach ($results as $result) {
                            ?>
                            <label for="channel_<?php echo $result->id; ?>"><input type="checkbox" name="channel[]"
                                                                                   value="<?php echo $result->id; ?>"
                                                                                   id="channel_<?php echo $result->id; ?>"/> <?php echo $result->title; ?>
                            </label><br/>
                        <?php } ?>
                    </div>
                <?php } ?>
                <button class="btn btn-primary" type="submit">Δημιουργία</button>
            </form>
        </div>
    </div>
</div>
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