<?php
  include_once "../includes/init.php";
  //καλώ 2 αρχεια για το sso
  include_once('CAS.php');
  include_once('cas_config.php');
  phpCAS::client($cas_protocol, $cas_sso_server, $cas_port, '');
  phpCAS::setCasServerCACert($cas_cert);
  phpCAS::handleLogoutRequests(true ,array($cas_sso_server));
  phpCAS::forceAuthentication();
  if (isset($_REQUEST['logout'])) {
    phpCAS::logout(array("service"=>$cas_logout_app_redirect_url));
  }


//stoixeia tautopoihshs
//Η συναρτηση getAttributes αποθηκευει στο array user όλα τα στοιχεια του χρηστη απο το sso.
$user = phpCAS::getAttributes();
//κρατάω 3 πεδία.
$username = $user['mail'];
$email = $user['mail'];

if ($user['GUStudentID']) {
    $aem = $user['GUStudentID'];
}else {
    $aem = "";
}

//έλεγχος αν το email είναι αποθηκευμενο στην βαση
$params = array(':email' => $user['mail']);
$sql = 'SELECT count(*) as email_counter FROM dk_users WHERE email = :email;';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$row2=$stmt->fetch();

if ($row2->email_counter==0) {//Αν δεν υπαρχει ο χρηστης στην βαση τον προσθετω.

    //Δημιουργώ έναν τυχαίο κωδικό για να τον προσθεσω στην βαση για να μην ειναι κενο το πεδιο password.
    $password = md5(randomPassword());
    // ελέγχει τι τυπος ειναι ο χρηστης και αν ειναι καθηγητης βάζει το 3 αλλιώς το 4 για φοιτητη
    if ($user['eduPersonAffiliation']=='faculty') {
        $type = 3;
    }else {
        $type = 4;
    }
    $activated = 1;
    //πα'ιρνω το επιθετο και το ονομα του χρηστη
    $last_name = $user['sn'];
    $first_name = $user['givenName'];
    $telephone = '';

    //βαζω στην βαση τα στοιχεια.
    $params = array(':username' => $username, ':email' => $email, ':password' => $password, ':type' => $type, ':activated' => $activated, ':last_name' => $last_name, ':first_name' => $first_name, ':telephone' => $telephone, ':aem' => $aem, ':ip' => $_SERVER['REMOTE_ADDR']);

    $sql = 'INSERT INTO dk_users (username, email, password, type, activated, last_name, first_name, telephone, aem, ip, user_type) VALUES (:username, :email, :password, :type, :activated, :last_name, :first_name, :telephone, :aem, :ip, "sso")';

    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
}

//αν υπαρχει ο χρηστης παρε τα στοιχεια και μην τον καταχωρησεις στην βαση
//παιρνει τα στοιχεια του
$params = array(':username' => $username, ':email' => $email, ':aem' => $aem );
$sql = 'SELECT * FROM dk_users WHERE username = :username AND email = :email AND aem = :aem ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$user = $stmt->fetchObject();
$total = $stmt->rowCount();


//ΕΠΙΤΥΧΙΑ LOGIN: αν βρεθεί ο χρήστης (ένας χρήστης) αποθήκευσε το στο SESSION και κάνε login.
if ($total == 1) {
    $_SESSION['userid'] = sanitize($user->id);
    $_SESSION['username'] = sanitize($user->username);
    $_SESSION['level'] = sanitize($user->type);
    header("Location: ../index.php");
    exit; //ΑΠΟΤΥΧΙΑ LOGIN: Αν δεν βρεθεί κάποιος χρήστης.
}
  ?>