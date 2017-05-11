<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}else{
    if($_SESSION['level']>2){
       header("Location: ".BASE_URL.'index.php');
        die();
    }
}
get_header();

if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $params = array(':id' => $id);
    $sql = 'DELETE FROM dk_lessons WHERE id = :id';
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
    $sortby .= "dk_lessons.id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}


$sql = "SELECT count(*) FROM dk_lessons join dk_questionnaire_lessons on dk_questionnaire_lessons.lessons_id =  dk_lesons.id GROUP by dk_questionnaire_lessons.lessons_id;";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$total_pages = $result->fetchColumn();


/* Setup page vars for display. */
/*if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;*/
$targetpage = "evaluation.php";    //your file name  (the name of this file)

$breadcrumb=array(
    array('title'=>'Αξιολόγηση Μαθημάτων','href'=>'')
);

// φέρνω όλα τα μαθήματα
$sql = "SELECT * FROM dk_lessons join dk_questionnaire_lessons on dk_questionnaire_lessons.lessons_id =  dk_lessons.id join dk_questionnaire on dk_questionnaire_lessons.questionnaire_id = dk_questionnaire.id where dk_questionnaire.time_begins < NOW() and dk_questionnaire.time_ends > NOW() GROUP by dk_questionnaire_lessons.lessons_id $sortby $sorthow LIMIT $start,$limit;";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$results = $stmt->fetchALL();
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Αξιολόγηση Μαθημάτων</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
        <table class="table table-striped">
            <thead>
            <tr>
                <th><a href="evaluation.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a></th>
                <th>Αξιολόγηση</th>
            </tr>
            </thead>
            <tbody>';
                foreach ($results as $result) {
                    echo '<tr>
                        <td>'.$result->title.'</td>
                        <td><a href="evaluate_questionnaire.php?id='.$result->questionnaire_id.'">
                            <i class="fa fa-list-alt" style="color: darkgreen;" aria-hidden="true"></i></a>
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
        /*$querystring = "";
        foreach ($_GET as $key => $value) {
            if ($key != "page") $querystring .= "&amp;$key=" . $value;
        }

        $pagination = "";
        if ($lastpage > 1) {
            $pagination .= "<ul class=\"pagination\">";
            //previous button
            if ($page > 1)
                $pagination .= "<li><a href=\"$targetpage?page=$prev$querystring\">Πίσω</a></li>";

            //pages
            if ($lastpage < 7 + ($adjacents * 2))    //not enough pages to bother breaking it up
            {
                for ($counter = 1; $counter <= $lastpage; $counter++) {
                    if ($counter == $page)
                        $pagination .= "<li><span class=\"current\">$counter</span></li>";
                    else
                        $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
                }
            } elseif ($lastpage > 5 + ($adjacents * 2))    //enough pages to hide some
            {
                //close to beginning; only hide later pages
                if ($page < 1 + ($adjacents * 2)) {
                    for ($counter = 1; $counter < 2 + ($adjacents * 2); $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li><span class=\"current\">$counter</span></li>";
                        else
                            $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
                    }
                    $pagination .= "<li><a href=\"$targetpage?page=$lpm1$querystring\">$lpm1</a></li>";
                    $pagination .= "<li><a href=\"$targetpage?page=$lastpage$querystring\">$lastpage</a></li>";
                } //in middle; hide some front and some back
                elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                    $pagination .= "<li><a href=\"$targetpage?page=1$querystring\">1</a></li>";
                    $pagination .= "<li><a href=\"$targetpage?page=2$querystring\">2</a></li>";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li><span class=\"current\">$counter</span></li>";
                        else
                            $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
                    }
                    $pagination .= "<li><a href=\"$targetpage?page=$lpm1$querystring\">$lpm1</a></li>";
                    $pagination .= "<li><a href=\"$targetpage?page=$lastpage$querystring\">$lastpage</a></li>";
                } //close to end; only hide early pages
                else {
                    $pagination .= "<li><a href=\"$targetpage?page=1$querystring\">1</a></li>";
                    $pagination .= "<li><a href=\"$targetpage?page=2$querystring\">2</a></li>";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li><span class=\"current\">$counter</span></li>";
                        else
                            $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
                    }
                }
            }

            //next button
            if ($page < $counter - 1)
                $pagination .= "<li><a href=\"$targetpage?page=$next$querystring\">Επόμενο</a></li>";
            $pagination .= "</ul>";
            echo $pagination;
        }*/
        pagination($total_pages, $_GET, $targetpage);
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
    echo '
    </div>
</div>
</div>';

get_footer();
?>
