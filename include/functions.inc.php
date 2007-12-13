<?php
####################################
# SURFnet IDS                      #
# Version 2.00.04                  #
# 25-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 2.00.04 Fixed a bug with printled incorrectly displaying boolean values
# 2.00.03 Rearranged functions and index
# 2.00.02 Modified sec_to_string for specifying a type
# 2.00.01 Added array support for printoption
# 1.05.01 Added sec_to_string and sensorname functions
# 1.04.19 Added footer function
# 1.04.18 Fixed typo in inet check
# 1.04.17 Added inet check for extractvars
# 1.04.16 Fixed bool check in extractvars
# 1.04.15 Removed cleansql function
# 1.04.14 Removed / from logsearch.php url
# 1.04.13 Added mac type to extractvars
# 1.04.12 Modified printhelp
# 1.04.11 Modified censorip()
# 1.04.10 Added censorip()
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
#
# 3 Miscellaneous
# 3.01		extractvars
# 3.02		geterror
# 3.03		checkSID
# 3.04		getaddress
# 3.05		genpass
# 3.06		pgboolval
# 3.07		validate_email
# 3.08		nf
# 3.09		size_hum_read
# 3.10		microtime_float
# 3.11		matchCIDR
# 3.12		getportdescr
# 3.13		censorip
# 3.14		footer
# 3.15		sec_to_string
# 3.16		sensorname
# 3.17		sorter
# 3.18		gen_org_sql
# 3.19		cleanfooter
#
# 4 Debug Functions
# 4.01		printer
# 4.02		debug_input
# 4.03		debug_sql
#
# 5 Print functions
# 5.01		printhelp
# 5.02		printsort
# 5.03		printMenuitem
# 5.04		printTabItem
# 5.05		printled
# 5.06		downlink
# 5.07		printover
# 5.08		printosimg
# 5.09		printflagimg
# 5.10		printradio
# 5.11		printcheckbox
# 5.12		printoption
#############################################

###############################
# 1 SQL functions
###############################

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

###############################
# 2 Date & Time functions
###############################

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

###############################
# 3 Misc functions
###############################

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
#   inet - ip address with/without cidr
#   mac - mac address regexp
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
                    $tainted[$temp] = $var;
                  } else {
                    if ($var == "true" || $var == "false") {
                      $var = pgboolval($var);
                    }
                    $clean[$temp] = $var;
                  }
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
                } elseif ($check == "inet") {
                  $chk = substr_count($var, "/");
                  if ($chk == 1) {
                    $ar_test = explode("/", $var);
                    $ip_test = $ar_test[0];
                    $mask_test = intval($ar_test[1]);
                    if (preg_match($ipregexp, $ip_test) && $mask_test >= 0 && $mask_test <= 32) {
                      $clean[$temp] = $var;
                    } else {
                      $tainted[$temp] = $var;
                    }
                  } elseif ($chk == 0) {
                    if (preg_match($ipregexp, $var)) {
                      $clean[$temp] = $var;
                    } else {
                      $tainted[$temp] = $var;
                    }
                  } else {
                    $tainted[$temp] = $var;
                  }
                } elseif ($check == "mac") {
                  $macregexp = '/^([a-zA-Z0-9]{2}:){5}[a-zA-Z0-9]{2}$/';
                  if (preg_match($macregexp, $var)) {
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
  $e = $v_errors[$m];

  if ($m < 90) {
    $type = "info";
  } else {
    $type = "error";
  }

  echo "<div class='all'>\n";
  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='${type}Block'>\n";
        echo "<div class='blockHeader'>" .ucfirst($type). "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='whois' method='get'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<td width='100'>" .ucfirst($type). " code:</td>\n";
                echo "<td width='200'>$m</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .ucfirst($type). " message:</td>\n";
                echo "<td>$e</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</errorBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>
  echo "</div>\n"; #</all>
}

# 3.03 checkSID
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

# 3.04 getaddress
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

# 3.05 genpass
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

# 3.06 pgboolval
# Function to return the PostgreSQL value for a boolean
function pgboolval($val) {
  $val = strtolower($val);
  if ($val == "t") return $val;
  else return "f";
}

# 3.07 validate_email
# Function to check if the given email is a valid email address
function validate_email($email) {
  $regex = '/^([a-zA-Z0-9_\-\.,]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
  return preg_match($regex, $email);
}

# 3.08 nf
# Function to format a number with a given amount of decimal places
function nf($nr, $num_decimal_places = 0) {
  return number_format($nr, $num_decimal_places, ".", ",");
}

# 3.09 size_hum_read
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

# 3.10 microtime_float
# Function used to calculate rendering time of the search pages.
# Returns current time in microseconds
function microtime_float() {
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

# 3.11 matchCIDR
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

# 3.12 getportdescr
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
      default  : return "No description"; break;
  }
}

