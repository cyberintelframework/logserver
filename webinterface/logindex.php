<?php include("menu.php"); set_title("Log Overview");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.11                  #
# 09-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.02.11 Added intval() for session variables
# 1.02.10 Added some more input checks and removed includes
# 1.02.09 Fixed a bug with the search querystring and the period
# 1.02.08 Removed intval from date browsing
# 1.02.07 Minor bugfixes and code cleaning
# 1.02.06 Enhanced debugging
# 1.02.05 Added debug option
# 1.02.04 Bugfix: missing FROM-clause
# 1.02.03 Added number formatting
#############################################

if (isset($_SESSION['s_total_search_records'])) {
  unset($_SESSION['s_total_search_records']);
}

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

if ($s_access_search == 9 && isset($_GET['org'])) {
  $q_org = intval($_GET['org']);
} elseif ($s_access_search == 9) {
  $q_org = 0;
} else {
  $q_org = intval($s_org);
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
#$db_org_name = pg_result($result_getorg, 0);

### Default browse method is weekly.
if (isset($_GET['b'])) {
  $b = pg_escape_string($_GET['b']);
  $pattern = '/^(weekly|daily|monthly|all)$/';
  if (preg_match($pattern, $b) != 1) {
    $b = "weekly";
  }
}
else {
  $b = "weekly";
}

$year = date("Y");
if ($b == "monthly") {
  $month = $_GET['i'];
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
  $day = $_GET['i'];
  if ($day == "") { $day = date("d"); }
  $prev = $day - 1;
  $next = $day + 1;
  $start = getStartDay($day, $month, $year);
  $end = getEndDay($day, $month, $year);
} else {
  $day = date("d");
}
if ($b == "weekly") {
  $day = $_GET['i'];
  if ($day == "") { $day = date("d"); }
  $prev = $day - 7;
  $next = $day + 7;
  $start = getStartWeek($day, $month, $year);
  $end = getEndWeek($day, $month, $year);
}
if ($b == "all") {
  $searchqs = "";
  $tsquery = "";
} else {
  $searchqs = "&amp;from=$start&amp;to=$end";
  $tsquery = "timestamp >= $start AND timestamp <= $end";
}

echo "Checking organisation ranges for attacks sourced by these ranges.<br /><br />\n";
### BROWSE MENU
$today = date("U");
echo "<form name='selectorg' method='get' action='logindex.php?org=$q_org'>\n";
  echo "<input type='hidden' name='org' value='$q_org' />\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='logindex.php?b=$b&amp;i=$prev&amp;org=$q_org';>\n";
  }
  else {
    echo "<input type='button' value='Prev' class='button' disabled>\n";
  }
  echo "<select name='b' onChange='javascript: this.form.submit();'>\n";
    echo printOption("all", "All", $b) . "\n";
    echo printOption("daily", "Daily", $b) . "\n";
    echo printOption("weekly", "Weekly", $b) . "\n";
    echo printOption("monthly", "Monthly", $b) . "\n";
  echo "</select>\n";

  if ($s_access_search == 9) {
    if (!isset($_GET['org'])) {
      $err = 1;
    }
    $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
    $result_orgs = pg_query($pgconn, $sql_orgs);
    echo "<select name='org' onChange='javascript: this.form.submit();'>\n";
      echo printOption(0, "All", $q_org) . "\n";
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
    }
    else {
      echo "<input type='button' value='Next' class='button' onClick=window.location='logindex.php?b=$b&amp;i=$next&amp;org=$q_org';>\n";
    }
  }
  else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";

echo "<table class='datatable'>\n";
  ### Showing period
  echo "<tr>\n";
    if ($b == "all") {
      echo "<td class='dataheader' width='600' colspan='2'>All results</td>\n";
    } elseif ($b == "daily") {
      $datestart = date("d-m-Y", $start);
      echo "<td class='dataheader' width='600' colspan='2'>Results from $datestart</td>\n";
    } else {
      $datestart = date("d-m-Y", $start);
      $dateend = date("d-m-Y", $end);
      echo "<td class='dataheader' width='600' colspan='2'>Results from $datestart to $dateend</td>\n";
    }
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' width='500'>Detected connections</td>\n";
    echo "<td class='dataheader' width='100'>Statistics</td>\n";
  echo "</tr>\n";

  add_db_table("attacks");
  add_db_table("sensors");
  $where[] = "$tsquery";
  if ($s_access_search != 9 || ($s_access_search == 9 && $q_org != 0)) {
    $where[] = " sensors.organisation = $q_org ";
  }
  prepare_sql();

  $sql_severity = "SELECT DISTINCT attacks.severity, COUNT(attacks.severity) as total ";
  $sql_severity .= " FROM $sql_from ";
  $sql_severity .= " $sql_where ";
  $sql_severity .= " GROUP BY attacks.severity ";

  $result_severity = pg_query($pgconn, $sql_severity);

  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_SEVERITY: $sql_severity<br />\n";
    echo "</pre>\n";
  }

  while($row = pg_fetch_assoc($result_severity)) {
    $severity = $row['severity'];
    $count = $row['total'];
    $description = $severity_ar[$severity];
    echo "<tr>\n";
      echo "<td class='datatd'>$description</td>\n";
      if ($severity == 0 || $severity == 16) {
        echo "<td class='datatd' align='right'><a href='logsearch.php?f_sev=$severity&amp;f_field=source&amp;f_search=&amp;org=$q_org$searchqs'>" . nf($count) . "</a>&nbsp;</td>\n";
      }
      elseif ($severity == 1 || $severity == 32) {
        echo "<td class='datatd' align='right'><a href='logattacks.php?sev=$severity&amp;org=$q_org$searchqs'>" . nf($count) . "</a>&nbsp;</td>\n";
      }
    echo "</tr>\n";
  }
echo "</table>\n";
pg_close($pgconn);
?>
<?php footer(); ?>
