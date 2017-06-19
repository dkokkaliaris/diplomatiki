<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}else{
    if ($_SESSION['level']!=1) {
        header("Location: ".BASE_URL.'index.php');
        exit;
    }
}

$alert = '';
if (isset($_GET['del']) && sanitize($_GET['del'])>0) {
    $del = sanitize($_GET['del']);
    $params = array(':id' => $del);
    $sql = 'DELETE FROM dk_departments WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $alert .= "<div class='alert alert-success'>Η διαγραφή του τμήματος πραγματοποιήθηκε με επιτυχία.</div>";
}

if (isset($_GET['a']) && sanitize($_GET['a'])>0) {
    $alert .= "<div class='alert alert-success'>Η αλλαγή των στοιχείων του τμήματος πραγματοποιήθηκε με επιτυχία.</div>";
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

$sql = "SELECT count(*) FROM dk_departments;";
$result = $dbh->prepare($sql);
$result->execute();
$total_pages = $result->fetchColumn();
$targetpage = "departmants.php";
get_header();
$breadcrumb=array(
    array('title'=>'Διαχείριση Χρηστών','href'=>'users.php'),
    array('title'=>'Διαχείριση Τμημάτων','href'=>'')
);
echo '<div id="newDepartment" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Προσθήκη Νέου Τμήματος</h4>
            </div>
            <div class="modal-body">
                <div class="container-fluid">

                    <form id="new-department" method="post" enctype="multipart/form-data" accept-charset="utf-8" novalidate="">
                        <input type="hidden" name="department_id" id="department_id" value="" />
                        <div class="row">
                            <div class="col-sm-12">
                                <label for="department_name" class="form-control-label">Παρακαλούμε συμπληρώστε την ονομασία του τμήματος στο παρακάτω πεδίο της φόρμας.</label>
                                <input class="form-control" id="department_name" name="department_name" placeholder="Ονομασία Τμήματος" />
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
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">'.$alert.'
            <h3>Διαχείριση Τμημάτων
                <a class="btn btn-primary btn-sm pull-right" data-toggle="modal" data-target="#newDepartment">Προσθήκη Νέου Τμήματος</a>
            </h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <form action="departments.php" method="get">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th><a href="departments.php?sortby=id&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">ID</a></th>
                        <th><a href="departments.php?sortby=name&amp;sorthow='.($sorthow == "desc"?"asc":"desc").'">Ονομασία Τμήματος</a></th>
                        <th>Ενέργειες</th>
                    </tr>
                    <tr>
                        <td><input type="text" class="form-control" placeholder="ID" name="id" id="id"/></td>
                        <td><input type="text" class="form-control" placeholder="Ονομασία Τμήματος" name="name" id="name"/></td>
                        <td>
                            <button type="submit" class="btn btn-sm btn-primary">Αναζήτηση</button>
                        </td>

                    </tr>
                    </thead>';
                    $addtosql = "";

					$id = isset($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_STRING) : '';
                    $onoma = isset($_REQUEST['name']) ? filter_var($_REQUEST['name'], FILTER_SANITIZE_STRING) : '';
					if (!empty($id)) {
                        $addtosql .= " AND id LIKE '%$id%'";
                    }
                    if (!empty($onoma)) {
                        $addtosql .= " AND name LIKE '%$onoma%'";
                    }
                    //Παίρνουμε όλους τους χρήστες
                    $sql = "SELECT * FROM dk_departments WHERE 1 $addtosql $sortby $sorthow LIMIT $start,$limit";
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute();
                    $users = $stmt->fetchAll();
                    foreach ($users as $user) {

                        echo '<tr>
                              <td>' . $user->id . '</td>
                              <td id="nameDepartment-' . $user->id . '">' . $user->name . '</td>
                              <td><button class="btn btn-sm btn-success edit_q" data-id="' . $user->id . '">
                                <span class="fa fa-pencil" aria-hidden="true"></span></button> <a onclick=\'return confirm("Είστε σιγουρος ότι θέλετε να διαγράψετε το τμήμα;")\' class="btn btn-sm btn-danger" href="departments.php?del=' . $user->id . '"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>
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
        $('#new-department').on('submit', (function (e) {
            e.preventDefault();
            if($('#new-department').valid()){
                var data = new FormData();
                //data.append('file', $('#file')[0].files[0]);
                if(jQuery("#department_id").val()!=''){
                    data.append('mode', "edit_department");
                    data.append('name', jQuery('#department_name').val());
                    data.append('id', jQuery('#department_id').val());
                    jQuery.ajax({
                        type: 'POST',
                        url: 'ajax_questions.php',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        success: function (data, textStatus, XMLHttpRequest) {
                            jQuery("#nameDepartment-"+data['id']).html(data['name']);
                            jQuery("#department_id").val('');
                            jQuery('#newDepartment .close').click();
                        }, error: function (jqXHR, textStatus, errorThrown) {
                            console.log(JSON.stringify(jqXHR));
                            console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                        }
                    });
                }else{
                    data.append('mode', "add_department");
                    data.append('name', jQuery('#department_name').val());
                    jQuery.ajax({
                        type: 'POST',
                        url: 'ajax_questions.php',
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: data,
                        success: function (data, textStatus, XMLHttpRequest) {
                            console.log(data);

                            $('.table').append('<tr><td>' + data['id'] + '</td><td>' + data['name'] + '</td><td><a class="btn btn-sm btn-success" href="departments.php?id=' + data['id'] + '"><span class="fa fa-pencil" aria-hidden="true"></span></a> <a onclick=\'return confirm("Είστε σίγουρος ότι θέλετε να διαγράψετε το τμήμα;")\' class="btn btn-sm btn-danger" href="departments.php?del=' + data['id'] + '"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td></tr>');

                            jQuery('#newDepartment .close').click();
                        }, error: function (jqXHR, textStatus, errorThrown) {
                            console.log(JSON.stringify(jqXHR));
                            console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
                        }
                    });
                }

            }
        }));

        jQuery(document).on('click', '.edit_q', function (e) {
            e.preventDefault();
            var id = jQuery(this).data('id');
            var nameElement = jQuery("#nameDepartment-"+id).html();
            jQuery("#department_name").val(nameElement);
            jQuery("#department_id").val(id);
            jQuery("#newDepartment").modal('show');
        });
    });
</script>
<?php get_footer();?>