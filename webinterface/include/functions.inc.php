<?php
####################################
# SURFnet IDS                      #
# Version 1.02.08                  #
# 26-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.02.08 Added the stripinput function
# 1.02.07 Fixed a bug with empty $db_table when preparing the sql
# 1.02.06 Added pgboolval() function
# 1.02.05 Added validate_email() fucntion
# 1.02.04 Modified prepare_sql_where function. Renamed to prepare_sql with a hook to prepare_sql_from()
#############################################

// Removes certain strings from the input. This is used to prevent XSS attacks.
function stripinput($input) {
  $pattern_ar = array("<script>", "</script>", "<", "</", ">", "%");
  foreach($pattern_ar as $pattern) {
    $input = str_replace($pattern, '', $input);
  }
  return $input;
}

// Return the PostgreSQL value for a boolean ('t' (TRUE) or 'f' (FALSE)), default FALSE 
function pgboolval($val) {
	$val = strtolower($val);
	if ($val == "t") return $val;
	else return "f";
}

// Return true if submitted e-mail address is valid (something@domain.ext)
function validate_email($email) {
	$regex = '/^([a-zA-Z0-9_\-\.,]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
	return preg_match($regex, $email);
}

// Format a number (number_format)
function nf($nr, $num_decimal_places = 0) {
	return number_format($nr, $num_decimal_places, ".", ",");
}

// Function to add tables to sql-FROM (used by searchresults)
function add_db_table($tbl) {
	global $db_table;

        if (!empty($db_table)) {
          if (!in_array($tbl, $db_table)) $db_table[] = $tbl;
        } else {
          $db_table[] = $tbl;
        }
}

// Function for creating sql-WHERE (used by searchresults)
function prepare_sql() {
	global $db_table, $where, $sql_where;
        if (empty($db_table)) {
          $db_table = array();
        }
	
	# Creating link between sensors and attacks table.
	if (in_array("attacks", $db_table)) {
          add_db_table("sensors");
          $where[] = "sensors.id = attacks.sensorid";
        }
        # Creating link between attacks and details table.
	if (in_array("details", $db_table) || in_array("binaries", $db_table)) {
          add_db_table("details");
          add_db_table("attacks");
          $where[] = "details.attackid = attacks.id";
        }
        # Creating link between binaries and details table.
	if (in_array("binaries", $db_table)) {
          add_db_table("binaries");
          $where[] = "binaries.bin = details.text";
        }
	
	$sql_where = "";
	foreach ($where as $val) {
		if (empty($sql_where)) $sql_where .= " WHERE ";
		else $sql_where .= " AND ";
                check_where_table($val);
		$sql_where .= $val . " ";
	}
	prepare_sql_from();
}

function check_where_table($ch_val) {
  $ch_val = trim($ch_val);
  $arguments = explode(" ", $ch_val);
  $left = trim($arguments[0]);
#  $right = trim($arguments[2]);
  $pat = "/^.*\..*$/";
  if (preg_match($pat, $left)) {
    $ch_table = explode(".", $left);
    add_db_table($ch_table[0]);
  }
#  if (preg_match($pat, $right)) {
#    $ch_table = explode(".", $right);
#    add_db_table($ch_table[0]);
#  }
}

// Function for creating sql-FROM (used by searchresults)
function prepare_sql_from() {
	global $db_table, $sql_from;
	
	$sql_from = "";
	if (@count($db_table > 0)) {
		$sql_from = $db_table[0];
		for ($i = 1; $i < count($db_table); $i++) {
			$sql_from .= ", " . $db_table[$i];
		}
	}
}

# Function to convert amount of bytes into human readable format.
function size_hum_read($size){
  /*
  Returns a human readable size
  */
  $i=0;
  $iec = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
  while (($size/1024)>1) {
    $size=$size/1024;
    $i++;
  }
  return str_replace(".", ",", substr($size,0,strpos($size,'.')+3)." ".$iec[$i]);
}

# Function to print a radio button
function printRadio($desc, $radio_name, $value, $data)
{
  // prints a <input type='radio'>
  // $desc = Text behind the radio button.
  // $radio_name = name attribute
  // $value = value attribute
  // $data = data compared to value
  $return = "";
  $return .= "<input type=\"radio\" name=\"" . $radio_name . "\" value=\"" . $value . "\" id=\"" . $radio_name . "_" . $value . "\"";
  if ($data == $value) {
    $return .= " checked";
  }
  $return .= " style=\"cursor:pointer;\"> <label for=\"" . $radio_name . "_" . $value . "\" style=\"cursor:pointer;\">" . $desc . "</label>";
  return $return;
}

