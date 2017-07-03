<?php
header('Content-Type: application/json');
include_once "includes/init.php";
$arr = array();

$list_types = array('_'=>'','radio_text'=>'Ερώτηση Μοναδικής Επιλογής Κειμένου', 'check_text'=>'Ερώτηση Πολλαπλής Επιλογής Κειμένου','radio_number'=>'Ερώτηση Μοναδικής Επιλογής Αριθμού', 'check_number'=>'Ερώτηση Πολλαπλής Επιλογής Αριθμού', 'freetext_'=>'Ερώτηση Ελεύθερου Κειμένου', 'file_'=>'Ερώτηση Προσθήκης Αρχείου');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($_POST['mode'] == 'add_simple') {//pros8hkh erwthshs apo aplh erwthsh
        parse_str($_POST['form'], $form);
        $question = sanitize($form['question_desc']); //perigrafh neas erwthshs
        $type = sanitize($_POST['type']);//typos erwthshs
        $questionnaire_id = sanitize($_POST['questionnaire_id']);//id questionnaire

        //eisagwgh neas erwthshs και κρατάω το ID της
        $params = array(':question' => $question, ':type' => $type, ':template' => false, ':user_id' => $_SESSION['userid']);
        $sql = 'INSERT INTO dk_question (question, type, template, user_id) VALUES (:question, :type, :template, :user_id)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $new_id = $dbh->lastInsertId();
        if ($new_id > 0) {//eisagwgh apanthsewn ths
            if ($type == 'radio' || $type == 'check') {
                foreach ($form['choices'] as $key => $val) {//8a paroume tis times mono apo ton typo pou epileksame
                    $params = array(':new_id' => $new_id, ':value' => $val);
                    $sql = 'INSERT INTO dk_question_options (question_id, pick) VALUES (:new_id, :value)';
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute($params);
                }
            }
        }
        // ευρεση της τελευταίας αρίθμιησης της ερώτησης για ταξινόμηση και αύξηση κατα +1
        $params = array(':id' => $questionnaire_id);
        $sql = 'SELECT MAX(order_by) AS max FROM dk_questionnaire_questions WHERE questionnaire_id = :id ';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $max = $stmt->fetchObject();
        $max = $max->max;
        $max++;

        //update last editor
        $params = array(':id' => $questionnaire_id, ':last_editor' => $_SESSION['userid']);
        $sql = 'UPDATE dk_questionnaire SET last_editor = :last_editor where id = :id ;';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        //insert new question to dk_questionnaire_questions
        $params = array(':questionnaire_id' => $questionnaire_id, ':question_id' => $new_id, ':order_by' => $max);
        $sql = 'INSERT INTO dk_questionnaire_questions (questionnaire_id, question_id, order_by) VALUES (:questionnaire_id, :question_id, :order_by)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        //epistrofh stoixeiwn gia na topo8eth8oun sto pinaka emfganishs olwn twn erwthsewn
        $params = array(':id' => $new_id);
        $sql ="SELECT * FROM dk_question WHERE id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['question'] = $results;

        $params = array(':id' => $new_id);
        $sql = "SELECT pick FROM dk_question_options WHERE question_id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['options'] = $results;

    //2o MODAL
    } elseif ($_POST['mode'] == 'add_template') {//pros8ukh erwthshs apo template modal
        $template_questions = sanitize($_POST['template_questions']);
        $questionnaire_id = sanitize($_POST['questionnaire_id']);

        // βιρσκουμε την ταξινόμηση της τελευταίας ερώτησης ώστε να μπουν οι νέες ερωτήσεις στο τέλος
        // Στον κάτω πίνακα με τις ερωτησεις θελω να δω ποια ειναι η θεση/σειρα της τελευταιας ερωτησης απο το πεδιο order_by.
        //Αρα την ερωτηση ή τις ερωτήσεις που θα καταχωρήσω στην βαση θα ειναι +1 η θεση.
        $params = array(':id' => $questionnaire_id);
        $sql = 'SELECT max(order_by) as maxOrderBy  FROM dk_questionnaire_questions WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $max_orderBy = $stmt->fetchObject();
        $max = intval($max_orderBy->maxOrderBy);

        //ενημερώνω ποιος ειναι ο last editor
        $params = array(':id' => $questionnaire_id, ':last_editor' => $_SESSION['userid']);
        $sql = 'UPDATE dk_questionnaire SET last_editor = :last_editor where id = :id ;';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        // σπάμε το array και εισάγουμε τις ερωτήσεις
        $questionsArray = explode(',', $template_questions);

        //για καθε ερωτηση που εχει ερθει απο το ajax ...
        foreach ($questionsArray as $value) {
            if ($value != '') {

                //Παίρνουμε την ερώτηση που θέλουμε να αντιγράψουμε
                $params = array(':id' => $value);
                $sql = 'SELECT * FROM dk_question WHERE id = :id ';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
                $question = $stmt->fetchObject();
                $type = $question->type;
                $multitype = $question->multi_type;//typos erwthshs
                if($type == 'number' || $type == 'text'){ // αν έχουμε πολλαπλής με αριθμό η κείμενο κανουμε ανταλλαγή δεδομένων γιατι η βάση αναγνωρίζρι στη θέση τύπου μόνο radio check που είναι πλεον οι τιμές του type-multi
                    $t = $type;
                    $type = $multitype;
                    $multitype = $t;
                }

                // Αντιγράφουμε την ερωτηση στην βαση.
                $params = array(':question' => $question->question, ':type' => $type, ':multi_type'=>$multitype, ':template' => 0, ':user_id' => $_SESSION['userid']);
                $sql = 'INSERT INTO dk_question (question, type, multi_type, template, user_id) VALUES(:question, :type, :multi_type, :template, :user_id);';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);

                $new_id = $dbh->lastInsertId();

                //Αν η ερωτηση εχει πιθανες επιλογες και δεν ειναι κειμενου τις διαβαζω και τις αντιγραφω στην βαση.
                $params = array(':id' => $value);
                $sql = 'SELECT * FROM dk_question_options WHERE question_id = :id ';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
                $questionOptions = $stmt->fetchAll();

                foreach ($questionOptions as $questionOption) {
                    $params = array(':id' => $new_id, ':pick' => $questionOption->pick);
                    $sql = 'INSERT INTO dk_question_options (question_id, pick) VALUES(:id, :pick);';
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute($params);
                }

                //κανω την αντιστοιχηση και λεω οτι η νεα ερωτηση θα ανηκει σε αυτο το ερωτηματολογιο.
                $params = array(':questionnaire_id' => $questionnaire_id, ':question_id' => $new_id, ':order_by' => ++$max);
                $sql = 'INSERT INTO dk_questionnaire_questions (questionnaire_id, question_id, order_by) VALUES(:questionnaire_id, :question_id, :order_by);';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);

                //διαβαζω ξανα την ερωτηση και τις πιθανες επιλογές της για να τις στειλω πισω.
                $params = array(':id' => $value);
                $sql = "SELECT * FROM dk_question WHERE id=:id";
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetchObject();
                $arr[$value]['question'] = $result;
                $arr[$value]['question']->type = (array_key_exists($result->type.'_'.$result->multi_type, $list_types)?$list_types[$result->type.'_'.$result->multi_type]:"");
                $params = array(':id' => $value);
                $sql = "SELECT pick FROM dk_question_options WHERE question_id=:id";
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchALL();
                $arr[$value]['options'] = $results;
            }
        }

    }
    elseif ($_POST['mode'] == 'add_template_list') {//pros8ukh erwthshs apo template modal
        $template_questions = sanitize($_POST['template_questions']);
        $questionnaire_id = sanitize($_POST['questionnaire_id']);

        // βιρσκουμε την ταξινόμηση της τελευταίας ερώτησης ώστε να μπουν οι νέες ερωτήσεις στο τέλος
        // Στον κάτω πίνακα με τις ερωτησεις θελω να δω ποια ειναι η θεση/σειρα της τελευταιας ερωτησης απο το πεδιο order_by.
        //Αρα την ερωτηση ή τις ερωτήσεις που θα καταχωρήσω στην βαση θα ειναι +1 η θεση.
        $params = array(':id' => $questionnaire_id);
        $sql = 'SELECT max(order_by) as maxOrderBy  FROM dk_questionnaire_questions WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $max_orderBy = $stmt->fetchObject();
        $max = intval($max_orderBy->maxOrderBy);

        //ενημερώνω ποιος ειναι ο last editor
        $params = array(':id' => $questionnaire_id, ':last_editor' => $_SESSION['userid']);
        $sql = 'UPDATE dk_questionnaire SET last_editor = :last_editor where id = :id ;';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        // σπάμε το array και εισάγουμε τις ερωτήσεις
        $questionsArray = explode(',', $template_questions);

        //για καθε ερωτηση που εχει ερθει απο το ajax ...
        foreach ($questionsArray as $v) {
            if ($v != '') {
                $params = array(':id' => $v);
                $sql = 'SELECT question_id FROM dk_questionnaire_questions WHERE questionnaire_id = :id ';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
                $questions = $stmt->fetchALL();
                foreach($questions as $value){
                    if(!empty($value->question_id)){
                        //Παίρνουμε την ερώτηση που θέλουμε να αντιγράψουμε
                        $params = array(':id' => $value->question_id);
                        $sql = 'SELECT * FROM dk_question WHERE id = :id ';
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $question = $stmt->fetchObject();
                        $type = $question->type;
                        $multitype = $question->multi_type;//typos erwthshs
                        if($type == 'number' || $type == 'text'){ // αν έχουμε πολλαπλής με αριθμό η κείμενο κανουμε ανταλλαγή δεδομένων γιατι η βάση αναγνωρίζρι στη θέση τύπου μόνο radio check που είναι πλεον οι τιμές του type-multi
                            $t = $type;
                            $type = $multitype;
                            $multitype = $t;
                        }
                        // Αντιγράφουμε την ερωτηση στην βαση.
                        $params = array(':question' => $question->question, ':type' => $type, ':multi_type'=>$multitype, ':template' => 0, ':user_id' => $_SESSION['userid']);
                        $sql = 'INSERT INTO dk_question (question, type, multi_type, template, user_id) VALUES(:question, :type, :multi_type, :template, :user_id);';
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);

                        $new_id = $dbh->lastInsertId();

                        //Αν η ερωτηση εχει πιθανες επιλογες και δεν ειναι κειμενου τις διαβαζω και τις αντιγραφω στην βαση.
                        $params = array(':id' => $value->question_id);
                        $sql = 'SELECT * FROM dk_question_options WHERE question_id = :id ';
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $questionOptions = $stmt->fetchAll();

                        foreach ($questionOptions as $questionOption) {
                            $params = array(':id' => $new_id, ':pick' => $questionOption->pick);
                            $sql = 'INSERT INTO dk_question_options (question_id, pick) VALUES(:id, :pick);';
                            $stmt = $dbh->prepare($sql);
                            $stmt->execute($params);
                        }

                        //κανω την αντιστοιχηση και λεω οτι η νεα ερωτηση θα ανηκει σε αυτο το ερωτηματολογιο.
                        $params = array(':questionnaire_id' => $questionnaire_id, ':question_id' => $new_id, ':order_by' => ++$max);
                        $sql = 'INSERT INTO dk_questionnaire_questions (questionnaire_id, question_id, order_by) VALUES(:questionnaire_id, :question_id, :order_by);';
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);

                        //διαβαζω ξανα την ερωτηση και τις πιθανες επιλογές της για να τις στειλω πισω.
                        $params = array(':id' => $value->question_id);
                        $sql = "SELECT * FROM dk_question WHERE id=:id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $result = $stmt->fetchObject();
                        $arr[$value->question_id]['question'] = $result;
                        $arr[$value->question_id]['question']->type = (array_key_exists($result->type.'_'.$result->multi_type, $list_types)?$list_types[$result->type.'_'.$result->multi_type]:"");
                        $params = array(':id' => $value->question_id);
                        $sql = "SELECT pick FROM dk_question_options WHERE question_id=:id";
                        $stmt = $dbh->prepare($sql);
                        $stmt->execute($params);
                        $results = $stmt->fetchALL();
                        $arr[$value->question_id]['options'] = $results;
                    }
                }
            }
        }

    }
    elseif ($_POST['mode'] == 'remove_q') {//diagrafh erwthshs apo questionnaire
        $questionnaire_id = sanitize($_POST['questionnaire_id']);//id questionnaire
        $id = sanitize($_POST['id']);//id questionnaire
        $params = array(':questionnaire_id' => $questionnaire_id, ':question_id' => $id);
        $sql = 'DELETE FROM dk_questionnaire_questions WHERE questionnaire_id= :questionnaire_id AND question_id= :question_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        //update last editor
        $params = array(':id' => $questionnaire_id, ':last_editor' => $_SESSION['userid']);
        $sql = 'UPDATE dk_questionnaire SET last_editor = :last_editor where id = :id ;';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
    }
    elseif ($_POST['mode'] == 'submit-order') {//apo8hkeysh seiras erwthsewn
        parse_str($_POST['form'], $form);
        $questionnaire_id = sanitize($_POST['questionnaire_id']);//id questionnaire
        foreach ($form['order'] as $key => $val) {
            $params = array(':order_by' => $key, ':questionnaire_id' => $questionnaire_id, ':question_id' => $val);
            $sql = 'UPDATE dk_questionnaire_questions SET order_by=:order_by WHERE questionnaire_id= :questionnaire_id AND question_id= :question_id';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        }

        //update last editor
        $params = array(':id' => $questionnaire_id, ':last_editor' => $_SESSION['userid']);
        $sql = 'UPDATE dk_questionnaire SET last_editor = :last_editor where id = :id ;';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
    }
    //φερνω τα δεδομενα της ερωτησης ωστε να εμφανιστει το modal επεξεργασιας της ερωτησης.
    elseif ($_POST['mode'] == 'edit_q') {
        $id = sanitize($_POST['id']);//id question
        $params = array(':id' => $id);
        $sql = "SELECT * FROM dk_question WHERE id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['question'] = $results;
        $params = array(':id' => $id);
        $sql = "SELECT pick FROM dk_question_options WHERE question_id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['options'] = $results;

    }

    //αποθηκεύονται οι αλλαγες που εγιναν στο modal επεξεργασιας της ερωτησης.
    elseif ($_POST['mode'] == 'update_q') {
        parse_str($_POST['form'], $form);
        $id = $form['id'];//id question
        $question = sanitize($form['question_desc']); //perigrafh neas erwthshs
        $type = sanitize($_POST['type']);
        $questionnaire_id = isset($_POST['questionnaire_id'])?sanitize($_POST['questionnaire_id']):'';
        $multitype = sanitize($_POST['multi_type']);//typos erwthshs
        if($type == 'number' || $type == 'text'){ // αν έχουμε πολλαπλής με αριθμό η κείμενο κανουμε ανταλλαγή δεδομένων γιατι η βάση αναγνωρίζρι στη θέση τύπου μόνο radio check που είναι πλεον οι τιμές του type-multi
            $t = $type;
            $type = $multitype;
            $multitype = $t;
        }

        $arr['id'] = $id;
        $arr['form'] = $form;
        $arr['type'] = $type;

        //update last editor
        if(!empty($questionnaire_id)){
            $params = array(':id' => $questionnaire_id, ':last_editor' => $_SESSION['userid']);
            $sql = 'UPDATE dk_questionnaire SET last_editor = :last_editor where id = :id ;';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        }

        $params = array(':question' => $question, ':type' => $type, ':multi_type' => $multitype, ':id' => $id);
        $sql = 'UPDATE dk_question SET question=:question, type=:type, multi_type=:multi_type  WHERE id= :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        if ($type == 'radio' || $type == 'check') {
            $params = array(':id' => $id);
            $sql = 'DELETE FROM dk_question_options WHERE question_id= :id';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);

            foreach ($form['choices'] as $key => $val) {//8a paroume tis times mono apo ton typo pou epileksame
                $params = array(':id' => $id, ':value' => $val);
                $sql = 'INSERT INTO dk_question_options (question_id, pick) VALUES (:id, :value)';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
            }
        }

        $params = array(':id' => $id);
        $sql = "SELECT * FROM dk_question WHERE id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['question'] = $results;
        $arr['question'][0]->type = (array_key_exists($results[0]->type.'_'.$results[0]->multi_type, $list_types)?$list_types[$results[0]->type.'_'.$results[0]->multi_type]:"");

        $params = array(':id' => $id);
        $sql = "SELECT pick FROM dk_question_options WHERE question_id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['options'] = $results;
    }
    //Απο το edit_questionnaire.php πήρα τα δεδομένα όλου του πίνακα form data και για να διαβασω το κλειδί mode το διαβαζω με το $_POST.
    //Παίρνω την τιμή του mode βλέπω με τα if ότι είμαι στο add_question.
    elseif ($_POST['mode'] == 'add_question') {
        // έρχεται ως string "question_desc=Mia+Erotisi&choices%5B%5D=One&choices%5B%5D=Two"
        // και το μετρατρέπω σε
        // $form['question_desc'] = "Mia Erotisi";
        // $form['choices'] = array("One","Two");
        parse_str($_POST['form'],$form);//κάνω όλα τα δεδομενα του πεδίου/κλειδιου form και τα κάνω απο string σε array

        //αφου εσπασα το string βαζω καθε τιμή του σε μια αλλη μεταβλητη και το φιλτράρω.
        $question = sanitize($form['question_desc']); //perigrafh neas erwthshs
        $type = sanitize($_POST['type']);//typos erwthshs
        $questionnaire_id = isset($_POST['questionnaire_id'])?sanitize($_POST['questionnaire_id']):'';//id questionnaire
        $isTemplate = sanitize($_POST['isTemplate']);
        $multitype = sanitize($_POST['type-multi']);//typos erwthshs
        if($type == 'number' || $type == 'text'){ // αν έχουμε πολλαπλής με αριθμό η κείμενο κανουμε ανταλλαγή δεδομένων γιατι η βάση αναγνωρίζρι στη θέση τύπου μόνο radio check που είναι πλεον οι τιμές του type-multi
            $t = $type;
            $type = $multitype;
            $multitype = $t;

        }
        if(!empty($questionnaire_id)){
            //πριν κανεις οτιδηποτε με την βαση κανε update για αυτον που επεξεργαστηκε τελευταιος (last editor)
            $params = array(':id' => $questionnaire_id, ':last_editor' => $_SESSION['userid']); //userid του last editor
            $sql = 'UPDATE dk_questionnaire SET last_editor = :last_editor where id = :id ;';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        }

        //eisagwgh neas erwthshs
        $params = array(':question' => $question, ':type' => $type, ':multi_type' => $multitype, ':template' => $isTemplate, ':user_id' => $_SESSION['userid']);
        $sql = 'INSERT INTO dk_question (question, type, multi_type, template, user_id) VALUES (:question, :type, :multi_type, :template, :user_id)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $new_id = $dbh->lastInsertId();
        if ($new_id > 0) {//eisagwgh apanthsewn ths
            if ($type == 'radio' || $type == 'check') {
                foreach ($form['choices'] as $key => $val) {//8a paroume tis times mono apo ton typo pou epileksame
                    $params = array(':new_id' => $new_id, ':value' => $val);
                    $sql = 'INSERT INTO dk_question_options (question_id, pick) VALUES (:new_id, :value)';
                    $stmt = $dbh->prepare($sql);
                    $stmt->execute($params);
                }
            }
        }

        if(!empty($questionnaire_id)){
            //Αφού βάλω στην βάση την ερώτηση πρεπει να βρω την θεση της τελευταιας ερωτησης του τρεχοντος ερωτηματολογιου
            $params = array(':id' => $questionnaire_id);
            $sql = 'SELECT MAX(order_by) AS max FROM dk_questionnaire_questions WHERE questionnaire_id = :id ';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $max = $stmt->fetchObject();
            $max = $max->max; //αν η προηγουμενη ερωτηση ειναι στην θεση 10
            $max++; //θα βαλω την νεα ερωτηση στην θεση 11

            //insert new question to dk_questionnaire_questions
            //Μεχρι τωρα εχω βαλει την νεα ερωτηση στην βαση και τις επιλογες στον πινακα options. Τώρα θελω να βαλω την νεα ερωτηση σε ποιο ερωτηματολογιο αντιστοιχει.
            $params = array(':questionnaire_id' => $questionnaire_id, ':question_id' => $new_id, ':order_by' => $max);
            $sql = 'INSERT INTO dk_questionnaire_questions (questionnaire_id, question_id, order_by) VALUES (:questionnaire_id, :question_id, :order_by)';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
        }

        //Στο τελευταιο κομματι προετοιμαζω τα δεδομενα που θα απαντησει το ajax.
        //epistrofh stoixeiwn gia na topo8eth8oun sto pinaka emfganishs olwn twn erwthsewn
        $params = array(':id' => $new_id);
        $sql = "SELECT * FROM dk_question WHERE id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['question'] = $results;
        $arr['question'][0]->type = (array_key_exists($results[0]->type.'_'.$results[0]->multi_type, $list_types)?$list_types[$results[0]->type.'_'.$results[0]->multi_type]:"");

        $params = array(':id' => $new_id);
        $sql = "SELECT pick FROM dk_question_options WHERE question_id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['options'] = $results;

    //φερνει τον αριθμο των απαντησεων
    }elseif($_POST['mode'] == 'questionnaire_count'){
        $id = sanitize($_POST['id']);
        $params = array(':id' => $id);
        $sql = "SELECT * FROM dk_answers WHERE  questionnaire_id = :id;";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $answers = $stmt->fetchALL();
        $arr['flag'] = sizeof($answers);
        if(sizeof($answers)>0){
            $arr['answers'] = '';
            $x=0;
            foreach($answers as $a){
                if($x!=0){
                    $arr['answers'] .= ', ';
                }
                $arr['answers'] .= $a->id;
                $x++;
            }
        }

    //διαγραφει όλες τις απαντησεις ενος ερωτηματολογιου οταν θελω να το σβησω το ερωτηματολογιο.
    }elseif($_POST['mode'] == 'delete_all_answers'){
        $id = sanitize($_POST['id']);

        $params = array(':id' => $id);
        $sql = 'SELECT id FROM dk_questionnaire_questions WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $question = $stmt->fetchAll();

        if(sizeof($question)>0){
            foreach ($question as $q){
                $params = array(':id' => $q->id);
                $sql = 'DELETE FROM dk_arduino WHERE question_id = :id';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);

            }

        }

        $params = array(':id' => $id);
        $sql = "DELETE FROM dk_answers WHERE  questionnaire_id = :id ;";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_questionnaire_channel WHERE id_questionnaire = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_questionnaire_questions WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_tokens WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $sql = 'DELETE FROM dk_questionnaire WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $arr['id'] = $_POST;

    //διαγράφει το ερωτηματολογιο.
    }elseif($_POST['mode'] == 'delete_questionnaire'){
        $id = sanitize($_POST['id']);

        $params = array(':id' => $id);
        $sql = 'SELECT id FROM dk_questionnaire_questions WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $question = $stmt->fetchAll();

        if(sizeof($question)>0){
            foreach ($question as $q){
                $params = array(':id' => $q->id);
                $sql = 'DELETE FROM dk_arduino WHERE question_id = :id';
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);

            }

        }
        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_questionnaire_questions WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_tokens WHERE questionnaire_id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $params = array(':id' => $id);
        $sql = 'DELETE FROM dk_questionnaire_channel WHERE id_questionnaire = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $sql = 'DELETE FROM dk_questionnaire WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

    //αντιγραφή ερωτηματολογιου προσθηκη διπλοτυπου.
    }elseif ($_POST['mode'] == 'dublicate_questionnaire') {
        $id = sanitize($_POST['id']);

        $params = array(':id' => $id);
        $sql = "SELECT * FROM dk_questionnaire WHERE id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchObject();
        if(!empty($result)){
            //αντιγραφει το ερωτηματολογιο ως κενό μονο περιγραφη χωρις ερωτησερις
            $params = array(':id' => $id);
            $sql = 'INSERT INTO dk_questionnaire (title, description, time_begins, time_ends, template, user_id, lesson_id, last_edit_time, last_editor) SELECT title, description, time_begins, time_ends, template, user_id, lesson_id, last_edit_time, last_editor FROM dk_questionnaire WHERE id=:id ';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $new_id = $dbh->lastInsertId();
            $arr['id'] = $new_id;
            $arr['question'] = $result->title;

            //αντιγραφει τα καναλια που ανηκει το ερωτηματολογιο
            $params = array(':id' => $id);
            $sql = "INSERT INTO dk_questionnaire_channel (id_questionnaire, id_channel) SELECT $new_id, id_channel FROM dk_questionnaire_channel WHERE id_questionnaire=:id ";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);

            //αντιγραφει τις ερωτησεις του ερωτηματολογιου
            $params = array(':id' => $id);
            $sql = "INSERT INTO dk_questionnaire_questions (questionnaire_id, question_id, order_by) SELECT $new_id, question_id, order_by FROM dk_questionnaire_questions WHERE questionnaire_id=:id ";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);

            //αντιγραφει το μαθημα που ανηκει το ερωτηματολογιο
            $params = array(':id' => $result->lesson_id);
            $sql = "SELECT * FROM dk_lessons where id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $lesson = $stmt->fetchObject();
            $arr['lesson'] = $lesson->title;

            //φερνει τον αριθμο των ερωτησεων που εχω αντιγραψει
            $params = array(':id' => $result->id);
            $sql = "SELECT count(*) FROM dk_questionnaire_questions WHERE questionnaire_id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $arr['questionnaire_sum'] = $stmt->fetchColumn();

            //βαζω το last_editor στην βαση
            $params = array(':id' => $result->last_editor);
            $sql = "SELECT * FROM dk_users where id = :id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $lastTimeEditor = $stmt->fetchObject();
            $arr['last_editor'] = $lastTimeEditor->username;
            if ($_SESSION['level'] == 1 || $_SESSION['level'] == 2) {
                // φέρνω τον χρήστη που ανήκει το ερωτηματολόγιο
                $params = array(':id' => $result->user_id);
                $sql = "SELECT username FROM dk_users where id = :id";
                $stmt = $dbh->prepare($sql);
                $stmt->execute($params);
                $lastTimeEditor = $stmt->fetchObject();
                $arr['editor'] = $lastTimeEditor->username;
            }

            if ($result->template == 0)
                $arr['time_begins'] = (new DateTime($result->time_begins))->format('d/m/Y H:i');
            else $arr['time_begins'] = '-';
            if ($result->template == 0)
                $arr['time_ends'] = (new DateTime($result->time_ends))->format('d/m/Y H:i');
            else $arr['time_ends'] = '-';
        }

    //αντιγραφή μίας ερώτησης
    }elseif ($_POST['mode'] == 'dublicate_question') {
        $id = sanitize($_POST['id']);

        $params = array(':id' => $id);
        $sql = "SELECT * FROM dk_question WHERE id=:id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchObject();
        if(!empty($result)){
            //αντιγραφει την ερωτηση την εκφωνηση και τον τυπο
            $params = array(':id' => $id);
            $sql = 'INSERT INTO dk_question ( question, type, multi_type, template, user_id) SELECT question, type, multi_type, template, user_id FROM dk_question WHERE id=:id';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $new_id = $dbh->lastInsertId();
            $arr['id'] = $new_id;
            $arr['question'] = $result->question;
            $arr['type'] = (array_key_exists($result->type.'_'.$result->multi_type, $list_types)?$list_types[$result->type.'_'.$result->multi_type]:"");

            //αντιγραφει τις πιθναες επιλογες
            $params = array(':id' => $id);
            $sql = "INSERT INTO dk_question_options (question_id, pick) SELECT $new_id, pick FROM dk_question_options WHERE question_id=:id";
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);

            $params = array(':id' => $result->id);
            $sql = 'SELECT dk_question_options.pick FROM dk_question_options INNER JOIN dk_question ON dk_question.id=dk_question_options.question_id WHERE dk_question.id = :id';
            $stmt = $dbh->prepare($sql);
            $stmt->execute($params);
            $arr['options'] = $stmt->fetchALL();
        }

    //προσθηκη τμήματος
    }elseif($_POST['mode'] == 'add_department'){
        //insert department
        $name = sanitize($_POST['name']);
        $params = array(':name' => $name);
        $sql = "INSERT INTO dk_departments (name)  VALUES (:name)";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $new_id = $dbh->lastInsertId();
        $arr['id'] = $new_id;
        $arr['name'] = $name;

    //επεξεργασια τμηματος
    }elseif($_POST['mode'] == 'edit_department'){
        //insert department
        $name = sanitize($_POST['name']);
        $id = sanitize($_POST['id']);
        $params = array(':name' => $name, ':id'=>$id);
        $sql = "UPDATE dk_departments SET name = :name where id = :id ;";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $arr['id'] = $id;
        $arr['name'] = $name;
    // οταν επιλεξω ενα ερωτηματολογιο απο το modal να μου φερει τις αντιστοιχες ερωτησεις του που ειναι πολλαπλής αριθμητικές.
    }elseif($_POST['mode'] == 'get_arduino_questions'){
        $id = sanitize($_POST['id']);
        $params = array(':id' => $id);
        $sql = 'SELECT B.id AS id, B.question AS question FROM dk_questionnaire_questions AS A JOIN dk_question AS B ON A.question_id=B.id WHERE A.questionnaire_id = :id AND (B.type = "check" OR B.type = "radio") ';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchALL();
        $arr['questions'] = $results;

    //modal προσθηκης νεας συσκευης arduino (παρομοιο με την επεξεργασια συσκευης)
    }elseif($_POST['mode'] == 'add_arduino'){
        //insert arduino
        $questionnaire_id = sanitize($_POST['questionnaire']);
        $question_id = sanitize($_POST['question']);
        $arduino_id = sanitize($_POST['arduino_id']);

        //get question to dk_questionnaire_questions
        $params = array(':questionnaire_id' => $questionnaire_id, ':question_id' => $question_id);
        $sql = 'SELECT id FROM dk_questionnaire_questions WHERE questionnaire_id = :questionnaire_id AND question_id = :question_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $id = $stmt->fetchObject();

        $params = array(':arduino_id' => $arduino_id, ':id' => $id->id, ':date' => date('Y-m-d H:i:s'));
        $sql = 'INSERT INTO dk_arduino (arduino_id, question_id, ip, last_active) VALUES (:arduino_id, :id, "", :date)';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $new_id = $dbh->lastInsertId();

        $params = array(':question_id' => $question_id);
        $sql = 'SELECT question FROM dk_question WHERE id = :question_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $question = $stmt->fetchObject();

        $params = array(':questionnaire_id' => $questionnaire_id);
        $sql = 'SELECT title FROM dk_questionnaire WHERE id = :questionnaire_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $questionnaire = $stmt->fetchObject();

        $arr['id'] = $new_id;
        $arr['arduino'] = $arduino_id;
        $arr['question'] = $question->question;
        $arr['questionnaire_title'] = $questionnaire->title;

    }elseif($_POST['mode'] == 'get_arduino'){
        $id = sanitize($_POST['id']);
        $params = array(':id' => $id);
        $sql = 'SELECT A.id, A.arduino_id, B.questionnaire_id, B.question_id AS question FROM dk_arduino AS A JOIN dk_questionnaire_questions AS B ON A.question_id = B.id WHERE A.id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchObject();
        $arr['id'] = $results->id;
        $arr['arduino_id'] = $results->arduino_id;
        $arr['questionnaire'] = $results->questionnaire_id;
        $arr['question'] = $results->question;
    //modal επεξεργασίας arduino
    }elseif($_POST['mode'] == 'edit_arduino'){
        $questionnaire_id = sanitize($_POST['questionnaire']);
        $question_id = sanitize($_POST['question']);
        $arduino_id = sanitize($_POST['arduino_id']);
        $id = sanitize($_POST['id']);

        //παιρνω το id από τον κοινοπ πινακα  των ερωτησεων και ερωτηματολογιων.
        $params = array(':questionnaire_id' => $questionnaire_id, ':question_id' => $question_id);
        $sql = 'SELECT id FROM dk_questionnaire_questions WHERE questionnaire_id = :questionnaire_id AND question_id = :question_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $q_id = $stmt->fetchObject();

        //κανω ενημερωση τον πινακα του arduino με το id το κοινο.
        $params = array(':arduino_id' => $arduino_id, ':question_id' => $q_id->id, ':id' => $id);
        $sql = 'UPDATE dk_arduino SET arduino_id = :arduino_id, question_id = :question_id  where id = :id ;';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        //παιρνει απο το κοινο id παιρνει την ερωτηση
        $params = array(':question_id' => $question_id);
        $sql = 'SELECT question FROM dk_question WHERE id = :question_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $question = $stmt->fetchObject();

        //Παιρνει και το ερωτηματολογιο.
        $params = array(':questionnaire_id' => $questionnaire_id);
        $sql = 'SELECT title FROM dk_questionnaire WHERE id = :questionnaire_id';
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        $questionnaire = $stmt->fetchObject();

        $arr['id'] = $id;
        $arr['arduino'] = $arduino_id;
        $arr['question'] = $question->question;
        $arr['questionnaire_title'] = $questionnaire->title;

    }
    //η ιδια εντολη με το str που κανει τον πινακα json sting (αυτο μονο απο ajax στο αρχειο πισω)
    echo json_encode($arr);
}
?>