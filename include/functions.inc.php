<?php
####################################
# SURFnet IDS                      #
# Version 1.04.09                  #
# 01-02-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.09 Removed unused sql functions and added INDEX
# 1.04.08 Added add_to_sql, reset_sql
# 1.04.07 Bugfix in getEndWeek()
# 1.04.06 Added getepoch() function
# 1.04.05 Removed the stripinput function
# 1.04.04 Changed the debug function
# 1.04.03 Added getPortDescr() function
# 1.04.02 Added showSearchTemplates() function
# 1.04.01 Added cleansql() function
# 1.03.03 Added debug function
# 1.03.02 Changed getStartWeek() to correctly display the start of the week
# 1.03.01 Released as part of the 1.03 package
# 1.02.09 Added genpass and stripinput function
# 1.02.08 Removed admin_header function and fixed prepare_sql bug
# 1.02.07 Fixed a bug with empty $db_table when preparing the sql
# 1.02.06 Added pgboolval() function
# 1.02.05 Added validate_email() fucntion
# 1.02.04 Modified prepare_sql_where function. Renamed to prepare_sql with a hook to prepare_sql_from()
# 1.02.03 Initial release
#############################################

#############################################
# INDEX
#
# 1 SQL Functions
# 1.01		reset_sql
# 1.02		add_to_sql
# 1.03		prepare_sql
#
# 2 Date & Time Functions
# 2.01		getepoch
# 2.02		getstartweek
# 2.03		getendweek
# 2.04		getstartmonth
# 2.05		getendmonth
# 2.06		getstartday
# 2.07		getendday
#
# 3 Miscellaneous
# 3.01		extractvars
# 3.02		geterror
# 3.03		showsearchtemplates
# 3.04		cleansql
# 3.05		checkSID
# 3.06		getaddress
# 3.07		genpass
# 3.08		pgboolval
# 3.09		validate_email
# 3.10		nf
# 3.11		size_hum_read
# 3.12		printradio
# 3.13		printcheckbox
# 3.14		printoption
# 3.15		microtime_float
# 3.16		matchCIDR
# 3.17		getportdescr
#
# 4 Debug Functions
# 4.01		printer
# 4.02		debug_input
# 4.03		debug_sql
#############################################

# 1.01 reset_sql
# Function to reset all the SQL variables
function reset_sql() {
  global $select, $table, $where, $group, $order;
  global $sql_select, $sql_from, $sql_where, $sql_group, $sql_order;
  $select = array();
  $sql_select = "";
  $table = array();
  $sql_from = "";
  $where = array();
  $sql_where = "";
  $group = array();
  $sql_group = "";
  $order = array();
  $sql_order = "";
}

# 1.02 add_to_sql
# Function to add a variable to the SQL arrays
function add_to_sql($add, $ar) {
  global ${$ar};
  if (empty(${$ar})) {
    ${$ar} = array();
  }
  if (trim($add) != "") {
    if (!in_array($add, ${$ar})) {
      ${$ar}[] = $add;
    }
  }
}

# 1.03 prepare_sql
# Function to convert the SQL arrays to SQL strings
function prepare_sql() {
  # Defining the global source arrays
  global $table, $where, $select, $order, $group;
  # Defining the global result strings
  global $sql_from, $sql_where, $sql_select, $sql_order, $sql_group;

  if ($where) {
    $sql_where = "";
    if (@count($where > 0)) {
      $sql_where = " WHERE $where[0] ";
      for ($i = 1; $i < count($where); $i++) {
        $sql_where .= " AND " . $where[$i];
      }
    }
  }

  $sql_from = "";
  if (@count($table > 0)) {
    $sql_from = $table[0];
    for ($i = 1; $i < count($table); $i++) {
      $sql_from .= ", " . $table[$i];
    }
  }

  if ($select) {
    $sql_select = "";
    if (@count($db_table > 0)) {
      $sql_select = $select[0];
      for ($i = 1; $i < count($select); $i++) {
        $sql_select .= ", " . $select[$i];
      }
    }
  } else {
    $sql_select = " * ";
  }

  if ($group) {
    $sql_group = "";
    if (@count($group > 0)) {
      $sql_group = $group[0];
      for ($i = 1; $i < count($group); $i++) {
        $sql_group .= ", " . $group[$i];
      }
    }
  }

  if ($order) {
    $sql_order = "";
    if (@count($order > 0)) {
      $sql_order = $order[0];
      for ($i = 1; $i < count($order); $i++) {
        $sql_order .= ", " . $order[$i];
      }
    }
  }
}

