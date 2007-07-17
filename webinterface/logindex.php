<?php include("menu.php"); set_title("Log Overview");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.12                  #
# 03-07-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 1.04.12 Added yearly option 
# 1.04.11 Added IP exclusion stuff
# 1.04.10 Changed printhelp stuff
# 1.04.09 Fixed severity stuff
# 1.04.08 Fixed typo
# 1.04.07 add_to_sql()
# 1.04.06 Replaced $where[] with add_where()
# 1.04.05 Changed some sql stuff
# 1.04.04 Added ORDER BY for organisation select box
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Code layout
# 1.03.01 Released as part of the 1.03 package
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

# Unsetting the total search result count if it is set
if (isset($_SESSION['s_total_search_records'])) {
  unset($_SESSION['s_total_search_records']);
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_org",
                "b",
                "i",
		"int_to",
		"int_from"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking access
if ($s_access_search == 9 && isset($clean['org'])) {
  $q_org = $clean['org'];
} elseif ($s_access_search == 9) {
  $q_org = 0;
} else {
  $q_org = intval($s_org);
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);

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
  $searchqs = "";
  $tsquery = "";
} else {
  $searchqs = "&amp;int_from=$start&amp;int_to=$end";
  $tsquery = "timestamp >= $start AND timestamp <= $end";
}

### BROWSE MENU
$today = date("U");
echo "<form name='selectorg' method='get' action='logindex.php?int_org=$q_org'>\n";
  echo "<input type='hidden' name='int_org' value='$q_org' />\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='logindex.php?b=$b&amp;i=$prev&amp;int_org=$q_org';>\n";
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
    $sql_orgs = "SELECT id, organisation FROM organisations WHERE NOT organisation = 'ADMIN' ORDER BY organisation";
    $debuginfo[] = $sql_orgs;
    $result_orgs = pg_query($pgconn, $sql_orgs);
    echo "<select name='int_org' onChange='javascript: this.form.submit();'>\n";
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
    } else {
      echo "<input type='button' value='Next' class='button' onClick=window.location='logindex.php?b=$b&amp;i=$next&amp;int_org=$q_org';>\n";
    }
  } else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";
echo "<br />\n";

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

  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  if ($s_access_search != 9 || ($s_access_search == 9 && $q_org != 0)) {
    add_to_sql("sensors", "table");
    add_to_sql("attacks.sensorid = sensors.id", "where");
    add_to_sql("sensors.organisation = $q_org", "where");
  }
  add_to_sql("DISTINCT attacks.severity", "select");
  add_to_sql("COUNT(attacks.severity) as total", "select");
  add_to_sql("attacks.severity", "group");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();

  $sql_severity = "SELECT $sql_select ";
  $sql_severity .= " FROM $sql_from ";
  $sql_severity .= " $sql_where ";
  $sql_severity .= " GROUP BY $sql_group ";
  $debuginfo[] = $sql_severity;
  $result_severity = pg_query($pgconn, $sql_severity);

  while($row = pg_fetch_assoc($result_severity)) {
    $severity = $row['severity'];
    $count = $row['total'];
    $description = $v_severity_ar[$severity];
    echo "<tr>\n";
      echo "<td class='datatd'>$description " .printhelp($severity). "</td>\n";
      if ($severity == 0 || $severity == 16) {
        echo "<td class='datatd' align='right'><a href='logsearch.php?int_sev=$severity&amp;int_org=$q_org$searchqs'>" . nf($count) . "</a>&nbsp;</td>\n";
      } elseif ($severity == 1 || $severity == 32) {
        echo "<td class='datatd' align='right'><a href='logattacks.php?int_sev=$severity&amp;int_org=$q_org$searchqs'>" . nf($count) . "</a>&nbsp;</td>\n";
      } else {
        echo "<td class='datatd' align='right'><a href='logsearch.php?int_sev=$severity&amp;int_org=$q_org$searchqs'>" . nf($count) . "</a>&nbsp;</td>\n";
      }
    echo "</tr>\n";
  }
echo "</table>\n";
echo "<br /><br />\n";

reset_sql();

# SELECT
add_to_sql("DISTINCT arp_alert.type", "select");
add_to_sql("sensors.id", "select");
add_to_sql("keyname", "select");
add_to_sql("organisation", "select");
add_to_sql("COUNT(arp_alert.id) as total", "select");
# FROM
add_to_sql("arp_alert", "table");
add_to_sql("sensors", "table");
# WHERE
add_to_sql("$tsquery", "where");
add_to_sql("arp_alert.sensorid = sensors.id", "where");
if ($s_access_search != 9 || ($s_access_search == 9 && $q_org != 0)) {
  add_to_sql("sensors.organisation = $q_org", "where");
}
# GROUP BY
add_to_sql("type", "group");
add_to_sql("sensors.id", "group");
add_to_sql("keyname", "group");
add_to_sql("organisation", "group");

prepare_sql();

$sql_arp = "SELECT $sql_select ";
$sql_arp .= " FROM $sql_from ";
$sql_arp .= " $sql_where ";
$sql_arp .= " GROUP BY $sql_group ";

$debuginfo[] = $sql_arp;

$result_arp = pg_query($pgconn, $sql_arp);

echo "<table class='datatable'>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' colspan='3' width='600'>ARP Logs</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' width='400'>Type</td>\n";
    echo "<td class='dataheader' width='100'>Sensor</td>\n";
    echo "<td class='dataheader' width='100'>Statistics</td>\n";
  echo "</tr>\n";

  while($row = pg_fetch_assoc($result_arp)) {
    $id = $row['id'];
    $type = $row['type'];
    $type = $v_arp_alerts[$type];
    $keyname = $row['keyname'];
    $count = $row['total'];
    $orgid = $row['organisation'];
    echo "<tr>\n";
      echo "<td class='datatd'>$type</td>\n";
      echo "<td class='datatd'>$keyname</td>\n";
      echo "<td class='datatd' align='right'><a href='arplog.php?int_org=$orgid&amp;int_filter=$id$searchqs'>" . nf($count) . "</a></td>\n";
    echo "</tr>\n";
  }
echo "</table>\n";

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
