<?php
include_once "includes/init.php";
$id = $_GET['id'];
$params = array(':id' => $id, ':user_id' => $_SESSION['userid']);
$sql = "SELECT * FROM dk_answers where questionnaire_id = :id AND user_id = :user_id";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$continue = $stmt->fetchAll();
if(sizeof($continue)>0){
    get_header();
    $breadcrumb=array(
    array('title'=>'Αξιολόγηση Ερωτηματολογίου','href'=>''),
);
    echo '<div class="container-fluid" xmlns="http://www.w3.org/1999/html">
        '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">
                <h3>Αξιολόγηση Ερωτηματολογίου</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <p>Έχετε ήδη αξιολογήσει το συγκεκριμένο ερωτηματολόγιο. Παρακαλούμε επιστρέψτε στην αρχική σελίδα.<br /></p>
                <a href="evaluation.php" class="btn btn-primary">Επιστροφή</a>
            </div>
        </div>
    </div>';
}else{
$alert = '';
$id = $_GET['id'];
// φέρνω το ερωτηματολόγιο
$params = array(':id' => $id);
$sql = "SELECT * FROM dk_questionnaire where id = :id";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchObject();

$params = array(':id' => $result->user_id);//διδάσκων
$sql = 'SELECT first_name, last_name FROM dk_users WHERE id = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$user = $stmt->fetchObject();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $params = array(':id' => $id);
    $sql = "SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    $requiredFields = true;
    foreach ($results as $q) {

        $params = array(':id' => $q->question_id);
        $sql = "SELECT * FROM dk_question where id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $questionData = $stmt->fetchObject();

        if ($questionData->type == 'file') {
            if (!is_uploaded_file($_FILES['question-' . $q->question_id]['tmp_name'])) {
                $alert .= "<div class='alert alert-danger'>Παρακαλούμε συμπληρώστε όλα τα πεδία της φόρμας.</div>";
                $requiredFields = false;
                break;
            }
        } else {
            if (!isset($_POST['question-' . $q->question_id]) || $_POST['question-' . $q->question_id] == '') {
                $alert .= "<div class='alert alert-danger'>Θα πρέπει να συμπληρώσετε όλα τα πεδία της φόρμας.</div>";
                $requiredFields = false;
                break;
            }
        }
    }

    if ($requiredFields) {
        foreach ($results as $q) {

            $params = array(':id' => $q->question_id);
            $sql = "SELECT * FROM dk_question where id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $questionData = $stmt->fetchObject();
            if ($questionData->type == 'file') {
                $target_dir = "uploads/";
                $fileArray = (explode(".", $_FILES['question-' . $q->question_id]['name']));
                $fileName = random_string(10) . '.' . $fileArray[1];
                $target_file = $target_dir . $fileName;
                move_uploaded_file($_FILES['question-' . $q->question_id]["tmp_name"], $target_file);

                $date = date('Y-m-d H:i:s');

                $params = array(':questionnaire_id' => $id, ':question_id' => $q->question_id, ':user_id' => session_status() == PHP_SESSION_ACTIVE ? $_SESSION['userid'] : 0, ':time' => $date, ':type' => $questionData->type, ':filename' => $fileName, ':hashname' => md5($fileName));
                $sql = 'INSERT INTO dk_answers (questionnaire_id, question_id, user_id, time, type, filename, hashname) VALUES (:questionnaire_id, :question_id, :user_id, :time, :type, :filename, :hashname)';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
            } else {
                if (isset($_POST['question-' . $q->question_id]) && $_POST['question-' . $q->question_id] != '') {
                    $answer = $_POST['question-' . $q->question_id];

                    $date = date('Y-m-d H:i:s');
                    if(is_array($answer)){
                        foreach($answer as $a){
                            $params = array(':questionnaire_id' => $id, ':question_id' => $q->question_id, ':answer' => $a, ':user_id' => session_status() == PHP_SESSION_ACTIVE ? $_SESSION['userid'] : 0, ':time' => $date, ':type' => $questionData->type);
                            $sql = 'INSERT INTO dk_answers (questionnaire_id, question_id, answer, user_id, time, type) VALUES (:questionnaire_id, :question_id, :answer, :user_id, :time, :type)';
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute($params);
                        }

                    }else{
                        $params = array(':questionnaire_id' => $id, ':question_id' => $q->question_id, ':answer' => $answer, ':user_id' => session_status() == PHP_SESSION_ACTIVE ? $_SESSION['userid'] : 0, ':time' => $date, ':type' => $questionData->type);
                        $sql = 'INSERT INTO dk_answers (questionnaire_id, question_id, answer, user_id, time, type) VALUES (:questionnaire_id, :question_id, :answer, :user_id, :time, :type)';
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                    }
                }
            }
        }

        if (isset($_GET['token'])) {
            $token = $_GET['token'];
            $start = substr($token, 0, 6);
            $end = substr($token, 6);
            $params = array(':start' => $start, ':end' => $end);
            $sql = "UPDATE dk_tokens SET used = 1 where seira = :start and token_code = :end;";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        }

        header("Location: thank_you_page.php");
    }
}
get_header();
$breadcrumb=array(
    array('title'=>'Αξιολόγηση Ερωτηματολογίου','href'=>''),
);
echo '<div class="container-fluid" xmlns="http://www.w3.org/1999/html">
        '.show_breacrumb($breadcrumb).'
        <div class="row plaisio">
			<div align="center">
				<div class="col-sm-12">'.$alert .'
					<h4>Αξιολόγηση Ερωτηματολογίου '.$result->title.'</h4>
				</div>
				<p>Υπεύθυνος Εκπ. Προγράμματος: '.$user->first_name.' '.$user->last_name.'</p> 
				<p>Περιγραφή Εκπ. Προγράμματος: '.$result->description.'</p>
				<p>Ημερομηνία Έναρξης: '.$result->time_begins.'</p>
				<p>Ημερομηνία Λήξης: '.$result->time_ends.'</p>
			</div>
		</div>
		
        <div class="header-row">
            <div class="container">
                <div class="row">
                    <div class="col-xs-6">Ερώτηση</div>
                    <div class="col-xs-6">Απαντήσεις</div>
                </div>
            </div>
        </div>
        <div class="table-charts">';
            if (isset($_GET['token'])) {
                $url = 'evaluate_questionnaire.php?id=' . $id . '&token=' . $_GET['token'];
            }else{
                $url = 'evaluate_questionnaire.php?id=' . $id;
            }
            echo '<form action="'.$url.'" method="post" enctype="multipart/form-data">';
                $questionNo = 0;
                // φέρνω τις ερωτήσεις του
                $params = array(':id' => $id);
                $sql = "SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id order by questionnaire_id";
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll();

                foreach ($results as $q) {
                    $params = array(':id' => $q->question_id);
                    $sql = "SELECT * FROM dk_question where id = :id";
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute($params);
                    $questionData = $stmt->fetchObject();

                    $params = array(':id' => $q->question_id);
                    $sql = "SELECT * FROM dk_question_options where question_id = :id";
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute($params);
                    $questionOptions = $stmt->fetchAll();

                    echo '<div class="table-row">
                        <div class="row">
                            <div class="col-md-6 col-sm-12"><strong>'.++$questionNo . '. ' . $questionData->question.'</strong></div>
                            <div class="col-md-6 col-sm-12">';
                                if ($questionData->type == 'radio') {
                                    foreach ($questionOptions as $questionOption) {
                                       echo'<p><label><input type="radio" name="question-' . $q->question_id.'" value="'.$questionOption->pick.'">'.$questionOption->pick.'
                                        </label></p>';
                                    }
                                } else if ($questionData->type == 'check') {
                                    foreach ($questionOptions as $questionOption) {
                                       echo '<p><label><input type="checkbox" name="question-' . $q->question_id.'[]" value="'.$questionOption->pick.'">'.$questionOption->pick.'
                                        </label></p>';
                                    }
                                } else if ($questionData->type == 'freetext') {
                                    echo '<p><textarea rows="4" cols="50" name="question-' . $q->question_id.'"></textarea></p>';
                                } else {
                                    echo '<p><input type="file" name="question-' . $q->question_id.'" id="question-' . $q->question_id.'"></p>';
                                }
                            echo '</div>
                        </div>
                    </div>';
                }
                echo '<input type="submit" value="Καταχώρηση Απαντήσεων" name="submit" class="btn btn-success btn-sm" style="margin-bottom: 10px;">
            </form>
        </div>
    </div>';
}
get_footer();
?>