# 2.01 getepoch
# Function to convert a regular datetime string to epoch format
# Example input: 29-01-2007 00:11:31
function getepoch($stamp) {
  list($date, $time) = explode(" ", $stamp);
  list($day, $mon, $year) = explode("-", $date);
  list($hour, $min) = explode(":", $time);
  // Date MUST BE valid
  $day = intval($day);
  $mon = intval($mon);
  $year = intval($year);
  if (($day > 0) && ($mon > 0) && ($year > 0)) {
    if (checkdate($mon, $day, $year)) {
      // Valid date, check time
      $hour = intval($hour);
      $min = intval($min);
      if (!(($minute >= 0) && ($min < 60) && ($hour >= 0) && ($hour < 24))) {
        // Invalid time, generate midnight (0:00)
        $hour = $min = 0;
      }
      $epoch = mktime($hour, $min, 0, $mon, $day, $year);
      return $epoch;
    }
  }
}

# 2.02 getstartweek
# Function used to determine the start of a week. Returns timestamp in epoch format.
function getStartWeek($day = '', $month = '', $year = '') {
  $dayofweek = date("w", mktime(0,0,0,$month,$day,$year));
  $startofweek = $day - $dayofweek + 1;
  $stamp = mktime(0,0,0,$month,$startofweek,$year);
  return $stamp;
}

# 2.03 getendweek
# Function used to determine the end of a week. Returns timestamp in epoch format.
function getEndWeek($day = '', $month = '', $year = '') {
  $dayofweek = date("w", mktime(0,0,0,$month,$day,$year));
  $startofweek = $day - $dayofweek + 1;
  $endofweek = $startofweek + 6;
  $stamp = mktime(23,59,59,$month,$endofweek,$year);
  return $stamp;
}

# 2.04 getstartmonth
# Function used to determine the start of a month. Returns timestamp in epoch format.
function getStartMonth($month = '', $year = '') {
  $stamp = mktime(0,0,0,$month,1,$year);
  return $stamp;
}

# 2.05 getendmonth
# Function used to determine the end of a month. Returns timestamp in epoch format.
function getEndMonth($month = '', $year = '') {
  $endofmonth = date("t", mktime(0,0,0,$month,1,$year));
  $stamp = mktime(23,59,59,$month,$endofmonth,$year);
  return $stamp;
}

# 2.06 getstartday
# Function used to determine the start of the day. Returns timestamp in epoch format.
function getStartDay($day = '', $month = '', $year = '') {
  $stamp = mktime(0,0,0,$month,$day,$year);
  return $stamp;
}

# 2.07 getendday
# Function used to determine the end of the day. Returns timestamp in epoch format.
function getEndDay($day = '', $month = '', $year = '') {
  $stamp = mktime(23,59,59,$month,$day,$year);
  return $stamp;
}

