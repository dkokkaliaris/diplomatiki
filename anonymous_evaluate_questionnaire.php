<?php
include_once "includes/init.php";

$ip = $_SERVER['REMOTE_ADDR'];

$id = $_GET['id'];
$alert = '';
// ελέγχω αν ο χρήστης έχει ξανακάνει αξιολόγηση το συγκεκριμένο ερωτηματολόγιο, και αν ναι αν περασε μιση ωρα απο την τελευταία φορά
$params = array(':ip'=>$ip,':id' => $id);
$sql = "SELECT * FROM dk_ips where ip = :ip and questionnaire_id = :id";
$stmt = $dbh->prepare($sql);
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

// φέρνω το ερωτηματολόγιο
$params = array(':id' => $id);
$sql = "SELECT * FROM dk_questionnaire where id = :id";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchObject();

//Αν μπορω να αξιολογήσω το ερωτηματολογιο...
if ($_SERVER['REQUEST_METHOD'] == "POST" && $available) {

    $params = array(':id' => $id);
    $sql = "SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    $requiredFields = true;
    //Αποθηκεύω τα αποτελέσματα που συμπληρωσε ο χρηστης.
    foreach ($results as $q) {

        $params = array(':id' => $q->question_id);
        $sql = "SELECT * FROM dk_question where id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $questionData = $stmt->fetchObject();

        if ($questionData->type == 'file') {
            //Αν δεν ανεβασε σε κάποια ερωτηση αρχειο τοτε εμφ. το alert.
            if (!is_uploaded_file($_FILES['question-' . $q->question_id]['tmp_name'])) {
                $alert = "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
                $requiredFields = false;
                break;
            }
        } else {
            //Αν δεν ειναι τυπου αρχειου και ειναι κενο alert.
            if (!isset($_POST['question-' . $q->question_id]) || $_POST['question-' . $q->question_id] == '') {
                $alert = "<div class='alert alert-danger'>Παρακαλούμε συμπληρώστε όλα τα πεδία της φόρμας.</div>";
                $requiredFields = false;
                break;
            }
        }
    }

    //Αν εχει συμπληρωσει όλα τα πεδια της φορμας...
    if ($requiredFields) {
        foreach ($results as $q) {
            //για καθε απαντηση που θα δωσει βαζει στην βαση.
            $params = array(':id' => $q->question_id);
            $sql = "SELECT * FROM dk_question where id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $questionData = $stmt->fetchObject();
            //αν ειναι ερωτηση αρχειου τον κωδικα τον πηρα απο το pdf των οδηγιων.
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
            //αν δεν ειναι ερωτηση αρτχειου...
            } else {
                if (isset($_POST['question-' . $q->question_id]) && $_POST['question-' . $q->question_id] != '') {
                    $answer = $_POST['question-' . $q->question_id];

                    $date = date('Y-m-d H:i:s');

                    if(is_array($answer)){
                        foreach($answer as $a){
                            $params = array(':questionnaire_id' => $id, ':question_id' => $q->question_id, ':answer' => $answer, ':user_id' => session_status() == PHP_SESSION_ACTIVE ? $_SESSION['userid'] : 0, ':time' => $date, ':type' => $questionData->type);
                            $sql = 'INSERT INTO dk_answers (questionnaire_id, question_id, answer, user_id, time, type) VALUES (:questionnaire_id, :question_id, :answer, :user_id, :time, :type)';
                            $stmt = $dbh->prepare($sql);
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

        //προσθετει στην βαση τις απαντησεις.
        $params = array(':ip'=>$ip,':id' => $id);
        $sql = "SELECT * FROM dk_ips where ip = :ip and questionnaire_id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $ipsResult = $stmt->fetchObject();

        if ($ipsResult == null) {
            $params = array(':ip' => $ip, ':timestamp' => date('Y-m-d H:i:s'), ':questionnaire_id' => $id);
            $sql = 'INSERT INTO dk_ips (ip, timestamp, questionnaire_id) VALUES (:ip, :timestamp, :questionnaire_id)';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        } else {
            $params = array(':timestamp' => date('Y-m-d H:i:s'));
            $sql = 'UPDATE dk_ips SET timestamp =  :timestamp';
            $stmt = $dbh->prepare();
            $stmt->execute($params);
        }
        header("Location: thank_you_page.php");
        exit;
    }
}
get_header();
$breadcrumb=array(
    array('title'=>'Ανώνυμη Αξιολόγηση Ερωτηματολογίου','href'=>'')
);

echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">'.$alert.'
                <h4>Ανώνυμη Αξιολόγηση Ερωτηματολογίου '.$result->title.'</h4>
            </div>
        </div>
        <div class="header-row">
            <div class="container">
                <div class="row">
                    <div class="col-xs-6">Ερώτηση</div>
                    <div class="col-xs-6">Πιθανές Απαντήσεις</div>
                </div>
            </div>
        </div>
        <div class="table-charts">';

            if ($available) {
                echo '<form action="anonymous_evaluate_questionnaire.php?id='.$id.'" method="post" enctype="multipart/form-data" novalidate="" id="anonymous_evaluate_form">';
                    $questionNo = 0;
                    // φέρνω τις ερωτήσεις του
                    $params = array(':id' => $id);
                    $sql = "SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id order by order_by";
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
                                <div class="col-md-6 col-sm-12"><strong>'. ++$questionNo . '. ' . $questionData->question.'</strong>
                                </div>
                                    <div class="col-md-6 col-sm-12">';
                                    if ($questionData->type == 'radio') {
                                        foreach ($questionOptions as $questionOption) {
                                            echo '<p><label><input type="radio" name="question-' . $q->question_id.'" value="'.$questionOption->pick.'"  required="">'.$questionOption->pick.'
                                            </label></p>';
                                        }
                                    } else if ($questionData->type == 'check') {
                                        foreach ($questionOptions as $questionOption) {
                                            echo '<p><label><input type="checkbox" name="question-' . $q->question_id.'" value="'.$questionOption->pick.'"  required="">'.$questionOption->pick.'
                                            </label></p>';
                                        }
                                    } else if ($questionData->type == 'freetext') {
                                        echo '<p><textarea rows="4" cols="50" name="question-' . $q->question_id.'"  required=""></textarea></p>';
                                    } else {
                                        echo '<p><input type="file" name="question-' . $q->question_id.'" id="question-' . $q->question_id .'"></p>';
                                    }
                                echo '</div>
                            </div>
                        </div>';
                    }
                    echo '<input type="submit" value="Καταχώρηση Απαντήσεων" name="submit" class="btn btn-success btn-sm" style="margin-bottom: 10px;">
                </form>';
            } else {
                echo '<h5>Για να αξιολογήσετε το ερωτηματολόγιο θα πρέπει να μεσολαβήσουν τουλάχιστον 30 λεπτά από την προηγούμενη σας αξιολόγηση.</h5>';
            }
    echo '</div>
</div>';
?>
<script>
    jQuery(document).ready(function () {
        jQuery('#anonymous_evaluate_form').validate();
    });
</script>
<?php
get_footer();
?>
