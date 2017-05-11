<?php
include_once "includes/init.php";
get_header();
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
// σε περίπτωση που θέλω να διαγράψω ένα μάθημα, παίρνω από το URL
// το action και το ID του και τρέχω το query.
if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $params = array(':id' => $id);
    $sql = 'DELETE FROM dk_lessons WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);

    echo "<div class='alert alert-success'>Η διαγραφή του μαθήματος πραγματοποιήθηκε με επιτυχία.</div>";
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
$sql = 'SELECT count(*) FROM dk_lessons where user_id = :id';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$total_pages = $stmt->fetchColumn();


/* Setup page vars for display. */
/*if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;*/
$targetpage = "lessons.php";    //your file name  (the name of this file)

// φέρνω όλα τα μαθήματα
if ($_SESSION['level'] == 3)
    $ssql = "SELECT * FROM dk_lessons where user_id = " . $_SESSION['userid'] . " $sortby $sorthow LIMIT $start,$limit;";
else
    $sql = "SELECT A.*,B.first_name,B.last_name FROM dk_lessons A JOIN dk_users B ON A.user_id=B.id  $sortby $sorthow LIMIT $start,$limit;";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchALL();

// ================================== TO echo ξεκινάει εδώ ============================================

$breadcrumb=array(
    array('title'=>'Εκπαιδευτικά Προγράμματα','href'=>'')
);

echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Εκπαιδευτικά Προγράμματα
                <a class="btn btn-primary btn-sm pull-right" href="add_lesson.php">Προσθήκη Νέου</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th><a href="lessons.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">#</a></th>
                    <th><a href="lessons.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a></th>
                    <th><a href="lessons.php?sortby=username&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Επιβλέπων Καθηγητής</a></th>
                    <th>Ενέργειες</th>
                </tr>
                </thead>
                <tbody>';
                foreach ($results as $result) {
                    echo '
                    <tr>
                        <th scope="row">'.$result->id.'</th>
                        <td>'.$result->title.'</td>
                        <td>'.$result->first_name.''.$result->last_name.' </td>
                        <td><a class="btn btn-sm btn-danger" onclick=\'return confirm("Είστε σίγουρος οτι θέλετε να διαγράψετε το μάθημα '.$result->title.';")\' href="lessons.php?action=delete&id='.$result->id.'"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>
                    </tr>';
                }
                echo'
                </tbody>
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

// ================================== TO echo τελειώνει εδώ ============================================
// ================================== TO echo τελειώνειεδώ ============================================
// ================================== TO echo τελειώνειεδώ ============================================

get_footer();
?>