# 3.01 extractvars
# Function to extract the variables from input arrays like GET and POST
# Input validation is done based on the variable names
# Possible checks are:
#   int - intval()
#   escape - pg_escape_string()
#   html - htmlentities()
#   strip - strip_tags()
#   md5 - md5 regexp
#   bool - boolean regexp
#   ip - ip address regexp
#   net - network range regexp
# These checks should be prepended to the variable name separated by a _ character
# Examples:
# int_id - Will convert the variable to an integer and put the result in the cleaned array as $clean['id']
# ip_ip - Checks if the variable is a valid IP address, if so result will be put in $clean['ip'] else $tainted['ip']
function extractvars($source, $allowed) {
  if (!is_array($source)) {
    return 1;
  } else {
    global $clean;
    global $tainted;

    # Setting up the regular expression for an IP address
    $ipregexp = '/^([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
    $ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
    $ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
    $ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))$/';

    foreach ($source as $key => $var) {
      if (!is_array($var)) {
        $var = trim($var);
        if ($var != "") {
          if (in_array($key, $allowed)) {
            $explodedkey = explode("_", $key);
            $temp = array_pop($explodedkey);
            $count = count($explodedkey);
            if ($count != 0) {
              foreach ($explodedkey as $check) {
                if ($check == "int") {
                  $var = intval($var);
                  $clean[$temp] = $var;
                } elseif ($check == "escape") {
                  $var = pg_escape_string($var);
                  $clean[$temp] = $var;
                } elseif ($check == "html") {
                  $var = htmlentities($var);
                  $clean[$temp] = $var;
                } elseif ($check == "strip") {
                  $var = strip_tags($var);
                  $clean[$temp] = $var;
                } elseif ($check == "md5") {
                  $md5pattern = '/^[a-zA-Z0-9]{32}$/';
                  if (!preg_match($md5pattern, $var)) {
                    $tainted[$temp] = $var;
                  } else {
                    $clean[$temp] = $var;
                  }
                } elseif ($check == "bool") {
                  $var = strtolower($var);
	          $pattern = '/^(t|true|f|false)$/';
                  if (!preg_match($pattern, $var)) {
                    $var = "f";
                  } else {
                    if ($var == "true" || $var == "false") {
                      $var = pgboolval($var);
                    }
                  }
                  $clean[$temp] = $var;
                } elseif ($check == "ip") {
                  if (!preg_match($ipregexp, $var)) {
                    $tainted[$temp] = $var;
                  } else {
                    $clean[$temp] = $var;
                  }
                } elseif ($check == "net") {
                  $ar_test = explode("/", $var);
                  $ip_test = $ar_test[0];
                  $mask_test = intval($ar_test[1]);
                  if (preg_match($ipregexp, $ip_test) && $mask_test >= 0 && $mask_test <= 32) {
                    $clean[$temp] = $var;
                  } else {
                    $tainted[$temp] = $var;
                  }
                } elseif (!in_array($temp, $clean)) {
                  $tainted[$temp] = $var;
                }
              }
            } else {
              $tainted[$temp] = $var;
            } // $count != 0
          } // in_array($key, $allowed)
        } // $var != ""
      } else {
        $tainted[$key] = $var;
      } // !is_array($var)
    } // foreach
  } // !is_array($source)
  return 0;
}

# 3.02 geterror
# Function to retrieve the error message given the error number
function geterror($m) {
  global $v_errors;
  $e_file = $_SERVER['SCRIPT_NAME'];
  $e_file = basename($e_file);
  $e_file = str_replace(".", "", $e_file);
  $m = $v_errors[$e_file][$m];
  $m = "<p>$m</p>\n";
  $m = "<font color='red'>" .$m. "</font>";
  return $m;
}

