<?php include("menu.php"); set_title("Check"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.10                  #
# 03-07-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.10 Added yearly option 
# 1.04.09 Added IP exclusions stuff
# 1.04.08 Set default back to weekly  
# 1.04.07 Added possible attacks check 
# 1.04.06 wrong amount of attacks bug fixed
# 1.04.05 add_to_sql()
# 1.04.04 Changed some sql stuff
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Code layout
# 1.03.01 Released as part of the 1.03 package
# 1.02.08 Added intval() to session variables + pattern matching on $b + intval() for $month and $day
# 1.02.07 Added some more input checks and removed includes
# 1.02.06 Removed intval from date browsing
# 1.02.05 Minor bugfixes and code cleaning
# 1.02.04 Enhanced debugging
# 1.02.03 Added number formatting
# 1.02.02 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

$allowed_get = array(
                "int_org",
		"b",
		"i",
		"int_to",
		"int_from"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_search == 9 && isset($clean['org'])) {
  $q_org = $clean['org'];
} else {
  $q_org = intval($s_org);
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$db_org_name = pg_escape_string(pg_result($result_getorg, 0));

$debuginfo[] = $sql_getorg;

### Default browse method is weekly.
if (isset($tainted['b'])) {
  $b = $tainted['b'];
  $pattern = '/^(weekly|daily|monthly|yearly|all)$/';
  if (!preg_match($pattern, $b)) {
    $b = "weekly";
  }
} else {
  $b = "weekly";
}

$year = date("Y");
if ($b == "yearly") {
  $yearly = $tainted['i'];
  if ($yearly == "") { $yearly = date("Y"); }
  $yearly = intval($yearly);
  $next = $yearly + 1;
  $prev = $yearly - 1;
  $start = mktime(0, 0, 0, 1, 1, $yearly);
  $end = mktime(23, 59, 59, 12, 31, $yearly);
} else {
  $yearly = date("Y");
}
if ($b == "monthly") {
  $month = $tainted['i'];
  if ($month == "") { $month = date("n"); }
  $month = intval($month);
  $next = $month + 1;
  $prev = $month - 1;
  $start = getStartMonth($month, $year);
  $end = getEndMonth($month, $year);
} else {
  $month = date("n");
}
if ($b == "daily") {
  $day = $tainted['i'];
  if ($day == "") { $day = date("d"); }
  $day = intval($day);
  $prev = $day - 1;
  $next = $day + 1;  
  $start = getStartDay($day, $month, $year);
  $end = getEndDay($day, $month, $year);
} else {
  $day = date("d");
}
if ($b == "weekly") {
  $day = $tainted['i'];
  if ($day == "") { $day = date("d"); }
  $day = intval($day);
  $prev = $day - 7;
  $next = $day + 7;
  $start = getStartWeek($day, $month, $year);
  $end = getEndWeek($day, $month, $year);
}
if ($b == "all") {
  $dateqs = "";
  $tsquery = "";
} else {
  $dateqs = "&amp;int_from=$start&amp;int_to=$end";
  $tsquery = "timestamp >= $start AND timestamp <= $end";
}

echo "Checking organisation ranges for attacks sourced by these ranges.<br /><br />\n";
### BROWSE MENU
$today = date("U");

echo "<form name='selectorg' method='get' action='logcheck.php?int_org=$q_org'>\n";
#  echo "<input type='hidden' name='int_org' value='$q_org' />\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='logcheck.php?b=$b&amp;i=$prev&amp;int_org=$q_org';>\n";
  } else {
    echo "<input type='button' value='Prev' class='button' disabled>\n";
  }
  echo "<select name='b' onChange='javascript: this.form.submit();'>\n";
    echo printOption("all", "All", $b) . "\n";
    echo printOption("daily", "Daily", $b) . "\n";
    echo printOption("weekly", "Weekly", $b) . "\n";
    echo printOption("monthly", "Monthly", $b) . "\n";
    echo printOption("yearly", "Yearly", $b) . "\n";
  echo "</select>\n";

  if ($s_access_search == 9) {
    if (!isset($clean['org'])) {
      $err = 1;
    }
    $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
    $debuginfo[] = $sql_orgs;
    $result_orgs = pg_query($pgconn, $sql_orgs);
    echo "<select name='int_org' onChange='javascript: this.form.submit();'>\n";
      while ($row = pg_fetch_assoc($result_orgs)) {
        $org_id = $row['id'];
        $organisation = $row['organisation'];
        echo printOption($org_id, $organisation, $q_org) . "\n";
      }
    echo "</select>&nbsp;\n";
  }

  if ($b != "all") {
    if ($end > $today) {
      echo "<input type='button' value='Next' class='button' disabled>\n";
    } else {
      echo "<input type='button' value='Next' class='button' onClick=window.location='logcheck.php?b=$b&amp;i=$next&amp;int_org=$q_org';>\n";
    }
  } else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";

if ($err != 1) {
  $sql_ranges = "SELECT ranges FROM organisations WHERE id = $q_org";
  $debuginfo[] = $sql_ranges;
  $result_ranges = pg_query($pgconn, $sql_ranges);
  $row = pg_fetch_assoc($result_ranges);

  if ($row['ranges'] == "") {
    echo "No ranges present for this organisation.<br />\n";
    $err = 1;
  }
}

if ($err != 1) {
  ### Showing period.
  echo "<br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      if ($b == "all") {
        echo "<td class='dataheader' width='600' colspan='5'>All results</td>\n";
      } elseif ($b == "daily") {
        $datestart = date("d-m-Y", $start);
        echo "<td class='datatitle' width='600' colspan='5'>Results from $datestart</td>\n";
      } else {
        $datestart = date("d-m-Y", $start);
        $dateend = date("d-m-Y", $end);
        echo "<td class='datatitle' width='600' colspan='5'>Results from $datestart to $dateend</td>\n";
      }
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' width='300'>Range</td>\n";
      echo "<td class='dataheader' width='150'>Malicious Attacks</td>\n";
      echo "<td class='dataheader' width='150'>Unique Source Addresses</td>\n";
      echo "<td class='dataheader' width='150'>Possible Malicious Attacks</td>\n";
      echo "<td class='dataheader' width='150'>Unique Source Addresses</td>\n";
      
    echo "</tr>\n";
}

