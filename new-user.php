<?php
include_once "includes/init.php";
get_header();

echo '<div class="container-fluid">
<div class="row breadcrumb">
    <div class="col-sm-12">
    <a href="index.php">Αρχική Σελίδα</a> &gt; <a href="users.php">Διαχείριση Χρηστών</a> &gt; Νέος Χρήστης
    </div>
</div>
<div class="row">
<div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
<div class="box">';

if ($_SERVER['REQUEST_METHOD']=="POST") {
    $onoma=filter_var($_POST['onoma'], FILTER_SANITIZE_STRING);
    $epwnymo=filter_var($_POST['epwnymo'], FILTER_SANITIZE_STRING);
    $email=filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $username=filter_var($_POST['username'], FILTER_SANITIZE_STRING);

    $password=filter_var($_POST['password'], FILTER_SANITIZE_STRING);
    $password_c=filter_var($_POST['password_c'], FILTER_SANITIZE_STRING);

    // Ελέγχω αν το username υπάρχει
    $query="SELECT count(*) as user_counter FROM dk_users WHERE username='$username'";
    $result=$mysqli->query($query);
    $row=$result->fetch_assoc();

    // Ελέγχω αν το email υπάρχει
    $query="SELECT count(*) as email_counter FROM dk_users WHERE email='$email'";
    $result2=$mysqli->query($query);
    $row2=$result2->fetch_assoc();

    // Ελέγχω αν ο χρήστης συμπλήρωσε όλα τα πεδία
    if (empty($onoma) ||empty($epwnymo) || empty($email) || empty($username)) {
        echo "<div class='alert alert-danger'>Έχετε αφήσει κενά πεδία στην φόρμα.</div>";
    // Ελέγχω αν οι κωδικοί ταιριάζουν
    }else if ($password!=$password_c) {
        echo "<div class='alert alert-danger'>Οι κωδικοί δεν ταιρίαζουν.</div>";
    // ελέγχω αν έχει δοθεί έγγυρη διεύθυνση email
    }else if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        echo "<div class='alert alert-danger'>Το email δεν είναι έγκυρο.</div>";
    // ελέγχω για την μοναδικότητα του username
    }else if ($row['user_counter']>0) {
        echo "<div class='alert alert-danger'>Το όνομα χρήστη χρησιμοποιείται ήδη.</div>";
    // ελέγχω για την μοναδικότητα του email
    }else if ($row2['email_counter']>0) {
        echo "<div class='alert alert-danger'>Το email χρησιμοποιείται.</div>";
    }
}

echo '<h3>Νέος Χρήστης</h3>


<p>Παρακαλούμε συμπληρώστε την φόρμα με τα στοιχεία σας, ώστε να γίνει η εγγραφή.</p>
<form action="new-user.php" method="post">
<label for="onoma">Όνομα (*)</label>
<input type="text" name="onoma" id="onoma" value="'.$_POST['onoma'].'" class="form-control" />
<br />
<label for="epwnymo">Επώνυμο (*)</label>
<input type="text" name="epwnymo" id="epwnymo" value="'.$_POST['epwnymo'].'" class="form-control" />
<br />
<label for="email">Email (*)</label>
<input type="text" name="email" id="email" value="'.$_POST['email'].'" class="form-control" />
<br />
<label for="username">Username (*)</label>
<input type="text" name="username" id="username" value="'.$_POST['username'].'" class="form-control" />
<br />
<label for="password">Κωδικός (*)</label>
<input type="password" name="password" id="password" class="form-control" />
<br />
<label for="password_c">Επιβεβαίωση κωδικού (*)</label>
<input type="password" name="password_c" id="password_c" class="form-control" />
<br />
<button class="btn btn-sm btn-primary btn-block" type="submit">Εγγραφή</button>
</form>
<small><em> Τα πεδία με (*) είναι υποχρεωτικά.</em></small>
</div>
</div>
</div>
</div>';
include("footer.php");
?>