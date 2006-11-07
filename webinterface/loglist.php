<?php include("menu.php"); set_title("Check");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.01 Code layout
# 1.02.05 Added intval() to session variables + modified daily table header
# 1.02.04 Added some more input checks and removed includes
# 1.02.03 Enhanced debugging
# 1.02.02 Added debug option
# 1.02.01 Added number formatting
#############################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

### Checking for organisation.
if (isset($_GET['org']) && $s_access_search == 9) {
  $q_org = intval($_GET['org']);
} elseif ($s_access_search == 9) {
  echo "No organisation given in the querystring.<br />\n";
  $err = 1;
} else {
  $q_org = intval($s_org);
}

### Checking for period.
if (isset($_GET['to']) && isset($_GET['from'])) {
  $start = intval($_GET['from']);
  $end = intval($_GET['to']);
  $tsquery = "attacks.timestamp >= $start AND attacks.timestamp <= $end";
  $dateqs = "&amp;from=$start&amp;to=$end";
} else {
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
} else {
  $b = "weekly";
}

### Checking sort method.
if (isset($_GET['sort'])) {
  $sort = pg_escape_string(stripinput($_GET['sort']));
  if ($sort == "ip") {
    $orderby = "ORDER BY source ASC";
  } elseif ($sort == "count") {
    $orderby = "ORDER BY total DESC";
  } else {
    $orderby = "ORDER BY total DESC";
  }
} else {
  $orderby = "ORDER BY total DESC";
}

### Checking IP range to search.
if (isset($_GET['range'])) {
  $range = pg_escape_string(stripinput($_GET['range']));
  echo "Checking unique source addresses for $range.<br /><br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      if ($b == "all") {
        echo "<td class='dataheader' width='600' colspan='2'>All results</td>\n";
      } elseif ($b == "daily") {
        $datestart = date("d-m-Y", $start);
        echo "<td class='datatitle' width='600' colspan='2'>Results from $datestart</td>\n";
      } else {
        $datestart = date("d-m-Y", $start);
        $dateend = date("d-m-Y", $end);
        echo "<td class='datatitle' width='600' colspan='2'>Results from $datestart to $dateend</td>\n";
      }
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader'><a href='loglist.php?range=$range&amp;org=$q_org&amp;b=$b&amp;sort=ip$dateqs'>Source IP Address</a></td>\n";
      echo "<td class='dataheader'><a href='loglist.php?range=$range&amp;org=$q_org&amp;b=$b&amp;sort=count$dateqs'>Attacks</a></td>\n";
    echo "</tr>\n";
} else {
  echo "No range was given to search.<br />\n";
  echo "<a href='logcheck.php'>Back</a><br />\n";
  $err = 1;
}

### Checking for errors.
if ($err != 1) {
  ### retrieving organisation ranges.

  add_db_table("attacks");
  $where[] = "$tsquery";
  $where[] = "sensors.organisation = $q_org";
  $where[] = "attacks.source <<= '$range'";
  $where[] = "attacks.severity = 1";
  prepare_sql();

  $sql_uniq = "SELECT DISTINCT attacks.source, COUNT(attacks.source) as total ";
  $sql_uniq .= " FROM $sql_from ";
  $sql_uniq .= " $sql_where ";
  $sql_uniq .= " GROUP BY attacks.source ";
  $sql_uniq .= " $orderby ";
  $result_uniq = pg_query($pgconn, $sql_uniq);

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_UNIQ: $sql_uniq<br />\n";
    echo "</pre>\n";
  }

  while ($row = pg_fetch_assoc($result_uniq)) {
    $source = $row['source'];
    $count = $row['total'];
    echo "<tr>\n";
      echo "<td class='datatd'>$source</td>\n";
      echo "<td class='datatd' align='right'><a href='logsearch.php?f_field=source&amp;f_search=$source&amp;f_sev=1$dateqs'>" . nf($count) . "</a>&nbsp;</td>\n";
    echo "</tr>\n";
  }
}
echo "</table>\n";
pg_close($pgconn);
?>
<?php footer(); ?>