# 3.13 censorip
# Function to determine if a destination IP address has to be censored or not
#function censorip($ip, $ranges_ar) {
function censorip($ip) {
  global $c_censor_ip;
  global $s_access_search;
  global $c_censor_word;
  global $orgranges_ar;
  if ($c_censor_ip == 2) {
    # Censor all destination IP's
    return $c_censor_word;
  } elseif ($c_censor_ip == 1) {
    if ($s_access_search != 9) {
      if (isset($orgranges_ar)) {
        # Censor all destination IP's not of organisation ranges
        $check = matchCIDR($ip, $orgranges_ar);
        if ($check == 1) {
          return $ip;
        } else {
          return $c_censor_word;
        }
      } else {
        return $ip;
      }
    } else {
      # Except if user is admin.
      return $ip;
    }
  } else {
    return $ip;
  }
}

# 3.14 footer
# Function to print the page footer
function footer() {
  global $c_version, $c_footer_address;

  if (isset($pgconn)) {
    pg_close($pgconn);
  }
  echo "</div>\n";
  echo "<div id=\"pageFooter\">\n";
    echo "<div class='pageFooterLeft'><b>SURFids version: $c_version</b> | <a href='http://ids.surfnet.nl' target='_blank'>http://ids.surfnet.nl</a></div>";
    echo "<div class='pageFooterRight'>$c_footer_address</div>";
  echo "</div>\n";
  echo "<div class='clearer'></div>\n";
  echo "</div>\n";
}

# 3.15 sec_to_string
# Function to convert a time in seconds to a human readable string
# for example "3600" will be "0d 1h 0m 0s"
function sec_to_string($sec, $type = 4) {
  if ($sec) {
    $onehour = 60 * 60;
    $oneday = $onehour * 24;

    $days = floor($sec / $oneday);
    $sec = $sec % $oneday;
    $hours = floor($sec / $onehour);
    $sec = $sec % $onehour;
    $minutes = floor($sec / 60);
    $seconds = $sec % 60;
    if ($type == 1) {
      $string = "${days}d";
    } elseif ($type == 2) {
      $string = "${days}d ${hours}h";
    } elseif ($type == 3) {
      $string = "${days}d ${hours}h ${minutes}m";
    } else {
      $string = "${days}d ${hours}h ${minutes}m ${seconds}s";
    }
    return $string;
  } else {
    return "undefined";
  }
}

# 3.16 sensorname
# Function to return the correct sensorname
# Input: keyname + vlanid
function sensorname($keyname, $vlanid) {
  if ($vlanid == 0) {
    $s = $keyname;
  } else {
    $s = "$keyname-$vlanid";
  }
  return $s;
}

# 3.17 sorter
# Function to setup the sort methods
function sorter($sort, $pattern) {
  global $sort_dir;
  if (!preg_match($pattern, $sort)) {
    return "";
  }
  $sorttype = substr($sort, 0, (strlen($sort) - 1));
  $sort_dir = substr($sort, -1, 1);
  if ($sort_dir == "a") {
    $dir = "ASC";
  } else {
    $dir = "DESC";
  }
  $str = "$sorttype $dir";
  if ($sorttype == "keyname") {
    $str .= ", vlanid $dir";
  }
  return "$str";
}

