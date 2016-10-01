<?php

function get_header() {
    include_once "header.php";
}


function get_footer() {
    include_once "footer.php";
}

// http://www.catswhocode.com/blog/10-awesome-php-functions-and-snippets
function cleanInput($input) {
  $search = array(
    '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
  );

    $output = preg_replace($search, '', $input);
    return $output;
}

function sanitize($input) {
    if (is_array($input)) {
        foreach($input as $var=>$val) {
            $output[$var] = sanitize($val);
        }
    }
    else {
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        $output = cleanInput(trim($input));
    }
    return $output;
}
// http://www.catswhocode.com/blog/10-awesome-php-functions-and-snippets


?>
