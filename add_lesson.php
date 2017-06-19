<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
$alert = '';
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // έλεγχω αν είναι καθηγητής και δεν έχει συμπληρώσει τον τίτλο
    if ($_SESSION['level'] == 3 && (!isset($_POST['title']) || $_POST['title'] == '')) {
        $alert .= "<div class='alert alert-danger'>Παρακαλούμε συμπληρώστε όλα τα πεδία της φόρμας.</div>";
    // έλεγχω αν είναι ΟΜΕΑ ή διαχειριστής και δεν έχει συμπληρώσει τον τίτλο και το όνομα καθηγητή
    } else if (($_SESSION['level'] == 1 || $_SESSION['level'] == 2) && ((!isset($_POST['title']) || $_POST['title'] == '') || (!isset($_POST['professors']) || $_POST['professors'] == 0))) {
        $alert .= "<div class='alert alert-danger'>Παρακαλούμε συμπληρώστε όλα τα πεδία της φόρμας.</div>";
    } else {
        $title = sanitize($_POST['title']);
        $department = sanitize($_POST['departments']);

        // Αν ειμαι ΟΜΕΑ ή διαχειριστής βάζω το userid το id του καθηγητή
        if($_SESSION['level'] == 1 || $_SESSION['level'] == 2){
            $userId = $_POST['professors'];
        }else {
        // Αν ειμαι καθηγητής μπαίνει αυτόματα το ID μου
            $userId = $_SESSION['userid'];
        }

        // Δημιουργούμε το μάθημα
        $params = array(':title' => $title, ':user_id' => $userId, ':department_id' => $department);
        $sql = 'INSERT INTO dk_lessons (title, user_id, department_id) VALUES (:title, :user_id, :department_id)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        // Η βάση μας γνωστοποιεί το ID του ερωτηματολογίου που μόλις δημιούργησε και το αποθηκεύουμε.
        $new_id = $dbh->lastInsertId();

        if ($new_id > 0) {
            header("Location: lessons.php?status=1");
            exit;
        } else {
            $alert .= "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Η δημιουργία του εκπαιδευτικού προγράμματος δεν πραγματοποιήθηκε με επιτυχία. Παρακαλούμε δοκιμάστε ξανά.</div></div></div>";
        }
    }
}
get_header();
$breadcrumb=array(
    array('title'=>'Εκπαιδευτικά Προγράμματα','href'=>'lessons.php'),
    array('title'=>'Προσθήκη Νέου Εκπαιδευτικού Προγράμματος','href'=>''),
);
echo '<div class="container-fluid">
'.show_breacrumb($breadcrumb);
echo $alert;
echo '<div class="row">
        <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
            <div class="box">
                <div class="row">
                    <div class="col-sm-12">
                        <h4>Προσθήκη Νέου Εκπαιδευτικού Προγράμματος</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <p>Για να δημιουργήσετε ένα νέο εκπαιδευτικό πρόγραμμα, παρακαλούμε συμπληρώστε στην παρακάτω φόρμα τα στοιχεία που απαιτούνται.</p>
                        <form action="add_lesson.php" method="post" id="add_lesson_form" novalidate="">
                            <div class="form-group">
                                <label class="form-control-label" for="title">Τίτλος Εκπαιδευτικού Προγράμματος: </label>
                                <input type="text" class="form-control" name="title" id="title" required=""/>
                            </div>';

                            if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
                                if($_SESSION['level'] == 1){
                                    $params = array();
                                    $where = 'type = 3 or type = 2 or type = 1';
                                }elseif($_SESSION['level'] == 2){
                                    $params = array(':user_id' => $_SESSION['userid']);
                                    $where = 'type = 3 or id= :user_id';
                                }
                                echo '<div class="form-group">
                                    <label class="form-control-label" for="professors">Επιβλέπων Καθηγητής: </label>
                                    <select name="professors" id="professors" class="form-control type" required="">
                                        <option value="" selected>Επιλογή Επιβλέποντα Καθηγητή</option>';
                                            $stmt = $dbh->prepare('SELECT * FROM dk_users where '.$where);
                                            $stmt->execute($params);
                                            $professors = $stmt->fetchALL();
                                            foreach ($professors as $professor) {
                                                echo '<option value="'.$professor->id.'">'.$professor->last_name." ".$professor->first_name.'</option>';
                                            }
                                    echo '</select>
                                </div>';
                            }
                            if ($_SESSION['level'] <=3) {
                                echo '<div class="form-group">
                                    <label class="form-control-label" for="departments">Τμήμα: </label>
                                    <select name="departments" id="departments" class="form-control type" required="">
                                        <option value="" selected>Επιλογή Τμήματος</option>';
                                            $stmt = $dbh->prepare('SELECT * FROM dk_departments where 1');
                                            $stmt->execute($params);
                                            $departments = $stmt->fetchALL();
                                            foreach ($departments as $d) {
                                                echo '<option value="'.$d->id.'">'.$d->name.'</option>';
                                            }
                                    echo '</select>
                                </div>';
                            }

                            echo '<br/>
                            <button class="btn btn-primary full-width" type="submit">Δημιουργία Εκπαιδευτικού Προγράμματος</button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';?>
<script>
    jQuery(document).ready(function () {
        jQuery('#add_lesson_form').validate();
    });
</script>
<?php get_footer();?>