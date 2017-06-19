<?php
include_once "includes/init.php";
get_header();
$breadcrumb=array(
    array('title'=>'Ανώνυμη Αξιολόγηση Εκπαιδευτικών Προγραμμάτων','href'=>'')
);

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

//Φερνει τα ερωτηματολογια με που ανηκουν στο καναι 2 των ανωνυμων αξιολογησεων.
$sql = "SELECT count(*) FROM dk_questionnaire_channel where id_channel = 2;";
$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();
$targetpage = "anonymous_questionnaires.php";

// φέρνω όλα τα ερωτηματολόγια που είναι στο κανάλι ανώνυμης αξιολόγησης
$sql = "SELECT dk_questionnaire.* FROM dk_questionnaire join dk_questionnaire_channel on dk_questionnaire_channel.id_questionnaire =  dk_questionnaire.id where dk_questionnaire.time_begins < NOW() and dk_questionnaire.time_ends > NOW() and dk_questionnaire_channel.id_channel = 2 and dk_questionnaire.template = 0 GROUP by dk_questionnaire.id $sortby $sorthow LIMIT $start,$limit;";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$results = $stmt->fetchALL();
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h4>Ανώνυμη Αξιολόγηση Εκπαιδευτικών Προγραμμάτων</h4>
        </div>
    </div>
	<br />
    <div class="row">
        <div class="col-sm-12">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><a href="anonymous_questionnaires.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                    <th><a href="anonymous_questionnaires.php?sortby=lesson_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Εκπαιδευτικό Πρόγραμμα</a></th>
                    <th>
                        <a href="anonymous_questionnaires.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a>
                    </th>
                    <th><a href="anonymous_questionnaires.php?sortby=user_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Επιβλέπων Καθηγητής</a></th>
                    <th><a href="anonymous_questionnaires.php?sortby=time_begins&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Έναρξης</a></th>
                    <th><a href="anonymous_questionnaires.php?sortby=time_ends&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Λήξης</a></th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>';
            if($results){
            foreach ($results as $result) {
                echo '<tr>
                    <th scope="row">'.$result->id.'</th>
                    <td>';
                        // φέρνω το μάθημα του ερωτηματολογίου
                        $params = array(':id' => $result->lesson_id);
                        $sql = "SELECT * FROM dk_lessons where id = :id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $lesson = $stmt->fetchObject();

                        echo $lesson->title;

                    echo '</td>
                    <td>'.$result->title.'</td>
                    <td>';
                        // φέρνω το μάθημα του ερωτηματολογίου
                        $params = array(':id' => $result->user_id);
                        $sql = "SELECT dk_users.first_name, dk_users.last_name FROM dk_users where id = :id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $user = $stmt->fetchObject();
                        echo $user->first_name.' '.$user->last_name;
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
                        <a href="anonymous_evaluate_questionnaire.php?id='.$result->id.'"><span class="fa fa-list-alt" style="color: darkgreen;" aria-hidden="true"></span></a>
                    </td>
                </tr>';
            }
            }
            echo '
            </tbody>
        </table>';

        // http://aspektas.com/blog/really-simple-php-pagination/
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
        pagination($total_pages, $_GET, $targetpage);
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================

        echo '</div>
    </div>
</div>';
get_footer();
?>