# 3.03 showsearchtemplates
# Function to handle searchtemplates
function showSearchTemplates($sql) {
  $query = pg_query($sql);
  while ($row = pg_fetch_assoc($query)) {
    $querystring = "";
    $db_querystring = $row["querystring"];
    // parse querystring
    $parse = explode("|", $db_querystring);
    for ($i = 0; $i < (count($parse) - 1); $i++) {
      // $i == even -> key, $i == odd -> value
      if (($i % 2) == 0) $querystring .= $parse[$i];
      else {
        // parse value
        // %dt = datetime
        $key = $parse[$i];
        if (substr($key, 0, 3) == "%dt") {
          // set current timestamp
          $dt = time();
          $sub = substr($key, 3);
          if (strlen($sub) > 0) {
            // substitute date
            if (substr($sub, 0, 1) == "-") {
              $sub = substr($sub, 1);
              $date_min = 60;
              $date_hour = 60 * $date_min;
              $date_day = 24 * $date_hour;
              $date_week = 7 * $date_day;
              $date_month = 31 * $date_day;
              $date_year = 365 * $date_day;
              $dt_sub = 0;
              // determine substitute value
              //"H", "D", "T", "W", "M", "Y"
              switch ($sub) {
                case "%Y":
                  $dt_sub = $date_year;
                  break;
                case "%M":
                  $dt_sub = $date_month;
                  break;
                case "%W":
                  $dt_sub = $date_week;
                  break;
                case "%D":
                  $dt_sub = $date_day;
                  break;
                case "%H":
                  $dt_sub = $date_hour;
                  break;
                case "%T":
                  // today
                  $dt = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                  break;
                default:
                  // use absolute value
                  $dt_sub = $sub;
                  break;
              }
              if ($dt_sub > 0) $dt -= $dt_sub;
            }
          }
          $querystring .= urlencode(date("d-m-Y H:i:s", $dt));
        }
      }
    }
    if (!empty($parse[$i])) $querystring .= $parse[$i];
    
    echo "<a href=\"/logsearch.php?" . $querystring . "\" class=\"searchtemplate_item\">" . $row["title"] . "</a>\n";
  }
}

# 3.04 cleansql
# Function to cleanup a SQL statement with unwanted SQL commands
function cleansql($s_sql) {
  $pattern_ar = array("UNION", "JOIN", "INNER", "OUTER", "INSERT", "DELETE", "UPDATE", "INTO", "login");
  $s_sql = strtolower($s_sql);
  foreach($pattern_ar as $pattern) {
    $pattern = strtolower($pattern);
    $s_sql = str_replace($pattern, '', $s_sql);
  }
  return $s_sql;
}

# 3.05 checkSID
# Function to compare the current session ID to the session ID in the database
function checkSID(){
  global $c_checksession_ua;
  global $c_checksession_ip;
  $err = 0;
  if ($c_chksession_ip == 1) {
    $sid = session_id();
    $sql_checksid = "SELECT ip, useragent FROM sessions WHERE sid = '$sid'";
    $result_check = pg_query($sql_checksid);
    $numrows_check = pg_num_rows($result_check);
    if ($numrows_check != 0) {
      $row = pg_fetch_assoc($result_check);
      $db_ip = $row['ip'];
      $remoteip = $_SERVER['REMOTE_ADDR'];
      if ($db_ip != $remoteip) {
        $err = 1;
      }
    } else {
      $err = 1;
    }
  }
  if ($c_chksession_ua == 1) {
    $sid = session_id();
    $sql_checksid = "SELECT useragent FROM sessions WHERE sid = '$sid'";
    $result_check = pg_query($sql_checksid);
    $numrows_check = pg_num_rows($result_check);
    if ($numrows_check != 0) {
      $row = pg_fetch_assoc($result_check);
      $db_ua = $row['useragent'];
      $useragent = md5($_SERVER['HTTP_USER_AGENT']);
      if ($db_ua != $useragent) {
        $err = 1;
      }
    } else {
      $err = 1;
    }
  }
  if ($err == 0) {
    return 0;
  } elseif ($err == 1) {
    return 1;
  }
}

# 3.06 getaddress
# Function to get the current URL with the correct port and directory
function getaddress() {
  global $c_web_port;
  $absfile = $_SERVER['SCRIPT_NAME'];
  $file = basename($absfile);
  $dir = str_replace($file, "", $absfile);
  $dir = ltrim($dir, "/");
  $https = $_SERVER['HTTPS'];
  if ($https == "") {
    $http = "http";
  } else {
    $http = "https";
  }
  $servername = $_SERVER['SERVER_NAME'];
  $address = "$http://$servername:$c_web_port/$dir";
  return $address;
}

