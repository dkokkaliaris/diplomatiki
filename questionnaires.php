<?php
include_once "includes/init.php";
get_header();

if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
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


$sql = "SELECT count(*) FROM dk_questionnaire where template = 0 and user_id = " . $_SESSION['userid'] . ";";

$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();


/* Setup page vars for display. */
if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;
$targetpage = "questionnaires.php";    //your file name  (the name of this file)

// φέρνω όλα τα ερωτηματολόγια
if ($_SESSION['level'] == 3)
    $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire where template = 0 and user_id = " . $_SESSION['userid'] . " and (lockedtime is null or lockedtime < NOW()) $sortby $sorthow LIMIT $start,$limit;");
else
    $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire where template = 0 $sortby $sorthow LIMIT $start,$limit;");
$stmt->execute();

$results = $stmt->fetchALL();

?>

<br/>
<div class="container">
    <div class="col-sm-3">
        <?php include "sidebar.php"; ?>
    </div>
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-9">
                <h3>Ερωτηματολόγια</h3>
            </div>
            <div class="col-sm-3">
                <a class="btn btn-primary" href="add_questionnaire.php">Προσθήκη Νέου</a>
                <!-- <a class="btn btn-primary" href="add_questionnaire_from_template.php">Προσθήκη Νέου με βάση κάποιο template</a>-->
            </div>
        </div>
        <table class="table table-striped">
            <thead>
            <tr>
                <th><a href="questionnaires.php?sortby=id&amp;sorthow=<?php if ($sorthow == "desc") {
                        echo "asc";
                    } else {
                        echo "desc";
                    } ?>">#</a></th>
                <th><a href="questionnaires.php?sortby=title&amp;sorthow=<?php if ($sorthow == "desc") {
                        echo "asc";
                    } else {
                        echo "desc";
                    } ?>">Σύντομος Τίτλος</a></th>
                <th>Μάθημα</th>
                <th>Σύνολο ερωτήσεων</th>
                <th>Last Editor</th>

                <?php
                if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
                    ?>
                    <th>Διαχειριστής</th>
                    <?php
                }
                ?>
                <th><a href="questionnaires.php?sortby=time_begins&amp;sorthow=<?php if ($sorthow == "desc") {
                        echo "asc";
                    } else {
                        echo "desc";
                    } ?>">Ημερομηνία Έναρξης</a></th>
                <th><a href="questionnaires.php?sortby=time_ends&amp;sorthow=<?php if ($sorthow == "desc") {
                        echo "asc";
                    } else {
                        echo "desc";
                    } ?>">Ημερομηνία Λήξης</a></th>
                <th>Ενέργειες</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($results as $result) {
                ?>
                <tr>
                    <th scope="row"><?php echo $result->id; ?></th>
                    <td><?php echo $result->title; ?></td>
                    <td>
                        <?php
                        // φέρνω το μάθημα του ερωτηματολογίου
                        $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire_lessons where questionnaire_id = $result->id");
                        $stmt->execute();
                        $lessonQ = $stmt->fetchObject();

                        $stmt = $dbh->prepare("SELECT * FROM dk_lessons where id = $lessonQ->lessons_id");
                        $stmt->execute();
                        $lesson = $stmt->fetchObject();

                        echo $lesson->title;
                        ?>
                    </td>
                    <td>
                        <?php
                        $sql = "SELECT count(*) FROM dk_questionnaire_questions WHERE questionnaire_id = $result->id";
                        $rr = $dbh->prepare($sql);
                        $rr->execute();
                        echo $rr->fetchColumn();
                        ?>
                    </td>
                    <td>
                        <?php
                        // φέρνω τον χρήστη που επεξεργάστηκε τελυταία φορά το ερωτηματολόγιο
                        $stmt = $dbh->prepare("SELECT * FROM dk_users where id = $result->last_editor");
                        $stmt->execute();
                        $lastTimeEditor = $stmt->fetchObject();
                        echo $lastTimeEditor->username
                        ?>
                    </td>
                    <?php
                    if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
                        ?>
                        <td>
                            <?php
                            // φέρνω τον χρήστη που ανήκει το ερωτηματολόγιο
                            $stmt = $dbh->prepare("SELECT * FROM dk_users where id = $result->user_id");
                            $stmt->execute();
                            $lastTimeEditor = $stmt->fetchObject();
                            echo $lastTimeEditor->username
                            ?>
                        </td>
                    <?php } ?>
                    <td>
                        <?php
                        if ($result->template == 0)
                            echo (new DateTime($result->time_begins))->format('d/m/Y H:i:s');
                        else echo '-';
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($result->template == 0)
                            echo (new DateTime($result->time_ends))->format('d/m/Y H:i:s');
                        else echo '-';
                        ?>
                    </td>

                    <td><a class="btn btn-xs btn-success" href="edit_questionnaire.php?id=<?php echo $result->id; ?>">Επεξεργασία</a> <a class="btn btn-xs btn-danger" href="questionnaires.php?action=delete&id=<?php echo $result->id; ?>">Διαγραφή</a></td>

                </tr>
            <?php } ?>
            </tbody>
        </table>

        <?php
        // http://aspektas.com/blog/really-simple-php-pagination/
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
        $querystring = "";
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
        }
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
        ?>


    </div>
</div>

<?php
get_footer();
?>
