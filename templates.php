<?php
include_once "includes/init.php";
get_header();

if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $stmt = $dbh->prepare('DELETE FROM dk_questionnaire_channel WHERE id_questionnaire = :id');
    $params=array(':id'=> $id);
    $stmt->execute($params);//διαγραφουμε πρωτα από τον πίνακα dk_questionnaire_channel, διότι υπάρχει foreign key
    $stmt = $dbh->prepare('DELETE FROM dk_questionnaire WHERE id = :id');
    $params = array(':id' => $id);
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
    $sortby .= $_REQUEST['sortby'];
} else {
    $sortby .= "id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = $_REQUEST['sorthow'];
} else {
    $sorthow = "desc";
}


$sql = "SELECT count(*) FROM dk_questionnaire where template = 1 and user_id = " . $_SESSION['userid'] . ";";
$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();


/* Setup page vars for display. */
/*if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;*/
$targetpage = "templates.php";    //your file name  (the name of this file)

// φέρνω όλα τα Templates
if ($_SESSION['level'] == 3)
    $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire where template = 1 and user_id = " . $_SESSION['userid'] . " and (lockedtime is null or lockedtime < NOW()) $sortby $sorthow LIMIT $start,$limit;");
else
    $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire where template = 1 $sortby $sorthow LIMIT $start,$limit;");
$stmt->execute();
$stmt->execute();
$results = $stmt->fetchALL();

echo '<div class="container-fluid">
    <div class="row breadcrumb">
        <div class="col-sm-12">
        <a href="index.php">Αρχική Σελίδα</a> &gt; Πρότυπα Ερωτηματολόγια
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <h3>Πρότυπα Ερωτηματολόγια
                <a class="btn btn-primary btn-sm pull-right" href="add_template.php">Προσθήκη Νέου</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
        <table class="table table-striped">
            <thead>
            <tr>
                <th><a href="templates.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">#</a></th>
                <th><a href="templates.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Σύντομος Τίτλος</a></th>
                <th>Ενέργειες</th>
            </tr>
            </thead>
            <tbody>';
            foreach ($results as $result) {
                echo '<tr>
                    <th scope="row">'.$result->id.'</th>
                    <td>'.$result->title.'</td>
                    <td>
                        <a href="edit_template.php?id='.$result->id.'" class="btn btn-success btn-sm" type="button"><span
                                class="fa fa-pencil" aria-hidden="true"></span></a>
                        <a onclick=\'return confirm("Διαγραφή")\' class="btn btn-danger btn-sm" href="templates.php?action=delete&id='.$result->id.'&"
                           type="button"><span class="fa fa-trash-o" aria-hidden="true"></span></a>
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