<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
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
    $sortby .= "A.id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}

$targetpage = "results.php";    //your file name  (the name of this file)
$addtosql = '';
$search_id = isset($_REQUEST['id']) ? sanitize($_REQUEST['id']) : '';
$lesson = isset($_REQUEST['lesson']) ? sanitize($_REQUEST['lesson']) : '';
$username = isset($_REQUEST['username']) ? sanitize($_REQUEST['username']) : '';
$time_begins = isset($_REQUEST['time_begins']) ? sanitize(urldecode($_REQUEST['time_begins'])) : '';
$time_ends = isset($_REQUEST['time_ends']) ? sanitize(urldecode($_REQUEST['time_ends'])) : '';

if (!empty($search_id)) {
    $addtosql .= " AND A.id = $search_id";
}
if (!empty($lesson)) {
    $addtosql .= " AND C.name LIKE '%$lesson%'";
}
if (!empty($username)) {
    $addtosql .= " AND (B.username LIKE '%$username%' OR B.first_name LIKE '%$username%' OR B.last_name LIKE '%$username%')";
}
if (!empty($time_begins)) {
    $addtosql .= " AND (A.time_begins BETWEEN '$time_begins 00:00:00' AND '$time_begins 23:59:59')";
}
if (!empty($time_ends)) {
    $addtosql .= " AND (A.time_ends BETWEEN '$time_ends 00:00:00' AND '$time_ends 23:59:59')";
}

if($_SESSION['level'] == 1 || $_SESSION['level'] == 2){
    $params = array();
    $sql = "SELECT A.*, D.type, B.last_name, B.first_name, C.title AS lesson_title FROM dk_questionnaire A join dk_answers AS D on A.id = D.questionnaire_id JOIN dk_users B ON A.user_id=B.id JOIN dk_lessons C ON A.lesson_id=C.id where template = 0 $addtosql group by A.id $sortby $sorthow  LIMIT $start,$limit;";
}else{
// φέρνω όλα τα ερωτηματολόγια που έχουν απαντηθεί και δεν είναι κλειδωμένα
    $params = array(':id' => $_SESSION['userid']);
    $sql = "SELECT A.*, D.type, B.last_name, B.first_name, C.title AS lesson_title FROM dk_questionnaire A join dk_answers AS D on A.id = D.questionnaire_id JOIN dk_users B ON A.user_id=B.id JOIN dk_lessons C ON A.lesson_id=C.id where template = 0 and A.user_id = :id $addtosql group by A.id $sortby $sorthow  LIMIT $start,$limit;";
}
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();
$total_pages = $stmt->fetchColumn();

get_header();
$breadcrumb=array(
    array('title'=>'Αποτελέσματα Αξιολογήσεων','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">
                <h3>Αποτελέσματα Αξιολογήσεων</h3>
            </div>
        </div>
        <div class="row">
        <div class="col-sm-12">
        <form action="results.php" method="get">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><a href="results.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                        <th><a href="results.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Εκπαιδευτικό Πρόγραμμα</a></th>
                        <th><a href="results.php?sortby=user_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Επιβλέπων Καθηγητής</a></th>
                        <th><a href="results.php?sortby=time_begins&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Έναρξης</a></th>
                        <th><a href="results.php?sortby=time_ends&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Λήξης</a></th>
                        <th>Συνολικές Αξιολογήσεις</th>
                        <th>Ενέργειες</th>
                    </tr>
                    <tr>
                        <td><input type="text" class="form-control" placeholder="ID" name="id" id="id" value="'.$search_id.'"/></td>
                        <td><input type="text" class="form-control" placeholder="Εκπαιδευτικό Πρόγραμμα" name="lesson" id="lesson"  value="'.$lesson.'" /></td>
                        <td><input type="text" class="form-control" placeholder="Επιβλέπων Καθηγητής" name="username" id="username" value="'.$username.'"/></td>
                        <td><input type="text" class="form-control" placeholder="Ημερομηνία Έναρξης" name="time_begins" id="time_begins" value="'.$time_begins.'" /></td>
                        <td><input type="text" class="form-control" placeholder="Ημερομηνία Λήξης" name="time_ends" id="time_ends" value="'.$time_ends.'" /></td>
                        <td></td>
                        <td>
                            <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                        </td>

                    </tr>
                </thead>
                <tbody>';

                foreach ($results as $result) {
                    echo '<tr>
                        <th scope="row">'.$result->id.'</th>
                        <td>'.$result->lesson_title.'</td>
                        <td>'.$result->first_name.' '.$result->last_name.'</td>
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
                        <td>';
                            $params = array(':id' => $result->id);
                            $sql = "SELECT user_id FROM dk_answers where questionnaire_id = :id AND question_id IN ( SELECT question_id FROM dk_questionnaire_questions where order_by = 1 )";
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute($params);
                            $count = $stmt->rowCount();
                            echo $count;
                        echo '</td>
                        <td>';
                            if(($_SESSION['level']==3 &&$_SESSION['userid']==$result->user_id && $result->lockedtime<date('Y-m-d H:i:s'))||$_SESSION['level']==1 || $_SESSION['level']==2){//αν ειναι ο καθηγητης του ερωτηματολογίου και έχει περάσει η ημερομηνία κλειδώματος ή αν είναι Διαχειριστής ή αν είναι ΟΜΕΑ θα εμφανιστεί το κουμπί εμφανισης αποτελεσμάτων
                                echo '<a data-toggle="tooltip" data-placement="bottom" title="Προβολή Αποτελεσμάτων" href="questionnaire_graphs.php?id='.$result->id.'" class="btn btn-warning"><span class="fa fa-area-chart" aria-hidden="true"></span></a>';
                            }
                        echo '</td>
                    </tr>';
                }

                echo '</tbody>
            </table>
        </form>
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
?>
<script>
    jQuery('#time_begins').datetimepicker({
        lang: 'el',
        timepicker: false,
        format: 'Y-m-d',
        formatDate: 'd/m/Y'
    });
    jQuery('#time_ends').datetimepicker({
        lang: 'el',
        timepicker: false,
        format: 'Y-m-d',
        formatDate: 'd/m/Y'
    });
</script>

<?php
get_footer();
?>
