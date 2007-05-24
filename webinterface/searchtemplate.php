<?php include("menu.php"); set_title("Searchtemplates");

#################################################
# SURFnet IDS                      		#
# Version 1.04.06                  		#
# 24-05-2007                       		#
# Peter Arts 					#
# Modified by Jan van Lith & Kees Trippelvitz   #
#################################################

#############################################
# Changelog:
# 1.04.06 Fixed a bug with the links (reported by Bjoern Weiland)
# 1.04.05 Fixed a bug with timestamping
# 1.04.04 Changed data input handling
# 1.04.03 Added debug info	    
# 1.04.02 Added template administration
# 1.04.01 Initial version
#############################################

$allowed_get = array(
                "action",
                "int_delete",
		"strip_html_escape_tsstart",
		"strip_html_escape_tsend",
		"tsselect",
		"strip_html_escape_sttitle",
);
$check = extractvars($_GET, $allowed_get);
if (isset($clean['tsstart'])) {
  $clean['tsstart'] = urldecode($clean['tsstart']);
}
if (isset($clean['tsend'])) {
  $clean['tsend'] = urldecode($clean['tsend']);
}
if (isset($tainted['tsselect'])) {
  $tainted['tsselect'] = urldecode($tainted['tsselect']);
}
debug_input();

$action = $tainted['action'];
$pattern = '/^(admin)$/';
if (!preg_match($pattern, $action)) {
  $action = "";
}

if ($action == "admin") {
  $userid = intval($_SESSION["s_userid"]);
  $delete_id = $clean["delete"];
  if ($delete_id > 0) {
    // remove this template
    $sql = "DELETE FROM searchtemplate WHERE userid = '" . intval($userid) . "' AND id = '" . intval($delete_id) . "'";
    $debuginfo[] = $sql;
    $query = pg_query($sql);
    if (pg_affected_rows($query) == 1) {
      echo "<p><b>Searchtemplate removed.</b></p>\n";
    } else {
      echo "<p><b>Searchtemplate NOT removed.</p>\n";
    }
  }
  $sql = "SELECT * FROM searchtemplate WHERE userid = '" . intval($userid) . "' ORDER BY title";
  $debuginfo[] = $sql;
  $query = pg_query($sql);
  echo "<table border=0 cellspacing=2 cellpadding=2 class='datatable'>\n";
  while ($row = pg_fetch_assoc($query)) {
    // list current searchtemplates
    echo " <tr class='datatr'>\n";
    echo "  <td class='datatd'>" . $row["title"] . "</td>\n";
    echo "  <td class='datatd'>[ <a href=\"searchtemplate.php?action=admin&int_delete=" . $row["id"] . "\" onclick=\"return confirm('Delete this searchtemplate?');\">delete</a> ]</td>\n";
    echo " </tr>\n";
  }
  echo "</table>\n";
} else {
  // get querystring
  $querystring = urldecode($_SERVER['QUERY_STRING']);
  // remove date/time values from querystring
  $search = array("|", "?", "&strip_html_escape_tsstart=", "&strip_html_escape_tsend=", "&tsselect=", $clean['tsstart'], $clean['tsend'], $tainted['tsselect']);
  $querystring = str_replace($search, "", $querystring);

  // check for selected value or user input
  $ts_select = $tainted["tsselect"];
  $ar_valid_values = array("H", "D", "T", "W", "M", "Y");
  if (in_array($ts_select, $ar_valid_values)) {
    $ts_start = "|%dt-%" . $ts_select . "|";
    $ts_end = "|%dt|";
  } else {
    // replace date/time values
    $ts_start = $clean["tsstart"];
    list($date, $time) = explode(" ", $ts_start);
    list($day, $mon, $year) = explode("-", $date);
    list($hour, $min) = explode(":", $time);
    $ts_start = mktime($hour, $min, 0, $mon, $day, $year);
    $ts_end = $clean["tsend"];
    list($date, $time) = explode(" ", $ts_end);
    list($day, $mon, $year) = explode("-", $date);
    list($hour, $min) = explode(":", $time);
    $ts_end = mktime($hour, $min, 0, $mon, $day, $year);
		
    $ts_now = time();
    // future dates doesn't make sense
    if ($ts_start > $ts_now) {
      $ts_start = $ts_now;
    }
    if ($ts_end > $ts_now) {
      $ts_end = $ts_now;
    }
		
    $dif_start = abs($ts_now - $ts_start);
    $dif_end = abs($ts_now - $ts_end);
		
    if ($dif_start > 0) {
      $dif_start = "-" . $dif_start;
    } else {
      $dif_start = "";
    }
    if ($dif_end > 0) {
      $dif_end = "-" . $dif_end;
    } else {
      $dif_end = "";
    }
    $ts_start = "|%dt" . $dif_start . "|";
    $ts_end = "|%dt" . $dif_end . "|";
  }

  // secure title input
  $title = preg_replace('/[^a-z0-9 -_]+/i', '', $clean["sttitle"]);
  if (!empty($title)) {
    $querystring = "strip_html_escape_tsstart=" . $ts_start . "&strip_html_escape_tsend=" . $ts_end . "&" . $querystring;
 	
    // save the querystring for this user
    $userid = intval($_SESSION["s_userid"]);
    if ($userid > 0) {
      $sql = "INSERT INTO searchtemplate (title, userid, querystring) VALUES ('" . pg_escape_string($title) . "', '" . intval($userid) . "', '" . pg_escape_string($querystring) . "')";
      $debuginfo[] = $sql;
      $query = pg_query($sql);
      if (pg_affected_rows($query) == 1) {
        $m = geterror(1);
        echo $m;
      } else {
        $m = geterror(94);
        echo $m;
      }
    } else {
      $m = geterror(92);
      echo $m;
    }
  } else {
    $m = geterror(93);
    echo $m;
  }
  echo "<p><a href=\"search.php\">Back to search</a></p>\n";
}
debug_sql();
footer(); ?>
