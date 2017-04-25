<?php
include_once "includes/init.php";
get_header();

$ip = $_SERVER['REMOTE_ADDR'];

$id = $_GET['id'];

// ελέγχω αν ο χρήστης έχει ξανακάνει αξιολόγιση το συγκεκριμένο ερωτηματολόγιο, και αν ναι αν περασε μιση ωρα απο την τελευταία φορά
$stmt = $dbh->prepare("SELECT * FROM dk_ips where ip = :ip and questionnaire_id = :id");
$params = array(':ip'=>$ip,':id' => $id);
$stmt->execute($params);
$row = $stmt->fetchObject();

$available = true;
if ($row != null) {
    $before = $row->timestamp;
    $today = date("Y-m-d H:i:s");

    $diff =  strtotime($today) - strtotime($before);

    if ($diff < 1800 /* 30 lepta * 60 deuterolepta*/) {
        $available = false;
    }
}

function random_string($length)
{
    $key = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));

    for ($i = 0; $i < $length; $i++) {
        $key .= $keys[array_rand($keys)];
    }

    return $key;
}

// φέρνω το ερωτηματολόγιο
$stmt = $dbh->prepare("SELECT * FROM dk_questionnaire where id = :id");
$params = array(':id' => $id);
$stmt->execute($params);
$result = $stmt->fetchObject();

if ($_SERVER['REQUEST_METHOD'] == "POST" && $available) {

    $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id");
    $params = array(':id' => $id);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    $requiredFields = true;
    foreach ($results as $q) {

        $stmt = $dbh->prepare("SELECT * FROM dk_question where id = :id");
        $params = array(':id' => $q->question_id);
        $stmt->execute($params);
        $questionData = $stmt->fetchObject();

        if ($questionData->type == 'file') {
            if (!is_uploaded_file($_FILES['question-' . $q->question_id]['tmp_name'])) {
                echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
                $requiredFields = false;
                break;
            }
        } else {
            if (!isset($_POST['question-' . $q->question_id]) || $_POST['question-' . $q->question_id] == '') {
                echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
                $requiredFields = false;
                break;
            }
        }
    }

    if ($requiredFields) {
        foreach ($results as $q) {

            $stmt = $dbh->prepare("SELECT * FROM dk_question where id = :id");
            $params = array(':id' => $q->question_id);
            $stmt->execute($params);
            $questionData = $stmt->fetchObject();
            if ($questionData->type == 'file') {
                $target_dir = "uploads/";
                $fileArray = (explode(".", $_FILES['question-' . $q->question_id]['name']));
                $fileName = random_string(10) . '.' . $fileArray[1];
                $target_file = $target_dir . $fileName;
                move_uploaded_file($_FILES['question-' . $q->question_id]["tmp_name"], $target_file);

                $date = date('Y-m-d H:i:s');

                $stmt = $dbh->prepare('INSERT INTO dk_answers (questionnaire_id, question_id, user_id, time, type, filename, hashname) VALUES (:questionnaire_id, :question_id, :user_id, :time, :type, :filename, :hashname)');
                $params = array(':questionnaire_id' => $id, ':question_id' => $q->question_id, ':user_id' => session_status() == PHP_SESSION_ACTIVE ? $_SESSION['userid'] : 0, ':time' => $date, ':type' => $questionData->type, ':filename' => $fileName, ':hashname' => md5($fileName));
                $stmt->execute($params);
            } else {
                if (isset($_POST['question-' . $q->question_id]) && $_POST['question-' . $q->question_id] != '') {
                    $answer = $_POST['question-' . $q->question_id];

                    $date = date('Y-m-d H:i:s');

                    $stmt = $dbh->prepare('INSERT INTO dk_answers (questionnaire_id, question_id, answer, user_id, time, type) VALUES (:questionnaire_id, :question_id, :answer, :user_id, :time, :type)');
                    $params = array(':questionnaire_id' => $id, ':question_id' => $q->question_id, ':answer' => $answer, ':user_id' => session_status() == PHP_SESSION_ACTIVE ? $_SESSION['userid'] : 0, ':time' => $date, ':type' => $questionData->type);
                    $stmt->execute($params);
                }
            }
        }

        $stmt = $dbh->prepare("SELECT * FROM dk_ips where ip = :ip and questionnaire_id = :id");
        $params = array(':ip'=>$ip,':id' => $id);
        $stmt->execute($params);
        $ipsResult = $stmt->fetchObject();


        if ($ipsResult == null) {
            $stmt = $dbh->prepare('INSERT INTO dk_ips (ip, timestamp, questionnaire_id) VALUES (:ip, :timestamp, :questionnaire_id)');
            $params = array(':ip' => $ip, ':timestamp' => date('Y-m-d H:i:s'), ':questionnaire_id' => $id);
            $stmt->execute($params);
        } else {
            $stmt = $dbh->prepare('UPDATE dk_ips SET timestamp =  :timestamp');
            $params = array(':timestamp' => date('Y-m-d H:i:s'));
            $stmt->execute($params);
        }

        header("Location: thank_you_page.php");
    }
}
$breadcrumb=array(
    array('title'=>'Ανώνυμη αξιολόγηση Ερωτηματολογίου','href'=>'')
);

echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">
                <h3>'.$result->title.'</h3>
            </div>
        </div>';

    echo '<div class="row">
            <div class="col-sm-12">';

                if ($available) {
                    echo '<form action="anonymous_evaluate_questionnaire.php?id='.$id.'" method="post" enctype="multipart/form-data">';
                        $questionNo = 0;
                        // φέρνω τις ερωτήσεις του
                        $stmt = $dbh->prepare("SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id order by order_by");
                        $params = array(':id' => $id);
                        $stmt->execute($params);
                        $results = $stmt->fetchAll();

                        foreach ($results as $q) {

                            $stmt = $dbh->prepare("SELECT * FROM dk_question where id = :id");
                            $params = array(':id' => $q->question_id);
                            $stmt->execute($params);
                            $questionData = $stmt->fetchObject();

                            $stmt = $dbh->prepare("SELECT * FROM dk_question_options where question_id = :id");
                            $params = array(':id' => $q->question_id);
                            $stmt->execute($params);
                            $questionOptions = $stmt->fetchAll();


                            echo '<p>'. ++$questionNo . '. ' . $questionData->question.'</p>';

                            echo '<br>
                            <p>';
                                if ($questionData->type == 'radio') {
                                    foreach ($questionOptions as $questionOption) {
                                        echo '<label><input type="radio" name="question-' . $q->question_id.'" value="'.$questionOption->pick.'">'.$questionOption->pick.'
                                        </label>';
                                    }
                                } else if ($questionData->type == 'check') {
                                    foreach ($questionOptions as $questionOption) {
                                        echo '<label><input type="checkbox" name="question-' . $q->question_id.'" value="'.$questionOption->pick.'">'.$questionOption->pick.'
                                        </label>';
                                    }
                                } else if ($questionData->type == 'freetext') {
                                    echo '<textarea rows="4" cols="50" name="question-' . $q->question_id.'"></textarea>';
                                } else {
                                    echo '<input type="file" name="question-' . $q->question_id.'" id="question-' . $q->question_id .'">';
                                }
                            echo '</p><hr/>';
                        }
                        echo '<input type="submit" value="Καταχώρηση απαντήσεων" name="submit" class="btn btn-success btn-sm" style="margin-bottom: 10px;">
                    </form>';
                } else {
                    echo '<h4>Πρέπει να περάσουν 30 λεπτά από την προηγούμενη αξιολόγιση.</h4>';
                }
        echo '</div>
    </div>
</div>';

get_footer();
?>
