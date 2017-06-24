<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();

$limit = 20;
$adjacents = 5;
if (isset($_GET['page'])) {
    $page = sanitize($_GET['page']);
    $start = ($page - 1) * $limit;            //first item to display on this page
} else {
    $page = 1;
    $start = 0;                //if no page var is given, set start to 0
}

$sortby = 'order by ';

$addtosql = "";
$search_id = isset($_REQUEST['id']) ? sanitize($_REQUEST['id']) : '';
$title = isset($_REQUEST['title']) ? sanitize($_REQUEST['title']) : '';
$lesson = isset($_REQUEST['lesson']) ? sanitize($_REQUEST['lesson']) : '';
$last_editor = isset($_REQUEST['last_editor']) ? sanitize($_REQUEST['last_editor']) : '';
$username = isset($_REQUEST['username']) ? sanitize($_REQUEST['username']) : '';
$time_begins = isset($_REQUEST['time_begins']) ? sanitize(urldecode($_REQUEST['time_begins'])) : '';
$time_ends = isset($_REQUEST['time_ends']) ? sanitize(urldecode($_REQUEST['time_ends'])) : '';

if (!empty($search_id)) {
    $addtosql .= " AND A.id = $search_id";
}
if (!empty($title)) {
    $addtosql .= " AND A.title LIKE '%$title%'";
}
if (!empty($lesson)) {
    $addtosql .= " AND C.name LIKE '%$lesson%'";
}
if (!empty($last_editor)) {
    $addtosql .= " AND (D.username LIKE '%$last_editor%' OR D.first_name LIKE '%$last_editor%' OR D.last_name LIKE '%$last_editor%')";
}
if (!empty($username)) {
    $addtosql .= " AND (B.username LIKE '%$username%' OR B.first_name LIKE '%$username%' OR B.last_name LIKE '%$username%')";
}
if (!empty($time_begins)) {
    $addtosql .= " AND (A.time_begins BETWEEN '$time_begins 00:00:00' AND '$time_begins 23:59:59')";
}
if (!empty($time_ends)) {
    $addtosql .= " AND (A.time_ends BETWEEN '$time_ends 00:00:00' AND '$time_ends 23:59:59')";
}
// για ταξινόμηση
if (!empty($_REQUEST['sortby'])) {
    $sortby .= sanitize($_REQUEST['sortby']);
} else {
    $sortby .= "A.id";
}

if (!empty($_REQUEST['sorthow'])) {
    $sorthow = sanitize($_REQUEST['sorthow']);
} else {
    $sorthow = "desc";
}

// φέρνω όλα τα ερωτηματολόγια
$params = array();
if ($_SESSION['level'] == 3){
    $params = array(':id' => $_SESSION['userid'] );
    $sql = "SELECT A.* FROM dk_questionnaire A JOIN dk_users B ON A.user_id=B.id JOIN dk_lessons C ON A.lesson_id=C.id JOIN dk_users D ON A.last_editor=D.id WHERE A.template = 0 and A.user_id = :id and (lockedtime is null or lockedtime < NOW()) $addtosql $sortby $sorthow LIMIT $start,$limit;";
    $sql_count = "SELECT count(*) FROM dk_questionnaire A JOIN dk_users B ON A.user_id=B.id JOIN dk_lessons C ON A.lesson_id=C.id JOIN dk_users D ON A.last_editor=D.id WHERE A.template = 0 and A.user_id = :id and (lockedtime is null or lockedtime < NOW()) $addtosql;";
}else{
    $sql = "SELECT A.* FROM dk_questionnaire  A JOIN dk_users B ON A.user_id=B.id JOIN dk_lessons C ON A.lesson_id=C.id JOIN dk_users D ON A.last_editor=D.id WHERE A.template = 0 $addtosql $sortby $sorthow LIMIT $start,$limit;";
    $sql_count = "SELECT count(*) FROM dk_questionnaire  A JOIN dk_users B ON A.user_id=B.id JOIN dk_lessons C ON A.lesson_id=C.id JOIN dk_users D ON A.last_editor=D.id WHERE A.template = 0 $addtosql;";
}
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();

$stmt = $dbh->prepare($sql_count);
$stmt->execute($params);
$total_pages = $stmt->fetchColumn();

