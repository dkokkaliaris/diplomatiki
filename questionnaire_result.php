<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
get_header();?>
<script src="<?php echo BASE_URL ?>js/Chart.min.js"></script>
<?php $id = sanitize($_GET['id']);

// φέρνω όλες τις απαντήσεις
$params = array(':id' => $id);
$sql = "SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id ;";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchALL();

// φέρνω τις πληροφορίες του ερωτηματολογίου
$params = array(':id' => $id);
$sql = "SELECT * FROM dk_questionnaire where id = :id ;";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);

$questionnaire = $stmt->fetchObject();
$breadcrumb=array(
    array('title'=>'Αποτελέσματα Αξιολογήσεων ανά Ερωτηματολόγιο','href'=>'results.php'),
    array('title'=>'Αποτελέσματα ερωτηματολογίου','href'=>''),
);
echo '<div class="container-fluid">
   '.show_breacrumb($breadcrumb).'

    <div class="row">
        <div class="col-xs-8">
            <h3>Αποτελέσματα ερωτηματολογίου '.$questionnaire->title.'</h3>
        </div>
        <div class="col-xs-4 text-xs-right">
            <form>
                <input type="button" class="btn btn-warning btn-sm" value="Εκτύπωση Σελίδας" onClick="window.print()">
            </form>
        </div>
    </div>
</div>
<div class="header-row">
    <div class="container">
        <div class="row">
            <div class="col-xs-6">Ερώτηση</div>
            <div class="col-xs-6">Απαντήσεις</div>
        </div>
    </div>
</div>
<div class="table-charts">';
    foreach ($results as $result) {
        echo '<div class="table-row">
            <div class="container">
                <div class="row">
                    <div class="col-xs-6">';

                        $params = array(':id' => $result->question_id);
                        $sql = "SELECT * FROM dk_question where id = :id ;";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);


                        $question = $stmt->fetchObject();
                        if($question->multi_type == 'number'){
                            $final_sum++;
                            $final_labels[] = $question->question;
                        }

                        switch($question->type):
                            case 'radio':
                                $type = ($question->multi_type=='number'?'Πολλαπλής αιρθμού (Radio)': 'Πολλαπλής κειμένου (Radio)');
                                break;
                            case 'check':
                                $type = ($question->multi_type=='number'?'Πολλαπλής αιρθμού (Checkbox)': 'Πολλαπλής κειμένου (Checkbox)');
                                break;
                            case 'freetext':
                                $type = 'Ελεύθερο κείμενο';
                                break;
                            case 'file':
                                $type = 'Αρχείο';
                                break;
                        endswitch;

                        echo '<strong>'.$question->question.'</strong>';
                        echo '<br />'.$type.
                        '</td>
                    </div>
                    <div class="col-xs-6">
                        <!--div class="table-height"-->
                            <table class="table table-sm">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Απάντηση</th>
                                </tr>
                                </thead>';
                                $i=0;
                                $params = array(':questionnaire_id'=>$id, ':id' => $result->question_id);
                                $sql = "SELECT * FROM dk_answers where questionnaire_id = :questionnaire_id and question_id = :id ;";
                                $stmt = $dbh->prepare($sql);

                                $stmt->execute($params);

                                $answers = $stmt->fetchAll();
                                foreach ($answers as $answer) {$i++;
                                    echo '<tr>
                                        <td>'.$i.'</td>
                                        <td>'.($answer->type != 'file'?$answer->answer:'<a href="downloadFile.php?fileName='.$answer->filename.'" target="_blank" type="button"><span class="fa fa-download" aria-hidden="true"></span></a>').'
                                        </td>
                                    </tr>';
                                }
                            echo '</table>
                        <!--/div-->
                    </div>
                </div>
            </div>
        </div>';
    }
echo '</div>';

get_footer();
?>
