<?php
include_once "includes/init.php";
get_header();
if(!$_SESSION){
    header("Location: /login.php");
    exit();
}
?>
<?php
if ($_SERVER['REQUEST_METHOD']=="GET") {
    if(sanitize($_GET['action'])=="delete"){
        $id = sanitize($_GET['id']);
        $stmt = $dbh->prepare('DELETE FROM dk_questionnaire WHERE id = :id');
        $params=array(':id'=> $id);
        $stmt->execute($params);
    }
}
?>

<div class="container">
    <div class="col-sm-3">
        <?php include "sidebar.php"; ?>
    </div>
    <div class="col-sm-9">
        <div class="row">
            <div class="col-sm-9">
                <h1>Ερωτηματολόγια</h1>
            </div>
            <div class="col-sm-3">
                <a class="btn btn-primary" href="add_question.php">Προσθήκη Νέου</a>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                  <th>#</th>
                  <th>Σύντομος Τίτλος</th>
                  <th>Ηημερομηνία Έναρξης</th>
                  <th>Ηημερομηνία Λήξης</th>
                  <th>Κλειδωμένο</th>
                  <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
            <?php $stmt = $dbh->prepare('SELECT * FROM dk_questionnaire');
                $stmt->execute();
                $results =$stmt->fetchALL();
                foreach($results as $result){?>
                <tr>
                  <th scope="row"><?php echo $result->id;?></th>
                  <td><?php echo $result->title;?></td>
                  <td><?php echo $result->date_begins;?></td>
                  <td><?php echo $result->date_ends;?></td>
                  <td><?php if($result->is_locked){echo "Ναι";}else{echo "Όχι";}?></td>
                  <td><a href="edit_question.php/?id=<?php echo $result->id;?>" type="button"><span class="fa fa-pencil" aria-hidden="true"></span></a> <a onclick='return confirm("Διαγραφή")'  href='?id=<?php echo $result->id;?>&action=delete'type="button"><span class="fa fa-trash-o" aria-hidden="true"></span></a></td>
                </tr>
            <?php }?>
            </tbody>
        </table>
    </div>
</div>

<?php
get_footer();
?>
