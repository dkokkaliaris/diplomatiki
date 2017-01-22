<?php
include_once "includes/init.php";
get_header();
?>

<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $types = sanitize($_POST['types']);
    // αλλάζουμε τον τύπου για τον κάθε χρήστη
    foreach ($types as $id => $type) {
        $stmt = $dbh->prepare('UPDATE dk_users SET type = :type where id = :id');
        $params = array(':type' => $type, ':id' => $id);
        $stmt->execute($params);
    }
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

$sql = "SELECT count(*) FROM dk_users;";
$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();

/* Setup page vars for display. */
if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;
$targetpage = "users.php";    //your file name  (the name of this file)

?>

<div class="container">
    <div class="col-sm-3">
        <?php include "sidebar.php"; ?>
    </div>
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-8">
                <h2>Διαχείριση Χρηστών</h2>
            </div>
        </div>
        <hr/>
        <div class="row">
            <form action="users.php" method="get">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th><a href="users.php?sortby=id&amp;sorthow=<?php if ($sorthow == "desc") {
                                echo "asc";
                            } else {
                                echo "desc";
                            } ?>">ID</a></th>
                        <th><a href="users.php?sortby=first_name&amp;sorthow=<?php if ($sorthow == "desc") {
                                echo "asc";
                            } else {
                                echo "desc";
                            } ?>">Όνομα</a></th>
                        <th><a href="users.php?sortby=last_name&amp;sorthow=<?php if ($sorthow == "desc") {
                                echo "asc";
                            } else {
                                echo "desc";
                            } ?>">Επώνυμο</a></th>
                        <th><a href="users.php?sortby=aem&amp;sorthow=<?php if ($sorthow == "desc") {
                                echo "asc";
                            } else {
                                echo "desc";
                            } ?>">AEM</a></th>
                        <th><a href="users.php?sortby=email&amp;sorthow=<?php if ($sorthow == "desc") {
                                echo "asc";
                            } else {
                                echo "desc";
                            } ?>">Email</a></th>
                        <th><a href="users.php?sortby=username&amp;sorthow=<?php if ($sorthow == "desc") {
                                echo "asc";
                            } else {
                                echo "desc";
                            } ?>">Username</a></th>
                        <th><a href="users.php?sortby=telephone&amp;sorthow=<?php if ($sorthow == "desc") {
                                echo "asc";
                            } else {
                                echo "desc";
                            } ?>">Κινητό</a></th>
                        <th>Ενέργειες</th>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input type="text" class="form-control" placeholder="Όνομα" name="first_name" id="first_name"/></td>
                        <td><input type="text" class="form-control" placeholder="Επώνυμο" name="last_name" id="last_name"/></td>
                        <td><input type="text" class="form-control" placeholder="ΑΕΜ" name="aem" id="aem"/></td>
                        <td><input type="text" class="form-control" placeholder="Email" name="email" id="email"/></td>
                        <td><input type="text" class="form-control" placeholder="Username" name="username" id="username"/></td>
                        <td><input type="text" class="form-control" placeholder="Κινητό" name="telephone" id="telephone"/></td>

                        <td>
                            <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                        </td>
                    </tr>
                    </thead>

                    <?php
                    $addtosql = "";

                    $onoma = isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : '';
                    $epwnymo = isset($_REQUEST['last_name']) ? $_REQUEST['last_name'] : '';
                    $aem = isset($_REQUEST['aem']) ? $_REQUEST['aem'] : '';
                    $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
                    $username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
                    $kinito = isset($_REQUEST['v']) ? $_REQUEST['telephone'] : '';

                    if (!empty($onoma)) {
                        $addtosql .= " AND first_name LIKE '%$onoma%'";
                    }
                    if (!empty($epwnymo)) {
                        $addtosql .= " AND last_name LIKE '%$epwnymo%'";
                    }
                    if (!empty($aem)) {
                        $addtosql .= " AND aem LIKE '%$aem%'";
                    }
                    if (!empty($email)) {
                        $addtosql .= " AND email LIKE '%$email%'";
                    }
                    if (!empty($username)) {
                        $addtosql .= " AND username LIKE '%$username%'";
                    }
                    if (!empty($kinito)) {
                        $addtosql .= " AND telephone LIKE '%$kinito%'";
                    }

                    //Παίρνουμε όλους τους χρήστες
                    $stmt = $dbh->prepare("SELECT * FROM dk_users WHERE 1 $addtosql $sortby $sorthow LIMIT $start,$limit");
                    $stmt->execute();
                    $users = $stmt->fetchAll();

                    foreach ($users as $user) {

                        echo '<tr>
                              <td>' . $user->id . '</td>
                              <td>' . $user->first_name . '</td>
                              <td>' . $user->last_name . '</td>
                              <td>' . $user->aem . '</td>
                              <td>' . $user->email . '</td>
                              <td>' . $user->username . '</td>
                              <td>' . $user->telephone . '</td>
                              <td><a class="btn btn-xs btn-success" href="/questionnaire/edit_user.php?id=' . $user->id . '">Επεξεργασία</a></td>
                          </tr>';
                    }

                    ?>

                </table>
            </form>

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
</div>

<?php
get_footer();
?>
