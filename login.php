<?php
include_once "includes/init.php";
get_header();
$alert = '';
echo '<div class="container">';
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
        $_SESSION['full_name'] = sanitize($user->last_name." ".$user->first_name);
        $_SESSION['level'] = sanitize($user->type);
        header("Location: index.php");
        exit;

    //ΑΠΟΤΥΧΙΑ LOGIN: Αν δεν βρεθεί κάποιος χρήστης.
    } else {
        echo "<div class='row'><div class='col-sm-12'><div class='alert alert-danger'>Τα στοιχεία που συμπληρώσατε είναι λάθος. Παρακαλούμε δοκιμάστε ξανά.</div></div></div>";
    }
}

echo '<br />
        <div class="row">
             <div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
                <div class="box absolute-height">
                    <div class="form-group">
                        <h4>Είσοδος</h4>
                        <label class="form-control-label" for="login-selection"></label>
                        <select id="login-selection" class="form-control type">
                            <option value="id-1" selected="">Είσοδος στο σύστημα με χρήση των κωδικών του arch.icte.uowm.gr</option>
                            <option value="id-2">Είσοδος στο σύστημα με χρήση των ιδρυματικών κωδικών</option>
                            <option value="id-3">Είσοδος στο σύστημα για υποβολή ανώνυμων αξιολογήσεων</option>
                            <option value="id-4">Είσοδος στο σύστημα με χρήση κωδικού token</option>
                        </select>
                    </div>

                    <div class="login-container" id="id-1"><br/>
						<h5>Είσοδος στο σύστημα με χρήση των κωδικών του arch.icte.uowm.gr</h5>
                        <p>Για να συνδεθείτε στο πληροφοριακό σύστημα αξιολόγησης σεμιναρίων και μαθημάτων χρησιμοποιώντας τους κωδικούς από το εργαστήριο ψηφιακών συστημάτων και αρχιτεκτονικής υπολογιστών (arch.icte.uowm.gr), παρακαλούμε συμπληρώστε τα αντίστοιχα πεδία στην παρακάτω φόρμα.</p>
                        <p class="profile-name-card"></p>
                        <form class="form-signin" id="login_form" action="" method="post" novalidate="">
                            <span class="reauth-email"></span>
                            <div class="form-group">
                                <input class="form-control" placeholder="Όνομα Χρήστη (Username)" name="username" required="" autofocus="" type="text" required="">
                            </div>
                            <div class="form-group">
                                <input class="form-control" placeholder="Κωδικός Πρόσβασης (Password)" required="" name="password" type="password" required="">
                            </div>
                            <button class="btn btn-sm btn-primary btn-block btn-signin" type="submit">Είσοδος</button>
                        </form>
                    </div>

                    <div class="login-container hide" id="id-2"><br/>
						<h5>Είσοδος στο σύστημα με χρήση των ιδρυματικών κωδικών</h5>
                        <p>Για να συνδεθείτε στο πληροφοριακό σύστημα αξιολόγησης σεμιναρίων και μαθημάτων χρησιμοποιώντας τους ιδρυματικούς κωδικούς σας, παρακαλούμε πατήστε <a href="'.BASE_URL.'sso/home.php">εδώ</a>.</p>
                    </div>

                    <div class="login-container hide" id="id-3"><br/>
						<h5>Είσοδος στο σύστημα για υποβολή ανώνυμων αξιολογήσεων</h5>
                        <p>Για να συνδεθείτε στο πληροφοριακό σύστημα αξιολόγησης σεμιναρίων και μαθημάτων για υποβολή ανώνυμων αξιολογήσεων, παρακαλούμε πατήστε <a href="anonymous_questionnaires_warning.php">εδώ</a>.</p>
                    </div>

                    <div class="login-container hide" id="id-4"><br/>
						<h5>Είσοδος στο σύστημα με χρήση κωδικού token</h5>
                        <p>Για να συνδεθείτε στο πληροφοριακό σύστημα αξιολόγησης σεμιναρίων και μαθημάτων χρησιμοποιώντας τον μοναδικό κωδικό Token που λάβατε από τον υπεύθυνο του εκπαιδευτικού προγράμματος, παρακαλούμε συμπληρώστε την παρακάτω φόρμα.</p>
                        <form action="find_questionnaire_fromtoken.php" method="post" novalidate="" id="token_form">
                            <div class="form-group">
                                <input class="form-control" placeholder="Κωδικός Token" name="token" type="text" required="">
                            </div>
                            <button class="btn btn-sm btn-primary btn-block" type="submit">Είσοδος</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>';
?>

<script>
    jQuery(document).ready(function () {
        //όταν αλλάξω την επιλογή εισόδου απο την λιστα τοτε παιρνει την τιμη της επιλογης που εμείς βαλαμε το id του κουτιου
        //σε περίπτωση που παει πισω να του εμφανίσει τη επιλογή
        jQuery('.login-container').hide();
        //εμφανίζει μονο το div που εχει το id που επιλεχθηκε.
        jQuery('#'+jQuery('#login-selection').val()).fadeIn();
        jQuery('#login-selection').on('change', function () {

            //κρύβει ολα τα div (κουτια) με την κλαση login-container
            jQuery('.login-container').hide();
            //εμφανίζει μονο το div που εχει το id που επιλεχθηκε.
            jQuery('#'+jQuery('#login-selection').val()).fadeIn();
        });
        jQuery('#login_form').validate();
        jQuery('#token_form').validate();
    });
</script>

<?php
get_footer();
?>
