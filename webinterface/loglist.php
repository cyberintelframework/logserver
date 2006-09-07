<?php include("menu.php"); set_title("Check");?>
<?php

######################################
# SURFnet IDS                        #
# Version 1.02.03                    #
# 08-08-2006                         #
# Jan van Lith & Kees Trippelvitz    #
######################################

#############################################
# Changelog:
# 1.02.03 Changed the way access is handled and added intval() to $s_org and $s_admin
# 1.02.02 Added some input checks on the $b and $org variables
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = $s_access{1};

### Checking for organisation.
if (isset($_GET['org']) && $s_access_search == 9) {
  $s_org = intval($_GET['org']);
}
elseif ($s_access_search == 9) {
  echo "No organisation given in the querystring.<br />\n";
  $err = 1;
}

### Checking for period.
if (isset($_GET['to']) && isset($_GET['from'])) {
  $start = intval($_GET['from']);
  $end = intval($_GET['to']);
  $and = "AND";
  $tsquery = "attacks.timestamp >= $start AND attacks.timestamp <= $end";
  $dateqs = "&amp;from=$start&amp;to=$end";
}
else {
  $and = "";
  $tsquery = "";
  $dateqs = "";
}

### Checking for browse method in querystring.
if (isset($_GET['b'])) {
  $b = pg_escape_string($_GET['b']);
  $pattern = '/^(weekly|daily|monthly|all)$/';
  if (preg_match($pattern, $b) != 1) {
    $b = "weekly";
  }
}
#else {
#  echo "Browse variable not set in the querystring. <br />\n";
#  $err = 1;
#}

### Checking sort method.
if (isset($_GET['sort'])) {
  $sort = pg_escape_string(stripinput($_GET['sort']));
  if ($sort == "ip") {
    $orderby = "ORDER BY source ASC";
  }
  elseif ($sort == "count") {
    $orderby = "ORDER BY total DESC";
  }
  else {
    $orderby = "ORDER BY total DESC";
  }
}
else {
  $orderby = "ORDER BY total DESC";
}

### Checking IP range to search.
if (isset($_GET['range'])) {
  $range = pg_escape_string($_GET['range']);
  $range = stripinput($range);
  echo "Checking unique source addresses for $range.<br /><br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      if ($b == "all") {
        echo "<td class='dataheader' width='600' colspan='2'>All results</td>\n";
      }
      else {
        $datestart = date("d-m-Y", $start);
        $dateend = date("d-m-Y", $end);
        echo "<td class='datatitle' width='600' colspan='2'>Results from $datestart to $dateend</td>\n";
      }
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader'><a href='loglist.php?range=$range&amp;org=$s_org&amp;b=$b&amp;sort=ip$dateqs'>Source IP Address</a></td>\n";
      echo "<td class='dataheader'><a href='loglist.php?range=$range&amp;org=$s_org&amp;b=$b&amp;sort=count$dateqs'>Attacks</a></td>\n";
    echo "</tr>\n";
}
else {
  echo "No range was given to search.<br />\n";
  echo "<a href='logcheck.php'>Back</a><br />\n";
  $err = 1;
}

### Checking for errors.
if ($err != 1) {
  ### retrieving organisation ranges.

  $sql_uniq = "SELECT DISTINCT attacks.source, COUNT(attacks.source) as total FROM attacks, sensors WHERE attacks.sensorid = sensors.id AND sensors.organisation = $s_org AND attacks.source <<= '" .$range. "' AND attacks.severity = 1 $and $tsquery GROUP BY attacks.source $orderby";
  $result_uniq = pg_query($pgconn, $sql_uniq);
#  echo "SQLUNIQ: $sql_uniq<br />\n";
  while ($row = pg_fetch_assoc($result_uniq)) {
    $source = $row['source'];
    $count = $row['total'];
    echo "<tr>\n";
      echo "<td class='datatd'>$source</td>\n";
      echo "<td class='datatd'><a href='logsearch.php?f_field=source&amp;f_search=$source&amp;f_sev=1$dateqs'>$count</a></td>\n";
    echo "</tr>\n";
  }
}
echo "</table>\n";
pg_close($pgconn);
?>
<?php footer(); ?>
