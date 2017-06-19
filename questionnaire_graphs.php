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
$stmt = $dbh->prepare("SELECT * FROM dk_questionnaire_questions where questionnaire_id = :id ;");
$params = array(':id' => $id);
$stmt->execute($params);
$results = $stmt->fetchALL();

// φέρνω τις πληροφορίες του ερωτηματολογίου
$stmt = $dbh->prepare("SELECT * FROM dk_questionnaire where id = :id ;");
$params = array(':id' => $id);
$stmt->execute($params);
$questionnaire = $stmt->fetchObject();

$breadcrumb=array(
    array('title'=>'Αποτελέσματα Αξιολογήσεων','href'=>'results.php'),
    array('title'=>'Αποτελέσματα Ερωτηματολογίων','href'=>''),
);
echo '<div class="container-fluid">
   '.show_breacrumb($breadcrumb).'

    <div class="row">
        <div class="col-xs-8">
            <h3>Αποτελέσματα Ερωτηματολογίου '.$questionnaire->title.'</h3>
        </div>
        <div class="col-xs-4 text-xs-right">
            <form class="print-form">
                <input type="button" class="btn btn-warning btn-sm" value="Εκτύπωση Αποτελεσμάτων" onClick="window.print()">
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
    $final_sum = 0;$final_mo = 0;$final_mo_ar = array();$final_labels = array();$all_labels = array();
    $x = 0;
    foreach ($results as $result) {$x++;
        echo '<div class="table-row">
            <div class="container">
                <div class="row">
                    <div class="col-xs-6">';
                        $stmt = $dbh->prepare("SELECT * FROM dk_question where id = :id ;");
                        $params = array(':id' => $result->question_id);
                        $stmt->execute($params);
                        $question = $stmt->fetchObject();
                        $all_labels[] = $question->question;
                        if($question->multi_type == 'number'){
                            $final_sum++;
                            $final_labels[] = $question->question;
                        }

                        switch($question->type):
                            case 'radio':
                                $type = ($question->multi_type=='number'?'Ερώτηση Πολλαπλής Επιλογής Αριθμού (Radio)': 'Πολλαπλής κειμένου (Radio)');
                                break;
                            case 'check':
                                $type = ($question->multi_type=='number'?'Ερώτηση Πολλαπλής Επιλογής Αριθμού (Checkbox)': 'Πολλαπλής κειμένου (Checkbox)');
                                break;
                            case 'freetext':
                                $type = 'Ερώτηση Ελεύθερου Κειμένου';
                                break;
                            case 'file':
                                $type = 'Ερώτηση Προσθήκης Αρχείου';
                                break;
                        endswitch;
                        $params = array(':questionnaire_id'=>$id, ':id' => $result->question_id);
                        $sql ="SELECT * FROM dk_answers where questionnaire_id = :questionnaire_id and question_id = :id ;";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);

                        $answers = $stmt->fetchAll();
                        $data_pie = array();
                        $data = array();
                        $data_mo = 0;
                        $labels_pie = array();
                        $sum_answer = 0;//υπολογίζουμε συνολο για μεσο ορο
                        foreach ($answers as $answer) { //για κάθε απαντηση
                            if($answer->type == 'radio'||$answer->type == 'check'){ //αν είναι radio / check
                                if(empty($data[$answer->answer] ))$data[$answer->answer]=0; //αν δεν υπάρχει τιμή στο πινακας[απαντηση] κάνε 0
                                $data[$answer->answer] ++;
                                $data_mo+=$answer->answer;
                                if(!in_array($answer->answer, $labels_pie))$labels_pie[]=$answer->answer;
                                $sum_answer++;
                            }
                        }

                        if($question->multi_type == 'number'){
                            $mo = $data_mo/$sum_answer;
                            $stmt = $dbh->prepare("SELECT MAX(CONVERT(pick, UNSIGNED INTEGER)) AS max FROM dk_question_options where question_id = :id ;");
                            $params = array(':id' => $question->id);
                            $stmt->execute($params);
                            $max = $stmt->fetchObject();
                            $percentage_mo = 100 * $mo / $max->max;
                            $final_mo += $percentage_mo;
                            $final_mo_ar[] = round($percentage_mo, 2);
                        }
                        foreach($data as $d){
                            $data_pie[] = $d;
                        }

                        echo '<strong>'.$x.'. '.$question->question.'</strong>'.(!empty($mo)?' <br />Μέσος Όρος Ερώτησης: <strong>'.round($mo,2).'</strong> / '.$max->max.'':'');
                        echo '<br />'.$type;
                        if(sizeof($data_pie)>0){
                            echo '<div class="graph-td">
                            <canvas class="canvas-graph" id="question-'.$result->question_id.'" width="200" height="200"></canvas>';
                            ?>
                            <script>
                            var $all = parseInt(<?php echo sizeof($data_pie);?>);
                            var ctx = document.getElementById("question-<?php echo $result->question_id;?>");
                            var $labels = <?php echo json_encode($labels_pie); ?>;
                            var $data = <?php echo json_encode($data_pie); ?>;
                            Chart.defaults.global.responsive = true;
                            Chart.defaults.global.maintainAspectRatio = false;
                            var myPieChart = new Chart(ctx, {
                                type: 'pie',
                                data: {
                                    labels: $labels,
                                    datasets: [
                                        {
                                            data: $data,
                                            backgroundColor: [
                                                "#FF6384",
                                                "#36A2EB",
                                                "#FFCE56",
                                                'rgb(75, 192, 192)',
                                                'rgb(153, 102, 255)',
                                                'rgb(255, 159, 64)'
                                            ],
                                            hoverBackgroundColor: [
                                                "#FF6384",
                                                "#36A2EB",
                                                "#FFCE56",
                                                'rgb(75, 192, 192)',
                                                'rgb(153, 102, 255)',
                                                'rgb(255, 159, 64)'
                                            ]
                                        }]
                                    }
                            });
                            </script>
                            <?php
                            echo '</div>';
                        }
                    echo '</td>
                    </div>
                    <div class="col-xs-6">
                        <!--div class="table-height"-->
                            <table class="table table-sm">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Απάντηση</th>
                                </tr>
                                </thead>';
                                $i=0;
                                foreach ($answers as $answer) {$i++;
                                    echo '<tr>
                                        <td>'.$i.'</td>
                                        <td>'.($answer->type != 'file'?$answer->answer:'<a href="downloadFile.php?fileName='.$answer->filename.'" target="_blank"><span class="fa fa-download" aria-hidden="true"></span></a>').'
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
if($final_mo>0){
echo '<div class="container printbreak">
    <div class="row">
        <div class="col-sm-12">
        <div class="alert alert-info margin-30-0">
            <h3>Συγκεντρωτικά αποτελέσματα</h3>
            Συνολικός Μέσος Όρος από τις αριθμητικές ερωτήσεις: <strong>'. round($final_mo / $final_sum, 2).'%</strong>
        </div>
        <div class="height400">
            <canvas id="final-graph" width="400"height="400"></canvas>';
            ?>
            <script>

            var $labels = <?php echo json_encode($final_labels); ?>;
            var $data = <?php echo json_encode($final_mo_ar); ?>;
            var colors =['rgba(255, 99, 132,', 'rgba(54, 162, 235,', 'rgba(255, 206, 86,', 'rgba(75, 192, 192,', 'rgba(153, 102, 255,', 'rgba(255, 159, 64,'];
            var $datasets = [];
            for(i=0;i<$data.length;i++){
                color = Math.floor((Math.random() * 5));
                $datasets.push({
                    label: $labels[i],
                    data: [$data[i]],
                    backgroundColor: colors[color] + '0.2)',
                    borderColor: colors[color] + '1)',
                    borderWidth: 1,
                });
            }console.log($datasets);
            var ctx = document.getElementById("final-graph");
            Chart.defaults.global.responsive = true;
            Chart.defaults.global.maintainAspectRatio = false;
            var myPieChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Συγκεντρωτικά Αποτελέσματα'],
                    datasets: $datasets
                },options: {
                    responsive: true,
                    scales: {
                        yAxes: [{
                                display: true,
                                ticks: {
                                    beginAtZero: true,
                                    steps: 10,
                                    stepValue: 5,
                                    max: 100
                                }
                            }]
                    }
                }
            });
            </script>
            <?php
        echo '</div>';
        if(!empty($all_labels)){
            echo '<table class="table table-striped"><thead class="thead-inverse table-head"><tr><th>ID<th>Όλες οι ερωτήσεις </th></tr></thead>
            <tbody>';
            $x = 0;
            foreach($all_labels as $l){$x++;
                echo "<tr><th scope='row'>$x</th><td>$l</td></tr>";
            }
            echo '</tbody>
            </table>';
        }
        echo '</div>
    </div>
</div>';
}
get_footer();
?>

