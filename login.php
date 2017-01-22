<?php
include_once "includes/init.php";
get_header();
?>

<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    //Έλεγχος στην DB αν υπάρχει κάποιος χρήστης με αυτά τα στοιχεία και επιβεβαιωμένο email.
    $stmt = $dbh->prepare('SELECT * FROM dk_users WHERE username = :username AND password = :password ');
    $params = array(':username' => $username, ':password' => md5($password));
    $stmt->execute($params);

    $user = $stmt->fetchObject();
    $total = $stmt->rowCount();
    //ΕΠΙΤΥΧΙΑ LOGIN: αν βρεθεί ο χρήστης (ένας χρήστης) αποθήκευσε το στο SESSION και κάνε login.
    if ($total == 1) {
        $_SESSION['userid'] = sanitize($user->id);
        $_SESSION['username'] = sanitize($user->username);
        $_SESSION['level'] = sanitize($user->type);
        header("Location: /questionnaire/index.php");
        exit;

        //ΑΠΟΤΥΧΙΑ LOGIN: Αν δεν βρεθεί κάποιος χρήστης.
    } else {
        echo "<div class='alert alert-danger'>Τα στοιχεία που εισάγατε είναι λάθος ή δεν έχετε επιβεβαιώσει το email σας.</div>";
    }
}
?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">

                <div class="card-container">
                    <img class="profile-img-card" src="//ssl.gstatic.com/accounts/ui/avatar_2x.png">
                    <p class="profile-name-card"></p>
                    <form class="form-signin" action="" method="post">
                        <span class="reauth-email"></span>
                        <div class="form-group">
                            <input class="form-control" placeholder="Όνομα Χρήστη" name="username" required="" autofocus="" type="text">
                        </div>
                        <div class="form-group">
                            <input class="form-control" placeholder="Κωδικός" required="" name="password" type="password">
                        </div>
                        <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">Σύνδεση</button>
                    </form>
                    <a href="#" class="forgot-password">Ξεχάσατε τον κωδικό σας;</a><br/>
                    <a href="/questionnaire/register.php" class="forgot-password">Δημιουργία Λογαριασμού</a>
                </div>

                <div class="card-container">
                    Αξιολόγηση με χρήση token
                    <form action="find_questionnaire_fromtoken.php" method="post">
                        <div class="form-group">
                            <input class="form-control" placeholder="Token" name="token" type="text">
                        </div>
                        <button class="btn btn-lg btn-primary btn-block" type="submit">Αξιολόγηση</button>
                    </form>
                </div>

                <div class="card-container">
                    <a href="/questionnaire/anonymous_questionnaires.php">Ανώνυμη Αξιολόγηση</a>
                </div>

            </div>
        </div>
    </div>

<?php
get_footer();
?>
