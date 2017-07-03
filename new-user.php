<?php
	include_once "includes/init.php";
	if (!is_logged_in()) {
		header("Location: ".BASE_URL.'login.php');
		exit;
	}else{
		if ($_SESSION['level'] >= 2) {
			header("Location: ".BASE_URL.'index.php');
			exit;
		}
	}
    $alert = '';
	if ($_SERVER['REQUEST_METHOD']=="POST") {
		$onoma=(isset($_POST['onoma'])?sanitize($_POST['onoma']):'');
		$epwnymo=(isset($_POST['epwnymo'])?sanitize($_POST['epwnymo']):'');
		$email=(isset($_POST['email'])?sanitize($_POST['email']):'');
		$username=(isset($_POST['username'])?sanitize($_POST['username']):'');
		$type=(isset($_POST['type'])?sanitize($_POST['type']):'');

		$password=(isset($_POST['password'])?sanitize($_POST['password']):'');
		$password_c=(isset($_POST['password_c'])?sanitize($_POST['password_c']):'');
		$user_type = (isset($_POST['user_type'])?sanitize($_POST['user_type']):'');

		// Ελέγχω αν το username υπάρχει
		$stmt = $dbh->prepare('SELECT count(*) as user_counter FROM dk_users WHERE username = :username;');
		$params = array(':username' => $username);
		$stmt->execute($params);
		$row=$stmt->fetch();

		// Ελέγχω αν το email υπάρχει
		$stmt = $dbh->prepare('SELECT count(*) as email_counter FROM dk_users WHERE email = :email;');
		$params = array(':email' => $email);
		$stmt->execute($params);
		$row2=$stmt->fetch();

		$flag = true;
		$alert = '';

		// Ελέγχω αν ο χρήστης συμπλήρωσε όλα τα πεδία
		if (empty($onoma) ||empty($epwnymo) || empty($email) || empty($username)) {
			$alert .= "<div class='alert alert-danger'>Έχετε αφήσει κενά πεδία στην φόρμα.</div>";
			$flag = false;

		}

		// Ελέγχω αν οι κωδικοί ταιριάζουν
		if ($password!=$password_c) {
			$alert .= "<div class='alert alert-danger'>Οι κωδικοί δεν ταιρίαζουν.</div>";
			$flag = false;
		}

		// ελέγχω αν έχει δοθεί έγγυρη διεύθυνση email
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			$alert .= "<div class='alert alert-danger'>Το email δεν είναι έγκυρο.</div>";
			$flag = false;
		}

		// ελέγχω για την μοναδικότητα του username
		if ($row->user_counter>0) {
			$alert .= "<div class='alert alert-danger'>Το όνομα χρήστη χρησιμοποιείται ήδη.</div>";
			$flag = false;
		}

		// ελέγχω για την μοναδικότητα του email
		if ($row2->email_counter>0) {
			$alert .= "<div class='alert alert-danger'>Το email χρησιμοποιείται.</div>";
			$flag = false;
		}

		if($flag){
			$params = array(':username' => $username, ':email' => $email, ':password' => md5($password), ':type' => $type, ':activated' => 0, ':last_name' => $epwnymo, ':first_name' => $onoma, ':user_type'=>$user_type);
			print_r($params);
			$sql = 'INSERT INTO dk_users (username, email, password, type, activated, last_name, first_name, ip, user_type) VALUES (:username, :email, :password, :type, :activated, :last_name, :first_name, "", :user_type)';
			$stmt = $dbh->prepare($sql);
			$stmt->execute($params);
			header("Location: ".BASE_URL.'users.php?a=2');
			exit;
		}
	}

	get_header();
	$breadcrumb=array(
		array('title'=>'Διαχείριση Χρηστών','href'=>'users.php'),
		array('title'=>'Νέος Χρήστης','href'=>''),
	);
	echo '<div class="container-fluid">
	'.show_breacrumb($breadcrumb).'

	<div class="row">
		<div class="col-lg-6 col-md-8 col-sm-12 col-lg-offset-3 col-md-offset-2">
			<div class="box">';

				echo '<h3>Νέος Χρήστης</h3>
				'.$alert.'

				<form action="new-user.php" method="post" novalidate="" id="new_user_form">
				<label for="onoma">Όνομα (*)</label>
				<input type="text" name="onoma" id="onoma" value="'.(isset($_POST['onoma'])?sanitize($_POST['onoma']):'').'" class="form-control" required="" />
				<br />

				<label for="epwnymo">Επώνυμο (*)</label>
				<input type="text" name="epwnymo" id="epwnymo" value="'.(isset($_POST['epwnymo'])?sanitize($_POST['epwnymo']):'').'" class="form-control" required="" />
				<br />

				<label for="email">Email (*)</label>
				<input type="text" name="email" id="email" value="'.(isset($_POST['email'])?sanitize($_POST['email']):'').'" class="form-control" required="" />
				<br />
				<label for="email">Επίπεδο Χρήστη (*)</label>';
                $type_item = (isset($_POST['type'])?sanitize($_POST['type']):'');
                echo '<select name="type" id="type" class="form-control" required="" >
                    <option value="">Επιλογή Επιπέδου</option>
					<option value=1 '.($type_item==1?'selected':'').'>Διαχειριστής</option>
					<option value=2 '.($type_item==2?'selected':'').'>ΟΜΕΑ</option>
					<option value=3 '.($type_item==3?'selected':'').'>Καθηγητής</option>
					<option value=4 '.($type_item==4?'selected':'').'>Φοιτητής</option>
				</select>
				<br />

				<label for="email">Κατηγορία Κωδικών Χρήστη (*)</label>
				<select name="user_type" id="user_type" class="form-control" required="" >
                    <option value="">Επιλογή Κατηγορίας</option>
					<option value="icte" '.(isset($_POST['user_type']) && $_POST['user_type']=="icte"?'selected':'').'>Χρήστης με κωδικούς του arch.icte.uowm.gr</option>
					<option value="sso" '.(isset($_POST['user_type']) && $_POST['user_type']=="sso"?'selected':'').'>Χρήστης με ιδρυματικούς κωδικούς</option>
				</select>
				<br />

				<label for="username">Username (*)</label>
				<input type="text" name="username" id="username" value="'.(isset($_POST['username'])?sanitize($_POST['username']):'').'" class="form-control" required="" />
				<br />

				<label for="password">Κωδικός (*)</label>
				<input type="password" name="password" id="password" class="form-control" required="" />
				<br />

				<label for="password_c">Επιβεβαίωση Κωδικού (*)</label>
				<input type="password" name="password_c" id="password_c" class="form-control"required=""  />
				<br />

				<button class="btn btn-sm btn-primary btn-block" type="submit">Εγγραφή</button>
				</form>

				<small><em> Τα πεδία με (*) είναι υποχρεωτικά.</em></small>
			</div>
		</div>
	</div>
</div>';
?>

<script>
    jQuery(document).ready(function () {
        jQuery('#new_user_form').validate();
    });
</script>

<?php
	get_footer();
?>