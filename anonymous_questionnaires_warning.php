<?php
include_once "includes/init.php";
get_header();
$breadcrumb=array(
    array('title'=>'Ανώνυμη Αξιολόγηση Εκπαιδευτικών Προγραμμάτων','href'=>'')
);
echo '<div class="container-fluid">
    '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12">
            <h3>ΠΡΟΣΟΧΗ!</h3>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <form method="post" action="anonymous_questionnaires.php">
            <p>Θα θέλαμε να σας ενημερώσουμε πως με την είσοδο στην σελίδα ανώνυμων αξιολογήσεων θα καταγραφεί η διεύθυνση IP σας. Η τρέχουσα διεύθυνση ΙΡ σας είναι '.$_SERVER['REMOTE_ADDR'].'.</p>
			<p>Επίσης, για να αξιολογήσετε δύο εκπαιδευτικά προγράμματα θα πρέπει να παρέλθει χρονικό διάστημα τουλάχιστον 30 λεπτών.</p>
			<p>Πατώντας το κουμπί "Συνέχεια", σημαίνει ότι αποδέχεστε όλους τους όρους και τις προυποθέσεις του συστήματος για την καταχώρηση ανώνυμων αξιολογήσεων.</p>
            <p><a class="btn btn-primary" href="'.BASE_URL.'/anonymous_questionnaires.php">Συνέχεια</a></p>
            </form>
        </div>
    </div>
</div>';
get_footer();
?>
