<?php
include_once "includes/init.php";
get_header();


if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);

    //Έλεγχος στην DB αν υπάρχει κάποιος χρήστης με αυτά τα στοιχεία και επιβεβαιωμένο email.
    $stmt = $dbh->prepare('SELECT * FROM dk_users WHERE username = :username OR email = :email');
    $params = array(':username' => $username, ':email' => $email);
    $stmt->execute($params);

    // Ελέγχουμε αν υπάρχει ο χρήστης με το ίδιο username και κωδικό
    $total = $stmt->rowCount();
    if ($total >= 1) {
        echo "<div class='alert alert-danger'>Υπάρχει ήδη κάποιος χρήστης με αυτά τα στοιχεία. Παρακαλούμε προσθέστε νέα στοιχεία.</div>";
    } else {
        $password = sanitize($_POST['password']);
        $type = 1;
        $stmt = $dbh->prepare("INSERT INTO dk_users SET username=:username, email=:email, password=:password, type=:type");
        $params = array(':username' => $username, ':email' => $email, ':password' => md5($password), ':type' => $type);
        $stmt->execute($params);

        echo "<div class='alert alert-success'>Ο χρήστης δημιουργήθηκε.</div>";
    }
}
echo '<br /><br />
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-4 col-md-6 col-sm-12 col-lg-offset-4 col-md-offset-3">
            <div class="box">

                <img class="profile-img-card" src="//ssl.gstatic.com/accounts/ui/avatar_2x.png">
                <h4 class="text-sm-center">Εγγραφή Χρήστη</h4>
                <p class="profile-name-card"></p>
                <form class="form-signin" action="" method="post">
                    <span class="reauth-email"></span>
                    <div class="form-group">
                        <input class="form-control" placeholder="Όνομα Χρήστη" name="username" required="" autofocus="" type="text">
                    </div>
                    <div class="form-group">
                        <input class="form-control" placeholder="Email" name="email" required="" autofocus="" type="text">
                    </div>
                    <div class="form-group">
                        <input class="form-control" placeholder="Κωδικός" required="" name="password" type="password">
                    </div>
                    <button class="btn btn-primary btn-block btn-signin" type="submit">Δημιουρία</button>
                </form>
            </div>
        </div>
    </div>
</div>';

get_footer();
?>
