<?php include("menu.php"); set_title("Check"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 31-07-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.03 Added a few precautionary pg_escape_string commands
# 1.02.02 Added some input checks on the $b and $org variables
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_access = $_SESSION['s_access'];
$s_access_search = $s_access{1};
$and = "AND";
$adwhere = "WHERE";
$selall = "";
$selday = "";
$selweek = "";
$selmon = "";

if ($s_access_search == 9 && isset($_GET['org'])) {
  $s_org = intval($_GET['org']);
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $s_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$db_org_name = pg_result($result_getorg, 0);

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

### Checking period browse method.
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
  $dateqs = "&amp;from=$start&amp;to=$end";
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
  $dateqs = "&amp;from=$start&amp;to=$end";
  $tsquery = "timestamp >= $start AND timestamp <= $end";
  $selweek = "selected";
}
elseif ($b == "all") {
  $dateqs = "";
  $tsquery = "";
  $adwhere = "";
  $and = "";
  $selall = "selected";
}

### Checking for admin.
if ($s_access_search != 9) {
  echo "Checking organisation ranges for attacks sourced by these ranges.<br /><br />\n";
  ### BROWSE MENU
  $today = date("U");
  echo "<form name='selectorg' method='get' action='logcheck.php?org=$s_org'>\n";
    echo "<input type='hidden' name='org' value='$s_org' />\n";
    if ($b != "all") {
      echo "<input type='button' value='Prev' class='button' onClick=window.location='logcheck.php?b=$b&amp;d=$prev&amp;org=$s_org';>\n";
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
    if ($b != "all") {
      if ($end > $today) {
        echo "<input type='button' value='Next' class='button' disabled>\n";
      }
      else {
        echo "<input type='button' value='Next' class='button' onClick=window.location='logcheck.php?b=$b&amp;d=$next&amp;org=$s_org';>\n";
      }
    }
    else {
      echo "<input type='button' value='Next' class='button' disabled>\n";
    }
  echo "</form>\n";

}
### User is admin. Organisation check.
elseif ($db_org_name != "ADMIN" && $s_access_search == 9) {
  echo "Checking organisation ranges for attacks sourced by these ranges.<br /><br />\n";
  $today = date("U");
  echo "<form name='selectorg' method='get' action='logcheck.php?org=$s_org'>\n";
    if ($b != "all") {
      echo "<input type='button' value='Prev' class='button' onClick=window.location='logcheck.php?b=$b&amp;d=$prev&amp;org=$s_org';>\n";
    }
    else {
      echo "<input type='button' value='Prev' class='button' disabled>\n";
    }
    echo "<select name='b' onChange='javascript: this.form.submit();'>\n";
      echo "<option value='all' $selall>All</option>\n";
      echo "<option value='daily' $selday>Daily</option>\n";
      echo "<option value='weekly' $selweek>Weekly</option>\n";
      echo "<option value='monthly' $selmon>Monthly</option>\n";
    echo "</select>&nbsp;\n";

    $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
    $result_orgs = pg_query($pgconn, $sql_orgs);
    echo "<select name='org' onChange='javascript: this.form.submit();'>\n";
      while ($row = pg_fetch_assoc($result_orgs)) {
        $org_id = $row['id'];
        $organisation = $row['organisation'];
        if ($s_org == $org_id) {
          echo "<option value='$org_id' selected>$organisation</option>\n";
        }
        else {
          echo "<option value='$org_id'>$organisation</option>\n";
        }
      }
    echo "</select>&nbsp;\n";

    if ($b != "all") {
      if ($end > $today) {
        echo "<input type='button' value='Next' class='button' disabled>\n";
      }
      else {
        echo "<input type='button' value='Next' class='button' onClick=window.location='logcheck.php?b=$b&amp;d=$next&amp;org=$s_org';>\n";
      }
    }
    else {
      echo "<input type='button' value='Next' class='button' disabled>\n";
    }
  echo "</form>\n";
}
### User is admin, no organisation set.
elseif ($s_access_search == 9) {
  echo "Select an organisation to check.<br /><br />\n";
  $sql_org = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
  $result_org = pg_query($pgconn, $sql_org);
  echo "<form name='sel_org' action='logcheck.php' method='get'>\n";
    echo "<select name='org' onChange='javascript: this.form.submit();'>\n";
      echo "<option value='$s_org'>Select Organisation</option>\n";
      while ($row = pg_fetch_assoc($result_org)) {
        $organisation = $row['organisation'];
        $org_id = $row['id'];
        echo "<option value='$org_id'>$organisation</option>\n";
      }
    echo "</select>\n";
  echo "</form>\n";
  $err = 1;
}
### User is not admin
else {
  echo "No organisation given in the querystring.<br />\n";
  echo "<a href='index.php'>Back</a>\n";
  $err = 1;
}

if ($err != 1) {
  $sql_ranges = "SELECT ranges FROM organisations WHERE id = $s_org";
  $result_ranges = pg_query($pgconn, $sql_ranges);
  $row = pg_fetch_assoc($result_ranges);

  if ($row['ranges'] == "") {
    echo "No ranges present for this organisation.<br />\n";
    $err = 1;
  }
}

if ($err != 1) {
  ### Showing period.
  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      if ($b == "all") {
        echo "<td class='dataheader' width='600' colspan='3'>All results</td>\n";
      }
      else {
        $datestart = date("d-m-Y", $start);
        $dateend = date("d-m-Y", $end);
        echo "<td class='datatitle' width='600' colspan='3'>Results from $datestart to $dateend</td>\n";
      }
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' width='300'>Range</td>\n";
      echo "<td class='dataheader' width='150'>Malicious Attacks</td>\n";
      echo "<td class='dataheader' width='250'>Unique Source Addresses</td>\n";
    echo "</tr>\n";
}

if ($err != 1) {
  ### Looping through organisation info retrieved by soap connection.
  $ranges_ar = explode(";", $row['ranges']);

  foreach ($ranges_ar as $value) {
    $range = pg_escape_string($value);
    $sql_total = "SELECT COUNT(*) as total FROM attacks$details_table WHERE attacks.source <<= '" .$range. "' AND attacks.severity = 1 $and $tsquery";
    $result_total = pg_query($pgconn, $sql_total);
    $row_total = pg_fetch_assoc($result_total);
    $count_total = $row_total['total'];

    $sql_uniq = "SELECT DISTINCT source FROM attacks WHERE attacks.source <<= '" .$range. "' AND attacks.severity = 1 $and $tsquery";
    $result_uniq = pg_query($pgconn, $sql_uniq);
#    $row_uniq = pg_fetch_assoc($result_uniq);
    $count_uniq = pg_num_rows($result_uniq);

#    echo "SQLTOTAL: $sql_total<br />\n";
#    echo "SQLUNIQ: $sql_uniq<br />\n";
    echo "<tr>\n";
      echo "<td class='datatd'>$range</td>\n";
      if ($count_total > 0) {
#        echo "<td class='datatd'><a href='logsearch.php?f_field=source&amp;f_search=$range&amp;f_sev=1&amp;org=$s_org$dateqs'>$count_total</a></td>\n";
        echo "<td class='datatd'><a href='logsearch.php?f_field=source&amp;f_search=$range&amp;f_sev=1$dateqs'>$count_total</a></td>\n";
        if ($s_access_search == 9) {
          echo "<td class='datatd'><a href='loglist.php?range=$range$dateqs&amp;org=$s_org&b=$b'>$count_uniq</a></td>\n";
        }
        else {
          echo "<td class='datatd'><a href='loglist.php?range=$range$dateqs&amp;org=$s_org'>$count_uniq</a></td>\n";
        }
      }
      else {
        echo "<td class='datatd'>$count_total</td>\n";
        echo "<td class='datatd'>$count_uniq</td>\n";
      }
    echo "</tr>\n";

  }
  echo "</table>\n";
}

?>
<?php footer(); ?>
