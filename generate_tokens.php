<?php
include_once "includes/init.php";
if (!is_logged_in()) {
    header("Location: ".BASE_URL.'login.php');
    exit;
}
$alert = '';
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (!isset($_POST['questionnaire'], $_POST['no_of_tokens'], $_POST['time_begins'], $_POST['time_ends'])) {
        $alert = "<div class='alert alert-danger'>Παρακαλούμε συμπληρώστε όλα τα πεδία της φόρμας.</div>";
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
            header("Location: tokens.php?status=1");
            exit;
        } else {
            $alert = "<div class='alert alert-danger'>Η δημιουργία των κωδικών δεν πραγματοποιήθηκε με επιτυχία. Παρακαλούμε δοκιμάστε ξανά.</div>";
        }
    }
}
get_header();
$breadcrumb=array(
    array('title'=>'Διαχείριση Κωδικών Token','href'=>'tokens.php'),
    array('title'=>'Δημιουργία Κωδικών Token','href'=>''),
);
echo '
<div class="container-fluid">
   '.show_breacrumb($breadcrumb).'
    <div class="row">
        <div class="col-sm-12 col-lg-6 col-md-8 col-lg-offset-3 col-md-offset-2">'.$alert.'
            <div class="box">
            <div class="row">
                <div class="col-sm-12">
                    <h4>Δημιουργία Κωδικών Token</h4>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <form action="generate_tokens.php" method="post" id="generate_tokens_form" novalidate="">
                        <div class="form-group">
                            <label for="questionnaire" class="form-control-label">Ερωτηματολόγιο: </label>
                            <select name="questionnaire" id="questionnaire" class="form-control type" required="">
                                <option value="">Επιλογή Ερωτηματολογίου</option>';

                                if($_SESSION['level'] == 1 || $_SESSION['level'] == 2){
                                    $stmt = $dbh->prepare('SELECT * FROM dk_questionnaire where template = 0');
                                    $params = array();
                                }else {
                                    $stmt = $dbh->prepare('SELECT * FROM dk_questionnaire where template = 0 and user_id = :id;');
                                    $params = array(':id' => $_SESSION['userid']);
                                }

                                $stmt->execute($params);

                                $results = $stmt->fetchALL();
                                foreach ($results as $result) {
                                    echo '<option value="'.$result->id.'">'.$result->title.'</option>';
                                }
                            echo '</select>
                        </div>

                        <div class="form-group">
                            <label for="no_of_tokens" class="form-control-label">Πλήθος Κωδικών Token: </label>
                            <input type="number" id="no_of_tokens" name="no_of_tokens" class="form-control" min="0" required=""/>
                        </div>

                        <div class="form-group" id="date_start_layout">
                            <label for="time_begins" class="form-control-label">Ημερομηνία Έναρξης: </label>
                            <input type="text" class="form-control" name="time_begins" id="time_begins" autocomplete="off" required=""/>
                        </div>

                        <div class="form-group" id="date_ends_layout">
                            <label for="time_ends" class="form-control-label">Ημερομηνία Λήξης: </label>
                            <input type="text" class="form-control" name="time_ends" id="time_ends" autocomplete="off" required=""/>
                        </div>

                        <button class="btn btn-primary btn-sm full-width" type="submit">Δημιουργία Κωδικών</button>
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
    jQuery(document).ready(function () {
        jQuery('#generate_tokens_form').validate();
    });
</script>

<?php
get_footer();
?>