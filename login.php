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
        echo "<div class='alert alert-danger'>Τα στοιχεία που έχετε εισάγει είναι λάθος ή δεν έχετε επιβεβαιώσει το email σας.</div>";
    }
}
?>

<div class="container">
  <div class="row">
    <div class="col-md-12">

    </br>

    <h3>Για να συνδεθείτε στο σύστημα, παρακαλούμε επιλέξτε έναν από τους παρακάτω διαθέσιμους τρόπους:
    </h3>

    </br>
    </br>

    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingOne">
          <h4 class="panel-title">
            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">1) Σύνδεση στο σύστημα με χρήση του ιδρυματικού κωδικού (Σύνδεση με SSO)
            </a>
          </h4>
        </div>
    	
        <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">

        <div class="panel-body"> Για να συνδεθείτε στο σύστημα μέσω των ιδρυματικών κωδικών σας ως εγγεγραμμένος χρήστης, παρακαλούμε συμπληρώστε τα παρακάτω πεδία της φόρμας. Αυτός ο τρόπος σύνδεσης προτείνεται σε εγγεγραμμένους χρήστες όλων των επιπέδων (δηλαδή φοιτητές, καθηγητές, ΟΜ.Ε.Α. και διαχειριστές) που διαθέτουν ακαδημαϊκό λογαριασμό.
        <p class="profile-name-card"></p>

        <form class="form-signin" action="" method="post">
          <span class="reauth-email"></span>
            <div class="form-group">
              <input class="form-control" placeholder="Όνομα Χρήστη (Username)" name="username" required="" autofocus="" type="text">
            </div>

            <div class="form-group">
              <input class="form-control" placeholder="Κωδικός Πρόσβασης (Password)" required="" name="password" type="password">
            </div>
            <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">Είσοδος</button>
        </form>

          <a href="#" class="forgot-password">Ξεχάσατε τον κωδικό σας;</a><br/>
          <a href="register.php" class="forgot-password">Δημιουργία Λογαριασμού</a>
      </div>
    </div>
  </div>

<br/>
<br/>

<div class="panel panel-default">
  <div class="panel-heading" role="tab" id="headingTwo">
    <h4 class="panel-title">
      <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">2) Σύνδεση στο σύστημα με χρήση των στοιχείων του πληροφοριακού συστήματος Εργαστηρίου Ψηφιακών Συστημάτων και Αρχιτεκτονικής Υπολογιστών (arch.icte.uowm.gr)
      </a>
    </h4>
  </div>

  <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
    <div class="panel-body"> Για να συνδεθείτε στο σύστημα μέσω των κωδικών του Εργαστηρίου Ψηφιακών Συστημάτων και Αρχιτεκτονικής Υπολογιστών (arch.icte.uowm.gr), παρακαλούμε συμπληρώστε τα παρακάτω πεδία της φόρμας, δηλαδή όνομα χρήστη και κωδικό πρόσβασης. Ο συγκεκριμένος τρόπος σύνδεσης προτείνεται σε εγγεγραμμένους χρήστες όλων των επιπέδων (δηλαδή φοιτητές, καθηγητές, ΟΜ.Ε.Α. και διαχειριστές).

    <p class="profile-name-card"></p>
      <form class="form-signin" action="" method="post">
        <span class="reauth-email"></span>
          <div class="form-group">
            <input class="form-control" placeholder="Όνομα Χρήστη (Username)" name="username" required="" autofocus="" type="text">
          </div>

          <div class="form-group">
            <input class="form-control" placeholder="Κωδικός Πρόσβασης (Password)" required="" name="password" type="password">
          </div>

          <button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">Είσοδος</button>
      </form>

      <a href="#" class="forgot-password">Ξεχάσατε τον κωδικό σας;</a><br/>
      <a href="register.php" class="forgot-password">Δημιουργία Λογαριασμού</a>
    </div>
  </div>
</div>

<br/>
<br/>

<div class="panel panel-default">
  <div class="panel-heading" role="tab" id="headingThree">
    <h4 class="panel-title">
      <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">3) Σύνδεση στο σύστημα με χρήση ενός μοναδικού κωδικού Token
      </a>
    </h4>
  </div>

  <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
    <div class="panel-body"> Μέσω της καταχώρησης ενός μοναδικού κωδικού token, ένας χρήστης μπορεί να συνδεθεί στο σύστημα και να αξιολογήσει ένα και μοναδικό εκπαιδευτικό πρόγραμμα. Ο συγκεκριμένος τρόπος σύνδεσης προτείνεται σε μη εγγεγραμμένους χρήστες που έχουν παραλάβει έναν κωδικό token από τον υπεύθυνο του εκπαιδευτικού προγράμματος που παρακολούθησαν.

      <form action="find_questionnaire_fromtoken.php" method="post">
        <div class="form-group">
          <input class="form-control" placeholder="Μοναδικός Κωδικός Token" name="token" type="text">
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit">Είσοδος</button>
      </form>
    </div>
  </div>
</div>

<br/>
<br/>

<div class="panel panel-default">
  <div class="panel-heading" role="tab" id="headingFour">
    <h4 class="panel-title">
      <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFour" aria-expanded="false" aria-controls="collapseFour">4) Είσοδος στο σύστημα για ανώνυμη αξιολόγηση
      </a>
    </h4>
  </div>

  <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
    <div class="panel-body">Για να μεταβείτε στην σελίδα ανώνυμης αξιολόγησης, παρακαλούμε πατήστε <a href="anonymous_questionnaires.php">εδώ.</a>
    </div>
  </div>
</div>

<br/>
<br/>

<div class="panel panel-default">
  <div class="panel-heading" role="tab" id="headingFive">
    <h4 class="panel-title">
      <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseFive" aria-expanded="false" aria-controls="collapseFive">5) Σύνδεση στο σύστημα για αξιολόγηση με χρήση API
      </a>
    </h4>
  </div>

  <div id="collapseFive" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFive">
    <div class="panel-body">Για να μεταβείτε στην σελίδα της αξιολόγησης με χρήση API, παρακαλούμε πατήστε <a href="anonymous_questionnaires.php">εδώ.</a>
    </div> 
  </div>
 </div>
</div>

<?php
get_footer();
?>
