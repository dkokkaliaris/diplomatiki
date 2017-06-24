<?php

function get_header() {
    include_once "header.php";
}


function get_footer() {
    include_once "footer.php";
}

//Συναρτηση δημ. τυχαιου κωδικου
function random_string($length)
{
    $key = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));

    for ($i = 0; $i < $length; $i++) {
        $key .= $keys[array_rand($keys)];
    }

    return $key;
}

//Συναρτηση δημ. τυχαιου κωδικου
function randomPassword($num=10)
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
// http://www.catswhocode.com/blog/10-awesome-php-functions-and-snippets.
function is_logged_in(){
    if (isset($_SESSION['userid'] )) {
        return true;
    }else {
        return false;
    }
}

function is_set($value){
    return isset($_SESSION[$value])? $value : '';
}

//για την εμφάνιση των προηγούμενων σελίδων στην γραμμή των σελίδων
function show_breacrumb($breadcrumb){
    $path= '<div class="row breadcrumb">
        <div class="col-sm-12">
            <a href="index.php">Αρχική Σελίδα</a>';
            foreach($breadcrumb as $b){
                if(!empty($b['href'])){
                    $path .= ' &gt; <a href="'.$b['href'].'">'.$b['title'].'</a>';
                }else{
                    $path .= ' &gt; '.$b['title'];
                }
            }
        $path.= '</div>
    </div>';
    return $path;
}
// ================================== ΣΕΛΙΔΟΠΟΙΗΣΗ ============================================
function pagination( $total_pages, $get, $targetpage ){
    $limit = 20;
    $adjacents = 5;
    if (isset($get['page'])) {
        $page = filter_var($get['page'], FILTER_SANITIZE_NUMBER_INT);
    } else {
        $page = 1;
    }

    /* Setup page vars for display. */
    if ($page == 0) $page = 1;                    //if no page var is given, default to 1.
    $prev = $page - 1;                            //previous page is page - 1
    $next = $page + 1;                            //next page is page + 1
    $lastpage = ceil($total_pages / $limit);        //lastpage is = total pages / items per page, rounded up.
    $lpm1 = $lastpage - 1;

    $querystring = "";
    foreach ($get as $key => $value) {
        if ($key != "page") $querystring .= "&amp;$key=" . $value;
    }

    $pagination = "";
    if ($lastpage > 1) {
        $pagination .= "<ul class=\"pagination\">";
        //previous button
        if ($page > 1)
            $pagination .= "<li><a href=\"$targetpage?page=$prev$querystring\" class='prev'>Πίσω</a></li>";

        //pages
        if ($lastpage < 7 + ($adjacents * 2))    //not enough pages to bother breaking it up
        {
            for ($counter = 1; $counter <= $lastpage; $counter++) {
                if ($counter == $page)
                    $pagination .= "<li><span class=\"current\">$counter</span></li>";
                else
                    $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
            }
        } elseif ($lastpage > 5 + ($adjacents * 2))    //enough pages to hide some
        {
            //close to beginning; only hide later pages
            if ($page < 1 + ($adjacents * 2)) {
                for ($counter = 1; $counter < 2 + ($adjacents * 2); $counter++) {
                    if ($counter == $page)
                        $pagination .= "<li><span class=\"current\">$counter</span></li>";
                    else
                        $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
                }
                $pagination .= "<li><a href=\"$targetpage?page=$lpm1$querystring\">$lpm1</a></li>";
                $pagination .= "<li><a href=\"$targetpage?page=$lastpage$querystring\">$lastpage</a></li>";
            } //in middle; hide some front and some back
            elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                $pagination .= "<li><a href=\"$targetpage?page=1$querystring\">1</a></li>";
                $pagination .= "<li><a href=\"$targetpage?page=2$querystring\">2</a></li>";
                for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                    if ($counter == $page)
                        $pagination .= "<li><span class=\"current\">$counter</span></li>";
                    else
                        $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
                }
                $pagination .= "<li><a href=\"$targetpage?page=$lpm1$querystring\">$lpm1</a></li>";
                $pagination .= "<li><a href=\"$targetpage?page=$lastpage$querystring\">$lastpage</a></li>";
            } //close to end; only hide early pages
            else {
                $pagination .= "<li><a href=\"$targetpage?page=1$querystring\">1</a></li>";
                $pagination .= "<li><a href=\"$targetpage?page=2$querystring\">2</a></li>";
                for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                    if ($counter == $page)
                        $pagination .= "<li><span class=\"current\">$counter</span></li>";
                    else
                        $pagination .= "<li><a href=\"$targetpage?page=$counter$querystring\">$counter</a></li>";
                }
            }
        }

        //next button
        if ($page < $counter - 1)
            $pagination .= "<li><a href=\"$targetpage?page=$next$querystring\" class='next'>Επόμενο</a></li>";
        $pagination .= "</ul>";
        echo $pagination;
    }
}
// ================================== ΤΕΛΟΣ ΣΕΛΙΔΟΠΟΙΗΣΗΣ ============================================
?>
