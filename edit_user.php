<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}else{
    if($_SESSION['level']>=2){
       header("Location: ".BASE_URL.'index.php');
        die();
    }
}
get_header();

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $type = sanitize($_POST['type']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $username = sanitize($_POST['username']);
    $aem = sanitize($_POST['aem']);
    $email = sanitize($_POST['email']);
    $telephone = sanitize($_POST['telephone']);

    $params = array(':type' => $type, ':first_name' => $first_name, ':last_name' => $last_name, ':username' => $username, ':aem' => $aem, ':email' => $email, ':telephone' => $telephone, ':id' => $id);
    $sql = 'UPDATE dk_users SET type = :type, first_name = :first_name, last_name = :last_name, username = :username, aem = :aem, email = :email, telephone = :telephone where id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);

    header("Location: users.php?a=1");
}

//Παίρνουμε όλους τους χρήστες
$params = array(':id' => $id);
$sql = "SELECT * FROM dk_users where id = :id;";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$user = $stmt->fetchObject();
$breadcrumb=array(
    array('title'=>'Διαχείριση Χρηστών','href'=>'users.php'),
    array('title'=>'Επεξεργασία Χρηστών','href'=>''),
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
            <div class="box">
                <div class="row">
                    <div class="col-sm-12">
                        <h3>Επεξεργασία Χρηστών</h3>
                    </div>
                </div>
                <hr/>
                <div class="row">
                    <div class="col-sm-12">
                        <form action="edit_user.php?id='.$id.'" method="post">
                            <label for="first_name" class="form-control-label">Όνομα: </label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="'.$user->first_name.'"/>

                            <label for="last_name" class="form-control-label">Επώνυμο: </label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="'.$user->last_name.'"/>

                            <label for="username" class="form-control-label">Username: </label>
                            <input type="text" class="form-control" id="username" name="username" value="'.$user->username.'"/>

                            <label for="aem" class="form-control-label">ΑΕΜ: </label>
                            <input type="text" class="form-control" id="aem" name="aem" value="'.$user->aem.'"/>

                            <label for="email" class="form-control-label">Email: </label>
                            <input type="text" class="form-control" id="email" name="email" value="'.$user->email.'"/>

                            <label for="telephone" class="form-control-label">Τηλέφωνο: </label>
                            <input type="text" class="form-control" id="telephone" name="telephone" value="'.$user->telephone.'"/>

                            <label for="type" class="form-control-label">Τύπος Χρήστη:</label><br/>
                            <select name="type" id="type"
                                    class="form-control type" style="width: auto;">
                                <option value="0"'.($user->type == 0?'selected':'').'>Φοιτητής</option>
                                <option value="1"'.($user->type == 1?'selected':'').'>Διαχειριστής</option>
                                <option value="2"'.($user->type == 2?'selected':'').'>ΟΜ.Ε.Α.</option>
                                <option value="3"'.($user->type == 3?'selected':'').'>Καθηγητής</option>
                            </select>

                            <br/>
                            <div class="row">
                                <div class="col-sm-12">
                                    <button class="btn btn-sm btn-primary btn-block" type="submit">Αποθήκευση</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

get_footer();
?>