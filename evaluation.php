<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}

if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $stmt = $dbh->prepare('DELETE FROM dk_lessons WHERE id = :id');
    $params = array(':id' => $id);
    $stmt->execute($params);
}

$limit = 20;
$adjacents = 5;
if (isset($_GET['page'])) {
    $page = sanitize($_GET['page']);
    $start = ($page - 1) * $limit;            //first item to display on this page
} else {
    $page = 1;
    $start = 0;                //if no page var is given, set start to 0
}

$sortby = 'order by ';
// για ταξινόμηση
if (!empty($_REQUEST['sortby'])) {
    $sortby .= 'dk_questionnaire.'.sanitize($_REQUEST['sortby']);
} else {
    $sortby .= "dk_lessons.id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}

$targetpage = "evaluation.php";    //your file name  (the name of this file)
$id = isset($_REQUEST['id']) ? sanitize($_REQUEST['id']) : '';
$title = isset($_REQUEST['title']) ? sanitize($_REQUEST['title']) : '';
$lesson = isset($_REQUEST['lesson']) ? sanitize($_REQUEST['lesson']) : '';
$last_editor = isset($_REQUEST['last_editor']) ? sanitize($_REQUEST['last_editor']) : '';
$username = isset($_REQUEST['username']) ? sanitize($_REQUEST['username']) : '';
$time_begins = isset($_REQUEST['time_begins']) ? sanitize(urldecode($_REQUEST['time_begins'])) : '';
$time_ends = isset($_REQUEST['time_ends']) ? sanitize(urldecode($_REQUEST['time_ends'])) : '';
$addtosql="";
if (!empty($id)) {
    $addtosql .= " AND dk_questionnaire.id LIKE '%$id%'";
}
if (!empty($title)) {
    $addtosql .= " AND dk_questionnaire.title LIKE '%$title%'";
}
if (!empty($lesson)) {
    $addtosql .= " AND dk_lessons.title LIKE '%$lesson%'";
}
if (!empty($username)) {
    $addtosql .= " AND (dk_users.username LIKE '%$username%' OR dk_users.first_name LIKE '%$username%' OR dk_users.last_name LIKE '%$username%')";
}
if (!empty($time_begins)) {
    $addtosql .= " AND (dk_questionnaire.time_begins BETWEEN '$time_begins 00:00:00' AND '$time_begins 23:59:59')";
}
if (!empty($time_ends)) {
    $addtosql .= " AND (dk_questionnaire.time_ends BETWEEN '$time_ends 00:00:00' AND '$time_ends 23:59:59')";
}
$sql = "SELECT count(*) FROM dk_lessons join dk_questionnaire on dk_lessons.id = dk_questionnaire.lesson_id join dk_users on dk_users.id = dk_lessons.user_id where dk_questionnaire.time_begins < NOW() and dk_questionnaire.time_ends > NOW() $addtosql GROUP by dk_questionnaire.lesson_id;";$stmt = $dbh->prepare($sql);
$stmt->execute();
$total_pages = $stmt->fetchColumn();
// φέρνω όλα τα μαθήματα
$sql = "SELECT dk_lessons.title AS lesson_title, dk_questionnaire.*, dk_users.first_name, dk_users.last_name FROM dk_lessons join dk_questionnaire on dk_lessons.id = dk_questionnaire.lesson_id join dk_users on dk_users.id = dk_lessons.user_id where dk_questionnaire.time_begins < NOW() and dk_questionnaire.time_ends > NOW() $addtosql GROUP by dk_questionnaire.lesson_id $sortby $sorthow LIMIT $start,$limit;";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$results = $stmt->fetchALL();
get_header();
$breadcrumb=array(
    array('title'=>'Αξιολόγηση Μαθημάτων','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Αξιολόγηση Μαθημάτων</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <form action="evaluation.php" method="get">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><a href="evaluation.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                            <th><a href="evaluation.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Εκπαιδευτικό Πρόγραμμα</a></th>
                            <th><a href="evaluation.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a></th>
                            <th><a href="evaluation.php?sortby=user_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Επιβλέπων Καθηγητής</a></th>
                            <th><a href="evaluation.php?sortby=time_begins&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Έναρξης</a></th>
                            <th><a href="evaluation.php?sortby=time_ends&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Λήξης</a></th>
                            <th>Ενέργειες</th>
                        </tr>
                        <tr>
                            <td><input type="text" class="form-control" placeholder="ID" name="id" id="id" value="'.$id.'"/></td>
                            <td><input type="text" class="form-control" placeholder="Εκπαιδευτικό Πρόγραμμα" name="lesson" id="lesson" value="'.$lesson.'"/></td>
                            <td><input type="text" class="form-control" placeholder="Τίτλος" name="title" id="title" value="'.$title.'"/></td>
                            <td><input type="text" class="form-control" placeholder="Επιβλέπων Καθηγητής" name="username" id="username" value="'.$username.'"/></td>
                            <td><input type="text" class="form-control" placeholder="Ημερομηνία Έναρξης" name="time_begins" id="time_begins" value="'.$time_begins.'" /></td>
                            <td><input type="text" class="form-control" placeholder="Ημερομηνία Λήξης" name="time_ends" id="time_ends" value="'.$time_ends.'" /></td>

                            <td>
                                <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                            </td>

                        </tr>
                    </thead>
                    <tbody>';
                        foreach ($results as $result) {
                            echo '<tr>
                                <th scope="row">'.$result->id.'</th>
                                <td>';
                                    echo $result->lesson_title;
									echo '</td>
                                <td>'.$result->title.'</td>
                                <td>';
                                    // φέρνω το μάθημα του ερωτηματολογίου
                                    echo $result->first_name.' '.$result->last_name;
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
                                    <a data-toggle="tooltip" data-placement="bottom" title="Προβολή Ερωτηματολογίου" href="evaluate_questionnaire.php?id='.$result->id.'"><span class="fa fa-list-alt" style="color: darkgreen;" aria-hidden="true"></span></a>
                                </td>
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
    echo '
    </div>
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
    jQuery(document).ready(function () {
        jQuery('[data-toggle="tooltip"]').tooltip();
    });
</script>

<?php
get_footer();
?>