if ($err != 1) {

  ### Looping through organisation info retrieved by soap connection.
  $ranges_ar = explode(";", $row['ranges']);

  foreach ($ranges_ar as $range) {
    if (trim($range) != "") {
      add_to_sql("attacks", "table");
      add_to_sql("sensors", "table");
      add_to_sql("attacks.sensorid = sensors.id", "where");
      add_to_sql("attacks.source <<= '$range'", "where");
      add_to_sql("$tsquery", "where");

      # IP Exclusion stuff
      add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

      prepare_sql();

      $sql_total1 = "SELECT COUNT(attacks.id) as total ";
      $sql_total1 .= " FROM $sql_from ";
      $sql_total1 .= " $sql_where AND attacks.severity = 1";

      $sql_uniq1 = "SELECT DISTINCT source ";
      $sql_uniq1 .= " FROM $sql_from ";
      $sql_uniq1 .= " $sql_where AND attacks.severity = 1";
      
      $sql_total0 = "SELECT COUNT(attacks.id) as total ";
      $sql_total0 .= " FROM $sql_from ";
      $sql_total0 .= " $sql_where AND attacks.severity = 0";

      $sql_uniq0 = "SELECT DISTINCT source ";
      $sql_uniq0 .= " FROM $sql_from ";
      $sql_uniq0 .= " $sql_where AND attacks.severity = 0";

      $debuginfo[] = $sql_total0;
      $debuginfo[] = $sql_total1;
      $debuginfo[] = $sql_uniq0;
      $debuginfo[] = $sql_uniq1;

      $result_total1 = pg_query($pgconn, $sql_total1);
      $row_total1 = pg_fetch_assoc($result_total1);
      $count_total1 = $row_total1['total'];

      $result_uniq1 = pg_query($pgconn, $sql_uniq1);
      $count_uniq1 = pg_num_rows($result_uniq1);
      
      $result_total0 = pg_query($pgconn, $sql_total0);
      $row_total0 = pg_fetch_assoc($result_total0);
      $count_total0 = $row_total0['total'];

      $result_uniq0 = pg_query($pgconn, $sql_uniq0);
      $count_uniq0 = pg_num_rows($result_uniq0);

      reset_sql();

      echo "<tr>\n";
        echo "<td class='datatd'>$range</td>\n";
        if ($count_total1 > 0) {
          echo "<td class='datatd' align='right'><a href='logsearch.php?net_searchnet=$range&amp;int_sev=1&amp;int_org=$q_org$dateqs'>" . nf($count_total1) . "</a>&nbsp;</td>\n";
          if ($s_access_search == 9) {
            echo "<td class='datatd' align='right'><a href='loglist.php?net_range=$range$dateqs&amp;int_org=$q_org&b=$b'>" . nf($count_uniq1) . "</a>&nbsp;</td>\n";
          } else {
            echo "<td class='datatd' align='right'><a href='loglist.php?net_range=$range$dateqs&amp;int_org=$q_org'>" . nf($count_uniq1) . "</a>&nbsp;</td>\n";
          }
        } else {
          echo "<td class='datatd' align='right'>" . nf($count_total1) . "&nbsp;</td>\n";
          echo "<td class='datatd' align='right'>" . nf($count_uniq1) . "&nbsp;</td>\n";
        }
        if ($count_total0 > 0) {
          echo "<td class='datatd' align='right'><a href='logsearch.php?net_searchnet=$range&amp;int_sev=0&amp;int_org=$q_org$dateqs'>" . nf($count_total0) . "</a>&nbsp;</td>\n";
          echo "<td class='datatd' align='right'>" . nf($count_uniq0) . "&nbsp;</td>\n";
        } else {
          echo "<td class='datatd' align='right'>" . nf($count_total0) . "&nbsp;</td>\n";
          echo "<td class='datatd' align='right'>" . nf($count_uniq0) . "&nbsp;</td>\n";
        }
      echo "</tr>\n";
    }
  }
  echo "</table>\n";
}
debug_sql();
?>
<?php footer(); ?>
