<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}

$alert = '';
if (isset($_GET['del']) && sanitize($_GET['del'])>0) {
    $del = sanitize($_GET['del']);
    $params = array(':id' => $del);
    $sql = 'DELETE FROM dk_arduino WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $alert .= "<div class='alert alert-success'>Η διαγραφή του Arduino πραγματοποιήθηκε με επιτυχία.</div>";
}

if (isset($_GET['a']) && sanitize($_GET['a'])>0) {
    $alert .= "<div class='alert alert-success'>Η αλλαγή των στοιχείων του Arduino πραγματοποιήθηκε με επιτυχία.</div>";
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

$sql = "SELECT count(*) FROM dk_arduino;";
$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();
$targetpage = "arduino.php";
get_header();
$breadcrumb=array(
    array('title'=>'Διαχείριση Arduino','')
);

echo '<div id="modalArduino" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Προσθήκη Νέου Arduino</h4>
            </div>
            <div class="modal-body">
                <div class="container-fluid">

                    <form id="arduino-form" method="post" enctype="multipart/form-data" accept-charset="utf-8" novalidate="">
                        <input type="hidden" name="new_arduino_id" id="new_arduino_id" value="" />
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="arduino_id" class="form-control-label">Arduino ID</label>
                                    <input class="form-control" id="arduino_id" name="arduino_id" placeholder="XXXXXXX" />
                                </div>
                                <div class="form-group">
                                    <label for="arduino_qn" class="form-control-label">Επιλέξτε ένα από τα παρακάτω ερωτηματολόγια για να εμφανιστούν οι διαθέσιμες ερωτήσεις</label>
                                    <select name="arduino_qn" id="arduino_qn" class="form-control">
                                        <option value="">Ερωτηματολόγια</option>';
                                        //Αν ειμαι διαχειριστης φερνω ολα τα ερωτηματολογια.
                                        if($_SESSION['level'] == 1){
                                            $params = array();
                                            $sql = 'SELECT A.id, A.title FROM dk_questionnaire AS A JOIN dk_questionnaire_questions AS B ON A.id=B.questionnaire_id JOIN dk_question AS C ON B.question_id = C.id WHERE C.type = "check" OR C.type = "radio" GROUP BY A.id,A.title';
                                        //Αλλιως φερνω μονο τα δικα μου ερωτηματολογια.
                                        }else{
                                            $params = array(':userid' => $_SESSION['userid']);
                                            $sql = 'SELECT A.id, A.title FROM dk_questionnaire AS A JOIN dk_questionnaire_questions AS B ON A.id=B.questionnaire_id JOIN dk_question AS C ON B.question_id = C.id WHERE (C.type = "check" OR C.type = "radio") AND A.user_id = :userid GROUP BY A.id,A.title';
                                        }
                                        $stmt = $dbh->prepare($sql);
                                        $stmt->execute($params);
                                        $results = $stmt->fetchAll();
                                        //κραταω και το πληθος
                                        $total = $stmt->rowCount();

                                        if ($total > 0) {
                                            foreach ($results as $q) {
                                                    echo'<option value="'.$q->id.'">'.$q->title.'</option>';
                                            }
                                        }
                                    echo '</select>
                                </div>
                                <div class="form-group">
                                    <label for="arduino_qt" class="form-control-label">Διαθέσιμες ερωτήσεις</label>
                                    <select name="arduino_qt" id="arduino_qt" class="form-control">
                                        <option value="">Ερωτήσεις</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <br/><br/>
                        <div class="row">
                            <div class="col-sm-12 text-sm-right">
                                <button id="add_simple" class="btn btn-primary btn-sm">Αποθήκευση</button>
                            </div>
                        </div>
                        <br/>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <h3>Διαχείριση Arduino
                <a class="btn btn-primary btn-sm pull-right open-modal" data-toggle="modal" data-target="#modalArduino">Προσθήκη Νέου Arduino</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <form action="arduino_list.php" method="get">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th><a href="arduino_list.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                        <th><a href="arduino_list.php?sortby=arduino_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Arduino</a></th>
                        <th><a href="arduino_list.php?sortby=questionnaire_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ερωτηματολόγιο</a></th>
                        <th><a href="arduino_list.php?sortby=question_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ερώτηση</a></th>
                        <th>Ενέργειες</th>
                    </tr>
                    <tr>
                        <td><input type="text" class="form-control" placeholder="ID" name="id" id="id"/></td>
                        <td><input type="text" class="form-control" placeholder="Arduino ID" name="arduino_id" id="arduino_id"/></td>
						<td></td>
                        <td><input type="text" class="form-control" placeholder="Ερώτηση" name="question" id="question"/></td>
                        <td>
                            <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                        </td>
                    </tr>
                    </thead>';
                    $addtosql = "";

					$id = isset($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
                    $arduino = isset($_REQUEST['arduino_id']) ? filter_var($_REQUEST['arduino_id'], FILTER_SANITIZE_STRING) : '';
                    $question = isset($_REQUEST['question']) ? filter_var($_REQUEST['question'], FILTER_SANITIZE_STRING) : '';
					if (!empty($id)) {
                        $addtosql .= " AND id LIKE '%$id%'";
                    }
                    if (!empty($arduino)) {
                        $addtosql .= " AND arduino_id LIKE '%$arduino%'";
                    }
                    if (!empty($question)) {
                        $addtosql .= " AND question LIKE '%$question%'";
                    }
                    //Παίρνουμε όλους τους χρήστες
                    $sql = "SELECT C.id AS id, C.arduino_id AS arduino_id, B.question AS question,D.title as questionnaire_name FROM dk_questionnaire_questions AS A JOIN dk_question AS B ON A.question_id=B.id JOIN dk_arduino AS C ON C.question_id = A.id JOIN dk_questionnaire D ON D.id=A.questionnaire_id WHERE 1 $addtosql $sortby $sorthow LIMIT $start,$limit";
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute();
                    $arduinos = $stmt->fetchAll();
                    foreach ($arduinos as $a) {

                        echo '<tr>
                              <td>' . $a->id . '</td>
                              <td id="ardID-'.$a->id.'">' . $a->arduino_id . '</td>
                              <td id="ardQN-'.$a->id.'">' . $a->questionnaire_name . '</td>
                              <td id="ardQt-'.$a->id.'">' . $a->question . '</td>
                              <td><button class="btn btn-sm btn-success edit_q" data-id="' . $a->id . '">
                                <span class="fa fa-pencil" aria-hidden="true"></span></button> <a onclick=\'return confirm("Είστε σιγουρος ότι θέλετε να διαγράψετε το Arduino;")\' class="btn btn-sm btn-danger" href="arduino_list.php?del=' . $a->id . '"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>
                          </tr>';
                    }

                echo '</table>
            </form>';

            // http://aspektas.com/blog/really-simple-php-pagination/
            // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
            pagination($total_pages, $_GET, $targetpage);
            // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================

        echo '</div>
    </div>
</div>';?>
<script>
    jQuery(document).ready(function () {
        jQuery('.open-modal').on('click', function(){
            jQuery(".modal-title").html('Προσθήκη Νέου Arduino');
            jQuery("#new_arduino_id").val("");
        });

        jQuery('#arduino-form').on('submit', (function (e) {
            e.preventDefault();
            //αν εχει συμπληρωσει ολα τα πεδια...
            if(jQuery('#arduino-form').valid()){
                //δημιουργω τον πινακα data...
                var data = new FormData();
                //ΑΝ δεν ειναι άδειο το κρυφό πεδίο σημαινει ότι είναι το modal επεξεργασίας.
                if(jQuery("#new_arduino_id").val()!=''){
                    data.append('mode', "edit_arduino");
                    data.append('id', jQuery('#new_arduino_id').val());
                    data.append('arduino_id', jQuery('#arduino_id').val());
                    data.append('questionnaire', jQuery('#arduino_qn').val());
                    data.append('question', jQuery('#arduino_qt').val());
                    jQuery.ajax({
                        type: 'POST',
                        url: 'ajax_questions.php',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        success: function (data, textStatus, XMLHttpRequest) {
                            //εχουμε ονοματησει τα td καταλληλα ωστε να εχουν μοναδικο id ανα γραμμη και ενημερωνω τα πεδια.
                            jQuery("#ardID-"+data['id']).html(data['arduino']);
                            jQuery("#ardQt-"+data['id']).html(data['question']);
                            jQuery("#ardQN-"+data['id']).html(data['questionnaire_title']);
                            jQuery("#new_arduino_id").val('');
                        }
                    });
                }else{
                    //αφου ειναι κενο τοτε κανω δημιουργια arduino
                    data.append('mode', "add_arduino");
                    data.append('arduino_id', jQuery('#arduino_id').val());
                    data.append('questionnaire', jQuery('#arduino_qn').val());
                    data.append('question', jQuery('#arduino_qt').val());
                    jQuery.ajax({
                        type: 'POST',
                        url: 'ajax_questions.php',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        success: function (data, textStatus, XMLHttpRequest) {
                            $('.table').append('<tr><td>' + data['id'] + '</td><td id="ardID-'+data['id']+'">' + data['arduino'] + '</td><td id="ardQN-'+data['id']+'">' + data['questionnaire_title'] + '</td><td id="ardQt-'+data['id']+'">' + data['question'] + '</td><td><button class="btn btn-sm btn-success edit_q" data-id="' + data['id'] + '"><span class="fa fa-pencil" aria-hidden="true"></span></button> <a onclick=\'return confirm("Είστε σιγουρος ότι θέλετε να διαγράψετε το Arduino;")\' class="btn btn-sm btn-danger" href="arduino_list.php?del=' + data['id'] + '"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td></tr>');
                        }
                    });
                }
                jQuery('#modalArduino .close').click();

            }
        }));

        function get_options(id = null){
            jQuery("#arduino_qt option").each(function() {
                if(jQuery(this).val()!='')
                    jQuery(this).remove();
            });
            var data = new FormData();
            data.append('mode', "get_arduino_questions");
            data.append('id', id);
            jQuery.ajax({
                type: 'POST',
                url: 'ajax_questions.php',
                cache: false,
                contentType: false,
                processData: false,
                data: data,
                success: function (data, textStatus, XMLHttpRequest) {
                    if(data['questions'].length>0){
                        for(x=0;x<data['questions'].length;x++){
                            jQuery('#arduino_qt').append('<option value="'+data['questions'][x]['id']+'">'+data['questions'][x]['question']+'</option>')
                        }
                    }
                }
            });
        }

        jQuery(document).on('click', '.edit_q', function (e) {
            e.preventDefault();
            var id = jQuery(this).data('id');
            var data = new FormData();
            data.append('mode', "get_arduino");
            data.append('id', id);
            jQuery.ajax({
                type: 'POST',
                url: 'ajax_questions.php',
                cache: false,
                contentType: false,
                processData: false,
                data: data,
                success: function (data, textStatus, XMLHttpRequest) {
                    jQuery("#new_arduino_id").val(data['id']);
                    jQuery("#arduino_id").val(data['arduino_id']);
                    jQuery("#arduino_qn").val(data['questionnaire']).change();
                    jQuery("#arduino_qt").val(data['question']);

                    jQuery(".modal-title").html('Επεξεργασία Arduino');
                    jQuery("#modalArduino").modal('show');
                }
            });
        });

       jQuery("#arduino_qn").on('change', function(){
            var $questionnaire =jQuery(this).children(":selected").val();
            get_options($questionnaire);
        });


        jQuery('#modalArduino').on('hidden.bs.modal', function (e) {
            jQuery('#arduino_id').val('');
            jQuery('#arduino_qn').val('');
            jQuery("#arduino_qt option").each(function() {
                if(jQuery(this).val()!='')
                    jQuery(this).remove();
            });
        });
    });
</script>
<?php get_footer();?>