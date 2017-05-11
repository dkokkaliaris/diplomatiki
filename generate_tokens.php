<?php
include_once "includes/init.php";
get_header();
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}

// Μέθοδος αυτόματης δημιουργίας τυχαίου κωδικού
// http://stackoverflow.com/questions/6101956/generating-a-random-password-in-php
function randomPassword($num)
{
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $num; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}


if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (!isset($_POST['questionnaire'], $_POST['no_of_tokens'], $_POST['time_begins'], $_POST['time_ends'])) {
        echo "<div class='alert alert-danger'>Παρακαλώ συμπληρώστε όλα τα πεδία.</div>";
    } else {
        $no_of_tokens = sanitize($_POST['no_of_tokens']);
        $questionnaire = sanitize($_POST['questionnaire']);
        $begin = sanitize($_POST['time_begins']);
        $end = sanitize($_POST['time_ends']);


        $max_rank = 6;
        $max_token = 6;

        // φέρνω όλα τα tokens
        $sql = "SELECT * FROM dk_tokens;";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchALL();

        $tokens_exist = [];
        foreach ($results as $result) {
            $tokens_exist[] = $result->seira . $result->token_code;
        }

        $randomSeries = randomPassword($max_rank);

        for ($i = 0; $i < $no_of_tokens; $i++) {
            // Δημιουργώ τυχαίο κωδικό και ελέγχω αν υπάρχει στον πίνακα τοκεν
            $randomNum = randomPassword($max_token);
            while (in_array($randomSeries . $randomNum, $tokens_exist)) {
                $randomNum = randomPassword($max_token);
            }

            $params = array(':user_id' => $_SESSION['userid'], ':questionnaire_id' => $questionnaire, ':seira' => $randomSeries, ':token' => $randomNum, ':from_date' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $begin))), ':to_date' => date('Y-m-d H:i', strtotime(str_replace('/', '-', $end))), ':used' => 0);
            $sql = 'INSERT INTO dk_tokens (user_id, questionnaire_id, seira, token_code, from_date, to_date, used) VALUES(:user_id, :questionnaire_id, :seira, :token, :from_date, :to_date, :used);';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);

            // το καταχωρούμε στον πίνακα
            $tokens_exist[] = $randomSeries . $randomNum;
        }

        $new_id = $dbh->lastInsertId();

        if ($new_id > 0) {
            header("Location: tokens.php");
            //header("Location: edit_lesson.php?id=$new_id");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Η εκχώρηση δεν πραγματοποιήθηκε. Δοκιμάστε ξανά.</div>";
        }
    }
}
$breadcrumb=array(
    array('title'=>'Διαχείριση Κωδικών Token','href'=>'tokens.php'),
    array('title'=>'Προσθήκη Νέου Κωδικού Token','href'=>''),
);
echo '
<div class="container-fluid">
   '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12 col-lg-6 col-md-8 col-lg-offset-3 col-md-offset-2">
            <div class="box">
            <div class="row">
                <div class="col-sm-12">
                    <h3>Διαχείριση Κωδικών Token</h3>
                </div>
            </div>

            <br/>

            <div class="row">
                <div class="col-sm-12">
                    <form action="generate_tokens.php" method="post">
                        <div class="form-group">

                            <label for="questionnaire" class="form-control-label">Ερωτηματολόγιο: </label>
                            <select name="questionnaire" id="questionnaire"
                                    class="form-control type">
                                <option value="0">Επιλογή Ερωτηματολογίου</option>';
                                $stmt = $dbh->prepare('SELECT * FROM dk_questionnaire where template = 0 and user_id = :id;');
                                $params = array(':id' => $_SESSION['userid']);
                                $stmt->execute($params);

                                $results = $stmt->fetchALL();
                                foreach ($results as $result) {
                                    echo '<option value="'.$result->id.'">'.$result->title.'</option>';
                                }
                            echo '</select>
                        </div>

                        <div class="form-group">
                            <label for="no_of_tokens" class="form-control-label">Αριθμός tokens: </label>
                            <input type="number" id="no_of_tokens" name="no_of_tokens" class="form-control" min="0"/>
                        </div>

                        <div class="form-group" id="date_start_layout">
                            <label for="time_begins" class="form-control-label">Ημερομηνία Έναρξης: </label>
                            <input type="text" class="form-control" name="time_begins" id="time_begins" autocomplete="off"/>
                        </div>

                        <div class="form-group" id="date_ends_layout">
                            <label for="time_ends" class="form-control-label">Ημερομηνία Λήξης: </label>
                            <input type="text" class="form-control" name="time_ends" id="time_ends" autocomplete="off"/>
                        </div>

                        <button class="btn btn-primary btn-sm full-width" type="submit">Δημιουργία</button>
                    </form>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>';
?>
<script>
    jQuery('#time_begins').datetimepicker({
        lang: 'el',
        timepicker: false,
        format: 'd/m/Y',
        closeOnDateSelect: true
    });
    jQuery('#time_ends').datetimepicker({
        lang: 'el',
        timepicker: false,
        format: 'd/m/Y',
        closeOnDateSelect: true
    });
</script>

<?php
get_footer();
?>
