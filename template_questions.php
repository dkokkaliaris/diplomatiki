<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}

$alert = '';
if (isset($_GET['action']) && sanitize($_GET['action']) == "delete") {
    $id = sanitize($_GET['id']);
    $continue = false;
    if($_SESSION['level']!=1){// αν δεν είναι διαχειριστής ελέγχω να δω αν έχει πρόσβαση στο ερωτηματολόγιο
        $params = array(':id' => $id, ':u_id'=>$_SESSION['userid']);
        $sql = 'SELECT COUNT(*) as count FROM dk_question WHERE id = :id AND user_id = :u_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $flag = $stmt->fetchObject();
        if($flag->count>0){
            $continue = true;
        }
    }else $continue = true;

    if($continue){
        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_question_options WHERE question_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $sql = 'DELETE FROM dk_questionnaire_questions WHERE question_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $sql = 'DELETE FROM dk_question WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $alert = "<div class='alert alert-success'>Η διαγραφή της πρότυπης ερώτησης πραγματοποιήθηκε με επιτυχία.</div>";

    }else{
        $alert = "<div class='alert alert-danger'>Δεν έχετε το δικαίωμα να διαγράψετε την ερώτηση.</div>";
    }
}

$limit = 20;
$adjacents = 5;
if (isset($_GET['page'])) {
    $page = filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT);
    $start = ($page - 1) * $limit;            //first item to display on this page
} else {
    $page = 1;
    $start = 0;                //if no page var is given, set start to 0
}

