<?php
    $page_name = explode(".", basename($_SERVER['REQUEST_URI']))[0];$page = explode('_',$page_name);
	if (($_SESSION['level'] > 0) && ($_SESSION['level'] < 4)) {
		echo '<li class="nav-item'.(in_array('lessons', $page) || in_array('lesson', $page)?' active':'').'">
			<a class="nav-link" href="'.BASE_URL.'lessons.php">Εκπαιδευτικά Προγράμματα</a>
		</li>

		<li class="nav-item'.((in_array('questionnaires', $page) || in_array('questionnaire', $page))&& !in_array('graphs', $page) && !in_array('evaluate', $page)?' active':'').'">
			<a class="nav-link" href="'.BASE_URL.'questionnaires.php">Ερωτηματολόγια</a>
		</li>

		<li class="nav-item'.((in_array('templates', $page) || in_array('template', $page))&& !(  in_array('questions', $page) || in_array('question', $page))?' active':'').'">
			<a class="nav-link" href="'.BASE_URL.'templates.php">Πρότυπα Ερωτηματολόγια</a>
		</li>

		<li class="nav-item'.(in_array('template', $page) && (  in_array('questions', $page) || in_array('question', $page))?' active':'').'">
			<a class="nav-link" href="'.BASE_URL.'template_questions.php">Πρότυπες Ερωτήσεις</a>
		</li>

		<li class="nav-item'.(in_array('tokens', $page)?' active':'').'">
			<a class="nav-link" href="tokens.php">Διαχείριση Κωδικών Token</a>
		</li>';

		echo '<li class="nav-item'.(in_array('results', $page) || in_array('graphs', $page)?' active':'').'">
			<a class="nav-link" href="results.php">Αποτελέσματα Αξιολογήσεων</a>
		</li>';
	}

	if (($_SESSION['level'] == 1 ) || ($_SESSION['level'] == 4 )) {
		echo '<li class="nav-item'.(in_array('evaluation', $page) || in_array('evaluate', $page)?' active':'').'">
			<a class="nav-link" href="'.BASE_URL.'evaluation.php">Αξιολόγηση Μαθημάτων</a>
		</li>';
	}

	if ($_SESSION['level'] == 1) {
		echo '<li class="nav-item'.(in_array('users', $page) || in_array('user', $page) || in_array('new-user', $page)|| in_array('departments', $page)?' active':'').'">
			<a class="nav-link" href="'.BASE_URL.'users.php">Διαχείριση Χρηστών</a>
		</li>';
	}
?>