# 3.07 genpass
# Function to generate a random string of a certain length
function genpass($length = 8) {
  # start with a blank password
  $password = "";
  # define possible characters
  $possible = "0123456789bcdfghjkmnpqrstvwxyz";
  # set up a counter
  $i = 0;

  # add random characters to $password until $length is reached
  while ($i < $length) {

    # pick a random character from the possible ones
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

    # we don't want this character if it's already in the password
    $password .= $char;
    $i++;
  }
  # done!
  return $password;
}

# 3.08 pgboolval
# Function to return the PostgreSQL value for a boolean
function pgboolval($val) {
	$val = strtolower($val);
	if ($val == "t") return $val;
	else return "f";
}

# 3.09 validate_email
# Function to check if the given email is a valid email address
function validate_email($email) {
	$regex = '/^([a-zA-Z0-9_\-\.,]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
	return preg_match($regex, $email);
}

# 3.10 nf
# Function to format a number with a given amount of decimal places
function nf($nr, $num_decimal_places = 0) {
	return number_format($nr, $num_decimal_places, ".", ",");
}

# 3.11 size_hum_read
# Function to convert amount of bytes into human readable format.
function size_hum_read($size) {
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

# 3.12 printradio
# Function to print a radio button
function printRadio($desc, $radio_name, $value, $data) {
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

# 3.13 printcheckbox
# Function to print a checkbox
function printCheckBox($desc, $cb_name, $value, $data) {
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

# 3.14 printoption
# Function to print a select option
function printOption($value, $text, $val) {
  $return = "<option value=\"$value\"";
  if ($val == $value) {
    $return .= " selected";
  }
  $return .= ">$text</option>\n";
  return $return;
}

# 3.15 microtime_float
# Function used to calculate rendering time of the search pages.
# Returns current time in microseconds
function microtime_float() {
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

# 3.16 matchCIDR
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

# 3.17 getportdescr
# Function used to determine the description of a (well-known) port. To be extended...
function getPortDescr($aPort) {
  switch ($aPort) {
      case   20: return "ftp-data"; break;
      case   21: return "ftp"; break;
      case   22: return "ssh"; break;
      case   23: return "telnet"; break;
      case   25: return "smtp"; break;
      case   42: return "name"; break;
      case   43: return "whois"; break;
      case   53: return "domain"; break;
      case   69: return "tftp"; break;
      case   79: return "finger"; break;
      case   80: return "http"; break;
      case  109: return "pop2"; break;
      case  110: return "pop3"; break;
      case  115: return "sftp"; break;
      case  119: return "nntp"; break;
      case  135: return "msrpc"; break;
      case  137: return "netbios-ns"; break;
      case  138: return "netbios-dgm"; break;
      case  139: return "netbios-ssn"; break;
      case  143: return "imap4"; break;
      case  220: return "imap3"; break;
      case  389: return "ldap"; break;
      case  443: return "https"; break;
      case  445: return "microsoft-ds"; break;
      case  465: return "smtps"; break;
      case  993: return "imap4s"; break;
      case  995: return "pop3s"; break;
      case 5000: return "UPnP"; break;
      default  : return "Port could not be determined"; break;
  }
}

# 4.01 printer
# Function to print variables in a readable format
function printer($printvar) {
  echo "<pre>";
  print_r($printvar);
  echo "</pre>\n";
}

# 4.02 debug_input
# Function to print debug information about POST and GET variables
function debug_input() {
  global $c_debug_input;
  global $clean;
  global $tainted;
  if ($c_debug_input == 1) {
    echo "<pre>";
    echo "TAINTED:\n";
    print_r($tainted);
    echo "\n";
    echo "CLEAN:\n";
    print_r($clean);
    echo "</pre><br />\n";
  }
}

# 4.03 debug_sql
# Function to print debug information about the SQL queries done
function debug_sql() {
  global $c_debug_sql;
  global $debuginfo;
  if ($c_debug_sql == 1) {
    echo "<br /><br />\n";
    echo "<textarea cols=138 rows=20>";
    if (is_array($debuginfo)) {
      foreach ($debuginfo as $val) {
        echo "$val\n\n";
      }
    }
    echo "</textarea>\n";
  }
}

?>