# 3.18 gen_org_sql
# Function to generate the organisation clause for an SQL query
function gen_org_sql($f_ownranges = 0) {
  global $q_org, $pgconn;
  if ($q_org != 0) {
    $sql_getranges = "SELECT ranges FROM organisations WHERE id = $q_org";
    $result_getranges = pg_query($pgconn, $sql_getranges);
    $temp = pg_fetch_assoc($result_getranges);
    $orgranges = $temp['ranges'];
    $orgranges = rtrim($orgranges, ";");
    $orgranges_ar = explode(";", $orgranges);
    if ($f_ownranges == 0) {
      $tmp_sql = "(sensors.organisation = $q_org";
    } else {
      $tmp_sql = "(";
    }
    foreach ($orgranges_ar as $key => $value) {
      if ($value != "") {
        if ($key != (count($orgranges_ar) - 1)) {
          $ranges_sql .= "attacks.source <<= '$value' OR ";
        } else {
          $ranges_sql .= "attacks.source <<= '$value'";
        }
      }
    }
    if ($ranges_sql != "") {
      if ($f_ownranges == 1) {
        $tmp_sql .= "$ranges_sql";
      } else {
        $tmp_sql .= " OR ($ranges_sql)";
      }
    }
    $tmp_sql .= ")";
    return $tmp_sql;
  }
}

# 3.19 cleanfooter
# Function to print the page footer
function cleanfooter() {
  if (isset($pgconn)) {
    pg_close($pgconn);
  }
  echo "</div>\n";
  echo "<div id=\"pagecleanFooter\"></div>\n";
  echo "</div>\n";
}

###############################
# 4 Debug functions
###############################

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
    echo "<div class='all'>\n";
      echo "<div class='centerbig'>\n";
        echo "<div class='block'>\n";
          echo "<div class='dataBlock'>\n";
            echo "<div class='blockHeader'>Input Debugging</div>\n";
            echo "<div class='blockContent'>\n";
              echo "<pre>";
                echo "TAINTED:\n";
                print_r($tainted);
                echo "\n";
                echo "CLEAN:\n";
                print_r($clean);
              echo "</pre>\n";
            echo "</div>\n"; #</blockContent>
            echo "<div class='blockFooter'></div>\n";
          echo "</div>\n"; #</dataBlock>
        echo "</div>\n"; #</block>
      echo "</div>\n"; #</centerbig>
    echo "</div>\n"; #</all>
  }
}

# 4.03 debug_sql
# Function to print debug information about the SQL queries done
function debug_sql() {
  global $c_debug_sql;
  global $debuginfo;
  if ($c_debug_sql == 1) {
    echo "<div class='centerbig'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>SQL Debugging</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<textarea rows=20 class='debugsql'>";
              if (is_array($debuginfo)) {
                foreach ($debuginfo as $val) {
                  echo "$val\n\n";
                }
              }
            echo "</textarea>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</centerbig>
  }
}

###############################
# 5 Print functions
###############################

# 5.01 printhelp
# Function to print a help link to the documentation
function printhelp($id) {
  global $v_help;
  global $c_showhelp;
  $e_file = $_SERVER['SCRIPT_NAME'];
  $e_file = basename($e_file);
  $e_file = str_replace(".", "", $e_file);
  $m = $v_help[$e_file][$id];
  if ($c_showhelp == 1) {
    return "<a href='#' onmouseover='return overlib(\"$m\");' onmouseout='return nd();'>[?]</a>";
  } else {
    return "";
  }
}

