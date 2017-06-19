<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
$alert = '';
// σε περίπτωση που θέλω να διαγράψω ένα μάθημα, παίρνω από το URL
// το action και το ID του και τρέχω το query.
if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $continue = false;
    if($_SESSION['level']!=1){// αν δεν είναι διαχειριστής ελέγχω να δω αν έχει πρόσβαση στο ερωτηματολόγιο
        $params = array(':id' => $id, ':u_id'=>$_SESSION['userid']);
        $sql = 'SELECT COUNT(*) as count FROM dk_lessons WHERE id = :id AND user_id = :u_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $flag = $stmt->fetchObject();
        if($flag->count>0){//
            $continue = true;
        }
    }else $continue = true;
    if($continue){
        $id = sanitize($_GET['id']);
        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_lessons WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $alert .= "<div class='alert alert-success'>Η διαγραφή του εκπαιδευτικού προγράμματος πραγματοποιήθηκε με επιτυχία.</div>";
    }else{
        $alert .= "<div class='alert alert-danger'>Δεν έχετε δικαίωμα να διαγράψετε το εκπαιδευτικό πρόγραμμα.</div>";
    }

}
if (isset($_GET['status']) && sanitize($_GET['status']) == 1) {
    $alert .= "<div class='alert alert-success'>Η δημιουργία του εκπαιδευτικού προγράμματος πραγματοποιήθηκε με επιτυχία.</div>";
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

$targetpage = "lessons.php";    //your file name  (the name of this file)
$addtosql = "";
$id = isset($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
$title = isset($_REQUEST['title']) ? filter_var($_REQUEST['title'], FILTER_SANITIZE_STRING) : '';
$username = isset($_REQUEST['username']) ? filter_var($_REQUEST['username'], FILTER_SANITIZE_STRING) : '';
$department = isset($_REQUEST['department']) ? filter_var($_REQUEST['department'], FILTER_SANITIZE_STRING) : '';

if (!empty($id)) {
    $addtosql .= " AND A.id LIKE '%$id%'";
}
if (!empty($title)) {
    $addtosql .= " AND A.title LIKE '%$title%'";
}
if (!empty($username)) {
    $addtosql .= " AND (B.username LIKE '%$username%' OR B.first_name LIKE '%$username%' OR B.last_name LIKE '%$username%')";
}
if (!empty($department)) {
    $addtosql .= " AND C.name LIKE '%$department%'";
}

// φέρνω όλα τα μαθήματα
if ($_SESSION['level'] == 3){
    $params = array(':id' => $_SESSION['userid']);
    $sql = "SELECT A.*,B.first_name,B.last_name, C.name FROM dk_lessons A JOIN dk_users B ON A.user_id=B.id JOIN dk_departments C ON A.department_id=C.id where user_id = :id $addtosql $sortby $sorthow  LIMIT $start,$limit;";
}else{
    $params = array();
    $sql = "SELECT A.*,B.first_name,B.last_name, C.name FROM dk_lessons A JOIN dk_users B ON A.user_id=B.id JOIN dk_departments C ON A.department_id=C.id  $addtosql $sortby $sorthow LIMIT $start,$limit;";
}
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();

// ================================== TO echo ξεκινάει εδώ ============================================
get_header();

$breadcrumb=array(
    array('title'=>'Εκπαιδευτικά Προγράμματα','href'=>'')
);

echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <h3>Εκπαιδευτικά Προγράμματα
                <a class="btn btn-primary btn-sm pull-right" href="add_lesson.php">Προσθήκη Νέου Εκπαιδευτικού Προγράμματος</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <form action="lessons.php" method="get">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><a href="lessons.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                            <th><a href="lessons.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a></th>
                            <th><a href="lessons.php?sortby=username&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Επιβλέπων Καθηγητής</a></th>
                            <th><a href="lessons.php?sortby=department_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τμήμα</a></th>
                            <th>Ενέργειες</th>
                        </tr>
                        <tr>
                            <td><input type="text" class="form-control" placeholder="ID" name="id" id="id" value="'.$id.'"/></td>
                            <td><input type="text" class="form-control" placeholder="Τίτλος" name="title" id="title" value="'.$title.'"/></td>
                            <td><input type="text" class="form-control" placeholder="Επιβλέπων Καθηγητής" name="username" id="username" value="'.$username.'"/></td>
                            <td><input type="text" class="form-control" placeholder="Τμήμα" name="department" id="department" value="'.$department.'"/></td>
                            <td>
                                <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                            </td>

                        </tr>
                    </thead>
                    <tbody>';
                    foreach ($results as $result) {
                        echo '
                        <tr>
                            <th scope="row">'.$result->id.'</th>
                            <td>'.$result->title.'</td>
                            <td>'.$result->first_name.' '.$result->last_name.' </td>
                            <td>'.$result->name.'</td>
                            <td><a data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '.$result->title.'" class="btn btn-sm btn-danger" onclick=\'return confirm("Είστε σίγουρος ότι θέλετε να διαγράψετε το μάθημα '.$result->title.';")\' href="lessons.php?action=delete&id='.$result->id.'"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>
                        </tr>';
                    }
                    echo'
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">';

        pagination($total_pages, $_GET, $targetpage);
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
        echo '
        </div>
    </div>
</div>';

get_footer();
?>
