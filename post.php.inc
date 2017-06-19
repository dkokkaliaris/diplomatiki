<?php
//
//Process a Form and print results
//by Minas Dasygenis (minas@spam.vlsi.gr) 2010
//
//
//echo "Your post request was succesfully processed: ";
//print "<table border=1 width=50%>";
//
//foreach($_POST as $key => $value) {
//	if(is_array($value)){
//	print "<tr><td><strong>$key</strong></td>";
//		foreach($value as $key2 => $value2) {
//			print "<td>$value2</td>";
//						    }
//		 	    }
//else 
//  print "<tr><td><strong>$key</strong></td><td>$value</td>";
//}
//print "</table>";

//include_once("http://arch.icte.uowm.gr/post.php.inc");savemsg(json_encode($jTableResult));




/**
 * Replaces any parameter placeholders in a query with the value of that
 * parameter. Useful for debugging. Assumes anonymous parameters from 
 * $params are are in the same order as specified in $query
 *
 * @param string $query The sql query with parameter placeholders
 * @param array $params The array of substitution parameters
 * @return string The interpolated query

instructions on how to replace the old: 
$query02 = $dbconn->prepare("SELECT id FROM questions WHERE available= :av");
$query02->bindParam(':av', $available);
$query02->execute();

with the new:


$parameters=array("av"=>$available,"uid"=>$_SESSION['user_id']);
$sql="SELECT id FROM questions WHERE available= :av";
$query02=$dbconn->prepare($sql);
$query02->execute($parameters);
include_once("http://arch.icte.uowm.gr/post.php.inc");savemsg(json_encode(interpolateQuery($query,$parameters)));

or

$sql="SELECT * FROM questions WHERE id IN (:field) ORDER BY rand() LIMIT 0,1";
$arrayids=explode(",",$remq_ids);
$questionsmarks=str_repeat("?,",count($arrayids)-1)."?";
$sql="SELECT * FROM questions WHERE id IN ($questionsmarks) ORDER BY rand() LIMIT 0,1";
$parameters=$arrayids;
$query2=$dbconn->prepare($sql);
$query2->execute($parameters);
include_once("http://arch.icte.uowm.gr/post.php.inc");savemsg(json_encode(interpolateQuery($sql,$parameters)));





 */
function interpolateQuery($query, $params) {
    $keys = array();
    $values = $params;

    # build a regular expression for each parameter
    foreach ($params as $key => $value) {
        if (is_string($key)) {
            $keys[] = '/:'.$key.'/';
        } else {
            $keys[] = '/[?]/';
        }

        if (is_array($value))
            $values[$key] = implode(',', $value);

        if (is_null($value))
            $values[$key] = 'NULL';
    }
    // Walk the array to see if we can add single-quotes to strings
    array_walk($values, create_function('&$v, $k', 'if (!is_numeric($v) && $v!="NULL") $v = "\'".$v."\'";'));

    $query = preg_replace($keys, $values, $query, 1, $count);

    return $query;
}






function pdo_debugStrParams($stmt) {
  ob_start();
  $stmt->debugDumpParams();
  $r = ob_get_contents();
  ob_end_clean();
  return $r;
}




function print_r_xml($arr,$wrapper = 'data',$cycle = 1)
{
  //useful vars
  $new_line = "\n";
  $output="";

  //start building content
  if($cycle == 1) { $output = '<?xml version="1.0" encoding="UTF-8" ?>'.$new_line; }

  $output.= tabify($cycle - 1).'<'.$wrapper.'>'.$new_line;
  foreach($arr as $key => $val)
  {
    if(!is_array($val))
    {
      $output.= tabify($cycle).'<'.htmlspecialchars($key).'>'.$val.'</'.htmlspecialchars($key).'>'.$new_line;
    }
    else
    {
      $output.= print_r_xml($val,$key,$cycle + 1).$new_line;
    }
  }
  $output.= tabify($cycle - 1).'</'.$wrapper.'>';

  //return the value
  return $output;
}



function savemsg($msg)
{
$log=fopen("/tmp/ictest00516.txt","a");
fwrite($log,"\n ************************ NEW ENTRY". date(DATE_RFC2822) ." ************************ \n");
fwrite($log, $msg);
fwrite($log,"\n\n\n\n\n");
fclose($log);
}



/* tabify */
function tabify($num_tabs)
{
  $return="";
  for($x = 1; $x <= $num_tabs; $x++) { $return.= "\t"; }
  return $return;
}


//echo print_r_xml($_REQUEST);
$log=fopen("/tmp/ictest00516.debugpost.txt","a");
fwrite($log,"\n ************************ NEW ENTRY ************************ \n");
fwrite($log, print_r_xml($_REQUEST));
fwrite($log,"\n\n\n\n\n");
fclose($log);


  if (! empty($_POST) )
    {
	//echo "Έλαβα το POST";
    }

	chmod("/tmp/ictest00516.debugpost.txt",0777);
	chmod("/tmp/ictest00516.txt",0777);
?>
