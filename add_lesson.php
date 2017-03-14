<?php
include_once "includes/init.php";
get_header();
?>
<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // έλεγχω αν είναι καθηγητής και δεν έχει συμπληρώσει τον τίτλο
    if ($_SESSION['level'] == 3 && (!isset($_POST['title']) || $_POST['title'] == '')) {
        echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    // έλεγχω αν είναι ΟΜΕΑ η διαχειριστής και δεν έχει συμπληρώσει τον τίτλο και όνομα καθηγητή
    } else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && ((!isset($_POST['title']) || $_POST['title'] == '') || (!isset($_POST['professors']) || $_POST['professors'] == 0))) {
        echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    } else {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);

        // Αν ειμαι ΟΜΕΑ η διαχειριστής βάζω το userid το id του καθηγητή
        if($_SESSION['level'] == 1 || $_SESSION['level'] == 2){
            $userId = $_POST['professors'];
        }else {
        // αν ειμαι καθηγητής μπαίνει αυτόματα το ID μου
            $userId = $_SESSION['userid'];
        }

        // Δημιουργούμε το μάθημα
        $stmt = $dbh->prepare('INSERT INTO dk_lessons (title, user_id) VALUES (:title, :user_id)');
        $params = array(':title' => $title, ':user_id' => $userId);
        $stmt->execute($params);

        // Η βάση μας γνωστοποιεί το ID του ερωτηματολογίου που μόλις δημιούργησε και το αποθηκευύομε.
        $new_id = $dbh->lastInsertId();

        if ($new_id > 0) {
            header("Location: lessons.php");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Η εκχώρηση δεν πραγματοποιήθηκε. Δοκιμάστε ξανά.</div>";
        }
    }
}
?>

<br/>
<div class="container">
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-15">
                <h3>Προσθήκη Νέου Εκπαιδευτικού Προγράμματος</h3>
				<p>Για να δημιουργήσετε ένα νέο εκπαιδευτικό πρόγραμμα, παρακαλούμε συμπληρώστε στην παρακάτω φόρμα τον τίτλο  και τον υπεύθυνο του προγράμματος.</p>
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
                        <option value="" selected>Επιλογή Καθηγητή</option>
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
<?php
get_footer();
?>