/* Setup page vars for display. */
if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
$prev = $page - 1;                            //previous page is page - 1
$next = $page + 1;                            //next page is page + 1
$lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
$lpm1 = $lastpage - 1;
$targetpage = "questionnaires.php";    //your file name  (the name of this file)

$breadcrumb=array(
    array('title'=>'Ερωτηματολόγια','href'=>'')
);
$str = '';
if( $_SESSION['level']==1 || $_SESSION['level']==2 )$str = ' του Συστήματος';
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
        <div class="row">
            <div class="col-sm-12">
                <div class="alert alert-success" id="alert" style="display: none;"></div>
                <h3>Ερωτηματολόγια'.$str.'
                    <a class="btn btn-primary btn-sm pull-right" href="add_questionnaire.php">Προσθήκη Νέου Ερωτηματολογίου</a>
                </h3>
            </div>
        </div>
        <div class="row">
        <div class="col-sm-12">
        <form action="questionnaires.php" method="get">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><a href="questionnaires.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                        <th><a href="questionnaires.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a></th>
                        <th><a href="questionnaires.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Εκπαιδευτικό Πρόγραμμα</a></th>
                        <th>Σύνολο Ερωτήσεων</th>
                        <th><a href="questionnaires.php?sortby=last_editor&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τελευταία Τροποποίηση</a></th>'.
                        ($_SESSION['level'] == 1 || $_SESSION['level'] == 2?'
                        <th><a href="questionnaires.php?sortby=user_id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Επιβλέπων Καθηγητής</a></th>
                        ':'').'
                        <th><a href="questionnaires.php?sortby=time_begins&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Έναρξης</a></th>
                        <th><a href="questionnaires.php?sortby=time_ends&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ημερομηνία Λήξης</a></th>
                        <th>Ενέργειες</th>
                    </tr>
                    <tr>
                        <td><input type="text" class="form-control" placeholder="ID" name="id" id="id" value="'.$search_id.'"/></td>
                        <td><input type="text" class="form-control" placeholder="Τίτλος" name="title" id="tilte" value="'.$title.'"/></td>
                        <td><input type="text" class="form-control" placeholder="Εκπαιδευτικό Πρόγραμμα" name="lesson" id="lesson" value="'.$lesson.'"/></td>
                        <td></td>
                        <td><input type="text" class="form-control" placeholder="Username Χρήστη" name="last_editor" id="last_editor" value="'.$last_editor.'"/></td>'.
                        ($_SESSION['level'] == 1 || $_SESSION['level'] == 2?
                            '<td><input type="text" class="form-control" placeholder="Επιβλέπων Καθηγητής" name="username" id="username" value="'.$username.'"/></td>'
                        :'').'

                        <td><input type="text" class="form-control" placeholder="Ημερομηνία Έναρξης" name="time_begins" id="time_begins" value="'.$time_begins.'" /></td>
                        <td><input type="text" class="form-control" placeholder="Ημερομηνία Λήξης" name="time_ends" id="time_ends" value="'.$time_ends.'" /></td>

                        <td>
                            <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                        </td>

                    </tr>
                </thead>
                <tbody>';

                foreach ($results as $result) {
                    echo '<tr id="item-'.$result->id.'">
                        <th scope="row">'.$result->id.'</th>
                        <td>'.$result->title.'</td>
                        <td>';
                            $params = array(':id' => $result->lesson_id);
                            $sql = "SELECT * FROM dk_lessons where id = :id";
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute($params);
                            $lesson = $stmt->fetchObject();

                            echo $lesson->title;
                        echo '</td>
                        <td>';
                            $params = array(':id' => $result->id);
                            $sql = "SELECT count(*) FROM dk_questionnaire_questions WHERE questionnaire_id = :id";
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute($params);
                            echo $stmt->fetchColumn();
                        echo '</td>
                        <td>';
                            // φέρνω τον χρήστη που επεξεργάστηκε τελυταία φορά το ερωτηματολόγιο
                            $params = array(':id' => $result->last_editor);
                            $sql = "SELECT * FROM dk_users where id = :id";
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute($params);
                            $lastTimeEditor = $stmt->fetchObject();
                            echo $lastTimeEditor->username;
                        echo '</td>';
                        if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
                            echo '<td>';
                                // φέρνω τον χρήστη που ανήκει το ερωτηματολόγιο
                                $params = array(':id' => $result->user_id);
                                $sql = "SELECT * FROM dk_users where id = :id";
                                $stmt = $dbh->prepare($sql);
                                $stmt->execute($params);
                                $lastTimeEditor = $stmt->fetchObject();
                                echo $lastTimeEditor->username;
                               echo '</td>';
                        }
                        echo '<td>';
                            if ($result->template == 0)
                                echo (new DateTime($result->time_begins))->format('d/m/Y H:i');
                            else echo '-';
                        echo '</td>
                        <td>';
                            if ($result->template == 0)
                                echo (new DateTime($result->time_ends))->format('d/m/Y H:i');
                            else echo '-';

                        echo '</td>
                        <td>';
                            $flag_btns = true;
                            if($_SESSION['level']!=1 &&$_SESSION['userid']==$result->user_id && $result->lockedtime>date('Y-m-d H:i:s')&& !empty($flag_btns)){
                                $flag_btns = false;
                            }
                            if($flag_btns){
                                echo '<button data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '.$result->title.'" class="btn btn-sm btn-info dublicate" type="button" value="'.$result->id.'"><i class="fa fa-clone" aria-hidden="true"></i></button>
                                <a data-toggle="tooltip" data-placement="bottom" title="Προβολή '.$result->title.'" class="btn btn-sm btn-warning" target="_blank" href="questionnaire_pdf.php?id='.$result->id.'"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>
                                <a data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '.$result->title.'" class="btn btn-sm btn-success" href="edit_questionnaire.php?id='.$result->id.'"><span class="fa fa-pencil" aria-hidden="true"></span></a>
                                <a data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '.$result->title.'" data-id="'.$result->id.'" class="btn btn-sm btn-danger remove-item" href="questionnaires.php?del=' . $result->id . '"><span class="fa fa-trash-o" aria-hidden="true"></span></a>';
                            }
                        echo '</td>
                    </tr>';
                }
                echo'</tbody>
            </table>
        </form>
        </div>
        </div>
        <div class="row">
        <div class="col-sm-12">';
        // http://aspektas.com/blog/really-simple-php-pagination/
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
        pagination($total_pages, $_GET, $targetpage);
        // ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================

        echo '</div>
    </div>
