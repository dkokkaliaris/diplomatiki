<?php
include_once "includes/init.php";
get_header();
?>
<div class="container">
<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    //Έλεγχος στην DB αν υπάρχει κάποιος χρήστης με αυτά τα στοιχεία και επιβεβαιωμένο email.
    $params = array(':username' => $username, ':password' => md5($password));
    $sql = 'SELECT * FROM dk_users WHERE username = :username AND password = :password ';
    $stmt = $dbh->prepare($sql);
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
        echo "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Τα στοιχεία που εισάγατε είναι λάθος ή δεν έχετε επιβεβαιώσει το email σας.</div></div></div>";
    }
}
echo '<br />
<br />
        <div class="row">
             <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
                <div class="box fixed-height">
                    <div class="form-group">
                        <h3>Είσοδος</h3>
                        <label class="form-control-label" for="login-selection"></label>
                        <select id="login-selection" class="form-control type">
                            <option value="id-1" selected="">Σύνδεση μέσω του λογαριασμού στο arch.icte.uowm.gr</option>
                            <option value="id-2">Σύνδεση μέσω του ιδρυματικού λογαριασμού (Χρήση SSO)</option>
                            <option value="id-3">Σύνδεση για Ανώνυμη Αξιολόγηση</option>
                            <option value="id-4">Σύνδεση με χρήση κωδικού Token</option>
                            <option value="id-5">Σύνδεση μέσω Εφαρμογής API</option>
                        </select>
                    </div>
                    <div class="login-container" id="id-1">
                        <br/>
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
                            <button class="btn btn-sm btn-primary btn-block btn-signin" type="submit">Είσοδος</button>
                        </form>
                    </div>
                    <div class="login-container hide" id="id-2"><br/>
                        <p>Για να μεταβείτε στην σελίδα με χρήση των ιδρυματικών κωδικών, παρακαλούμε πατήστε <a href="'.BASE_URL.'sso/home.php">εδώ</a>.</p>
                    </div>
                    <div class="login-container hide" id="id-3"><br/>
                        <p>Για να μεταβείτε στην σελίδα ανώνυμης αξιολόγησης, παρακαλούμε πατήστε <a href="anonymous_questionnaires.php">εδώ</a>.</p>
                    </div>
                    <div class="login-container hide" id="id-4"><br/>
                        <p>Παρακαλούμε συμπληρώστε στο παρακάτω πεδίο της φόρμας τον μοναδικό κωδικό token που λάβατε από τον υπεύθυνο του εκπαιδευτικού προγράμματος που θέλετε να αξιολογήσετε.</p>
                        <form action="find_questionnaire_fromtoken.php" method="post">
                            <div class="form-group">
                                <input class="form-control" placeholder="Κωδικός Token" name="token" type="text">
                            </div>
                            <button class="btn btn-sm btn-primary btn-block" type="submit">Είσοδος</button>
                        </form>
                    </div>
                    <div class="login-container hide" id="id-5"><br/>
                        <h5>Σύνδεση μέσω Εφαρμογής API</h5>
                        <p>Για να μεταβείτε στην σελίδα αξιολόγησης με χρήση API, παρακαλούμε πατήστε <a href="anonymous_questionnaires.php">εδώ</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>';
?>
<script>
    jQuery(document).ready(function () {
        //όταν αλλάξω την επιλογή εισόδου απο την λιστα τοτε παιρνει την τιμη της επιλογης που εμείς βαλαμε το id του κουτιου
        jQuery('#login-selection').on('change', function () {

            //κρύβει ολα τα div (κουτια) με την κλαση login-container
            jQuery('.login-container').hide();
            //εμφανίζει μονο το div που εχει το id που επιλεχθηκε.
            jQuery('#'+jQuery('#login-selection').val()).fadeIn();
        });
    });
</script>

<?php
get_footer();
?>
