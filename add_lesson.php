<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();
$breadcrumb=array(
    array('title'=>'Εκπαιδευτικά Προγράμματα','href'=>'lessons.php'),
    array('title'=>'Προσθήκη Νέου Εκπαιδευτικού Προγράμματος','href'=>''),
);
echo '<div class="container-fluid">
'.show_breacrumb($breadcrumb);

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
            echo "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Η εκχώρηση δεν πραγματοποιήθηκε. Δοκιμάστε ξανά.</div></div></div>";
        }
    }
}
echo '<div class="row">
        <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
            <div class="box">
                <div class="row">
                    <div class="col-sm-12">
                        <h3>Προσθήκη Νέου Εκπαιδευτικού Προγράμματος</h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <p>Για να δημιουργήσετε ένα νέο εκπαιδευτικό πρόγραμμα, παρακαλούμε συμπληρώστε στην παρακάτω φόρμα τον τίτλο  και τον υπεύθυνο του προγράμματος.</p>
                        <form action="add_lesson.php" method="post">
                            <div class="form-group">
                                <label class="form-control-label" for="title">Τίτλος: </label>
                                <input type="text" class="form-control" name="title" id="title"/>
                            </div>';

                            if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
                                echo '<div class="form-group">
                                    <label class="form-control-label" for="professors">Καθηγητής: </label>
                                    <select name="professors" id="professors" class="form-control type">
                                        <option value="" selected>Επιλογή Καθηγητή</option>';
                                            $stmt = $dbh->prepare('SELECT * FROM dk_users where type = 3');
                                            $stmt->execute();
                                            $professors = $stmt->fetchALL();
                                            foreach ($professors as $professor) {
                                                echo '<option value="'.$professor->id.'">'.$professor->last_name." ".$professor->first_name.'</option>';
                                            }
                                        }
                                    echo '</select>
                                    <button class="btn btn-primary full-width" type="submit">Δημιουργία</button>
                                </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';
get_footer();
?>