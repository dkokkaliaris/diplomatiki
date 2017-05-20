<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();

if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $params = array(':id' => $id);
    $sql = 'DELETE FROM dk_questionnaire WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
}

$limit = 20;
$adjacents = 5;
if (isset($_GET['page'])) {
    $page = filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT);
    $start = ($page - 1) * $limit;            //first item to display on this page
} else {
    $page = 1;
    $start = 0;                //if no page var is given, set start to 0
}

$sortby = 'order by ';
// για ταξινόμηση
if (!empty($_REQUEST['sortby'])) {
    $sortby .= sanitize($_REQUEST['sortby']);
} else {
    $sortby .= "id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}

$params = array(':id' => $_SESSION['userid']);
$sql = 'SELECT count(*) FROM dk_questionnaire where template = 0 and user_id = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$total_pages = $stmt->fetchColumn();


/* Setup page vars for display. */
if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;
$targetpage = "questionnaires.php";    //your file name  (the name of this file)

// φέρνω όλα τα ερωτηματολόγια
$params = array();
if ($_SESSION['level'] == 3){
    $params = array(':id' => $_SESSION['userid'] );
    $sql = "SELECT * FROM dk_questionnaire where template = 0 and user_id = :id and (lockedtime is null or lockedtime < NOW()) $sortby $sorthow LIMIT $start,$limit;";
}else
    $sql = "SELECT * FROM dk_questionnaire where template = 0 $sortby $sorthow LIMIT $start,$limit;";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();
$breadcrumb=array(
    array('title'=>'Ερωτηματολόγια','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">
                <h3>Ερωτηματολόγια
                    <a class="btn btn-primary btn-sm pull-right" href="add_questionnaire.php">Προσθήκη Νέου Ερωτηματολογίου</a>
                </h3>
            </div>
        </div>
        <div class="row">
        <div class="col-sm-12">
        <table class="table table-striped">
            <thead>
            <tr>
                <th><a href="questionnaires.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">#</a></th>
                <th><a href="questionnaires.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Σύντομος Τίτλος</a></th>
                <th>Μάθημα</th>
                <th>Σύνολο ερωτήσεων</th>
                <th>Last Editor</th>'.
                ($_SESSION['level'] == 1 || $_SESSION['level'] == 2?'<th>Διαχειριστής</th>':'').'
                <th><a href="questionnaires.php?sortby=time_begins&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Έναρξης</a></th>
                <th><a href="questionnaires.php?sortby=time_ends&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Λήξης</a></th>
                <th>Ενέργειες</th>
            </tr>
            </thead>
            <tbody>';

            foreach ($results as $result) {
                echo '<tr>
                    <th scope="row">'.$result->id.'</th>
                    <td>'.$result->title.'</td>
                    <td>';
                        // φέρνω το μάθημα του ερωτηματολογίου
                        $params = array(':id' => $result->id);
                        $sql = "SELECT * FROM dk_questionnaire_lessons where questionnaire_id = :id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $lessonQ = $stmt->fetchObject();

                        $params = array(':id' => $lessonQ->lessons_id);
                        $sql = "SELECT * FROM dk_lessons where id = :id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $lesson = $stmt->fetchObject();

                        echo $lesson->title;
                    echo '</td>
                    <td>';
                        $params = array(':id' => $result->id);
                        $sql = "SELECT count(*) FROM dk_questionnaire_questions WHERE questionnaire_id = :id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        echo $stmt->fetchColumn();
                    echo '</td>
                    <td>';
                        // φέρνω τον χρήστη που επεξεργάστηκε τελυταία φορά το ερωτηματολόγιο
                        $params = array(':id' => $result->last_editor);
                        $sql = "SELECT * FROM dk_users where id = :id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $lastTimeEditor = $stmt->fetchObject();
                        echo $lastTimeEditor->username;
                    echo '</td>';
                    if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
                        echo '<td>';
                            // φέρνω τον χρήστη που ανήκει το ερωτηματολόγιο
                            $params = array(':id' => $result->user_id);
                            $sql = "SELECT * FROM dk_users where id = :id";
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute($params);
                            $lastTimeEditor = $stmt->fetchObject();
                            echo $lastTimeEditor->username;
                           echo '</td>';
                    }
                    echo '<td>';
                        if ($result->template == 0)
                            echo (new DateTime($result->time_begins))->format('d/m/Y H:i');
                        else echo '-';
                    echo '</td>
                    <td>';
                        if ($result->template == 0)
                            echo (new DateTime($result->time_ends))->format('d/m/Y H:i');
                        else echo '-';

                    echo '</td>
                    <td><a class="btn btn-sm btn-success" href="edit_questionnaire.php?id='.$result->id.'"><span class="fa fa-pencil" aria-hidden="true"></span></a> <a class="btn btn-sm btn-danger" href="questionnaires.php?del=' . $result->id . '"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>

                </tr>';
            }
            echo'</tbody>
        </table>
        </div>
        </div>
        <div class="row">
        <div class="col-sm-12">';
        // http://aspektas.com/blog/really-simple-php-pagination/
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
        pagination($total_pages, $_GET, $targetpage);
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================

        echo '</div>
    </div>
</div>';
get_footer();
?>