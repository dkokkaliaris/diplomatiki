<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}else{
    if ($_SESSION['level']!=1) {
        header("Location: ".BASE_URL.'index.php');
        exit;
    }
}
get_header();

if ($_GET['del'] && sanitize($_GET['del'])>0) {
    $del = sanitize($_GET['del']);
    $params = array(':id' => $del);
    $sql = 'DELETE FROM dk_users WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    echo "<div class='alert alert-success'>Η διαγραφή του χρήστη πραγματοποιήθηκε με επιτυχία.</div>";
}

if ($_GET['a'] && sanitize($_GET['a'])>0) {
    echo "<div class='alert alert-success'>Η αλλαγή των στοιχείων του χρήστη πραγματοποιήθηκε με επιτυχία.</div>";
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $types = sanitize($_POST['types']);
    // αλλάζουμε τον τύπο για τον κάθε χρήστη
    foreach ($types as $id => $type) {
        $params = array(':type' => $type, ':id' => $id);
        $sql = 'UPDATE dk_users SET type = :type where id = :id';
        $stmt = $dbh->prepare($sql);
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
    $sortby .= sanitize($_REQUEST['sortby']);
} else {
    $sortby .= "id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}

$sql = "SELECT count(*) FROM dk_users;";
$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();

/* Setup page vars for display. */
/*if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;*/
$targetpage = "users.php";    //your file name  (the name of this file)
$breadcrumb=array(
    array('title'=>'Διαχείριση Χρηστών','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Διαχείριση Χρηστών
                <a class="btn btn-primary btn-sm pull-right" href="new-user.php">Νέος Χρήστης</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <form action="users.php" method="get">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th><a href="users.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                        <th><a href="users.php?sortby=first_name&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Όνομα</a></th>
                        <th><a href="users.php?sortby=last_name&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Επώνυμο</a></th>
                        <th><a href="users.php?sortby=aem&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">AEM</a></th>
                        <th><a href="users.php?sortby=email&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Email</a></th>
                        <th><a href="users.php?sortby=username&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Username</a></th>
                        <th><a href="users.php?sortby=telephone&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Κινητό</a></th>
                        <th><a href="users.php?sortby=type&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τύπος Χρήστη</a></th>
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
                        <td><input type="text" class="form-control" placeholder="Επίπεδο Χρήστη" name="type" id="type"/></td>

                        <td>
                            <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                        </td>

                    </tr>
                    </thead>';

                    $addtosql = "";

                    $onoma = isset($_REQUEST['first_name']) ? filter_var($_REQUEST['first_name'], FILTER_SANITIZE_STRING) : '';
                    $epwnymo = isset($_REQUEST['last_name']) ? filter_var($_REQUEST['last_name'], FILTER_SANITIZE_STRING) : '';
                    $aem = isset($_REQUEST['aem']) ? filter_var($_REQUEST['aem'], FILTER_SANITIZE_STRING) : '';
                    $email = isset($_REQUEST['email']) ? filter_var($_REQUEST['email'], FILTER_SANITIZE_EMAIL) : '';
                    $username = isset($_REQUEST['username']) ? filter_var($_REQUEST['username'], FILTER_SANITIZE_STRING) : '';
                    $kinito = isset($_REQUEST['v']) ? filter_var($_REQUEST['telephone'], FILTER_SANITIZE_STRING) : '';
                    $type = isset($_REQUEST['type']) ? filter_var($_REQUEST['type'], FILTER_SANITIZE_STRING) : '';

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
                    if (!empty($type)) {
                        $addtosql .= " AND type LIKE '%$type%'";
                    }

                    //Παίρνουμε όλους τους χρήστες
                    $stmt = $dbh->prepare("SELECT * FROM dk_users WHERE 1 $addtosql $sortby $sorthow LIMIT $start,$limit");
                    $stmt->execute();
                    $users = $stmt->fetchAll();
                    $stmt = $dbh->prepare('UPDATE dk_users SET type = :type where id = :id');
            $params = array(':type' => $type, ':id' => $id);
            $stmt->execute($params);

                    foreach ($users as $user) {

                        echo '<tr>
                              <td>' . $user->id . '</td>
                              <td>' . $user->first_name . '</td>
                              <td>' . $user->last_name . '</td>
                              <td>' . $user->aem . '</td>
                              <td>' . $user->email . '</td>
                              <td>' . $user->username . '</td>
                              <td>' . $user->telephone . '</td>
                              <td>' . $user->type . '</td>
                              <td><a class="btn btn-sm btn-success" href="edit_user.php?id=' . $user->id . '">
                                <span class="fa fa-pencil" aria-hidden="true"></span></a> <a class="btn btn-sm btn-danger" href="users.php?del=' . $user->id . '"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>

                          </tr>';
                    }

                echo '</table>
            </form>';

            // http://aspektas.com/blog/really-simple-php-pagination/
            // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
            pagination($total_pages, $_GET, $targetpage);
            // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================

        echo '</div>
    </div>
</div>';

get_footer();
?>