$sortby = 'order by ';
// για ταξινόμηση
if (!empty($_REQUEST['sortby'])) {
    $sortby .= sanitize($_REQUEST['sortby']);
} else {
    $sortby .= "id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}

$params=array(':id'=> $_SESSION['userid']);
$sql = 'SELECT count(*) FROM dk_questionnaire where template = 1 and user_id = :id';
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$total_pages = $stmt->fetchColumn();
$targetpage = "template_questions.php";    //your file name  (the name of this file)

$addtosql = "";
$id = (isset($_GET['action']) && sanitize($_GET['action']) == "delete"?'':isset($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '');
$question = isset($_REQUEST['question']) ? filter_var($_REQUEST['question'], FILTER_SANITIZE_STRING) : '';
$type = isset($_REQUEST['type']) ? filter_var($_REQUEST['type'], FILTER_SANITIZE_STRING) : '';

if (!empty($id)) {
    $addtosql .= " AND id LIKE '%$id%'";
}
if (!empty($question)) {
    $addtosql .= " AND question LIKE '%$question%'";
}
if (!empty($type)) {
    if($type=='text' || $type=='number'){
        $addtosql .= " AND multi_type LIKE '%$type%'";
    }else{
        $addtosql .= " AND type LIKE '%$type%'";
    }
}

// φέρνω όλες τθε ερωτήσεις
$params = array();
if ($_SESSION['level'] == 3){
    $params = array(':id' => $_SESSION['userid']);
    $sql = "SELECT * FROM dk_question where template = 1 and user_id = :id $addtosql $sortby $sorthow LIMIT $start,$limit;";
}else {
    $sql = "SELECT * FROM dk_question where template = 1 $addtosql $sortby $sorthow LIMIT $start,$limit;";
}
$stmt = $dbh->prepare($sql);
$stmt->execute($params);

$results = $stmt->fetchALL();

get_header();
echo '<div id="templateQuestion" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Προσθήκη Νέας Πρότυπης Ερώτησης</h4>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <form id="template-questions" method="post" enctype="multipart/form-data" accept-charset="utf-8" novalidate="">
                        <div class="row">
                            <div class="col-sm-6">
                                <label for="title-q" class="form-control-label">Ερώτηση: </label>
                                <textarea rows="3" class="form-control" name="question_desc" required="" id="title-q"></textarea>
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
                                                <option value="-1">Τύπος Πιθανών Απαντήσεων</option>
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
                                            <textarea rows="3" class="form-control" name="question_desc" required="" id="title-q-edit"></textarea>
                                            <label class="form-control-label" for="type-q-edit">Επιλογή Τύπου Ερώτησης: </label>
                                            <select class="form-control" id="type-q-edit" required="">
												<option value="">Επιλογή Τύπου Ερώτησης</option>
												<option value="text">Ερώτηση Πολλαπλής Επιλογής (Κειμένου)</option>
												<option value="number">Ερώτηση Πολλαπλής Επιλογής (Αριθμού)</option>
												<option value="freetext">Ερώτηση Ελεύθερου Κειμένου</option>
												<option value="file">Ερώτηση Προσθήκης Αρχείου</option>
											</select>
                                        </div>
                                        <div class="col-sm-6">
                                            <div id="q-answers-edit">
                                                <div class="hidden_block" id="choices_container-edit">
                                                    <h6>Πιθανές Απαντήσεις:</h6>
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
$breadcrumb=array(
    array('title'=>'Πρότυπες Ερωτήσεις','href'=>'')
);
$list_types = array('_'=>'','radio_text'=>'Ερώτηση Μοναδικής Επιλογής Κειμένου', 'check_text'=>'Ερώτηση Πολλαπλής Επιλογής Κειμένου','radio_number'=>'Ερώτηση Μοναδικής Επιλογής Αριθμού', 'check_number'=>'Ερώτηση Πολλαπλής Επιλογής Αριθμού', 'freetext_'=>'Ερώτηση Ελεύθερου Κειμένου', 'file_'=>'Ερώτηση Προσθήκης Αρχείου');
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <div class="alert alert-success" id="alert" style="display: none;"></div>
            <h3>Πρότυπες Ερωτήσεις
				<a class="btn btn-primary btn-sm pull-right" data-toggle="modal" data-target="#templateQuestion">Προσθήκη Νέας Πρότυπης Ερώτησης</a>
            </h3>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="questions">
                <form action="template_questions.php" method="get">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><a href="template_questions.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                                <th><a href="template_questions.php?sortby=question&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ερώτηση</a></th>
                                <th><a href="template_questions.php?sortby=type&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τύπος Ερώτησης</a></th>
                                <th>Επιλογές</th>
                                <th>Πρότυπο Ερωτηματολόγιο</th>
                                <th>Ερωτηματολόγιο</th>
                                <th>Ενέργειες</th>
                            </tr>
                            <tr>
                                <td><input type="text" class="form-control" placeholder="ID" name="id" id="id" value="'.$id.'"/></td>
                                <td><input type="text" class="form-control" placeholder="Ερώτηση" name="question" id="question" value="'.$question.'"/></td>
                                <td>
                                    <select class="form-control" id="type" name="type">
                                        <option value="">Τύπος Ερώτησης</option>
                                        <option '.($type=='text'?'selected':'').' value="text">Ερώτηση Πολλαπλής Επιλογής (Κειμένου)</option>
                                        <option '.($type=='number'?'selected':'').' value="number">Ερώτηση Πολλαπλής Επιλογής (Αριθμού)</option>
                                        <option '.($type=='freetext'?'selected':'').' value="freetext">Ερώτηση Ελεύθερου Κειμένου</option>
                                        <option '.($type=='file'?'selected':'').' value="file">Ερώτηση Προσθήκης Αρχείου</option>
                                    </select>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                                </td>

                            </tr>
                        </thead>
                        <tbody>';
                        foreach ($results as $q) {
                            echo '<tr id="item-'.$q->id.'">
                                <th scope="row">'.$q->id.'</th>
                                <td>'.$q->question.'</td>
                                <td>'.(array_key_exists($q->type.'_'.$q->multi_type, $list_types)?$list_types[$q->type.'_'.$q->multi_type]:"").'</td>
                                <td>';
                                    $params = array(':id' => $q->id);
                                    $sql = 'SELECT dk_question_options.pick FROM dk_question_options INNER JOIN dk_question ON dk_question.id=dk_question_options.question_id WHERE dk_question.id = :id';
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
                                echo'</td>
                                <td>';

                                    $params = array(':id' => $q->id);
                                    $sql = "SELECT * FROM dk_questionnaire_questions where question_id = :id;";
                                    $stmt = $dbh->prepare($sql);
                                    $stmt->execute($params);
                                    $templatesContainQuestion = $stmt->fetchALL();

                                    $i = 0;
                                    foreach ($templatesContainQuestion as $rr) {

                                        $params = array(':id' => $rr->questionnaire_id);
                                        $sql = "SELECT * FROM dk_questionnaire where id = :id;";
                                        $stmt = $dbh->prepare($sql);
                                        $stmt->execute($params);
                                        $stmt->execute();
                                        $template = $stmt->fetchObject();
                                        if ($template->template == 1) {
                                            echo '<a href="edit_template.php?id='.$rr->questionnaire_id.'">'.$rr->questionnaire_id.'></a>';
                                        }
                                    }
                                echo'</td>
                                <td>';

                                    $params = array(':id' => $q->id);
                                    $sql = "SELECT * FROM dk_questionnaire_questions where question_id = :id;";
                                    $stmt = $dbh->prepare($sql);
                                    $stmt->execute($params);
                                    $templatesContainQuestion = $stmt->fetchALL();

                                    $i = 0;
                                    foreach ($templatesContainQuestion as $rr) {

                                        $params = array(':id' => $rr->questionnaire_id);
                                        $sql = "SELECT * FROM dk_questionnaire where id = :id;";
                                        $stmt = $dbh->prepare($sql);
                                        $stmt->execute($params);
                                        $template = $stmt->fetchObject();
                                        if ($template->template == 0) {
                                            echo '<a href="edit_template.php?id='.$rr->questionnaire_id.'">'.$rr->questionnaire_id.'></a>';
                                        }
                                    }
                                echo'</td>
                                <td>
                                     <button data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '.$q->question.'" type="button" class="btn btn-sm btn-info dublicate" value="'.$q->id.'"><i class="fa fa-clone" aria-hidden="true"></i></button>
                                    <div  data-id="'.$q->id.'" class="btn btn-success btn-sm edit_q"
                                         data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '.$q->question.'"><span
                                                class="fa fa-pencil" aria-hidden="true"></span></div>
                                    <a href="template_questions.php?id='.$q->id.'&action=delete" data-id="'.$q->id.'" class="btn btn-danger btn-sm remove_q"
                                         data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '.$q->question.'"><span
                                                class="fa fa-trash-o" aria-hidden="true"></span></a>
                                </td>
                            </tr>';
                        }
                        echo'</tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">';

            // http://aspektas.com/blog/really-simple-php-pagination/
            // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
           pagination($total_pages, $_GET, $targetpage);
            // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================


        echo'</div>
    </div>
</div>';
?>
<script>
jQuery(document).ready(function () {
        $('#template-questions').on('submit', (function (e) {
            e.preventDefault();
            jQuery ('#alert').fadeOut();
            if($('#template-questions').valid()){
                var data = new FormData();
                data.append('mode', "add_question");
                data.append('form', jQuery('#template-questions').serialize());
                data.append('type', jQuery('#type-q').val());
                data.append('isTemplate', 1);
                data.append('type-multi', jQuery('#type-multi-q').val());
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

                        $('.table').append('<tr class="alert-success" id="'+ data['question'][0]['id'] +'">' +
                            '<th scope="row">' + data['question'][0]['id'] + '</th>' +
                            '<td>' + data['question'][0]['question'] + '</td>' +
                            '<td>' + data['question'][0]['type'] + '</td>' +
                            '<td>' + options + '</td>' +
                            '<td></td>' +
                            '<td></td>' +
                            '<td>' +
                            '<button data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '+ data['question'][0]['question'] +'" type="button" class="btn btn-sm btn-info dublicate" value="' + data['question'][0]['id'] + '"><i class="fa fa-clone" aria-hidden="true"></i></button>'+
                            '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '+ data['question'][0]['question'] +'"><span class="fa fa-pencil" aria-hidden="true"></span></div> ' +
                            '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '+ data['question'][0]['question'] +'"><span class="fa fa-trash-o" aria-hidden="true"></span></div>' +
                            '</td>' +
                            '</tr>'
                        );

                        jQuery('#templateQuestion .close').click();
                        jQuery ('#alert').html('Η δημιουργία της ερώτησης πραγματοποιήθηκε με επιτυχία.');
                        jQuery ('#alert').fadeIn();
                    }, error: function (jqXHR, textStatus, errorThrown) {
                        console.log(JSON.stringify(jqXHR));
                        console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                    }
                });
            }
        }));

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

        jQuery('#add_choice').on('click', function () {
            jQuery('#choice_options').append('<tr><td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>')
        });

        jQuery(document).on('click', '#add_choice-edit', function () {console.log(12);
            jQuery('#choice_edit_options').append('<tr><td><input class="form-control" name="choices[]" placeholder="Απάντηση"/></td><td><span class="fa fa-minus-circle fa-fw remove-obj" aria-hidden="true"></span></td></tr>')
        });

        jQuery(document).on('click', '.remove-obj', function () {
            var $this = jQuery(this);
            $this.parent().parent().remove();
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
        jQuery(document).on('click', '#update_q', function (e) {
            e.stopPropagation();
            e.preventDefault();
            jQuery(this).tooltip('dispose');
            jQuery ('#alert').fadeOut();
            if($('#content-questions-edit').valid()){
                var id = jQuery('#id-edit').val();
                var data = new FormData();

                data.append('mode', "update_q");
                data.append('id', jQuery('#id-edit').val());
                data.append('form', jQuery('#content-questions-edit').serialize());
                data.append('type', jQuery('#type-q-edit').val());console.log(jQuery('#type-q-edit').val());
                data.append('multi_type', jQuery('#type-multi-q-edit').val());console.log(jQuery('#type-multi-q-edit').val());

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

                        var id = data['question'][0]['id'];
                        var element = $(".table tr th:contains(" + id + ")");
                        element.parent().empty();

                        $('.table').append('<tr class="alert-success" id="' + data['question'][0]['id'] + '">' +
                            '<th scope="row">' + data['question'][0]['id'] + '</th>' +
                            '<td>' + data['question'][0]['question'] + '</td>' +
                            '<td>' + data['question'][0]['type'] + '</td>' +
                            '<td>' + options + '</td>' +
                            '<td></td>' +
                            '<td></td>' +
                            '<td>' +
                            '<button data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '+ data['question'][0]['question'] +'" type="button" class="btn btn-sm btn-info dublicate" value="' + data['question'][0]['id'] + '"><i class="fa fa-clone" aria-hidden="true"></i></button>'+
                            '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '+ data['question'][0]['question'] +'"><span class="fa fa-pencil" aria-hidden="true"></span></div> ' +
                            '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '+ data['question'][0]['question'] +'"><span class="fa fa-trash-o" aria-hidden="true"></span></div>' +
                            '</td>' +
                            '</tr>'
                        );

                        jQuery('#editQuestion .close').click();
                        jQuery ('#alert').html('Η επεξεργασία της ερώτησης πραγματοποιήθηκε με επιτυχία.');
                        jQuery ('#alert').fadeIn();
                    }, error: function (jqXHR, textStatus, errorThrown) {
                        console.log(JSON.stringify(jqXHR));
                        console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                    }
                });
            }
        });
        jQuery(document).on('click','.dublicate', function (e) {
            e.preventDefault();
            var data = new FormData(); //φταχνω ενα αντικειμενο form data
            var $id = jQuery(this).val();
            jQuery(this).tooltip('dispose');
            jQuery ('#alert').fadeOut();
            data.append('mode', "dublicate_question"); // ποιο κομμάτι κώδιικα θα κληθεί στο αρχείο AJAX PHP
            data.append('id', $id);

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
                    if(data['options'].length>0){
                        for (var i = 0; i < data['options'].length; i++) {
                            options += data['options'][i]['pick'];
                            if (data['options'].length - 1 > i) {
                                options += ", ";
                            }
                        }
                    }

                    // οτι μου εχει επιστρεψει το ajax το προσαρμοζω στην html και το βαζω στο τελος του πινακα.
                    jQuery('#item-'+$id).after('<tr class="alert-success" id="' + data['id'] + '">' +
                            '<th scope="row">' + data['id'] + '</th>' +
                            '<td>' + data['question'] + '</td>' +
                            '<td>' + data['type'] + '</td>' +
                            '<td>' + options + '</td>' +
                            '<td></td>' +
                            '<td></td>' +
                            '<td>' +
                            '<button data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '+ data['question'][0]['question'] +'" type="button" class="btn btn-sm btn-info dublicate" value="' + data['question'][0]['id'] + '"><i class="fa fa-clone" aria-hidden="true"></i></button>'+
                            '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-success edit_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '+ data['question'][0]['question'] +'"><span class="fa fa-pencil" aria-hidden="true"></span></div> ' +
                            '<div data-id="' + data['question'][0]['id'] + '" class="btn btn-danger remove_q btn-sm" data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '+ data['question'][0]['question'] +'"><span class="fa fa-trash-o" aria-hidden="true"></span></div>' +
                            '</td>' +
                            '</tr>');
                    // κλείνω το modal
                    jQuery('#simpleQuestion .close').click();
                    jQuery ('#alert').html('Το αντίγραφο της ερώτησης δημιουργήθηκε με επιτυχία.');
                    jQuery ('#alert').fadeIn();
                    jQuery('[data-toggle="tooltip"]').tooltip();
                }, error: function (jqXHR, textStatus, errorThrown) {
                    console.log(JSON.stringify(jqXHR));
                    console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                }
            });
        });
    });
</script>

<?php
get_footer();
?>
