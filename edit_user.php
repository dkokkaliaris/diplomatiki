<?php
include_once "includes/init.php";
get_header();
?>

<?php
$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $type = sanitize($_POST['type']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $username = sanitize($_POST['username']);
    $aem = sanitize($_POST['aem']);
    $email = sanitize($_POST['email']);
    $telephone = sanitize($_POST['telephone']);

    $stmt = $dbh->prepare('UPDATE dk_users SET type = :type, first_name = :first_name, last_name = :last_name, username = :username, aem = :aem, email = :email, telephone = :telephone where id = :id');
    $params = array(':type' => $type, ':first_name' => $first_name, ':last_name' => $last_name, ':username' => $username, ':aem' => $aem, ':email' => $email, ':telephone' => $telephone, ':id' => $id);
    $stmt->execute($params);

    header("Location: users.php?a=1");
}

//Παίρνουμε όλους τους χρήστες
$stmt = $dbh->prepare("SELECT * FROM dk_users where id = $id;");
$stmt->execute();
$user = $stmt->fetchObject();

?>

    <div class="container">
        <div class="col-sm-3">
            <?php include "sidebar.php"; ?>
        </div>
        <div class="col-sm-9">
            <div class="row">
                <div class="col-sm-8">
                    <h2>Επεξεργασία Χρηστών</h2>
                </div>
            </div>
            <hr/>
            <div class="row col-sm-9">
                <form action="edit_user.php?id=<?php echo $id ?>" method="post">
                    <label for="first_name" class="form-control-label">Όνομα: </label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user->first_name ?>"/>

                    <label for="last_name" class="form-control-label">Επώνυμο: </label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user->last_name ?>"/>

                    <label for="username" class="form-control-label">Username: </label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $user->username ?>"/>

                    <label for="aem" class="form-control-label">ΑΕΜ: </label>
                    <input type="text" class="form-control" id="aem" name="aem" value="<?php echo $user->aem ?>"/>

                    <label for="email" class="form-control-label">Email: </label>
                    <input type="text" class="form-control" id="email" name="email" value="<?php echo $user->email ?>"/>

                    <label for="telephone" class="form-control-label">Τηλέφωνο: </label>
                    <input type="text" class="form-control" id="telephone" name="telephone" value="<?php echo $user->telephone ?>"/>

                    <label for="type" class="form-control-label">Τύπος Χρήστη:</label><br/>
                    <select name="type" id="type"
                            class="form-control type" style="width: auto;">
                        <option value="0" <?php if ($user->type == 0) echo 'selected' ?>>Φοιτητής</option>
                        <option value="1" <?php if ($user->type == 1) echo 'selected' ?>>Διαχειριστής</option>
                        <option value="2" <?php if ($user->type == 2) echo 'selected' ?>>ΟΜ.Ε.Α.</option>
                        <option value="3" <?php if ($user->type == 3) echo 'selected' ?>>Καθηγητής</option>
                    </select>

                    <br/><br/>
                    <div class="row">
                        <div class="col-sm-12">
                            <button class="btn btn-primary " type="submit">Αποθήκευση</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

<?php
get_footer();
?>
