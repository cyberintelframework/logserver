<?php include("menu.php"); set_title("Log Overview");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.06                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.06 Added intval() to $s_admin and $s_org
# 1.02.05 Added some intvals and pg_escape_strings
# 1.02.04 Added some input checks on the $b and $org variables
# 1.02.03 Fixed a bug in the sql_severity query (organisation bug)
# 1.02.02 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';
include 'include/variables.inc.php';

if (isset($_SESSION['s_total_search_records'])) {
  unset($_SESSION['s_total_search_records']);
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = $s_access{1};
$and = "AND";
$adwhere = "WHERE";
$selall = "";
$selday = "";
$selweek = "";
$selmon = "";
$q_org = $s_org;

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

### Setting organisation if user is admin.
if ($s_access_search == 9) {
  if (isset($_GET['org'])) {
    $q_org = intval($_GET['org']);
  }
  else {
    $q_org = 0;
  }
}

### Checking period browse method
if ($b == "daily" || $b == "monthly") {
  $day = $_GET['d'];
  if ($day == "") {
    $day = date("d");
  }
  $day = intval($day);
  $prev = $day - 1;
  $next = $day + 1;
  $month = date("n");
  $year = date("Y");
  if ($b == "daily") {
    $start = getStartDay($day,$month,$year);
    $end = getEndDay($day,$month,$year);
    $selday = "selected";
  }
  elseif ($b == "monthly") {
    $month = $_GET['d'];
    if ($month == "") {
      $month = date("n");
    }
    $month = intval($month);
    $prev = $month - 1;
    $next = $month + 1;
    $start = getStartMonth($month,$year);
    $end = getEndMonth($month,$year);
    $selmon = "selected";
  }
  $searchqs = "&amp;from=$start&amp;to=$end";
  $tsquery = "timestamp >= $start AND timestamp <= $end";
}
elseif ($b == "weekly") {
  $day = $_GET['d'];
  if ($day == "") {
    $day = date("d");
  }
  $day = intval($day);
  $prev = $day - 7;
  $next = $day + 7;
  $month = date("n");
  $year = date("Y");
  $start = getStartWeek($day,$month,$year);
  $end = getEndWeek($day,$month,$year);
  $searchqs = "&amp;from=$start&amp;to=$end";
  $tsquery = "timestamp >= $start AND timestamp <= $end";
  $selweek = "selected";
}
elseif ($b == "all") {
  $searchqs = "";
  $tsquery = "";
  $adwhere = "";
  $and = "";
  $selall = "selected";
}

### Admin check.
if ($s_access_search == 9) {
  if (isset($_GET['org']) && $q_org != 0) {
    if ($adwhere == "WHERE") {
      $adwhere = "AND";
    }
    $sql_severity = "SELECT DISTINCT attacks.severity, COUNT(attacks.severity) as total FROM attacks, sensors WHERE sensors.organisation = $q_org AND sensors.id = attacks.sensorid $adwhere $tsquery GROUP BY attacks.severity";
  } else {
    $sql_severity = "SELECT DISTINCT attacks.severity, COUNT(attacks.severity) as total FROM attacks $adwhere $tsquery GROUP BY attacks.severity";
  }
}
else {
  $sql_severity = "SELECT DISTINCT attacks.severity, COUNT(attacks.severity) as total FROM attacks, sensors WHERE $tsquery $and attacks.sensorid = sensors.id AND sensors.organisation = " .$q_org. " GROUP BY attacks.severity";
}
$result_severity = pg_query($pgconn, $sql_severity);

#echo "SQL: $sql_severity<br />\n";
#echo "QORG: $q_org<br />\n";

### Period and organisation menu
$today = date("U");
echo "<form name='selectorg' method='get' action='logindex.php'>\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='logindex.php?b=$b&amp;d=$prev&amp;org=$q_org';>\n";
  }
  else {
    echo "<input type='button' value='Prev' class='button' disabled>\n";
  }
  echo "<select name='b' onChange='javascript: this.form.submit();'>\n";
    echo "<option value='all' $selall>All</option>\n";
    echo "<option value='daily' $selday>Daily</option>\n";
    echo "<option value='weekly' $selweek>Weekly</option>\n";
    echo "<option value='monthly' $selmon>Monthly</option>\n";
  echo "</select>\n";

  ### If user is admin, then enable organisation menu.
  if ($s_access_search == 9) {
    $err = 1;
    $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
    $result_orgs = pg_query($pgconn, $sql_orgs);
      echo "<select name='org' onChange='javascript: this.form.submit();'>\n";
        echo "<option value='0'>All</option>\n";
        while ($row = pg_fetch_assoc($result_orgs)) {
          $org_id = $row['id'];
          $organisation = $row['organisation'];
          if ($q_org == $org_id) {
            echo "<option value='$org_id' selected>$organisation</option>\n";
          }
          else {
            echo "<option value='$org_id'>$organisation</option>\n";
          }
        } 
      echo "</select>&nbsp;\n";
  }
  if ($b != "all") {
    if ($end > $today) {
      echo "<input type='button' value='Next' class='button' disabled>\n";
    }
    else {
      echo "<input type='button' value='Next' class='button' onClick=window.location='logindex.php?b=$b&amp;d=$next&amp;org=$q_org';>\n";
    }
  }
  else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";
### End menu

echo "<table class='datatable'>\n";
  ### Showing period
  echo "<tr>\n";
    if ($b == "all") {
      echo "<td class='dataheader' width='600' colspan='2'>All results</td>\n";
    }
    else {
      $datestart = date("d-m-Y", $start);
      $dateend = date("d-m-Y", $end);
      echo "<td class='dataheader' width='600' colspan='2'>Results from $datestart to $dateend</td>\n";
    }
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' width='500'>Detected connections</td>\n";
    echo "<td class='dataheader' width='100'>Statistics</td>\n";
  echo "</tr>\n";

  while($row = pg_fetch_assoc($result_severity)) {
    $severity = $row['severity'];
    $count = $row['total'];
    $description = $severity_ar[$severity];
    echo "<tr>\n";
      echo "<td class='datatd'>$description</td>\n";
      if ($severity == 0 || $severity == 16) {
        echo "<td class='datatd'><a href='logsearch.php?f_sev=$severity&amp;f_field=source&amp;f_search=&amp;org=$q_org$searchqs'>$count</a></td>\n";
      }
      elseif ($severity == 1 || $severity == 32) {
        echo "<td class='datatd'><a href='logattacks.php?sev=$severity&amp;org=$q_org$searchqs'>$count</a></td>\n";
      }
    echo "</tr>\n";
  }
echo "</table>\n";
pg_close($pgconn);
?>
<?php footer(); ?>
