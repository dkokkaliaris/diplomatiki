<?php
include_once "includes/init.php";
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


$sql = "SELECT count(*) FROM dk_questionnaire_channel where id_channel = 2;";
$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();


/* Setup page vars for display. */
/*if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;*/
$targetpage = "evaluation.php";    //your file name  (the name of this file)


// φέρνω όλα τα ερωτηματολόγια που είναι στο κανάλι ανώνυμης αξιολόγισης
$sql = "SELECT dk_questionnaire.* FROM dk_questionnaire join dk_questionnaire_channel on dk_questionnaire_channel.id_questionnaire =  dk_questionnaire.id where dk_questionnaire.time_begins < NOW() and dk_questionnaire.time_ends > NOW() and dk_questionnaire_channel.id_channel = 2 and dk_questionnaire.template = 0 GROUP by dk_questionnaire.id $sortby $sorthow LIMIT $start,$limit;";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$results = $stmt->fetchALL();
$breadcrumb=array(
    array('title'=>'Μαθήματα','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Μαθήματα</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>
                    <a href="questionnaires.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a>
                </th>
                <th>Ενέργειες</th>
            </tr>
            </thead>
            <tbody>';

            foreach ($results as $result) {
                echo '<tr>
                    <td>'.$result->title.'</td>
                    <td>
                        <a href="anonymous_evaluate_questionnaire.php?id='.$result->id.'" type="button"><span  class="fa fa-list-alt" style="color: darkgreen;" aria-hidden="true"></span></a>
                    </td>
                </tr>';
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
