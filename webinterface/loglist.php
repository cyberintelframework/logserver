<?php include("menu.php"); set_title("Check");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.06                  #
# 09-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.06 Added IP exclusion stuff
# 1.04.05 add_to_sql()
# 1.04.04 Replaced $where[] with add_where()
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Code layout
# 1.03.01 Released as part of the 1.03 package
# 1.02.05 Added intval() to session variables + modified daily table header
# 1.02.04 Added some more input checks and removed includes
# 1.02.03 Enhanced debugging
# 1.02.02 Added debug option
# 1.02.01 Added number formatting
#############################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

$allowed_get = array(
                "int_org",
                "b",
		"int_to",
		"int_from",
		"net_range",
		"sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

### Checking for organisation.
if (isset($clean['org']) && $s_access_search == 9) {
  $q_org = $clean['org'];
} elseif ($s_access_search == 9) {
  $m = 91;
  $m = geterror($m);
  echo $m;
  $err = 1;
} else {
  $q_org = intval($s_org);
}

### Checking for period.
if (isset($clean['to']) && isset($clean['from'])) {
  $start = $clean['from'];
  $end = $clean['to'];
  $tsquery = "attacks.timestamp >= $start AND attacks.timestamp <= $end";
  $dateqs = "&amp;int_from=$start&amp;int_to=$end";
} else {
  $tsquery = "";
  $dateqs = "";
}

### Checking for browse method in querystring.
if (isset($tainted['b'])) {
  $b = pg_escape_string($tainted['b']);
  $pattern = '/^(weekly|daily|monthly|all)$/';
  if (!preg_match($pattern, $b)) {
    $b = "weekly";
  }
} else {
  $b = "weekly";
}

### Checking sort method.
if (isset($tainted['sort'])) {
  $sort = $tainted['sort'];
  $pattern = '/^(ip|count)$/';
  if (!preg_match($pattern, $sort)) {
    $sort = "count";
  }
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
if (isset($clean['range'])) {
  $range = $clean['range'];
  echo "Checking unique source addresses for $range.<br /><br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      if ($b == "all") {
        echo "<td class='dataheader' width='400' colspan='2'>All results</td>\n";
      } elseif ($b == "daily") {
        $datestart = date("d-m-Y", $start);
        echo "<td class='datatitle' width='400' colspan='2'>Results from $datestart</td>\n";
      } else {
        $datestart = date("d-m-Y", $start);
        $dateend = date("d-m-Y", $end);
        echo "<td class='datatitle' width='400' colspan='2'>Results from $datestart to $dateend</td>\n";
      }
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' ><a href='loglist.php?net_range=$range&amp;int_org=$q_org&amp;b=$b&amp;sort=ip$dateqs'>Source IP Address</a></td>\n";
      echo "<td class='dataheader' width='115'><a href='loglist.php?net_range=$range&amp;int_org=$q_org&amp;b=$b&amp;sort=count$dateqs'>Malicious Attacks</a></td>\n";
    echo "</tr>\n";
} else {
  echo "No range was given to search.<br />\n";
  echo "<a href='logcheck.php'>Back</a><br />\n";
  $err = 1;
}

### Checking for errors.
if ($err != 1) {
  ### retrieving organisation ranges.

  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  add_to_sql("attacks.source <<= '$range'", "where");
  add_to_sql("attacks.severity = 1", "where");
  add_to_sql("DISTINCT attacks.source", "select");
  add_to_sql("COUNT(attacks.source) as total", "select");
  add_to_sql("attacks.source", "group");
  add_to_sql("$orderby", "order");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();

  $sql_uniq = "SELECT $sql_select ";
  $sql_uniq .= " FROM $sql_from ";
  $sql_uniq .= " $sql_where ";
  if ($sql_group) {
    $sql_uniq .= " GROUP BY $sql_group ";
  }
  if ($sql_order) {
    $sql_uniq .= " $sql_order ";
  }
  $result_uniq = pg_query($pgconn, $sql_uniq);

  $debuginfo[] = $sql_uniq;

  while ($row = pg_fetch_assoc($result_uniq)) {
    $source = $row['source'];
    $count = $row['total'];
    echo "<tr>\n";
      echo "<td class='datatd'>$source</td>\n";
      echo "<td class='datatd' align='left'><a href='logsearch.php?ip_searchip=$source&amp;int_sev=1$dateqs'>" . nf($count) . "</a>&nbsp;</td>\n";
    echo "</tr>\n";
  }
}
echo "</table>\n";
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
