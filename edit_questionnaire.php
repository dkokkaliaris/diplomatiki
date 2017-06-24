<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();
$breadcrumb=array(
    array('title'=>'Ερωτηματολόγια','href'=>'questionnaires.php'),
    array('title'=>'Επεξεργασία Ερωτηματολογίου','href'=>''),
);
$id = sanitize($_GET['id']);
$params = array(':id' => $id);//ελεγχος αν το ερωτηματολογιο υπαρχει
$sql = 'SELECT COUNT(*) as count FROM dk_questionnaire WHERE id = :id';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$flag = $stmt->fetchObject();
if($flag->count>0){
$continue = false;
if($_SESSION['level']!=1 && $_SESSION['level']!=2){// αν δεν είναι διαχειριστής ή ΟΜΕΑ ελέγχω να δω αν έχει πρόσβαση στο ερωτηματολόγιο
    $params = array(':id' => $id, ':u_id'=>$_SESSION['userid']);
    $sql = 'SELECT COUNT(*) as count FROM dk_questionnaire WHERE id = :id AND user_id = :u_id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $flag = $stmt->fetchObject();
    if($flag->count>0){//
        $continue = true;
    }
}else $continue = true;
if(!$continue){
    echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Επεξεργασία Ερωτηματολογίου</h3>
            <div class="alert alert-danger">Δεν έχετε το δικαίωμα να διαχειριστείτε αυτό το ερωτηματολόγιο.</div>
        </div>
    </div>
</div>';
}else{
    $alert = '';
    if (isset($_GET['status']) && sanitize($_GET['status']) == 1) {
        $alert .= "<div class='alert alert-success'>Η δημιουργία του ερωτηματολογίου πραγματοποιήθηκε με επιτυχία.</div>";
    }
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $date_begins = sanitize($_POST['time_begins']);
    $date_ends = sanitize($_POST['time_ends']);
    $lesson = sanitize($_POST['lesson']);

    $lockTime = null;
    if (isset($_POST['lock']) && sanitize($_POST['lock']) == 0) {
        $lockTime = $_POST['locked_time'];
        $params = array(':title' => $title, ':description' => $description, ':date_begins' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_begins))), ':date_ends' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_ends))), ':last_edit_time' => date('Y-m-d H:i:s'), ':lesson_id' => $lesson, ':last_editor' => $_SESSION['userid'], ':id' => $id, ':lockedtime' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $lockTime))));
        $sql = 'UPDATE dk_questionnaire SET title = :title, description = :description, time_begins = :date_begins, time_ends = :date_ends, last_edit_time = :last_edit_time, lesson_id = :lesson_id, last_editor = :last_editor, lockedtime = :lockedtime WHERE id = :id';
        $stmt = $dbh->prepare($sql);
    } else {
        $params = array(':title' => $title, ':description' => $description, ':date_begins' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_begins))), ':date_ends' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $date_ends))), ':last_edit_time' => date('Y-m-d H:i:s'), ':lesson_id' => $lesson, ':last_editor' => $_SESSION['userid'], ':id' => $id, ':lockedtime' => $lockTime);
        $sql = 'UPDATE dk_questionnaire SET title = :title, description = :description, time_begins = :date_begins, time_ends = :date_ends, last_edit_time = :last_edit_time, lesson_id = :lesson_id, last_editor = :last_editor, lockedtime = :lockedtime WHERE id = :id';
        $stmt = $dbh->prepare($sql);
    }
    $stmt->execute($params);

    //remove unselected channels
    $params = array(':id' => $id);
    $sql = 'SELECT * FROM dk_questionnaire_channel WHERE id_questionnaire = :id ';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $c_list = $stmt->fetchALL();
    $channels_list = array();
    foreach ($c_list as $c) {
        $channels_list[] = $c->id_channel;
        if(!in_array($c->id_channel, $_POST['channel'])){
            $params = array(':id' => $c->id);
            $sql = 'DELETE FROM dk_questionnaire_channel WHERE id = :id';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        }
    }
    //insert new channels
    if(!empty($_POST['channel'])){
        foreach ($_POST['channel'] as $channel) {
            if(!in_array($channel, $channels_list)){
                $params = array(':id_questionnaire' => $id, ':id_channel' => $channel);
                $sql = 'INSERT INTO dk_questionnaire_channel (id_questionnaire, id_channel) VALUES (:id_questionnaire, :id_channel)';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
            }
        }
    }

    $alert .= "<div class='alert alert-success'>Η επεξεργασία του ερωτηματολογίου πραγματοποιήθηκε με επιτυχία.</div>";
}

$params = array(':id' => $id);
$sql = 'SELECT * FROM dk_questionnaire WHERE id = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchObject();
$total = $stmt->rowCount();
$params = array(':id' => $id);
$sql = 'SELECT dk_question.* FROM dk_question INNER JOIN dk_questionnaire_questions ON dk_questionnaire_questions.question_id=dk_question.id WHERE dk_questionnaire_questions.questionnaire_id = :id ORDER BY order_by';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$result_questions = $stmt->fetchALL();
$total_questions = $stmt->rowCount();

//Παίρνουμε τα κανάλια του ερωτηματολογίου
$params = array(':id' => $id);
$sql = 'SELECT id_channel FROM dk_questionnaire_channel WHERE id_questionnaire = :id ';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$c_list = $stmt->fetchALL();
$channels_list = [];
foreach ($c_list as $c) {
    $channels_list[] = $c->id_channel;
}

$questions = [];
foreach ($result_questions as $question) {
    $questions[] = $question->id;
}

$list_types = array('_'=>'','radio_text'=>'Ερώτηση Μοναδικής Επιλογής Κειμένου', 'check_text'=>'Ερώτηση Πολλαπλής Επιλογής Κειμένου','radio_number'=>'Ερώτηση Μοναδικής Επιλογής Αριθμού', 'check_number'=>'Ερώτηση Πολλαπλής Επιλογής Αριθμού', 'freetext_'=>'Ερώτηση Ελεύθερου Κειμένου', 'file_'=>'Ερώτηση Προσθήκης Αρχείου');

