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
        $sql = 'SELECT COUNT(*) as count FROM dk_questionnaire WHERE id = :id AND user_id = :u_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $flag = $stmt->fetchObject();
        if($flag->count>0){//
            $continue = true;
        }
    }else $continue = true;
    if($continue){
        $params=array(':id'=> $id);
        $sql = 'DELETE FROM dk_questionnaire_questions WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $sql = 'DELETE FROM dk_tokens WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $sql = 'DELETE FROM dk_questionnaire_channel WHERE id_questionnaire = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);//διαγραφουμε πρωτα από τον πίνακα dk_questionnaire_channel, διότι υπάρχει foreign key

        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_questionnaire WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $alert = "<div class='alert alert-success'>Η διαγραφή του πρότυπου ερωτηματολογίου πραγματοποιήθηκε με επιτυχία.</div>";
    }else{
        $alert .= "<div class='alert alert-danger'>Δεν έχετε δικαίωμα να διαγράψετε το ερωτηματολόγιο.</div>";
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
$targetpage = "templates.php";    //your file name  (the name of this file)

// φέρνω όλα τα Templates
$addtosql = "";
$id = (isset($_GET['action']) && sanitize($_GET['action']) == "delete" ?'':isset($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '');
$title = isset($_REQUEST['title']) ? filter_var($_REQUEST['title'], FILTER_SANITIZE_STRING) : '';

if (!empty($id)) {
    $addtosql .= " AND id LIKE '%$id%'";
}
if (!empty($title)) {
    $addtosql .= " AND title LIKE '%$title%'";
}

$params=array();
if ($_SESSION['level'] == 3){
    $params=array(':id'=> $_SESSION['userid']);
    $sql = "SELECT * FROM dk_questionnaire where template = 1 and user_id = :id and (lockedtime is null or lockedtime < NOW()) $addtosql $sortby $sorthow LIMIT $start,$limit;";
} else {
	$sql  = "SELECT * FROM dk_questionnaire where template = 1 $addtosql $sortby $sorthow LIMIT $start,$limit;";
}
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();

get_header();
$breadcrumb=array(
    array('title'=>'Πρότυπα Ερωτηματολόγια','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <div class="alert alert-success" id="alert" style="display: none;"></div>
            <h3>Πρότυπα Ερωτηματολόγια
                <a class="btn btn-primary btn-sm pull-right" href="add_template.php">Προσθήκη Νέου Πρότυπου Ερωτηματολογίου</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
        <form action="templates.php" method="get">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th><a href="templates.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                    <th><a href="templates.php?sortby=title&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Τίτλος</a></th>
                    <th>Ενέργειες</th>
                </tr>
                <tr>
                    <td><input type="text" class="form-control" placeholder="ID" name="id" id="id" value="'.$id.'"/></td>
                    <td><input type="text" class="form-control" placeholder="Τίτλος" name="title" id="title" value="'.$title.'"/></td>
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
                        <td>
                            <button data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '.$result->title.'" type="button" class="btn btn-sm btn-info dublicate" value="'.$result->id.'"><i class="fa fa-clone" aria-hidden="true"></i></button>
                            <a data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '.$result->title.'" href="edit_template.php?id='.$result->id.'" class="btn btn-success btn-sm"><span class="fa fa-pencil" aria-hidden="true"></span></a>
                            <a data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '.$result->title.'" onclick=\'return confirm("Θέλετε να διαγράψετε το πρότυπο ερωτηματολόγιο;")\' class="btn btn-danger btn-sm" href="templates.php?action=delete&id='.$result->id.'"><span class="fa fa-trash-o" aria-hidden="true"></span></a>
                        </td>
                    </tr>';
                }
                echo '</tbody>
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
    jQuery('.dublicate').on('click', function (e) {
        e.preventDefault();
        jQuery(this).tooltip('dispose');
        jQuery ('#alert').fadeOut();
        var data = new FormData(); //φτιαχνω ενα αντικειμενο form data
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
                jQuery('#item-'+$id).after('<tr class="alert-success" id="item-'+data['id']+'"><th scope="row">'+data['id']+'</th><td>'+data['question']+'</td><td><button type="button" data-toggle="tooltip" data-placement="bottom" title="Αντιγραφή '+data['question']+'" class="btn btn-sm btn-info dublicate" value="'+data['id']+'"><i class="fa fa-clone" aria-hidden="true"></i></button> <a data-toggle="tooltip" data-placement="bottom" title="Επεξεργασία '+data['question']+'" href="edit_template.php?id='+data['id']+'" class="btn btn-success btn-sm" type="button"><span class="fa fa-pencil" aria-hidden="true"></span></a> <a data-toggle="tooltip" data-placement="bottom" title="Διαγραφή '+data['question']+'" onclick=\'return confirm("Διαγραφή")\' class="btn btn-danger btn-sm" href="templates.php?action=delete&id='+data['id']+'&"type="button"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td></tr>');
                jQuery ('#alert').html('Το αντίγραφο του πρότυπου ερωτηματολογίου δημιουργήθηκε με επιτυχία.');
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