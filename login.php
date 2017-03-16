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
        header("Location: index.php");
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



                <div class="col-sm-4">
                        <div class="card-container">
                            <h5>Σύνδεση μέσω του λογαριασμού στο arch.icte.uowm.gr</h5>
                            <p>Παρακαλούμε συμπληρώστε στα παρακάτω πεδία της φόρμας τους κωδικούς που διαθέτετε στο πληροφοριακό σύστημα του εργαστηρίου ψηφιακών συστημάτων και αρχιτεκτονικής υπολογιστών (arch.icte.uowm.gr).</p>
                            <p class="profile-name-card"></p>
                            <form class="form-signin" action="" method="post">
                                <span class="reauth-email"></span>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Όνομα Χρήστη (Username)" name="username" required="" autofocus="" type="text">
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Κωδικός (Password)" required="" name="password" type="password">
                                </div>
                                <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">Είσοδος</button>
                            </form>
                        </div>
                </div>

                <div class="col-sm-4">
                        <div class="card-container">
                            <h5>Σύνδεση μέσω του ιδρυματικού λογαριασμού (Χρήση SSO)</h5>
                            <p>Για να μεταβείτε στην σελίδα με χρήση των ιδρυματικών κωδικών, παρακαλούμε πατήστε <a href="anonymous_questionnaires.php">εδώ</a>.</p>
                        </div>

                        <div class="card-container">
                            <h5>Σύνδεση για Ανώνυμη Αξιολόγηση</h5>
                            <p>Για να μεταβείτε στην σελίδα ανώνυμης αξιολόγησης, παρακαλούμε πατήστε <a href="anonymous_questionnaires.php">εδώ</a>.</p>
                        </div>
                </div>


                <div class="col-sm-4">
                        <div class="card-container">
                            <h5>Σύνδεση με χρήση κωδικού Token</h5>
                            <p>Παρακαλούμε συμπληρώστε στο παρακάτω πεδίο της φόρμας τον μοναδικό κωδικό token που λάβατε από τον υπεύθυνο του εκπαιδευτικού προγράμματος που θέλετε να αξιολογήσετε.</p>
                            <form action="find_questionnaire_fromtoken.php" method="post">
                                <div class="form-group">
                                    <input class="form-control" placeholder="Κωδικός Token" name="token" type="text">
                                </div>
                                <button class="btn btn-lg btn-primary btn-block" type="submit">Είσοδος</button>
                            </form>
                        </div>

                        <div class="card-container">
                            <h5>Σύνδεση μέσω Εφαρμογής API</h5>
                            <p>Για να μεταβείτε στην σελίδα αξιολόγησης με χρήση API, παρακαλούμε πατήστε <a href="anonymous_questionnaires.php">εδώ</a>.</p>
                        </div>
                </div>


            </div>
        </div>
    </div>

<?php
get_footer();
?>