//Όταν πατήσω εισαγωγή ερώτησης ανοιγει το modal που είναι κρυφό στην σελίδα.
echo '<div id="simpleQuestion" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Εισαγωγή Νέας Ερώτησης</h4>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">

                    <!-- Ξεκινάει η φόρμα του modal που είναι για την απλή ερώτηση -->
                        <form id="content-questions" method="post" enctype="multipart/form-data" accept-charset="utf-8" novalidate="">
                            <div class="row">
                                <div class="col-sm-6">
                                    <label for="title-q" class="form-control-label">Ερώτηση: </label>
                                    <textarea rows="3" class="form-control" name="question_desc" id="title-q" required=""></textarea>
                                    <label class="form-control-label" for="type-q">Επιλογή Τύπου Ερώτησης: </label>
                                    <select class="form-control" id="type-q" required="">
                                        <option value="">Δυνατοί Τύποι Ερώτησης</option>
                                        <option value="text">Ερώτηση Πολλαπλής Επιλογής (Κειμένου)</option>
                                        <option value="number">Ερώτηση Πολλαπλής Επιλογής (Αριθμού)</option>
                                        <option value="freetext">Ερώτηση Ελεύθερου Κειμένου</option>
                                        <option value="file">Ερώτηση Προσθήκης Αρχείου</option>
                                    </select>
                                </div>

                                <!-- Ξεκινάει η δεξιά στήλη (με το κρυφό div). -->
                                <div class="col-sm-6">
                                    <div id="q-answers">
                                        <div class="hidden_block" id="choices_container">
                                            <h6>Πιθανές Απαντήσεις:</h6>
                                            <div class="form-group">
                                            <!-- Στην δεξιά στήλη υπάρχει η επιλογή αν η ερώτηση είναι select box ή check box. -->
                                                <select class="form-control" id="type-multi-q" style="display: none;">
                                                    <option value="">Τύπος Πιθανών Απαντήσεων</option>
                                                    <option value="radio">Ερώτηση Μοναδικής Επιλογής (Radio Button)</option>
                                                    <option value="check">Ερώτηση Πολλαπλής Επιλογής (Checkbox)</option>
                                                </select>
                                            </div>

                                            <!-- Δημιουργώ έναν πίνακα με δύο στήλες, με κουτί για πιθανή απάντηση και ένα κουμπί (-). -->
                                            <table id="choice_options">
                                                <tr>
                                                    <td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td>
                                                    <!-- Αναθέτω στο remove-obj mία ενέργεια στο onclick. -->
                                                    <td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td>
                                                </tr>
                                            </table>
                                            <!-- Εδώ προσθέτω πιθανές επιλογές. -->
                                            <div id="add_choice" class="add_new"><span class="fa fa-plus" aria-hidden="true"></span>
                                                Προσθήκη Νέας
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12 text-sm-right">
                                    <button id="add_simple" class="btn btn-primary btn-sm">Εισαγωγή</button>
                                </div>
                            </div>
                            <br/>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    echo '<div id="templateQuestion" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Εισαγωγή Πρότυπης Ερώτησης</h4>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-12">
                                <form id="template-questions" method="post">
                                    <!-- To template_questions θα περιέχει το array των ερωτήσεων που θα πατηθούν σε μορφή String για να το στείλουμε στο AJAX -->
                                    <input type="hidden" name="template_questions" class="template_questions"/>
                                    <input type="hidden" name="questionnaire_id" class="questionnaire_id" value="'.$id.'"/>

                                    <table class="table">
                                        <thead class="thead-inverse table-head">
                                            <tr>
                                                <th>Ερώτηση</th>
                                                <th>Τύπος</th>
                                                <th>Επιλογές</th>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="template_layout">';
                                            // Φέρουμε τις πρότυπες ερωτήσεις του χρήστη απο την βαση (έχουν template=1 στην βάση)
                                            if($_SESSION['level'] == 1){
                                                $params = array(':questionnaire_id' => $id);
                                                $sql = 'SELECT *FROM dk_question WHERE template = 1 AND id NOT IN (SELECT question_id FROM dk_questionnaire_questions WHERE questionnaire_id = :questionnaire_id)';
                                            }else{
                                                $params = array(':questionnaire_id' => $id, ':id' => $_SESSION['userid']);
                                                $sql = 'SELECT * FROM dk_question WHERE template = 1 AND id NOT IN (SELECT question_id FROM dk_questionnaire_questions WHERE questionnaire_id = :questionnaire_id) AND user_id = :id ';
                                            }
                                            $stmt = $dbh->prepare($sql);
                                            $stmt->execute($params);
                                            $results = $stmt->fetchAll();
                                            //κραταω και το πληθος
                                            $total = $stmt->rowCount();

                                            //Αφού πάρω τηις πρότυπες ερωτήσεις κανω ένα loop για να εκτυπώσω.'
                                            if ($total > 0) {
                                                // τυπώνω όλες τις γραμμές με τις ερωτήσεις και τα δεδομένα τους
                                                foreach ($results as $q) {
                                                    //if (!in_array($q->id, $quesArray)) {
                                                        echo' <tr style="padding: 15px;" class="selection" data-id="'.$q->id.'">

                                                            <td>'.$q->question.'</td>
                                                            <td>'.(array_key_exists($q->type.'_'.$q->multi_type, $list_types)?$list_types[$q->type.'_'.$q->multi_type]:"").'</td>
                                                            <td>';
                                                            //Αν η ερώτηση ειναι πολλαπλης φερνει τις πιθανες επιλογες της ερωτησης ενω αν ειναι τυπου σχολιο ερωτηση τοτε θα μεινει κενο το πεδιο.
                                                                $params = array(':id' => $q->id, ':user_id' =>  $_SESSION['userid']);
                                                                $sql = 'SELECT dk_question_options.pick FROM dk_question_options INNER JOIN dk_question ON dk_question.id=dk_question_options.question_id WHERE dk_question.id = :id and dk_question.user_id =  :user_id';
                                                                $stmt = $dbh->prepare($sql);
                                                                $stmt->execute($params);
                                                                $options = $stmt->fetchALL();
                                                                $i = 0;
                                                                foreach ($options as $op) {
                                                                    echo $op->pick;
                                                                    if ($i < sizeof($options) - 1) {
                                                                        echo ", ";
                                                                    }
                                                                    $i++;
                                                                }
                                                            echo '</td>
                                                        </tr>';
                                                }
                                            }
                                            echo '
                                        </tbody>
                                    </table>
                                    <br/><br/>
                                    <div class="row">
                                        <div class="col-sm-12 text-sm-right">
                                            <button type="submit" class="btn btn-primary btn-sm">Εισαγωγή</button>
                                        </div>
                                    </div>
                                    <br/>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    echo '<div id="templateQuestionFromQuestionnaire" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Εισαγωγή όλων των ερωτήσεων ενός πρότυπου ερωτηματολογίου</h4>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-12">
                                <form id="template-questions-from-questionnaire" method="post">

                                    <input type="hidden" name="template_questions_list" class="template_questions_list"/>
                                    <input type="hidden" name="questionnaire_id" class="questionnaire_id" value="'.$id.'"/>';
                                    echo '<table class="table">
                                        <thead class="thead-inverse table-head">
                                            <tr>
                                                <th>Πρότυπα Ερωτηματολόγια</th>
                                            </tr>
                                        </thead>
                                        <tbody class="template_layout">';
                                        // Φέρουμε την λίστα με τα πρότυπα ερωτηματολόγια
                                        $params = array(':id' => $_SESSION['userid']);
                                        $sql = 'SELECT * FROM dk_questionnaire where template = 1 and user_id = :id';
                                        $stmt = $dbh->prepare($sql);
                                        $stmt->execute($params);
                                        $results = $stmt->fetchAll();
                                        $total = $stmt->rowCount();

                                        if ($total > 0) {
                                            foreach ($results as $r) {
                                                echo '<tr style="padding: 15px;" class="selection template_list" data-id="'.$r->id.'">
                                                    <td>'.$r->title.'</td>
                                                </tr>';
                                        }
                                    }
                                    echo ' </tbody>
                                    </table>
                                    <br/><br/>
                                    <div class="row">
                                        <div class="col-sm-12 text-sm-right">
                                            <button type="submit" class="btn btn-primary btn-sm">Εισαγωγή</button>
                                        </div>
                                    </div>
                                    <br/>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    echo '<div id="editQuestion" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Επεξεργασία Ερώτησης</h4>
                </div>
                <div class="modal-body">

                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-12">

                                <form id="content-questions-edit" method="post" enctype="multipart/form-data" novalidate="">
                                    <input type="hidden" id="id-edit" name="id"/>
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="title-q-edit" class="form-control-label">Ερώτηση: </label>
                                            <textarea rows="3" class="form-control" name="question_desc"
                                                      id="title-q-edit" required=""></textarea>
                                            <label class="form-control-label" for="type-q-edit">Επιλογή Τύπου Ερώτησης: </label>
                                            <select class="form-control" id="type-q-edit" name="type"  required="">
                                                <option value="">Δυνατοί Τύποι Ερώτησης</option>
                                                <option value="text">Ερώτηση Πολλαπλής Επιλογής (Κειμένου)</option>
                                                <option value="number">Ερώτηση Πολλαπλής Επιλογής (Αριθμού)</option>
                                                <option value="freetext">Ερώτηση Ελεύθερου Κειμένου</option>
                                                <option value="file">Ερώτηση Προσθήκης Αρχείου</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="q-answers-edit">
                                                <div class="hidden_block" id="choices_container-edit">
                                                    <h6>Πιθανές Απαντήσεις</h6>
                                                    <div class="form-group">
                                                        <select class="form-control" id="type-multi-q-edit" style="display: none;">
                                                            <option value="">Τύπος Πιθανών Απαντήσεων</option>
                                                            <option value="radio">Ερώτηση Μοναδικής Επιλογής (Radio Button)</option>
                                                            <option value="check">Ερώτηση Πολλαπλής Επιλογής (Checkbox)</option>
                                                        </select>
                                                    </div>
                                                    <table id="choice_edit_options">
                                                        <tr>
                                                            <td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td>
                                                            <td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td>
                                                        </tr>
                                                    </table>
                                                    <div id="add_edit_choice" class="add_new"><span class="fa fa-plus" aria-hidden="true"></span>
                                                        Προσθήκη Νέας
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <br/><br/>
                                    <div class="row">
                                        <div class="col-sm-12 text-sm-right">
                                            <button id="update_q" class="btn btn-primary btn-sm">Ενημέρωση</button>
                                        </div>
                                    </div>
                                    <br/>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
