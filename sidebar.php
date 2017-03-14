<ul>
    <?php if ($_SESSION['level'] > 0) { ?>
        <li>
            <a href="<?php echo BASE_URL ?>lessons.php">Εκπαιδευτικά Προγράμματα</a>
        </li>
        <li>
            <a href="<?php echo BASE_URL ?>questionnaires.php">Ερωτηματολόγια</a>
        </li>
        <li>
            <a href="<?php echo BASE_URL ?>template_questions.php">Πρότυπες Ερωτήσεις</a>
        </li>
        <li>
            <a href="<?php echo BASE_URL ?>templates.php">Πρότυπα Ερωτηματολόγια</a>
        </li>
        <li>
            <a href="results.php">Αποτελέσματα Αξιολογήσεων</a>
        </li>
        <li>
            <a href="tokens.php">Διαχείριση Κωδικών Token</a>
        </li>
    <?php }
    if ($_SESSION['level'] == 1) { ?>
        <li>
            <a href="<?php echo BASE_URL ?>users.php">Διαχείριση Χρηστών</a>
        </li>
    <?php }
    if ($_SESSION['level'] < 2) { ?>
        <li>
            <a href="<?php echo BASE_URL ?>evaluation.php">Αξιολόγηση Μαθημάτων</a>
        </li>
    <?php } ?>
</ul>