</div>';?>
<script>
jQuery(document).ready(function () {
    //Διαγραφή όλων των απαντησεων του ερωτηματολογιου
    function delete_all_answers(id){
        var data = new FormData();
        data.append('mode', "delete_all_answers");
        data.append('id', id);
        //στέλνω το id του ερωτηματολογιου που θελω να σβησω και παιρνω απαντηση απο το ajax_questions.php
        jQuery.ajax({
            type: 'POST',
            url: 'ajax_questions.php',
            cache: false,
            contentType: false,
            processData: false,
            data: data,
            success: function (data, textStatus, XMLHttpRequest) {console.log(data);
                jQuery('#item-'+id).fadeOut();
                jQuery('#item-'+id).remove();
                jQuery ('#alert').html('Η διαγραφή του ερωτηματολογίου και των ερωτήσεων του πραγματοποιήθηκε με επιτυχία.');
                jQuery ('#alert').fadeIn();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(JSON.stringify(jqXHR));
                console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
            }
        });
    }

    //διαγράφω το ερωτηματολογιο
    function delete_questionnaire(id){
        jQuery ('#alert').fadeOut();
        var data = new FormData();
        data.append('mode', "delete_questionnaire");
        data.append('id', id);

        jQuery.ajax({
            type: 'POST',
            url: 'ajax_questions.php',
            cache: false,
            contentType: false,
            processData: false,
            data: data,
            success: function (data, textStatus, XMLHttpRequest) {
                jQuery('#item-'+id).fadeOut();
                jQuery('#item-'+id).remove();
                jQuery ('#alert').html('Η διαγραφή του ερωτηματολογίου πραγματοποιήθηκε με επιτυχία.');
                jQuery ('#alert').fadeIn();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(JSON.stringify(jqXHR));
                console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
            }
        });
    }

    //Οταν πατηθεί το κοκκινο κουμπι της διαγραφής είναι ο πρώτος ελεγχος.
    jQuery('.remove-item').on('click', function (e) {
        e.preventDefault();
        jQuery(this).tooltip('dispose');
        var id = jQuery(this).data('id');
        jQuery ('#alert').fadeOut();
        var data = new FormData();
        //ζηταει να μερτρησει ποσες απαντησεις εχουν δωθει για το ερωτηματολογιο.
        data.append('mode', "questionnaire_count");
        data.append('id', id);

        jQuery.ajax({
            type: 'POST',
            url: 'ajax_questions.php',
            cache: false,
            contentType: false,
            processData: false,
            data: data,
            success: function (data, textStatus, XMLHttpRequest) {
                if(data['flag']>0){
                    //αν εχει απαντησεις και δεν ειναι κενο τοτε εμφανιζει αυτό...
                    if(confirm('Δε μπορείτε να διαγράψετε το ερωτηματολόγιο επειδή υπάρχουν απαντήσεις.Πρέπει πρώτα να διαγράψετε τις απαντήσεις του ερωτηματολογίου. Πατήστε Συνέχεια για να διαγραφούν οι απαντήσεις.')){
                        if(confirm('Προσοχή: έχετε επιλέξει να διαγραφούν συνολικά ['+data['flag']+'] απαντήσεις με ID '+data['answers']+' είστε σίγουροι ότι θέλετε να το κάνετε; Παράλληλα θα διαγραφεί και το Ερωτηματολόγιο.')){
                            //αν επιμενεις να πατησεις οκ θα σβησει ολες τις απαντησεις
                            delete_all_answers(id);
                        }
                    }
                }else{
                    //αν δεν εχει καθολου απαντησεις απλα το σβηνει
                    if(confirm('Θέλετε να διαγράψετε το ερωτηματολόγιο;')){
                        delete_questionnaire(id);
                    }
                }
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                alert(errorThrown);
            }
        });
    });

    //Αντιγραφή ερωτηματολογιου.
    jQuery('.dublicate').on('click', function (e) {
        e.preventDefault();
        jQuery(this).tooltip('dispose');
        var data = new FormData(); //φταχνω ενα αντικειμενο form data
        jQuery ('#alert').fadeOut();
        var $id = jQuery(this).val();
        data.append('mode', "dublicate_questionnaire"); // ποιο κομμάτι κώδιικα θα κληθεί στο αρχείο AJAX PHP
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
                // οτι μου εχει επιστρεψει το ajax το προσαρμοζω στην html και το βαζω στο τελος του πινακα.
                jQuery('#item-'+$id).after('<tr class="alert-success" id="'+data['id']+'"><th scope="row">' + data['id'] + '</th><td>'+data['question']+'</td><td>'+data['lesson']+'</td><td>'+data['questionnaire_sum']+'</td><td>'+data['last_editor']+'</td>'+(data['editor']!=null?'<td>'+data['editor']+'</td>':'')+'<td>'+data['time_begins']+'</td><td>'+data['time_ends']+'</td><td><button data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '+data['question']+'" class="btn btn-sm btn-info dublicate" type="button" value="'+data['id']+'"><i class="fa fa-clone" aria-hidden="true"></i></button><a data-toggle="tooltip" data-placement="bottom" title="Προβολή '+data['question']+'" class="btn btn-sm btn-warning" target="_blank" href="questionnaire_pdf.php?id='+data['id']+'"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a><a data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '+data['question']+'" class="btn btn-sm btn-success" href="edit_questionnaire.php?id='+data['id']+'"><span class="fa fa-pencil" aria-hidden="true"></span></a><a data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '+data['question']+'" data-id="'+data['id']+'" class="btn btn-sm btn-danger remove-item" href="questionnaires.php?del='+data['id']+'"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td></tr>');

                jQuery ('#alert').html('Το αντίγραφο του ερωτηματολογίου δημιουργήθηκε με επιτυχία.');
                jQuery ('#alert').fadeIn();
                jQuery('[data-toggle="tooltip"]').tooltip();
            }
        });
    });
});
jQuery('#time_begins').datetimepicker({
    lang: 'el',
    timepicker: false,
    format: 'Y-m-d',
    formatDate: 'd/m/Y'
});
jQuery('#time_ends').datetimepicker({
    lang: 'el',
    timepicker: false,
    format: 'Y-m-d',
    formatDate: 'd/m/Y'
});
</script>
<?php get_footer();?>