$breadcrumb=array(
    array('title'=>'Ερωτηματολόγια','href'=>'questionnaires.php'),
    array('title'=>'Επεξεργασία Ερωτηματολογίου','href'=>''),
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <h3>Επεξεργασία Ερωτηματολογίου '.$result->title.'</h3>

            <form action="edit_questionnaire.php?id='.$id.'" method="post" novalidate="" id="edit_questionnaire_form">
                <input type="hidden" name="questionnaire_id" id="questionnaire_id" value="'.$id.'"/>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="description" class="form-control-label">Περιγραφή: </label>
                            <textarea rows="5" class="form-control" name="description" id="description" required="">'.$result->description.'</textarea>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <input type="hidden" id="title" name="title" value="'.$result->title.'"/>
                        <div class="row">
                        <div class="form-group col-sm-6">
                            <label for="lesson" class="form-control-label">Εκπαιδευτικό Πρόγραμμα: </label>
                            <select name="lesson" id="lesson" required=""
                                    class="form-control type">
                                <option value="">Επιλογή Εκπαιδευτικού Προγράμματος</option>';
                                $params = array();
                                if ($_SESSION['level'] == 3){
                                    $params = array(':id' => $_SESSION['userid']);$sql = "SELECT * FROM dk_lessons where user_id = :id";
                                }else{
                                    $sql = 'SELECT * FROM dk_lessons';
                                }
                                $stmt = $dbh->prepare($sql);
                                $stmt->execute($params);

                                $lessons = $stmt->fetchALL();

                                foreach ($lessons as $lesson) {
                                    echo '<option value="'.$lesson->id.'" '.($lesson->id == $result->lesson_id?"selected":"").'>'.$lesson->title.'</option>';
                                }
                            echo '</select>
                        </div>';
                        if ($_SESSION['level'] == 2 || $_SESSION['level'] == 1) {

                            echo '<div class="form-group col-sm-6" id="locked">
                                <label for="lock" class="form-control-label">Κλείδωμα προβολής αποτελεσμάτων στον διδάσκοντα:   </label>
                                <br />
                                <label>';
                                $checked = '';
                                if ($result->lockedtime != null) {
                                    $today = date("Y-m-d H:i:s");
                                    $date = $result->lockedtime;
                                    echo 'this is ' . $date <= $today;
                                    if ($date > $today)
                                        $checked  = 'checked';
                                }
                                echo '<input type="radio" name="lock" id="lock" value="0" '.$checked.'/>Ναι</label>';
                                $checked = '';
                                if ($result->lockedtime != null) {
                                    $today = date("Y-m-d H:i:s");
                                    $date = $result->lockedtime;
                                    if ($date <= $today)
                                        $checked = 'checked';
                                } else {
                                    $checked = 'checked';
                                }
                                echo '<label><input type="radio" name="lock" value="1"'.$checked.'/>Όχι</label>
                                <input type="text" class="form-control" name="locked_time" id="locked_time"
                                       value="'.$result->lockedtime.'" autocomplete="off"/>
                            </div>';
                        }
                        echo '</div>
                        <div class="row">

                            <div class="form-group col-sm-6" id="date_start_layout">
                                <label for="time_begins" class="form-control-label">Ημερομηνία Έναρξης: </label>
                                <input type="text" class="form-control" name="time_begins" required="" id="time_begins"
                                       value="'.date('d/m/Y H:i', strtotime($result->time_begins)).'" autocomplete="off"/>
                            </div>
                            <div class="form-group col-sm-6" id="date_ends_layout">
                                <label for="time_ends" class="form-control-label">Ημερομηνία Λήξης: </label>
                                <input type="text" class="form-control" name="time_ends" required="" id="time_ends"
                                       value="'.date('d/m/Y H:i', strtotime($result->time_ends)).'" autocomplete="off"/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">';
                                // Φέρουμε την λίστα με τα κανάλια
                                $sql = 'SELECT * FROM dk_channel';
                                $stmt = $dbh->prepare($sql);
                                $stmt->execute();
                                $channels = $stmt->fetchAll();
                                $channels_total = $stmt->rowCount();
                                if ($channels_total > 0) {
                                    echo '<div class="form-group">
                                    <label class="form-control-label">Επιλογή Καναλιών Εισόδου: </label><br/>';

                                        foreach ($channels as $channel) {
                                            echo "
                                            <label for='channel_$channel->id'><input type='checkbox' name='channel[]' value='$channel->id' id='channel_$channel->id' ".(in_array($channel->id, $channels_list)?'checked':'')."/>$channel->title
                                            </label><br/>";
                                        }
                                    echo '</div>';
                                }
                            echo '</div>
                        </div>
                    </div>
                </div>
            <hr/>';

            //Στην κεντρική σελίδα δημιουργώ τα 3 κουμπιά που ενεργοποιούν τα modals.
            echo '
            <div class="row">
                <div class="col-sm-12">
                <!-- Στο κουμπι δηλώνω το id του modal που θα ανοιξει.  -->
                    <button id="simple-q" type="button" class="add-new-btn" data-toggle="modal" data-target="#simpleQuestion">
                        <i class="fa fa-file-o" aria-hidden="true"></i>
                        Εισαγωγή Νέας Ερώτησης
                    </button>

                    <button id="template-q" type="button" class="add-new-btn" data-toggle="modal" data-target="#templateQuestion">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                        Εισαγωγή Πρότυπης Ερώτησης
                    </button>

                    <button id="template-questionnaire" type="button" class="add-new-btn" data-toggle="modal"
                            data-target="#templateQuestionFromQuestionnaire">
                        <i class="fa fa-newspaper-o" aria-hidden="true"></i>
                        Εισαγωγή Πρότυπου Ερωτηματολογίου
                    </button>
                    <button type="submit" class="btn btn-success btn-sm save-btn pull-right" id="submit-order">
                        <span class="fa fa-floppy-o" aria-hidden="true"></span>
                        Αποθήκευση
                    </button>
                </div>
            </div>
            </form>
            <div class="row">
                <div class="col-sm-12">
                    <div class="table-head">
                        <div class="row">
                            <div class="col-sm-1">ID</div>
                            <div class="col-sm-3">Ερώτηση</div>
                            <div class="col-sm-2">Τύπος</div>
                            <div class="col-sm-4">Πιθανές Επιλογές</div>
                            <div class="col-sm-2">Ενέργειες</div>
                        </div>
                    </div>
                    <form id="list_order" method="post">
                        <ul class="questions" id="all_q">';//δεν γινεται table γιατι το sorting δουλευει μονο με λιστα.
                            $pos_i=0;
                            foreach ($result_questions as $q) {$pos_i++;
                                echo '<li class="ui-state-default">
                                    <input type="hidden" name="order[]" value="'.$q->id.'"/>
                                    <div class="row">
                                        <div class="col-sm-1">'.$pos_i.'</div>
                                        <div class="col-sm-3">'.$q->question.'</div>
                                        <div class="col-sm-2">'.(array_key_exists($q->type.'_'.$q->multi_type, $list_types)?$list_types[$q->type.'_'.$q->multi_type]:"").'</div>
                                        <div class="col-sm-4">';
                                            $params = array(':id' => $q->id);
                                            $sql = 'SELECT dk_question_options.pick FROM dk_question_options INNER JOIN dk_question ON dk_question.id=dk_question_options.question_id WHERE dk_question.id = :id ';
                                            $stmt = $dbh->prepare($sql);
                                            $stmt->execute($params);
                                            $options = $stmt->fetchALL();
                                            $i = 0;
                                            foreach ($options as $op) {
                                                echo $op->pick;
                                                if ($i < sizeof($options) - 1) {
                                                    echo ", ";
                                                }
                                                $i++;
                                            }
                                        echo '
                                        </div>
                                        <div class="col-sm-2">
                                            <div data-id="'.$q->id.'" class="btn btn-success edit_q btn-sm"
                                                 data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span
                                                        class="fa fa-pencil" aria-hidden="true"></span></div>
                                            <div data-id="'.$q->id.'" class="btn btn-danger remove_q btn-sm"
                                                 data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span
                                                        class="fa fa-trash-o" aria-hidden="true"></span></div>
                                        </div>
                                    </div>
                                </li>';
                            }
                        echo '
                        </ul>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';
?>
    <script>
        var array = [];
        jQuery(document).ready(function () {
            // https://jqueryui.com/sortable/
            $(function () {
                $("#edit_questionnaire_form").validate();
                $("#all_q").sortable();
                $("#all_q").disableSelection();
                //αποθηκευση της σειράς των ερωτήσεων με ajax, μολις γίνει drop
                $( "#all_q" ).sortable({
                    stop: function( ) {
                        jQuery.ajax({
                            type: 'POST',
                            url: 'ajax_questions.php',
                            data: {
                                mode: "submit-order",
                                form: jQuery('#list_order').serialize(),
                                questionnaire_id: jQuery('#questionnaire_id').val()
                            },
                            success: function (data, textStatus, XMLHttpRequest) {
                                //console.log(data);
                            },
                            error: function (MLHttpRequest, textStatus, errorThrown) {
                                alert(errorThrown);
                            }
                        });
                    }
                });
            });

            $('#locked_time').hide();

            if ($('input[type=radio][name=lock]:checked').val() == 0) {
                $('#locked_time').show();
            }

            $('input[type=radio][name=lock]').change(function () {
                if (this.value == '1') {
                    $('#locked_time').hide();
                }
                else if (this.value == '0') {
                    $('#locked_time').show();
                }
            });

            // AJX για την εισαωγή πρότυπων ερωτήσεων (χύμα)
            //Έχω την τελική φόρμα με τις ερωτησεις
            //Απο το 2ο modal οταν σταλει η φορμα με τις ερωτησεις ...
            $('#template-questions').on('submit', (function (e) {
                //Κόβω την αποστολή φόρμας με refresh (POST).
                e.preventDefault();
                if($('#template-questions').valid()){
                    //Ετοιμαζω τα δεδομένα για το Ajax.
                    var data = new FormData();

                    //Δηλαδή ποια συναρτηση θα τρέξει.
                    data.append('mode', "add_template");

                    //Να πάρει το πεδιο με τα id των ερωτησεων.
                    data.append('template_questions', jQuery('.template_questions').val());

                    //και το id του ερωτηματολογίου
                    data.append('questionnaire_id', jQuery('.questionnaire_id').val());

                    //ξεκινάει το ajax αρχείο ajax_questions.php
                    jQuery.ajax({
                        type: 'POST',
                        url: 'ajax_questions.php',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        success: function (data, textStatus, XMLHttpRequest) {
                            //το success ειναι οταν θα επιστρεψω απο το Ajax
                            // το option ειναι ενα κενο string που θα κολλησω τις πιθανες επιλογες.
                            var options = "";
                            array = [];
                            //αδειαζω το template_questions
                            jQuery('.template_questions').val(null);

                            //βλεπει ποσες γραμμες εχει ο πινακας των ερωτησεων κατω
                            var pos_q = jQuery('#all_q li').size();
                            //στο data ειναι οι ερωτησεις που εχει επιστρεψει το ajax_questions.php.
                            //To .each ειναι foreach στην javascript.
                            //Άρα εχω 2 loop που έχουν τις ερωτησεις που αυτες οι ερωτησεις ειναι πινκες γιατι εχουν και τις επιλογες τους.
                            $.each(data, function (index, value) {
                                options = "";
                                //ο υποπινακας με τις επιλογες.
                                for (var i = 0; i < value['options'].length; i++) {
                                    //ενωση των στοιχειων.
                                    options += value['options'][i]['pick'];
                                    //ελεγχω αν το στοιχειο που εξετα=ζω ειναι το τελευταιο.
                                    //αν δεν ειναι βαζω κομμα.
                                    if (value['options'].length - 1 > i) {
                                        options += ", ";
                                    }
                                }

                                console.log(value['options']);
                                console.log(value['question']);

                                //αυξανω την θεση κατα ενα
                                pos_q++;
                                //στον πινακα questions κανει append δηλαδη βαζει την ερωτηση στον πινακα.
                                jQuery('.questions').append('<li class="ui-state-default alert-success">' +
                                    '<input type="hidden" name="order[]" value="' + value['question']['id'] + '" />' +
                                    '<div class="row">' +
                                    '<div class="col-sm-1">' + pos_q + '</div>' +
                                    '<div class="col-sm-3">' + value['question']['question'] + '</div>' +
                                    '<div class="col-sm-2">' + value['question']['type'] + '</div>' +
                                    '<div class="col-sm-4">' + options + '</div>' +
                                    '<div class="col-sm-2">' +
                                    '<div data-id="' + value['question']['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span class="fa fa-pencil" aria-hidden="true"></span></div>' +
                                    ' <div data-id="' + value['question']['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span class="fa fa-trash-o" aria-hidden="true"></span></div>' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>');
                            });
                            $('#template-questions tr.selection').removeClass('selected');
                            //κλεινει το modal.
                            jQuery('#templateQuestion .close').click();
                        }, error: function (jqXHR, textStatus, errorThrown) {
                            console.log(JSON.stringify(jqXHR));
                            console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                        }
                    });
                }
            }));

            $('#template-questions-from-questionnaire').on('submit', (function (e) {
                e.preventDefault();
                var data = new FormData();
                data.append('mode', "add_template_list");
                data.append('template_questions', jQuery('.template_questions_list').val());
                data.append('questionnaire_id', jQuery('.questionnaire_id').val());

                jQuery.ajax({
                    type: 'POST',
                    url: 'ajax_questions.php',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: data,
                    success: function (data, textStatus, XMLHttpRequest) {console.log(data);
                        var options = "";
                        array = [];
                        var pos_q = jQuery('#all_q li').size();
                        jQuery('.template_questions').val(null);
                        $.each(data, function (index, value) {
                            options = "";
                            for (var i = 0; i < value['options'].length; i++) {
                                options += value['options'][i]['pick'];
                                if (value['options'].length - 1 > i) {
                                    options += ", ";
                                }
                            }
                            pos_q ++;
                            jQuery('.questions').append('<li class="ui-state-default alert-success"><input type="hidden" name="order[]" value="' + value['question']['id'] + '" /><div class="row"><div class="col-sm-1">' + pos_q + '</div><div class="col-sm-3">' + value['question']['question'] + '</div><div class="col-sm-2">' + value['question']['type'] + '</div><div class="col-sm-4">' + options + '</div><div class="col-sm-2"><div data-id="' + value['question']['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span class="fa fa-pencil" aria-hidden="true"></span></div> <div data-id="' + value['question']['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span class="fa fa-trash-o" aria-hidden="true"></span></div></div></div></div>');
                        });
                        $('#template-questions-from-questionnaire tr.selection').removeClass('selected');
                        jQuery('#templateQuestionFromQuestionnaire .close').click();
                    }, error: function (jqXHR, textStatus, errorThrown) {
                        console.log(JSON.stringify(jqXHR));
                        console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                    }
                });
            }));


            // AJAX για εισαγωγή ερώτησης στο ερωτηματολόγιο
            // Από την φόρμα είχα βάλει id το content-questions και τρέχει το javascript.
            $('#content-questions').on('submit', (function (e) {
                e.preventDefault();
                if($('#content-questions').valid()){
                    //Το Ajax θέλει να ξέρει το δεδομένα και την διεύθυνση που πρέπει να τα στείλει για να τρέξει και περιμένει μία απάντηση.
                    var data = new FormData(); //φταχνω ενα αντικειμενο form data
                    //Δημιουργώ εναν πινακα στην javascript ΚΕΝΟ και τον γεμιζω με δεδομένα.
                    //Στο αντικειμενο form data με όνομα data προσθλετω με την συνάρτηση append τα δεδομένα σαν το add μιας λίστας.
                    data.append('mode', "add_question"); // ποιο κομμάτι κώδιικα θα κληθεί στο αρχείο AJAX PHP
                    data.append('form', jQuery('#content-questions').serialize()); // όλα τα δεδομένα της φόρμας
                    data.append('type', jQuery('#type-q').val()); // τι τύπος ερώτησης είναι
                    data.append('questionnaire_id', jQuery('#questionnaire_id').val()); // ID ερωτηματολογίου
                    data.append('isTemplate', 0); // αν ειναι πρότυπη ερώτηση
                    data.append('type-multi', jQuery('#type-multi-q').val()); // σε περίπτωση που είναι πολλαπλής να δηλώσουμε αν είναι check ή radio
                    //Μέχρι εδω εχω προσθέσει όλα τα δεδομένα στον πίνακα και αυτά που έχω γράψει.

                    //Τρεχω το Ajax που είναι τύπου POST.
                    jQuery.ajax({
                        type: 'POST',
                        url: 'ajax_questions.php',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        success: function (data, textStatus, XMLHttpRequest) {
                            console.log(data);
                            var options = "";
                            var pos_q = jQuery('#all_q li').size();
                            for (var i = 0; i < data['options'].length; i++) {
                                options += data['options'][i]['pick'];
                                if (data['options'].length - 1 > i) {
                                    options += ", ";
                                }
                            }
                            pos_q ++;
                            // οτι μου εχει επιστρεψει το ajax το προσαρμοζω στην html και το βαζω στο τελος του πινακα.
                            jQuery('.questions').append('<li class="ui-state-default alert-success"><input type="hidden" name="order[]" value="' + data['question'][0]['id'] + '" /><div class="row"><div class="col-sm-1">' + pos_q + '</div><div class="col-sm-3">' + data['question'][0]['question'] + '</div><div class="col-sm-2">' + data['question'][0]['type'] + '</div><div class="col-sm-4">' + options + '</div><div class="col-sm-2"><div data-id="' + data['question'][0]['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span class="fa fa-pencil" aria-hidden="true"></span></div> <div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span class="fa fa-trash-o" aria-hidden="true"></span></div></div></div></div>');
                            // κλείνω το modal
                            jQuery('#simpleQuestion .close').click();
                        }, error: function (jqXHR, textStatus, errorThrown) {
                            console.log(JSON.stringify(jqXHR));
                            console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                        }
                    });
                }
            }));

            jQuery('.add_template').on('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                jQuery.ajax({
                    type: 'POST',
                    url: 'ajax_questions.php',
                    data: {
                        mode: "add_template",
                        id: jQuery(this).data('id'),
                        questionnaire_id: jQuery('#questionnaire_id').val()
                    },
                    success: function (data, textStatus, XMLHttpRequest) {
                        var options = "";
                        var pos_q = jQuery('#all_q li').size();
                        for (var i = 0; i < data['options'].length; i++) {
                            options += data['options'][i]['pick'];
                            if (data['options'].length - 1 > i) {
                                options += ", ";
                            }
                        }
                        pos_q ++;
                        jQuery('.questions').append('<li class="ui-state-default alert-success"><input type="hidden" name="order[]" value="' + data['question'][0]['id'] + '" /><div class="row"><div class="col-sm-1">' + pos_q + '</div><div class="col-sm-3">' + data['question'][0]['question'] + '</div><div class="col-sm-2">' + data['question'][0]['type'] + '</div><div class="col-sm-4">' + options + '</div><div data-id="' + data['question'][0]['id'] + '" class="col-sm-2 edit_q"><div class="btn btn-success btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span class="fa fa-pencil" aria-hidden="true"></span></div> <div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span class="fa fa-trash-o" aria-hidden="true"></span></div></div></div></div>');
                        jQuery('#simpleQuestion .close').click();
                    },
                    error: function (MLHttpRequest, textStatus, errorThrown) {
                        alert(errorThrown);
                    }
                });
            });

            jQuery(document).on('click', '.remove_q', function (e) {
                e.stopPropagation();
                e.preventDefault();
                var $this = jQuery(this);
                $this.parent().parent().addClass('alert-danger');
                jQuery.ajax({
                    type: 'POST',
                    url: 'ajax_questions.php',
                    data: {
                        mode: "remove_q",
                        id: jQuery(this).data('id'),
                        questionnaire_id: jQuery('#questionnaire_id').val()
                    },
                    success: function (data, textStatus, XMLHttpRequest) {
                        $this.parent().parent().parent().fadeOut('slow');
                        $this.parent().parent().parent().remove();
                    },
                    error: function (MLHttpRequest, textStatus, errorThrown) {
                        alert(errorThrown);
                    }
                });
            });

            // Προσθήκη νέας γραμμής στον πίνακα για πιθανή απάντηση
            // Μόλις πατήσω add_choice τότε προσθέτει μία επιλογή.
            jQuery('#add_choice').on('click', function () {
                //Στον πίνακα choice_options βάζει αυτόνν τον κώδικα που προσθέτει μία γραμμή που έχει το κοτί κειμένου και το (-).
                jQuery('#choice_options').append('<tr><td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>')
            });

            jQuery('#add_edit_choice').on('click', function () {
                jQuery('#choice_edit_options').append('<tr><td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>')
            });

            // Αφαιρεί πιθανή απάντηση
            jQuery(document).on('click', '.remove-obj', function () {
                //Βρίσκει το jquery(this) το αντικείμενο που πατήθηκε και σβήνει όλη την γραμμή πηγαίνοντας στον γονιό του γονιού όχι μόνο το κουμπί.
                jQuery(this).parent().parent().remove();
            });

            // Κρύβει/εμφανίζει το δεξί μέλος (πιθανές απαντήσεις)του modal αν η ερώτηση μου είναι radio ή checkbox
            //Το τype-q είναι το id του select box για την επιλογή του τύπου ερώτησης. Αν άλλαξει κάποια επιλογή εκτέλειται το event - javascript.
            jQuery('#type-q').on('change', function () {
                jQuery('.hidden_block').hide();
                var $value = jQuery(this).val();
                //Αν έχει τιμή text ή number τότε θα εμφανίσερι την δίπλα στήλη.
                if ($value == 'text' || $value == 'number'){
                    jQuery('#choices_container').fadeIn();
                    jQuery('#type-multi-q').fadeIn();
                //Αλλιώς κρύβει το δεξί div.
                }else{
                    jQuery('#type-multi-q').fadeOut();
                }
            });

            // Κρύβει/εμφανίζει το δεξί μέλος (πιθανές απαντήσεις)του modal αν η ερώτηση μου είναι radio ή checkbox
            jQuery('#type-q-edit').on('change', function () {
                jQuery('.hidden_block').hide();
                var $value = jQuery(this).val();

                if ($value == 'text' || $value == 'number'){
                    jQuery('#choices_container-edit').fadeIn();
                    jQuery('#type-multi-q-edit').fadeIn();
                }else{
                    jQuery('#type-multi-q-edit').fadeOut();
                }
            });

            //clear modal when it closes
            jQuery('#simpleQuestion').on('hidden.bs.modal', function (e) {
                jQuery('#title-q').val('');
                jQuery("#type-q").val($("#type-q option:first").val());
                jQuery('#type-multi-q').val('');
                jQuery("#type-multi-q").val($("#type-q option:first").val());
                jQuery("#choice_options").html('');
                jQuery('#choice_options').append('<tr><td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>');
                jQuery("#choices_container").hide();
            });

            jQuery(document).on('click', '.edit_q', function (e) {
                var id = jQuery(this).data('id');

                var data = new FormData();
                data.append('mode', "edit_q");
                data.append('id', id);

                jQuery.ajax({
                    type: 'POST',
                    url: 'ajax_questions.php',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: data,
                    success: function (data, textStatus, XMLHttpRequest) {console.log(data);
                        jQuery("#choices_container-edit").fadeOut();

                        var options = "";
                        for (var i = 0; i < data['options'].length; i++) {

                            options += '<tr><td><input class="form-control" name="choices[]" value="' + data['options'][i]['pick'] + '"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>';
                            if (data['options'].length - 1 > i) {
                                options += ", ";
                            }
                        }

                        jQuery('#title-q-edit').val(data['question'][0]['question']);
                        console.log(data['question'][0]['type'] + '__' +data['question'][0]['multi_type']);
                        $type = data['question'][0]['type'];
                        if ($type == 'radio' || $type == 'check'){
                            jQuery("#type-q-edit").val(data['question'][0]['multi_type']);
                            jQuery("#type-multi-q-edit").val(data['question'][0]['type']);
                            jQuery("#type-multi-q-edit").fadeIn();
                        }else{
                            jQuery("#type-q-edit").val(data['question'][0]['type']);
                            jQuery("#type-multi-q-edit").fadeOut();
                        }
                        jQuery("#id-edit").val(id);
                        console.log(jQuery("#id-edit").val());
                        if (data['question'][0]['type'] == 'radio' || data['question'][0]['type'] == 'check') {
                            jQuery("#choice_edit_options").html(options);
                            jQuery("#choices_container-edit").fadeIn();
                        }

                        jQuery("#editQuestion").modal('show');

                    },
                    error: function (MLHttpRequest, textStatus, errorThrown) {
                        alert(errorThrown);
                    }
                });
            });
            //update form
            $('#content-questions-edit').submit(function (e) {
                e.stopPropagation();
                e.preventDefault();
                if($('#content-questions-edit').valid()){

                    var id = jQuery('#id-edit').val();

                    var data = new FormData();
                    data.append('mode', "update_q");
                    data.append('id', jQuery('#id-edit').val());
                    data.append('form', jQuery('#content-questions-edit').serialize());
                    data.append('type', jQuery('#type-q-edit').val());
                    data.append('multi_type', jQuery('#type-multi-q-edit').val());
                    data.append('questionnaire_id', jQuery('#questionnaire_id').val());

                    jQuery.ajax({
                        type: 'POST',
                        url: 'ajax_questions.php',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        success: function (data, textStatus, XMLHttpRequest) {
                            console.log(data);
                            var options = "";
                            for (var i = 0; i < data['options'].length; i++) {
                                options += data['options'][i]['pick'];
                                if (data['options'].length - 1 > i) {
                                    options += ", ";
                                }
                            }

                            $('.ui-state-default :input[value="' + id + '"]').parent().remove();

                            var pos_q = jQuery('#all_q li').size();
                            pos_q ++;
                            jQuery('.questions').append('' +
                                '<li class="ui-state-default alert-success">' +
                                '<input type="hidden" name="order[]" value="' + data['question'][0]['id'] + '" />' +
                                '<div class="row"><div class="col-sm-1">' + pos_q + '</div><div class="col-sm-3">' + data['question'][0]['question'] + '</div>' +
                                '<div class="col-sm-2">' + data['question'][0]['type'] + '</div>' +
                                '<div class="col-sm-4">' + options + '</div>' +
                                '<div class="col-sm-2">' +
                                '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία">' +
                                '<span class="fa fa-pencil" aria-hidden="true"></span>' +
                                '</div> ' +
                                '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή">' +
                                '<span class="fa fa-trash-o" aria-hidden="true"></span>' +
                                '</div>' +
                                '</div>' +
                                '</div>' +
                                '</div>');

                            jQuery('#editQuestion .close').click();
                        }, error: function (jqXHR, textStatus, errorThrown) {
                            console.log(JSON.stringify(jqXHR));
                            console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                        }
                    });
                }
            });

            jQuery('#time_begins').datetimepicker({
                lang: 'el',
                timepicker: true,
                format: 'd/m/Y H:i',
                onSelectDate: function( ct ){console.log(ct);
                    jQuery('#time_ends').datetimepicker({minDate: new Date(ct)});
                },
                onSelectTime: function( ct ){console.log(ct);
                    jQuery('#time_ends').datetimepicker({minDate: new Date(ct)});
                }
            });
            jQuery('#time_ends').datetimepicker({
                lang: 'el',
                timepicker: true,
                format: 'd/m/Y H:i',
                onSelectDate: function( ct ){console.log(ct);
                    jQuery('#time_begins').datetimepicker({maxDate: new Date(ct)});
                },
                onSelectTime: function( ct ){console.log(ct);
                    jQuery('#time_begins').datetimepicker({maxDate: new Date(ct)});
                }

            });
            jQuery('#locked_time').datetimepicker({
                lang: 'el',
                timepicker: true,
                format: 'd/m/Y H:i',
                closeOnDateSelect: true
            });

            $(document).ready(function () {
                $('#template-no').on('change', function () {
                    jQuery('#date_start_layout').show();
                    jQuery('#date_ends_layout').show();
                });
            });

            $(document).ready(function () {
                $('#template-yes').on('change', function () {
                    jQuery('#date_start_layout').hide();
                    jQuery('#date_ends_layout').hide();
                });
            });

        });

        $(function () {
            $(".template_questions_layout").on('click', function () {
                var questionnaireId = $(this).attr("data-id");

                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected');

                    $.each(array, function (i) {
                        if (array[i] === questionnaireId) {
                            array.splice(i, 1);
                            return false;
                        }
                    });
                } else {
                    array.push(questionnaireId);
                    $(this).addClass('selected');
                }
                //το template_questions ειναι το πεδιο που αποθηκευω ολα τα id των ερωτησεων που θα περασουν στο Ajax σε μια γραμμη.
                $('.template_questions').val(array.toString());
            });
        });

        $(function () {
            // Φτιάχνω ένα κενό array με τα ID των πρότυπων ερωτήσεων που θα εισαχθούν στο ερωτηματολόγιο
            var array = [];

            // ενεργειες οταν πατάω πάνω σε πρότυπη ερώτηση στο δεύτερο Modal
            $(".selection").on('click', function () {
                var $list = (jQuery(this).hasClass('template_list')?'_list':'');
                if($('.template_questions'+$list).val()==''){
                    array = [];//αδιαζω το πίνακα κάθε φορά που επιλέγω νέες ερωτήσεις
                }
                // παίρνω το ID της ερώτησης από το data-attribute
                var questionId = $(this).attr("data-id");

                //Ελεγχω αν ειναι ηδη επιλεγμενη η ερωτηση απο τον χρηστη...
                if ($(this).hasClass('selected')) {
                    //τοτε αφαιρω την κλαση selected που στηην ουσια αφαιρει το μπλε απο την ερωτηση
                    $(this).removeClass('selected');
                    // και σβήνω από το array απο τον πινακα με τις ερωτησεις που θελω να περασω το ID το οποιό μόλις πάτησα πάνω του.
                    $.each(array, function (i) {
                        if (array[i] === questionId) {
                            array.splice(i, 1);
                            return false;
                        }
                    });
                //αλλιώς σημαινει οτι δεν ηταν επιλεγμενη η ερωτηση...
                } else {
                    //βαζω το id της ερωτησης στο array
                    array.push(questionId);
                    //και βαζω το μπλε στην επιλεγμενη
                    $(this).addClass('selected');
                }
                $('.template_questions'+$list).val(array.toString());
                //console.log(array.toString());
            });
        });
    </script>

<?php
}
}else{
    echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Επεξεργασία Ερωτηματολογίου</h3>
            <div class="alert alert-danger">Δεν έχετε το δικαίωμα να διαχειριστείτε αυτό το ερωτηματολόγιο.</div>
        </div>
    </div>
</div>';
}
get_footer();
?>