# 5.02 printsort
# Function to print a sort arrow.
function printsort($text, $sortitem) {
  global $url, $sort;
  $sort_dir = substr($sort, (strlen($sort) - 1), strlen($sort));
  if (!$sort_dir) {
    $sort_dir = "a";
  }
  $url = str_replace("&sort=${sort}", "", $url);
  $url = str_replace("sort=${sort}", "", $url);
  $url = str_replace("?&", "?", $url);
  $url = str_replace("&&", "&", $url);
  $chk = substr($sort, 0, (strlen($sort) - 1));
  if ($sortitem == $chk) {
    if ($sort_dir == "a") {
      $str = "<a href='${url}sort=${sortitem}d'>$text</a>";
      $str .= "&nbsp;<img src='images/up.gif' />";
    } else {
      $str = "<a href='${url}sort=${sortitem}a'>$text</a>";
      $str .= "&nbsp;<img src='images/down.gif' />";
    }
  } else {
    $str = "<a href='${url}sort=${sortitem}a'>$text</a>";
  }
  return $str;
}

# 5.03 printMenuitem
# Function to print a select option
function printMenuitem($i, $url, $text, $c) {
  global $address;
  $s .= "<li";
  if ($i == $c) {
    $s .= " class='selected' ";
  }
  $s .= "><a href='" .$address . $url. "'>$text</a></li>\n";
  return $s;
}

# 5.04 printTabItem 
# Function to print a select option
function printTabItem($i, $url, $text, $c) {
  global $address;
  $s = "<li id='sel" .$i. "'"; 
  if ($i == $c) {
    $s .= " class='selected' ";
  }
  if ($url == "") {
    $s .= "><a onclick='showtab(\"$i\");'>" .$text. "</a>\n";
  } else {
    $s .= "><a href='" .$address . $url. "' onclick='showtab(\"$i\");'>" .$text. "</a>\n";
  }
  return $s;
}

# 5.05 printled
# Function to print a led image based on a value
function printled($var) {
  if ("$var" == "0" || "$var" == "false") {
    $s = "<img src='images/stat-bw.gif' />\n";
  } elseif ("$var" == "1" || "$var" == "true") {
    $s = "<img src='images/stat-ok.gif' />\n";
  } else {
    $s = "<img src='images/stat-nb.gif' />\n";
  }
  return $s;
}

# 5.06 downlink
# Function to create a link with arrow
function downlink($href, $text, $over = '') {
  $a = "<a href='$href'";
  if ($over != "") {
    $a .= printover($over);
  }
  $a .= ">${text}&nbsp;<img src='images/eastdown_arrow9x9.gif' /></a>";
  return $a;
}

# 5.07 printover
# Function to print an overlib string
function printover($text) {
  $text = htmlspecialchars($text, ENT_QUOTES);
  $s = " onmouseover='return overlib(\"$text\");' onmouseout='return nd();'";
  return $s;
}

# 5.08 printosimg
# Function to print an OS image
function printosimg($os, $fingerprint) {
  global $c_surfidsdir;
  $osimg = "$c_surfidsdir/webinterface/images/ostypes/$os.gif";
  if (file_exists($osimg)) {
    $s = "<img class='fingerprint' src='images/ostypes/$os.gif' " .printover($fingerprint). " />";
  } else {
    $s = "<img class='fingerprint' src='images/ostypes/Blank.gif' " .printover($fingerprint). " />";
  }
  return $s;
}

# 5.09 printflagimg
# Function to print an OS image
function printflagimg($country, $countrycode) {
  if ($country != "none") {
    $s = "<img class='flag' src='images/worldflags/flag_" .$countrycode. ".gif' " .printover($country). " />";
  } else {
    $s = "<img class='flag' src='images/worldflags/flag.gif' " .printover("No Country Info"). " />";
  }
  return $s;
}

# 5.10 printradio
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

# 5.11 printcheckbox
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

# 5.12 printoption
# Function to print a select option
function printOption($value, $text, $val, $status = "") {
  global $v_sensorstatus_ar;
  $return = "<option value=\"$value\"";
  if (@is_array($val)) {
    if (in_array($value, $val)) {
      $return .= " selected";
    }
  } else {
    if ($val == $value) {
      $return .= " selected";
    }
  }
  if ($status != "") {
    $c = $v_sensorstatus_ar[$status]["class"];
    $return .= " class='$c'";
  }
  $return .= ">$text</option>\n";
  return $return;
}



?>
