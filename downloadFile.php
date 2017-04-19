<?php
header('Content-Disposition: attachment; filename="' . $_GET['fileName'] . '"');
header('Content-Type: application/octet-stream'); // or application/force-download

$target_dir = __DIR__ . "/uploads/";
readfile($target_file = $target_dir . $_GET['fileName']);

exit;
