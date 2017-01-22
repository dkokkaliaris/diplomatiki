<?php
include_once "includes/init.php";
get_header();
?>
<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($_SESSION['userid'] == 3 && (!isset($_POST['title']) || $_POST['title'] == '')) {
        echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    } else if (($_SESSION['userid'] == 1 || $_SESSION['userid'] == 2) && ((!isset($_POST['title']) || $_POST['title'] == '') || (!isset($_POST['professors']) || $_POST['professors'] == 0))) {
        echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);

        $userId = $_SESSION['userid'];

        if($_SESSION['userid'] == 1 || $_SESSION['userid'] == 2){
            $userId = $_POST['professors'];
        }

        // Δημιουργούμε το template
        $stmt = $dbh->prepare('INSERT INTO dk_lessons (title, user_id) VALUES (:title, :user_id)');
        $params = array(':title' => $title, ':user_id' => $userId);
        $stmt->execute($params);

        // Η βάση μας γνωστοποιεί το ID του ερωτηματολογίου που μόλις δημιούργησε και το αποθηκευύομε.
        $new_id = $dbh->lastInsertId();

        if ($new_id > 0) {
            header("Location: lessons.php");
            //header("Location: edit_lesson.php?id=$new_id");
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
                    <h2>Προσθήκη Νέου Μαθήματος</h2>
                </div>
            </div>
            <hr/>
            <div class="row">
                <form action="add_lesson.php" method="post">
                    <div class="form-group">
                        <label class="form-control-label" for="title">Τίτλος: </label>
                        <input type="text" class="form-control" name="title" id="title"/>
                    </div>

                    <?php
                    if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) { ?>
                    <div class="form-group">
                        <label class="form-control-label" for="professors">Καθηγητής: </label>
                        <select name="professors" id="professors" class="form-control type" style="width: auto;">
                            <option value="0">Επιλογή Καθηγητή</option>
                            <?php $stmt = $dbh->prepare('SELECT * FROM dk_users where type = 3');
                            $stmt->execute();
                            $professors = $stmt->fetchALL();

                            foreach ($professors as $professor) { ?>
                                <option value="<?php echo $professor->id; ?>"><?php echo $professor->username; ?></option>
                            <?php }
                            }
                            ?>

                        </select>
                    </div>
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