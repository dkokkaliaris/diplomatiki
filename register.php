<?php
include_once "includes/init.php";
get_header();
?>

<?php
if ($_SERVER['REQUEST_METHOD']=="POST") {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);

    //Έλεγχος στην DB αν υπάρχει κάποιος χρήστης με αυτά τα στοιχεία και επιβεβαιωμένο email.
    $stmt = $dbh->prepare('SELECT * FROM dk_users WHERE username = :username OR email = :email');
    $params=array(':username'=>$username,':email'=> $email);
    $stmt->execute($params);

    // Ελέγχουμε αν υπάρχει ο χρήστης με το ίδιο username και κωδικό
    $total = $stmt->rowCount();
    if ($total>=1) {
        echo "<div class='alert alert-danger'>Υπάρχει ήδη κάποιος χρήστης με αυτά τα στοιχεία. Παρακαλούμε προσθέστε νέα στοιχεία.</div>";
    }else {
        $password = sanitize($_POST['password']);
        $lvl = 1;
        $stmt = $dbh->prepare("INSERT INTO dk_users SET username=:username, email=:email, password=:password, lvl=:lvl");
        $params=array(':username'=>$username,':email'=> $email,':password'=> md5($password),':lvl'=> $lvl);
        $stmt->execute($params);

        echo "<div class='alert alert-success'>Ο χρήστης δημιουργήθηκε.</div>";
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
            <span   class="reauth-email"></span>
            <div class="form-group">
            <input class="form-control" placeholder="Όνομα Χρήστη" name="username" required="" autofocus="" type="text">
            </div>
             <div class="form-group">
            <input class="form-control" placeholder="Email" name="email" required="" autofocus="" type="text">
            </div>
            <div class="form-group">
            <input class="form-control" placeholder="Κωδικός" required="" name="password" type="password">
            </div>
            <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">Δημιουρία</button>
        </form>
    </div>
</div>
</div>
</div>

<?php
get_footer();
?>
