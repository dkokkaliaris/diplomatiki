<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();
$breadcrumb=array(
    array('title'=>'Πρότυπα Ερωτηματολόγια','href'=>'templates.php'),
    array('title'=>'Επεξεργασία Ερωτηματολογίου','href'=>''),
);
$id = sanitize($_GET['id']);
$params = array(':id' => $id);
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
            <div class="alert alert-danger">Δεν έχετε δικαίωμα να διαχειριστείτε αυτό το ερωτηματολόγιο</div>
        </div>
    </div>
</div>';
}else{
    $alert = '';
    if (isset($_GET['status']) && sanitize($_GET['status']) == 1) {
        $alert .= "<div class='alert alert-success'>Η προσθήκη του νέου ερωτηματολογίου πραγματοποιήθηκε με επιτυχία.</div>";
    }
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);

    $params = array(':title' => $title, ':description' => $description, ':last_edit_time' => date('Y-m-d H:i:s'), ':last_editor' => $_SESSION['userid'], ':id' => $id);
    $sql = 'UPDATE dk_questionnaire SET title = :title, description = :description, last_edit_time = :last_edit_time, last_editor = :last_editor WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $alert .= "<div class='alert alert-success'>Η επεξεργασία του Ερωτηματολογίου πραγματοποιήθηκε με επιτυχία.</div>";
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

$questions = [];
foreach ($result_questions as $question) {
    $questions[] = $question->id;
}

$list_types = array('_'=>'','radio_text'=>'Ερώτηση Μοναδικής Επιλογής Κειμένου', 'check_text'=>'Ερώτηση Πολλαπλής Επιλογής Κειμένου','radio_number'=>'Ερώτηση Μοναδικής Επιλογής Αριθμού', 'check_number'=>'Ερώτηση Πολλαπλής Επιλογής Αριθμού', 'freetext_'=>'Ερώτηση Ελεύθερου Κειμένου', 'file_'=>'Ερώτηση Προσθήκης Αρχείου');