# Function to print a checkbox
function printCheckBox($desc, $cb_name, $value, $data)
{
  // prints a <input type='checkbox'>
  // $desc = Text behind the radio button.
  // $cb_name = name attribute
  // $value = value attribute
  // $data = data compared to value
  $return = "<input type=\"checkbox\" name=\"" . $cb_name . "\" value=\"" . $value . "\" id=\"" . $cb_name . "_" . $value . "\"";
  if ($data == $value) {
    $return .= " checked";
  }
  $return .= " style=\"cursor:pointer;\"> <label for=\"" . $cb_name . "_" . $value . "\" style=\"cursor:pointer;\">" . $desc . "</label>";
  return $return;
}

# Function to print a select option
function printOption($value, $text, $val)
{
  $return = "<option value=\"$value\"";
  if ($val == $value) {
    $return .= " selected";
  }
  $return .= ">$text</option>\n";
  return $return;
}

# Function used to calculate rendering time of the search pages.
function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

# Function used to check if an ip address is inside the CIDR range specified.
function matchCIDR($addr, $cidr) {
  // $addr should be an ip address in the format '0.0.0.0'
  // $cidr should be a string in the format '100/8'
  //      or an array where each element is in the above format
  $output = false;

  if ( is_array($cidr) ) {
    foreach ( $cidr as $cidrlet ) {
      if ( matchCIDR( $addr, $cidrlet) ) {
        $output = true;
      }
    }
  } else {
    list($ip, $mask) = explode('/', $cidr);
    $mask = 0xffffffff << (32 - $mask);
    $output = ((ip2long($addr) & $mask) == (ip2long($ip) & $mask));
  }
  return $output;
}

# Function used to generate a chart image.
function makeChart($charttype, $title, $sql, $org) {
  global $pgconn;
  $result_chart = pg_query($pgconn, $sql);

  if ($charttype == 0) {
    $chart =  new PieChart();
  }
  elseif ($charttype == 1) {
    $chart = new HorizontalChart();
  }
  elseif ($charttype == 2) {
    $chart = new VerticalChart();
  }
  else {
    echo "Wrong type selected<br />\n";
    $siderr = 1;
  }

  if ($siderr != 1) {
   $totalrows = pg_num_rows($result_chart);
   if ($totalrows == 0) { $drawerr = 1; echo "No data to process "; }
   else {
    $chart->setTitle($title);
    while ($row = pg_fetch_row($result_chart)) {
      $key = $row[0];
      $value = $row[1];
      $dia = substr_count($key, "Dialogue");
      if ($dia > 0) {
        global $attacks_ar;
        $key = $attacks_ar[$key]["Attack"];
      }
      $chart->addPoint(new Point("$key ($value)", $value));
    }
    $chart->render("charts/$org.png");
    return "charts/$org.png";
   }
  }
  else {
    return "false";
  }
}

# Function used to determine the start of a week. Returns timestamp in epoch format.
function getStartWeek($day = '', $month = '', $year = '') {
  if ($day == '' || $month == '' || $year == '') {
    $day = date("d");
    $month = date("n");
    $year = date("Y");
  }
  $dayofweek = date("w", mktime(0,0,0,$month,$day,$year));
  $startofweek = $day - $dayofweek;
  $stamp = mktime(0,0,0,$month,$startofweek,$year);
  return $stamp;
}

# Function used to determine the end of a week. Returns timestamp in epoch format.
function getEndWeek($day = '', $month = '', $year = '') {
  if ($day == '' || $month == '' || $year == '') {
    $day = date("d");
    $month = date("n");
    $year = date("Y");
  }
  $dayofweek = date("w", mktime(0,0,0,$month,$day,$year));
  $startofweek = $day - $dayofweek;
  $endofweek = $startofweek + 6;
  $stamp = mktime(23,59,59,$month,$endofweek,$year);
  return $stamp;
}

# Function used to determine the start of a month. Returns timestamp in epoch format.
function getStartMonth($month = '', $year = '') {
  if ($month == '' || $year == '') {
    $month = date("n");
    $year = date("Y");
  }
  $stamp = mktime(0,0,0,$month,1,$year);
  return $stamp;
}

# Function used to determine the end of a month. Returns timestamp in epoch format.
function getEndMonth($month = '', $year = '') {
  if ($month == '' || $year == '') {
    $month = date("n");
    $year = date("Y");
  }
  $endofmonth = date("t", mktime(0,0,0,$month,1,$year));
  $stamp = mktime(23,59,59,$month,$endofmonth,$year);
  return $stamp;
}

# Function used to determine the start of the day. Returns timestamp in epoch format.
function getStartDay($day = '', $month = '', $year = '') {
  if ($day == '' || $month == '' || $year == '') {
    $day = date("d");
    $month = date("n");
    $year = date("Y");
  }
  $stamp = mktime(0,0,0,$month,$day,$year);
  return $stamp;
}

# Function used to determine the end of the day. Returns timestamp in epoch format.
function getEndDay($day = '', $month = '', $year = '') {
  if ($day == '' || $month == '' || $year == '') {
    $day = date("d");
    $month = date("n");
    $year = date("Y");
  }
  $stamp = mktime(23,59,59,$month,$day,$year);
  return $stamp;
}
?>
