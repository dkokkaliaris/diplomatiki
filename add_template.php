<?php
include_once "includes/init.php";
get_header();
?>
<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (!isset($_POST['title'], $_POST['description']) || ($_POST['title'] == '' || $_POST['description'] == '')) {
        echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);

        // Δημιουργούμε το template
        $stmt = $dbh->prepare('INSERT INTO dk_questionnaire (title, description, template, user_id, last_edit_time, last_editor) VALUES (:title, :description, :template, :user_id, :last_edit_time, :last_editor)');
        $params = array(':title' => $title, ':description' => $description, ':template' => 1, ':user_id' => $_SESSION['userid'], ':last_edit_time' => date('Y-m-d H:i:s'), ':last_editor' => $_SESSION['userid']);
        $stmt->execute($params);

        // Η βάση μας γνωστοποιεί το ID του ερωτηματολογίου που μόλις δημιούργησε και το αποθηκευύομε.
        $new_id = $dbh->lastInsertId();

        if ($new_id > 0) {
            foreach ($_POST['channel'] as $channel) {
                $stmt = $dbh->prepare('INSERT INTO dk_questionnaire_channel (id_questionnaire, id_channel) VALUES (:id_questionnaire, :id_channel)');
                $params = array(':id_questionnaire' => $new_id, ':id_channel' => $channel);
                $stmt->execute($params);
            }
            header("Location: edit_template.php?id=$new_id");
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
                    <h2>Προσθήκη Νέου Πρότυπου Ερωτηματολογίου</h2>
                </div>
            </div>
            <hr/>
            <div class="row">
                <form action="add_template.php" method="post">
                    <div class="form-group">
                        <label class="form-control-label" for="title">Τίτλος: </label>
                        <input type="text" class="form-control" name="title" id="title"/>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-control-label">Σύντομη Περιγραφή: </label>
                        <textarea rows="5" class="form-control" name="description" id="description"></textarea>
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
            format: 'd/m/Y H:i',
            closeOnDateSelect: true
        });
        jQuery('#time_ends').datetimepicker({
            lang: 'el',
            timepicker: true,
            format: 'd/m/Y H:i',
            closeOnDateSelect: true
        });
    </script>

<?php
get_footer();
?>