echo '<div id="simpleQuestion" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Προσθήκη Νέας Ερώτησης</h4>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-12">
                                <form id="content-questions" method="post" enctype="multipart/form-data" accept-charset="utf-8" novalidate="">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <label for="title-q" class="form-control-label">Ερώτηση: </label>
                                            <textarea rows="3" class="form-control" name="question_desc" id="title-q" required=""></textarea>
                                            <label class="form-control-label" for="type-q">Επιλογή Τύπου Ερώτησης: </label>
                                            <select class="form-control" id="type-q" required="">
                                                <option value="">Επιλογή Τύπου Ερώτησης</option>
                                                <option value="text">Ερώτηση Πολλαπλής Επιλογής (Κειμένου)</option>
                                                <option value="number">Ερώτηση Πολλαπλής Επιλογής (Αριθμού)</option>
                                                <option value="freetext">Ερώτηση Ελεύθερου Κειμένου</option>
                                                <option value="file">Ερώτηση Προσθήκης Αρχείου</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="q-answers">

                                                <div class="hidden_block" id="choices_container">
                                                    <h6>Πιθανές Απαντήσεις:</h6>
                                                    <div class="form-group">
                                                        <select class="form-control" id="type-multi-q" style="display: none;">
                                                            <option value="">Τύπος Πιθανών Απαντήσεων</option>
                                                            <option value="radio">Ερώτηση Μοναδικής Επιλογής (Radio Button)</option>
                                                            <option value="check">Ερώτηση Πολλαπλής Επιλογής (Checkbox)</option>
                                                        </select>
                                                    </div>
                                                    <table id="choice_options">
                                                        <tr>
                                                            <td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td>
                                                            <td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td>
                                                        </tr>
                                                    </table>
                                                    <div id="add_choice" class="add_new"><span class="fa fa-plus" aria-hidden="true"></span>
                                                        Προσθήκη Νέας
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <br/><br/>
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
                    <h4 class="modal-title">Επιλογή πρότυπης ερώτησης</h4>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-sm-12">
                                <form id="template-questions" method="post">

                                    <input type="hidden" name="template_questions" class="template_questions"/>
                                    <input type="hidden" name="questionnaire_id" class="questionnaire_id"
                                           value="'.$id.'"/>
                                   <table class="table">
                                        <thead class="thead-inverse table-head">
                                            <tr>
                                                <th>Περιγραφή</th>
                                                <th>Τύπος</th>
                                                <th>Επιλογές</th>
                                            </tr>
                                        </thead>
                                        <tbody class="template_layout" id="template-">';
                                            // Φέρουμε την λίστα με τις εωτήσεις
                                            if($_SESSION['level'] == 1){
                                                $params = array(':questionnaire_id' => $id);
                                                $sql = 'SELECT dk_question.*, dk_questionnaire_questions.question_id FROM dk_question LEFT JOIN dk_questionnaire_questions ON dk_questionnaire_questions.question_id=dk_question.id WHERE dk_question.template = 1 AND dk_questionnaire_questions.questionnaire_id IS NULL';
                                            }else{
                                                $params = array(':questionnaire_id' => $id, ':id' => $_SESSION['userid']);
                                                $sql = 'SELECT dk_question.*, dk_questionnaire_questions.question_id FROM dk_question LEFT JOIN dk_questionnaire_questions ON dk_questionnaire_questions.question_id=dk_question.id WHERE dk_question.template = 1 AND dk_questionnaire_questions.questionnaire_id IS NULL user_id = :id  GROUP BY dk_question.id';
                                            }
                                            $stmt = $dbh->prepare($sql);
                                            $stmt->execute($params);
                                            $results = $stmt->fetchAll();
                                            $total = $stmt->rowCount();

                                            if ($total > 0) {
                                                foreach ($results as $q) {
                                                        echo '
                                                        <tr style="padding: 15px;" class="selection" data-id="'.$q->id.'">
                                                            <td>'.$q->question.'</td>
                                                            <td>'.(array_key_exists($q->type.'_'.$q->multi_type, $list_types)?$list_types[$q->type.'_'.$q->multi_type]:"").'</td>
                                                            <td>';

                                                                $params = array(':id' => $q->id, ':userid'=> $_SESSION['userid']);
                                                                $sql = 'SELECT dk_question_options.pick FROM dk_question_options INNER JOIN dk_question ON dk_question.id=dk_question_options.question_id WHERE dk_question.id = :id and dk_question.user_id = :userid';
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
                                                            </td>
                                                        </tr>';
                                                   // }
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

    echo '<div id="editQuestion" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Επεξεργασία ερώτησης</h4>
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
                                            <label class="form-control-label" for="type-q">Επιλογή τύπου ερώτησης: </label>
                                            <select class="form-control" id="type-q-edit" name="type" required="">
                                                <option value="">Δυνατοί τύποι</option>
                                                <option value="text">Πολλαπλής κειμένου</option>
                                                <option value="number">Πολλαπλής αιρθμού</option>
                                                <option value="freetext">Ελεύθερο κείμενο</option>
                                                <option value="file">Αρχείο</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="q-answers-edit">

                                                <div class="hidden_block" id="choices_container-edit">
                                                    <h5>Πιθανές απαντήσεις</h5>
                                                    <div class="form-group">
                                                        <select class="form-control" id="type-multi-q-edit" style="display: none;">
                                                            <option value="">Τύπος πολλαπλής επιλογής</option>
                                                            <option value="radio">Radio</option>
                                                            <option value="check">Checkbox</option>
                                                        </select>
                                                    </div>
                                                    <table id="choice_edit_options">
                                                        <tr>
                                                            <td><input class="form-control" name="choices[]"
                                                                       placeholder="Απάντηση"/></td>
                                                            <td><span class="fa fa-minus-circle fa-fw remove-obj"
                                                                      aria-hidden="true"></span></td>
                                                        </tr>
                                                    </table>
                                                    <div id="add_choice-edit" class="add_new"><span class="fa fa-plus" aria-hidden="true"></span>
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

echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <h3>Επεξεργασία Ερωτηματολογίου '.$result->title.'</h3>

            <form action="edit_template.php?id='.$id.'" method="post" novalidate="" id="edit_tamplate_form">

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

                    </div>
                </div>

                <hr/>
                <div class="row">
                    <div class="col-sm-12">
                        <button id="simple-q" type="button" class="add-new-btn" data-toggle="modal" data-target="#simpleQuestion">
                            <i class="fa fa-file-o" aria-hidden="true"></i>
                            Εισαγωγή Νέας Ερώτησης
                        </button>
                        <button id="template-q" type="button" class="add-new-btn" data-toggle="modal" data-target="#templateQuestion">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            Εισαγωγή Πρότυπης Ερώτησης
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
                            <div class="col-sm-3">Περιγραφή</div>
                            <div class="col-sm-2">Τύπος</div>
                            <div class="col-sm-4">Επιλογές</div>
                            <div class="col-sm-2">Ενέργειες</div>
                        </div>
                    </div>
                    <form id="list_order" method="post">
                        <ul class="questions" id="all_q">';
                            $pos_i=0;
                            foreach ($result_questions as $q) { $pos_i++;
                                echo '<li class="ui-state-default"><input type="hidden" name="order[]" value="'.$q->id.'"/>
                                    <div class="row">
                                        <div class="col-sm-1">'.$pos_i.'</div>
                                        <div class="col-sm-3">'.$q->question.'</div>
                                        <div class="col-sm-2">'.(array_key_exists($q->type.'_'.$q->multi_type, $list_types)?$list_types[$q->type.'_'.$q->multi_type]:"").'</div>
                                        <div class="col-sm-4">';
                                            $params = array(':id' => $q->id, ':user_id' => $_SESSION['userid']);
                                            $sql = 'SELECT dk_question_options.pick FROM dk_question_options INNER JOIN dk_question ON dk_question.id=dk_question_options.question_id WHERE dk_question.id = :id and dk_question.user_id = :user_id';
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

        jQuery(document).ready(function () {

            $(function () {
                $('#edit_tamplate_form').validate();
                $("#all_q").sortable();
                $("#all_q").disableSelection();
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

            $('#template-questions').on('submit', (function (e) {
                e.preventDefault();
                var data = new FormData();
                data.append('mode', "add_template");
                data.append('template_questions', jQuery('.template_questions').val());
                data.append('questionnaire_id', jQuery('.questionnaire_id').val());

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

                        $.each(data, function (index, value) {
                            for (var i = 0; i < value['options'].length; i++) {
                                options += value['options'][i]['pick'];
                                if (value['options'].length - 1 > i) {
                                    options += ", ";
                                }
                            }
                            pos_q++;
                            console.log(value['options']);
                            console.log(value['question']);
                            jQuery('.questions').append('<li class="ui-state-default alert-success"><input type="hidden" name="order[]" value="' + value['question']['id'] + '" /><div class="row"><div class="col-sm-1">' + pos_q + '</div><div class="col-sm-3">' + value['question']['question'] + '</div><div class="col-sm-2">' + value['question']['type'] + '</div><div class="col-sm-4">' + options + '</div><div class="col-sm-2"><div data-id="' + value['question']['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span class="fa fa-pencil" aria-hidden="true"></span></div> <div data-id="' + value['question']['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span class="fa fa-trash-o" aria-hidden="true"></span></div></div></div></div>');
                        });
                        jQuery('#templateQuestion .close').click();
                    }, error: function (jqXHR, textStatus, errorThrown) {
                        console.log(JSON.stringify(jqXHR));
                        console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                    }
                });
            }));

            $('#content-questions').on('submit', (function (e) {
                e.preventDefault();
                if($('#content-questions').valid()){
                    var data = new FormData();
                    //data.append('file', $('#file')[0].files[0]);
                    data.append('mode', "add_question");
                    data.append('form', jQuery('#content-questions').serialize());
                    data.append('type', jQuery('#type-q').val());
                    data.append('questionnaire_id', jQuery('#questionnaire_id').val());
                    data.append('isTemplate', 0);
                    data.append('type-multi', jQuery('#type-multi-q').val()); // σε περίπτωση που είναι πολλαπλής να δηλώσουμε αν είναι check ή radio
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
                            pos_q++;
                            jQuery('.questions').append('<li class="ui-state-default alert-success"><input type="hidden" name="order[]" value="' + data['question'][0]['id'] + '" /><div class="row"><div class="col-sm-1">' + pos_q + '</div><div class="col-sm-3">' + data['question'][0]['question'] + '</div><div class="col-sm-2">' + data['question'][0]['type'] + '</div><div class="col-sm-4">' + options + '</div><div class="col-sm-2"><div data-id="' + data['question'][0]['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span class="fa fa-pencil" aria-hidden="true"></span></div> <div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span class="fa fa-trash-o" aria-hidden="true"></span></div></div></div></div>');
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
                if($('#add_template').valid()){
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
                            pos_q++;
                            jQuery('.questions').append('<li class="ui-state-default alert-success"><input type="hidden" name="order[]" value="' + data['question'][0]['id'] + '" /><div class="row"><div class="col-sm-1">' + pos_q + '</div><div class="col-sm-3">' + data['question'][0]['question'] + '</div><div class="col-sm-2">' + data['question'][0]['type'] + '</div><div class="col-sm-4">' + options + '</div><div class="col-sm-2"><div data-id="' + data['question'][0]['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία"><span class="fa fa-pencil" aria-hidden="true"></span></div> <div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή"><span class="fa fa-trash-o" aria-hidden="true"></span></div></div></div></div>');
                            jQuery('#simpleQuestion .close').click();
                        },
                        error: function (MLHttpRequest, textStatus, errorThrown) {
                            alert(errorThrown);
                        }
                    });
                }
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

            jQuery('#add_choice').on('click', function () {
                jQuery('#choice_options').append('<tr><td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>')
            });

            jQuery('#add_choice-edit').on('click', function () {
                jQuery('#choice_edit_options').append('<tr><td><input name="choices[]" class="form-control" placeholder="Απάντηση"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>')
            });

            jQuery(document).on('click', '.remove-obj', function () {
                var $this = jQuery(this);
                $this.parent().parent().remove();
            });

            jQuery('#type-q').on('change', function () {
                jQuery('.hidden_block').hide();
                var $value = jQuery(this).val();
                if ($value == 'text' || $value == 'number'){
                    jQuery('#choices_container').fadeIn();
                    jQuery('#type-multi-q').fadeIn();
                }else{
                    jQuery('#type-multi-q').fadeOut();
                }
            });

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
                    success: function (data, textStatus, XMLHttpRequest) {
                        jQuery("#choices_container-edit").fadeOut();

                        var options = "";
                        for (var i = 0; i < data['options'].length; i++) {
                            options += '<tr><td><input class="form-control" name="choices[]" value="' + data['options'][i]['pick'] + '"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>';
                            if (data['options'].length - 1 > i) {
                                options += ", ";
                            }
                        }

                        jQuery('#title-q-edit').val(data['question'][0]['question']);
                        console.log(data['question'][0]['type']);
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
                            var pos_q = jQuery('#all_q li').size();
                            var options = "";
                            for (var i = 0; i < data['options'].length; i++) {
                                options += data['options'][i]['pick'];
                                if (data['options'].length - 1 > i) {
                                    options += ", ";
                                }
                            }

                            $('.ui-state-default :input[value="' + id + '"]').parent().remove();
                            pos_q++;
                            jQuery('.questions').append('' +
                                '<li class="ui-state-default alert-success">' +
                                '<input type="hidden" name="order[]" value="' + data['question'][0]['id'] + '" />' +
                                '<div class="row"><div class="col-sm-1">' + pos_q + '</div>' +
                                    '<div class="col-sm-3">' + data['question'][0]['question'] + '</div>' +
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
                closeOnDateSelect: true
            });
            jQuery('#time_ends').datetimepicker({
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
            var array = [];
            $(".template_questions_layout").hover(function () {
                $(this).css('background-color', 'rgba(173, 216, 230, 0.6)');
            }, function () {
                // change to any color that was previously used.
                if (!$(this).hasClass('selected'))
                    $(this).css('background-color', '#fff');
            });

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
                $('.template_questions').val(array.toString());
                console.log(array.toString());
            });
        });

        $(function () {
            var array = [];

            $(".selection").on('click', function () {

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
                $('.template_questions').val(array.toString());
                console.log(array.toString());
            });
        });
    </script>

<?php }
}else{
     echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>Επεξεργασία Ερωτηματολογίου</h3>
            <div class="alert alert-danger">Δεν έχετε δικαίωμα να διαχειριστείτε αυτό το ερωτηματολόγιο</div>
        </div>
    </div>
</div>';
}
get_footer();
?>