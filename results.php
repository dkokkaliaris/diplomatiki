<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();

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
    $sortby .= "dk_questionnaire.id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}


$stmt = $dbh->prepare('SELECT count(*) FROM dk_questionnaire join dk_answers on dk_questionnaire.id = dk_answers.questionnaire_id where template = 0 and dk_questionnaire.user_id = :id group by dk_questionnaire.id');
$params = array(':id' => $_SESSION['userid']);
$stmt->execute($params);
//$total_pages = $result->fetchColumn();

/* Setup page vars for display. */
/*if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;*/
$targetpage = "questionnaires.php";    //your file name  (the name of this file)

// φέρνω όλα τα ερωτηματολόγια που έχουν απαντηθεί και δεν είναι κλειδωμένα
$stmt = $dbh->prepare("SELECT dk_questionnaire.*, dk_answers.type FROM dk_questionnaire join dk_answers on dk_questionnaire.id = dk_answers.questionnaire_id where template = 0 and dk_questionnaire.user_id = " . $_SESSION['userid'] . " and (dk_questionnaire.lockedtime is null or dk_questionnaire.lockedtime < NOW()) group by dk_questionnaire.id $sortby $sorthow  LIMIT $start,$limit;");
$stmt->execute();
$results = $stmt->fetchALL();
$total_pages = $stmt->fetchColumn();
$breadcrumb=array(
    array('title'=>'Αποτελέσματα Αξιολογήσεων ανά Ερωτηματολόγιο','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">
                <h3>Αποτελέσματα Αξιολογήσεων ανά Ερωτηματολόγιο</h3>
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
                        $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire_lessons where questionnaire_id = :id");
                        $params = array(':id' => $result->id);
                        $stmt->execute($params);
                        $lessonQ = $stmt->fetchObject();

                        $stmt = $dbh->prepare("SELECT * FROM dk_lessons where id = :id");
                        $params = array(':id' => $lessonQ->lessons_id);
                        $stmt->execute($params);
                        $lesson = $stmt->fetchObject();

                        echo $lesson->title;

                    echo '</td>
                    <td>';
                        if ($result->template == 0)
                            echo (new DateTime($result->time_begins))->format('d/m/Y H:i');
                        else echo '-';
                    echo '</td>
                    <td>';
                        if ($result->template == 0)
                            echo (new DateTime($result->time_ends))->format('d/m/Y H:i');
                        else echo '-';
                    echo '</td>
                    <td>
                        <a href="questionnaire_result.php?id='.$result->id.'" type="button"><span class="fa fa-list-alt" aria-hidden="true"></span></a>

                        <a href="questionnaire_graphs.php?id='.$result->id.'" type="button"><span class="fa fa-area-chart" aria-hidden="true"></span></a>
                    </td>
                </tr>';
            }

            echo '</tbody>
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
