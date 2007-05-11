<?php include("menu.php"); set_title("Ranking"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.13                  #
# 11-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Peter Arts                       #
####################################
# Contributors:                    #
# Bjoern Weiland                   #
# David de Coster                  #
####################################

####################################
# Changelog:
# 1.04.13 Added links for top source addresses
# 1.04.12 Added IP exclusions stuff
# 1.04.11 Added percentages to the stats
# 1.04.10 Fixed typo
# 1.04.09 Added geoip and p0f stuff
# 1.04.08 Added protocols ranking; add_to_sql();
# 1.04.07 Replaced $where[] with add_where()
# 1.04.06 Changed some sql stuff
# 1.04.05 Changed some text
# 1.04.04 Fixed a bug when selecting all data and with top filenames for organisation
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Added top 5 files and top 5 source IP's. Courtesy of Bjoern Weiland.
# 1.03.02 Organisation name bugfix
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 Added some more checks and removed includes
# 1.02.05 Removed the intval from date browsing
# 1.02.04 Minor bugfixes and code cleaning
# 1.02.03 Enhanced debugging
# 1.02.02 Added number formatting
# 1.02.01 Small fixes
####################################

### GEOIP STUFF
if ($c_geoip_enable == 1) {
  include '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

$allowed_get = array(
                "int_org",
		"b",
		"i"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_search == 9 && isset($clean['org'])) {
  $q_org = $clean['org'];
} elseif ($s_access_search == 9) {
  $q_org = 0;
} else {
  $q_org = intval($s_org);
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$db_org_name = pg_result($result_getorg, 0);

$debuginfo[] = $sql_getorg;

### Default browse method is weekly.
if (isset($tainted['b'])) {
  $b = $tainted['b'];
  $pattern = '/^(weekly|daily|monthly|all)$/';
  if (!preg_match($pattern, $b)) {
    $b = "weekly";
    $bs = "W";
  }
} else {
  $b = "weekly";
  $bs = "W";
}
$year = date("Y");
if ($b == "monthly") {
  $month = $tainted['i'];
  if ($month == "") { $month = date("n"); }
  $month = intval($month);
  $next = $month + 1;
  $prev = $month - 1;
  $start = getStartMonth($month, $year);
  $end = getEndMonth($month, $year);
  $bs = "M";
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
  $bs = "D";
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
  $bs = "";
} else {
  $dateqs = "&amp;int_from=$start&amp;int_to=$end";
  $tsquery = " timestamp >= $start AND timestamp <= $end";
}

### BROWSE MENU
$today = date("U");
echo "<form name='selectorg' method='get' action='rank.php?org=$q_org'>\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='rank.php?b=$b&amp;i=$prev&amp;int_org=$q_org';>\n";
  } else {
    echo "<input type='button' value='Prev' class='button' disabled>\n";
  }
  echo "<select name='b' onChange='javascript: this.form.submit();'>\n";
    echo printOption("all", "All", $b) . "\n";
    echo printOption("daily", "Daily", $b) . "\n";
    echo printOption("weekly", "Weekly", $b) . "\n";
    echo printOption("monthly", "Monthly", $b) . "\n";
  echo "</select>\n";

  if ($s_access_search == 9) {
    if (!isset($clean['org'])) {
      $err = 1;
    }
    $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
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
      echo "<input type='button' value='Next' class='button' onClick=window.location='rank.php?b=$b&amp;i=$next&amp;int_org=$q_org';>\n";
    }
  } else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";
echo "<br />\n";

### Checking period.
if ($b == "all") {
  $periodtext = "All results";
} else {
  $datestart = date("d-m-Y", $start);
  $dateend = date("d-m-Y", $end);
  $periodtext = "Results from $datestart to $dateend";
}
echo "&nbsp;&nbsp;<b>$periodtext</b>\n";

$sql_active = "SELECT count(id) as total FROM sensors WHERE tap != ''";
$result_active = pg_query($pgconn, $sql_active);
$row = pg_fetch_assoc($result_active);
$total_active = $row['total'];

$sql_sensors = "SELECT count(id) as total FROM sensors";
$result_sensors = pg_query($pgconn, $sql_sensors);
$row = pg_fetch_assoc($result_sensors);
$total_sensors = $row['total'];

$sql_getorg = "SELECT organisations.organisation FROM organisations, sensors WHERE sensors.organisation = organisations.id AND organisations.id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$row = pg_fetch_assoc($result_getorg);
$orgname = $row['organisation'];

$debuginfo[] = $sql_active;
$debuginfo[] = $sql_sensors;
$debuginfo[] = $sql_getorg;

echo "<table width='100%'>\n";
##########################
  add_to_sql("attacks", "table");
  add_to_sql("attacks.severity = 1", "where");
  add_to_sql("$tsquery", "where");
  add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_attacks = "SELECT $sql_select ";
  $sql_attacks .= " FROM $sql_from ";
  $sql_attacks .= " $sql_where ";

  $debuginfo[] = $sql_attacks;

  $result_attacks = pg_query($pgconn, $sql_attacks);
  $row = pg_fetch_assoc($result_attacks);
  $total_attacks = $row['total'];

  # Resetting the sql generation arrays
  $where = array();
  $table = array();
  $select = array();

  add_to_sql("attacks", "table");
  add_to_sql("attacks.severity = 32", "where");
  add_to_sql("$tsquery", "where");
  add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_downloads = "SELECT $sql_select ";
  $sql_downloads .= " FROM $sql_from ";
  $sql_downloads .= " $sql_where ";

  $debuginfo[] = $sql_downloads;

  $result_downloads = pg_query($pgconn, $sql_downloads);
  $row = pg_fetch_assoc($result_downloads);
  $total_downloads = $row['total'];

  # Resetting the sql generation arrays
  $where = array();
  $table = array();
  $select = array();

  if ($total_sensors != 0) {
    $avg_perc = floor(100 / $total_sensors);
  } else {
    $avg_perc = 0;
  }

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%' valign='top'>\n";
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td class='datatd' width='90%'>Total malicious attacks of all sensors</td>\n";
                echo "<td class='datatd' width='10%' align='right'>" . nf($total_attacks) . "&nbsp;</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
                echo "<td class='datatd'>&nbsp;</td>\n";
              echo "</tr>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td class='datatd'>Total downloaded malware of all sensors</td>\n";
                echo "<td class='datatd' align='right'>" . nf($total_downloads) . "&nbsp;</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
                echo "<td class='datatd'>&nbsp;</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              add_to_sql("attacks", "table");
              add_to_sql("sensors", "table");
              add_to_sql("attacks.severity = 1", "where");
              add_to_sql("sensors.organisation = $q_org", "where");
              add_to_sql("sensors.id = attacks.sensorid", "where");
              add_to_sql("$tsquery", "where");
              add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");

              # IP Exclusion stuff
              add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

              prepare_sql();
              $sql_attacks = "SELECT $sql_select ";
              $sql_attacks .= " FROM $sql_from ";
              $sql_attacks .= " $sql_where ";
              $result_attacks = pg_query($pgconn, $sql_attacks);

              $debuginfo[] = $sql_attacks;

              # Resetting the sql generation arrays
              $where = array();
              $table = array();
              $select = array();

              $row = pg_fetch_assoc($result_attacks);
              $org_attacks = $row['total'];
              if ($org_attacks == 0) {
                $org_attacks_perc = '0';
              } else {
                $org_attacks_perc = floor(($org_attacks / $total_attacks) * 100);
              }

              add_to_sql("attacks", "table");
              add_to_sql("sensors", "table");
              add_to_sql("attacks.severity = 32", "where");
              add_to_sql("sensors.organisation = $q_org", "where");
              add_to_sql("sensors.id = attacks.sensorid", "where");
              add_to_sql("$tsquery", "where");
              add_to_sql("DISTINCT COUNT(attacks.severity) as total", "select");

              # IP Exclusion stuff
              add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

              prepare_sql();
              $sql_downloads = "SELECT $sql_select ";
              $sql_downloads .= " FROM $sql_from ";
              $sql_downloads .= " $sql_where ";
              $result_downloads = pg_query($pgconn, $sql_downloads);

              $debuginfo[] = $sql_downloads;

              # Resetting the sql generation arrays
              $where = array();
              $table = array();
              $select = array();

              $row = pg_fetch_assoc($result_downloads);
              $org_downloads = $row['total'];
              if ($org_downloads == 0) {
                $org_downloads_perc = '0';
              } else {
                $org_downloads_perc = floor(($org_downloads / $total_downloads) * 100);
              }

              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td class='datatd' width='90%'>Total malicious attacks for $orgname</td>\n";
                  echo "<td class='datatd' width='10%' align='right'>" . nf($org_attacks) . "&nbsp;</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;% of total malicious attacks</td>\n";
                  echo "<td class='datatd' align='right'>$org_attacks_perc%&nbsp;</td>\n";
                echo "</tr>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td class='datatd'>Total downloaded malware by $orgname</td>\n";
                  echo "<td class='datatd' align='right'>" . nf($org_downloads) . "&nbsp;</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;% of total collected malware</td>\n";
                  echo "<td class='datatd' align='right'>$org_downloads_perc%&nbsp;</td>\n";
                echo "</tr>\n";
              echo "</table><br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
##########################
  add_to_sql("attacks", "table");
  add_to_sql("details", "table");
  add_to_sql("details.type = 1", "where");
  add_to_sql("details.attackid = attacks.id", "where");
  add_to_sql("$tsquery", "where");
  add_to_sql("DISTINCT details.text", "select");
  add_to_sql("COUNT(details.id) as total", "select");
  add_to_sql("details.text", "group");
  add_to_sql("total DESC LIMIT $c_topexploits OFFSET 0", "order");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_topexp = "SELECT $sql_select ";
  $sql_topexp .= " FROM $sql_from ";
  $sql_topexp .= " $sql_where ";
  $sql_topexp .= " GROUP BY $sql_group ORDER BY $sql_order ";
  $result_topexp = pg_query($pgconn, $sql_topexp);

  $debuginfo[] = $sql_topexp;

  add_to_sql("sensors", "table");
  add_to_sql("sensors.organisation = $q_org", "where");
  add_to_sql("sensors.id = attacks.sensorid", "where");
  prepare_sql();
  $sql_topexp_org = "SELECT $sql_select ";
  $sql_topexp_org .= " FROM $sql_from ";
  $sql_topexp_org .= " $sql_where ";
  $sql_topexp_org .= " GROUP BY $sql_group ORDER BY $sql_order ";

  $debuginfo[] = $sql_topexp_org;

  $result_topexp_org = pg_query($pgconn, $sql_topexp_org);

  # Resetting the sql generation arrays
  $where = array();
  $table = array();
  $select = array();
  $group = array();
  $order = array();

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top $c_topexploits exploits of all sensors</b>\n";
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='75%' class='datatd'>Exploit</td>\n";
                echo "<td width='20%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i=1;
              $grandtotal = 0;
              while ($row = pg_fetch_assoc($result_topexp)) {
                $exploit = $row['text'];
                $total = $row['total'];
                $exploit_ar[$exploit] = $total;
                $grandtotal = $grandtotal + $total;
              }
              if ($exploit_ar != "") {
                foreach ($exploit_ar as $key => $val) {
                  $attack = $v_attacks_ar[$key]["Attack"];
                  $attack_url = $v_attacks_ar[$key]["URL"];
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd' align='right'>$i.&nbsp;</td>\n";
                    if ($attack_url != "") {
                      echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
                    } else {
                      echo "<td class='datatd'>$attack</td>\n";
                    }
                    $perc = round($val / $grandtotal * 100);
                    echo "<td class='datatd' align='right'>" . nf($val) . "&nbsp;(${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              echo "<b>Top $c_topexploits exploits of your sensors</b>\n";
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td width='5%' class='datatd'>#</td>\n";
                  echo "<td width='75%' class='datatd'>Exploit</td>\n";
                  echo "<td width='20%' class='datatd'>Total</td>\n";
                echo "</tr>\n";
                $i = 1;
                $grandtotal = 0;
                $exploit_ar = "";
                while ($row = pg_fetch_assoc($result_topexp_org)) {
                  $exploit = $row['text'];
                  $total = $row['total'];
                  $exploit_ar[$exploit] = $total;
                  $grandtotal = $grandtotal + $total;
                }
                if ($exploit_ar != "") {
                  foreach ($exploit_ar as $key => $val) {
                    $attack = $v_attacks_ar[$key]["Attack"];
                    $attack_url = $v_attacks_ar[$key]["URL"];
                    echo "<tr class='datatr'>\n";
                      echo "<td class='datatd' align='right'>$i.&nbsp;</td>\n";
                      if ($attack_url != "") {
                        echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
                      } else {
                        echo "<td class='datatd'>$attack</td>\n";
                      }
                      $perc = round($val / $grandtotal * 100);
                      echo "<td class='datatd' align='right'>" . nf($val) . "&nbsp;(${perc}%)</td>\n";
                    echo "</tr>\n";
                    $i++;
                  }
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
########################## Top 10 sensors
  add_to_sql("DISTINCT sensors.organisation", "select");
  add_to_sql("sensors.keyname", "select");
  add_to_sql("COUNT(details.*) as total", "select");
  add_to_sql("attacks", "table");
  add_to_sql("details", "table");
  add_to_sql("sensors", "table");
  add_to_sql("details.type = 1", "where");
  add_to_sql("sensors.id = attacks.sensorid", "where");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("$tsquery", "where");
  add_to_sql("sensors.keyname", "group");
  add_to_sql("sensors.organisation", "group");
  add_to_sql("total DESC LIMIT $c_topsensors OFFSET 0", "order");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_top = "SELECT $sql_select";
  $sql_top .= " FROM $sql_from ";
  $sql_top .= " $sql_where ";
  $sql_top .= " GROUP BY $sql_group ORDER BY $sql_order";

  $debuginfo[] = $sql_top;
  $result_top = pg_query($pgconn, $sql_top);

  add_to_sql("sensors.organisation = $q_org", "where");
  prepare_sql();
  $sql_top_org = "SELECT $sql_select ";
  $sql_top_org .= " FROM $sql_from ";
  $sql_top_org .= " $sql_where ";
  $sql_top_org .= " GROUP BY $sql_group ORDER BY $sql_order";

  $debuginfo[] = $sql_top_org;

  $result_top_org = pg_query($pgconn, $sql_top_org);
  $numrows_top_org = pg_num_rows($result_top_org);

  # Resetting the sql generation arrays
  reset_sql();

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%' valign='top'>\n";
            echo "<b>Top $c_topsensors sensors</b>\n";
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td class='datatd' width='5%'>#</td>\n";
                echo "<td class='datatd' width='70%'>Sensor</td>\n";
                echo "<td class='datatd' width='25%'>Total exploits</td>\n";
              echo "</tr>\n";
              $i=1;
              $rank_ar = array();
              while ($row = pg_fetch_assoc($result_top)) {
                $db_org = $row['organisation'];

                $sql_getorg = "SELECT organisation FROM organisations WHERE id = $db_org";
                $result_getorg = pg_query($pgconn, $sql_getorg);
                $db_org_name = pg_result($result_getorg, 0);

                $debuginfo[] = $sql_getorg;

                $keyname = $row['keyname'];
                $total = $row['total'];
                $rank_ar[$keyname] = $i;
                if ($i <= $c_topsensors) {
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd' align='right'>$i.&nbsp;</td>\n";
                    if ($s_admin == 1) {
                      echo "<td class='datatd'>$db_org_name - $keyname</td>\n";
                    } elseif ($q_org == $db_org) {
                      echo "<td class='datatd'>$keyname</td>\n";
                    } else {
                      echo "<td class='datatd'>&nbsp;</td>\n";
                    }
                    echo "<td class='datatd' align='right'>" . nf($total) . "&nbsp;</td>\n";
                  echo "</tr>\n";
                }
                $i++;
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              echo "<b>Top $c_topsensors sensors of $orgname</b>\n";
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td class='datatd' width='5%'>#</td>\n";
                  echo "<td class='datatd' width='25%'>Overall Rank</td>\n";
                  echo "<td class='datatd' width='45%'>Sensor</td>\n";
                  echo "<td class='datatd' width='25%'>Total exploits</td>\n";
                echo "</tr>\n";
                $i = 1;
                while ($row_top_org = pg_fetch_assoc($result_top_org)) {
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd' align='right' align='right'>$i.&nbsp;</td>\n";
                    $keyname = $row_top_org['keyname'];
                    $total = $row_top_org['total'];
                    $rank_all = $rank_ar[$keyname];
                    echo "<td class='datatd' align='right'># $rank_all&nbsp;</td>\n";
                    echo "<td class='datatd'>$keyname</td>\n";
                    echo "<td class='datatd' align='right'	>" . nf($total) . "&nbsp;</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";

 ########################## Top 10 ports // Contribution by bjou

  add_to_sql("DISTINCT attacks.dport", "select");
  add_to_sql("COUNT(attacks.dport) as total", "select");
  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  add_to_sql("NOT attacks.dport = 0", "where");
  add_to_sql("attacks.dport", "group");
  add_to_sql("total DESC LIMIT $c_topports OFFSET 0", "order");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_topports = "SELECT $sql_select ";
  $sql_topports .= " FROM $sql_from ";
  $sql_topports .= " $sql_where ";
  $sql_topports .= " GROUP BY $sql_group ORDER BY $sql_order ";
  $result_topports = pg_query($pgconn, $sql_topports);

  $debuginfo[] = $sql_topports;

  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = attacks.sensorid", "where");
  add_to_sql("sensors.organisation = $q_org", "where");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_topports_org = "SELECT $sql_select ";
  $sql_topports_org .= " FROM $sql_from ";
  $sql_topports_org .= " $sql_where ";
  $sql_topports_org .= " GROUP BY $sql_group ORDER BY $sql_order";

  $debuginfo[] = $sql_topports_org;

  $result_topports_org = pg_query($pgconn, $sql_topports_org);

  # Resetting the sql generation arrays
  reset_sql();

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top $c_topports ports of all sensors</b>\n"; // change this into variable to be read from conf
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='15%' class='datatd'>Port</td>\n";
                echo "<td width='60%' class='datatd'>Port Description</td>\n";
                echo "<td width='20%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i=1;
              $grandtotal = 0;
              while ($row = pg_fetch_assoc($result_topports)) {
                $port = $row['dport'];
                $total = $row['total'];
                $grandtotal = $grandtotal + $total;
                $port_ar[$port] = $total;
              }
              if ($port_ar != "") {
                foreach ($port_ar as $key => $val) {
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";
                    if ($s_admin == 1 || $s_access_search == 9) {
                      echo "<td class='datatd'><a href='logsearch.php?dradio=A&int_dport=$key&orderm=DESC$dateqs'>$key</a></td>\n";
                    } else {
                      echo "<td class='datatd'>$key</td>\n";
                    }
                    echo "<td class='datatd'><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$key'>".getPortDescr($key)."</a></td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              echo "<b>Top $c_topports ports of your sensors</b>\n"; // change this into variable to be read from conf
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td width='5%' class='datatd'>#</td>\n";
                  echo "<td width='15%' class='datatd'>Port</td>\n";
                  echo "<td width='60%' class='datatd'>Port Description</td>\n";
                  echo "<td width='20%' class='datatd'>Total</td>\n";
                echo "</tr>\n";
                $i = 1;
                $grandtotal = 0;
                $port_ar = "";
                while ($row = pg_fetch_assoc($result_topports_org)) {
                  $port = $row['dport'];
                  $total = $row['total'];
                  $grandtotal = $grandtotal + $total;
                  $port_ar[$port] = $total;
                }
                if ($port_ar != "") {
                  foreach ($port_ar as $key => $val) {
                    echo "<tr class='datatr'>\n";
                      echo "<td class='datatd'>$i</td>\n";
                      echo "<td class='datatd'><a href='logsearch.php?dradio=A&int_dport=$key&orderm=DESC$dateqs'>$key</a></td>\n";
                      echo "<td class='datatd'><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$key'>".getPortDescr($key)."</a></td>\n";
                      $perc = round($val / $grandtotal * 100);
                      echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                    echo "</tr>\n";
                    $i++;
                  }
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";

 ########################## Top 10 source addresses // Contribution by bjou
  add_to_sql("DISTINCT attacks.source", "select");
  add_to_sql("COUNT(attacks.source) as total", "select");
  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  add_to_sql("attacks.source", "group");
  add_to_sql("total DESC LIMIT $c_topsourceips", "order");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_topsource = "SELECT $sql_select ";
  $sql_topsource .= " FROM $sql_from ";
  $sql_topsource .= " $sql_where ";
  $sql_topsource .= " GROUP BY $sql_group ORDER BY $sql_order ";
  $result_topsource = pg_query($pgconn, $sql_topsource);

  $debuginfo[] = $sql_topsource;

  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = attacks.sensorid", "where");
  add_to_sql("sensors.organisation = $q_org", "where");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_topsource_org = "SELECT $sql_select ";
  $sql_topsource_org .= " FROM $sql_from ";
  $sql_topsource_org .= " $sql_where ";
  $sql_topsource_org .= " GROUP BY $sql_group ORDER BY $sql_order";

  $debuginfo[] = $sql_topsource_org;

  $result_topsource_org = pg_query($pgconn, $sql_topsource_org);

  # Resetting the sql generation arrays
  reset_sql();

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top $c_topsourceips source addresses of all sensors</b>\n";// change this into variable to be read from conf
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='75%' class='datatd'>Address</td>\n";
                echo "<td width='20%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i = 1;
              $grandtotal = 0;
              while ($row = pg_fetch_assoc($result_topsource)) {
                $source = $row['source'];
                $total = $row['total'];
                $grandtotal = $grandtotal + $total;
                $source_ar[$source] = $total;
              }
              if ($source_ar != "") {
                foreach ($source_ar as $key => $val) {
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";

                    echo "<td class='datatd'>";
                      if ($c_enable_pof == 1) {
                        $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$key. "' ORDER BY last_tstamp DESC";
                        $result_finger = pg_query($pgconn, $sql_finger);
                        $numrows_finger = pg_num_rows($result_finger);

                        $fingerprint = pg_result($result_finger, 0);
                        $finger_ar = explode(" ", $fingerprint);
                        $os = $finger_ar[0];
                      } else {
                        $numrows_finger = 0;
                      }
                      if ($numrows_finger != 0) {
                        $osimg = "$c_surfidsdir/webinterface/images/$os.gif";
                        if (file_exists($osimg)) {
                          echo "<img src='images/$os.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
                        } else {
                          echo "<img src='images/Blank.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
                        }
                      } else {
                        echo "<img src='images/Blank.gif' alt='No info' title='No info' />&nbsp;";
                      }
                      if ($c_geoip_enable == 1) {
                        $record = geoip_record_by_addr($gi, $key);
                        $countrycode = strtolower($record->country_code);
                        $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
                        if (file_exists($cimg)) {
                          $country = $record->country_name;
                          echo "<img src='images/worldflags/flag_" .$countrycode. ".gif' onmouseover='return overlib(\"$country\");' onmouseout='return nd();' />&nbsp;";
                        } else {
                          echo "<img src='images/worldflags/flag.gif'  onmouseover='return overlib(\"No Country Info\");' onmouseout='return nd();' style='width: 18px;' />&nbsp;";
                        }
                      }
                      list($ip1, $ip2, $ip3, $ip4) = split("\.", $key);
                      echo "<a href='logsearch.php?sradio=A&sourceip%5B%5D=$ip1&sourceip%5B%5D=$ip2&sourceip%5B%5D=$ip3&sourceip%5B%5D=$ip4&int_sport=&int_smask=&dradio=A&destip%5B%5D=&destip%5B%5D=&destip%5B%5D=&destip%5B%5D=&int_dport=&int_dmask=&tsselect=$bs&strip_html_escape_tsstart=&strip_html_escape_tsend=&reptype=multi&int_sev=-1&int_attack=-1&strip_html_escape_virustxt=&strip_html_escape_filename=&orderm=DESC&strip_html_escape_tsstart=%3A&strip_html_escape_tsend=%3A'>$key</a>";
                      echo "  ";
                      echo "<a href='whois.php?ip_ip=$key'>[whois]</a>";
                    echo "</td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                  echo "</tr>\n";
                  $i++;
                }
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              echo "<b>Top $c_topsourceips source addresses of your sensors</b>\n";  // change this into variable to be read from conf
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td width='5%' class='datatd'>#</td>\n";
                  echo "<td width='75%' class='datatd'>Address</td>\n";
                  echo "<td width='20%' class='datatd'>Total</td>\n";
                echo "</tr>\n";
                $i = 1;
                $grandtotal = 0;
                $source_ar = "";
                while ($row = pg_fetch_assoc($result_topsource_org)) {
                  $source = $row['source'];
                  $total = $row['total'];
                  $grandtotal = $grandtotal + $total;
                  $source_ar[$source] = $total;
                }
                if ($source_ar != "") {
                  foreach ($source_ar as $key => $val) {
                    echo "<tr class='datatr'>\n";
                      echo "<td class='datatd'>$i</td>\n";
                        echo "<td class='datatd'>";
                          if ($c_enable_pof == 1) {
                            $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$key. "' ORDER BY last_tstamp DESC";
                            $result_finger = pg_query($pgconn, $sql_finger);
                            $numrows_finger = pg_num_rows($result_finger);

                            $fingerprint = pg_result($result_finger, 0);
                            $finger_ar = explode(" ", $fingerprint);
                            $os = $finger_ar[0];
                          } else {
                            $numrows_finger = 0;
                          }
                          if ($numrows_finger != 0) {
                            $osimg = "$c_surfidsdir/webinterface/images/$os.gif";
                            if (file_exists($osimg)) {
                              echo "<img src='images/$os.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
                            } else {
                              echo "<img src='images/Blank.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
                            }
                          } else {
                            echo "<img src='images/Blank.gif' alt='No info' title='No info' />&nbsp;";
                          }
                          if ($c_geoip_enable == 1) {
                            $record = geoip_record_by_addr($gi, $key);
                            $countrycode = strtolower($record->country_code);
                            $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
                            if (file_exists($cimg)) {
                              $country = $record->country_name;
                              echo "<img src='images/worldflags/flag_" .$countrycode. ".gif' onmouseover='return overlib(\"$country\");' onmouseout='return nd();' />&nbsp;";
                            } else {
                              echo "<img src='images/worldflags/flag.gif'  onmouseover='return overlib(\"No Country Info\");' onmouseout='return nd();' style='width: 18px;' />&nbsp;";
                            }
                          }
                          echo "<a href='whois.php?ip_ip=$key'>$key</a>";
                        echo "</td>\n";
                        $perc = round($val / $grandtotal * 100);
                        echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                    echo "</tr>\n";
                    $i++;
                  }
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";

 ########################## Top 10 Filenames // Contribution by bjou

  $sql_topfiles = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
    $sql_topfiles .= "(SELECT split_part(details.text, '/', 4) as file ";
    $sql_topfiles .= "FROM details, attacks WHERE NOT split_part(details.text, '/', 4) = '' ";
    if ($tsquery != "") {
      $sql_topfiles .= " AND $tsquery ";
    }
    $sql_topfiles .= "AND type = 4  AND details.attackid = attacks.id AND ";
    $sql_topfiles .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)) as sub ";
  $sql_topfiles .= "GROUP BY sub.file ORDER BY total DESC LIMIT $c_topfilenames";
  $result_topfiles = pg_query($pgconn, $sql_topfiles);

  $debuginfo[] = $sql_topfiles;

  $sql_topfiles_org = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
    $sql_topfiles_org .= "(SELECT split_part(details.text, '/', 4) as file ";
    $sql_topfiles_org .= "FROM details, attacks, sensors WHERE NOT split_part(details.text, '/', 4) = '' ";
    if ($tsquery != "") {
      $sql_topfiles_org .= " AND $tsquery ";
    }
    $sql_topfiles_org .= "AND sensors.id = details.sensorid AND sensors.organisation = $q_org ";
    $sql_topfiles_org .= "AND type = 4  AND details.attackid = attacks.id AND ";
    $sql_topfiles_org .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)) as sub ";
  $sql_topfiles_org .= "GROUP BY sub.file ORDER BY total DESC LIMIT $c_topfilenames";
  $result_topfiles_org = pg_query($pgconn, $sql_topfiles_org);

  $debuginfo[] = $sql_topfiles_org;
        
  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top $c_topfilenames filenames of all sensors</b>\n"; // change this into variable to be read from conf
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='75%' class='datatd'>Filename</td>\n";
                echo "<td width='20%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i = 0;
              $grandtotal = 0;
              while ($row = pg_fetch_assoc($result_topfiles)) {
                if ($i == $c_topfilenames) {
                  break;
                }
                $url = $row['file'];
                $total = $row['total'];
                $array = @parse_url($url);
                $filename = trim($array['path'],'/');
                $grandtotal = $grandtotal + $total;
                $file_ar[$filename] = $total;
              }
              if ($file_ar != "") {
                foreach ($file_ar as $key => $val) {
                  $i++;
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";
                    if ($s_admin == 1 || $s_access_search == 9) {
                      echo "<td class='datatd'><a href='logsearch.php?dradio=A&strip_html_escape_filename=$key&orderm=DESC$dateqs'>$key</a></td>\n";
                    } else {
                      echo "<td class='datatd'>$key</td>\n";
                    }
                    $perc = round($val / $grandtotal * 100);
                    echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              echo "<b>Top $c_topfilenames filenames of your sensors</b>\n";// change this into variable to be read from conf
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                   echo "<td width='5%' class='datatd'>#</td>\n";
                   echo "<td width='75%' class='datatd'>Filename</td>\n";
                   echo "<td width='20%' class='datatd'>Total</td>\n";
                echo "</tr>\n";

                $filenameArray = array();
                $i = 0;
                $grandtotal = 0;
                $file_ar = "";
                while ($row = pg_fetch_assoc($result_topfiles_org)) {
                  if ($i == $c_topfilenames) {
                    break;
                  }
                  $url = $row['file'];
                  $total = $row['total'];
                  $array = @parse_url($url);
                  $filename = trim($array['path'],'/');
                  $grandtotal = $grandtotal + $total;
                  $file_ar[$filename] = $total;
                }
                if ($file_ar != "") {
                  foreach ($file_ar as $key => $val) {
                    $i ++;
                    echo "<tr class='datatr'>\n";
                      echo "<td class='datatd'>$i</td>\n";
                      echo "<td class='datatd'><a href='logsearch.php?dradio=A&strip_html_escape_filename=$key&orderm=DESC$dateqs'>$key</a></td>\n";
                      $perc = round($val / $grandtotal * 100);
                      echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                    echo "</tr>\n";
                  }
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";

 ########################## Top 10 Protocols

  $sql_topproto = "SELECT DISTINCT sub.proto, COUNT(sub.proto) as total FROM ";
    $sql_topproto .= "(SELECT split_part(details.text, '/', 1) as proto ";
    $sql_topproto .= "FROM details, attacks WHERE 1 = 1 ";
    if ($tsquery != "") {
      $sql_topproto .= " AND $tsquery ";
    }
    $sql_topproto .= "AND type = 4  AND details.attackid = attacks.id AND ";
    $sql_topproto .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)) as sub ";
  $sql_topproto .= "GROUP BY sub.proto ORDER BY total DESC LIMIT $c_topprotocols";
  $result_topproto = pg_query($pgconn, $sql_topproto);

  $debuginfo[] = $sql_topproto;

  $sql_topproto_org = "SELECT DISTINCT sub.proto, COUNT(sub.proto) as total FROM ";
    $sql_topproto_org .= "(SELECT split_part(details.text, '/', 1) as proto ";
    $sql_topproto_org .= "FROM details, attacks, sensors WHERE 1 = 1 ";
    if ($tsquery != "") {
      $sql_topproto_org .= " AND $tsquery ";
    }
    $sql_topproto_org .= "AND sensors.id = details.sensorid AND sensors.organisation = $q_org ";
    $sql_topproto_org .= "AND type = 4  AND details.attackid = attacks.id AND ";
    $sql_topproto_org .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)) as sub ";
  $sql_topproto_org .= "GROUP BY sub.proto ORDER BY total DESC LIMIT $c_topprotocols";
  $result_topproto_org = pg_query($pgconn, $sql_topproto_org);

  $debuginfo[] = $sql_topproto_org;
        
  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top $c_topprotocols download protocols of all sensors</b>\n";
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='75%' class='datatd'>Protocol</td>\n";
                echo "<td width='20%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i = 0;
              $grandtotal = 0;
              while ($row = pg_fetch_assoc($result_topproto)) {
                if ($i == $c_topprotocols) {
                  break;
                }
                $tempproto = $row['proto'];
                $total = $row['total'];
                $proto = str_replace(":", "", $tempproto);
                $grandtotal = $grandtotal + $total;
                $proto_ar[$proto] = $total;
              }
              if ($proto_ar != "") {
                foreach ($proto_ar as $key => $val) {
                  $i++;
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";
                    echo "<td class='datatd'>$key</td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              echo "<b>Top $c_topprotocols download protocols of your sensors</b>\n";
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                   echo "<td width='5%' class='datatd'>#</td>\n";
                   echo "<td width='75%' class='datatd'>Protocol</td>\n";
                   echo "<td width='20%' class='datatd'>Total</td>\n";
                echo "</tr>\n";

                $i = 0;
                $grandtotal = 0;
                $proto_ar = "";
                while ($row = pg_fetch_assoc($result_topproto_org)) {
                  if ($i == $c_topprotocols) {
                    break;
                  }
                  $tempproto = $row['proto'];
                  $total = $row['total'];
                  $proto = str_replace(":", "", $tempproto);
                  $grandtotal = $grandtotal + $total;
                  $proto_ar[$proto] = $total;
                }
                if ($proto_ar != "") {
                  foreach ($proto_ar as $key => $val) {
                    $i ++;
                    echo "<tr class='datatr'>\n";
                      echo "<td class='datatd'>$i</td>\n";
                      echo "<td class='datatd'>$key</td>\n";
                      $perc = round($val / $grandtotal * 100);
                      echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                    echo "</tr>\n";
                  }
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";

 ########################## Top 10 attacker OS's

  $sql_topos = "SELECT DISTINCT sub.os, COUNT(sub.os) as total FROM ";
    $sql_topos .= "(SELECT split_part(system.name, ' ', 1) as os ";
    $sql_topos .= "FROM system, attacks WHERE 1 = 1 ";
    if ($tsquery != "") {
      $sql_topos .= " AND $tsquery ";
    }
    $sql_topos .= " AND attacks.source = system.ip_addr AND ";
    $sql_topos .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)) as sub ";
  $sql_topos .= "GROUP BY sub.os ORDER BY total DESC LIMIT $c_topos";
  $result_topos = pg_query($pgconn, $sql_topos);

  $debuginfo[] = $sql_topos;

  $sql_topos_org = "SELECT DISTINCT sub.os, COUNT(sub.os) as total FROM ";
    $sql_topos_org .= "(SELECT split_part(system.name, ' ', 1) as os ";
    $sql_topos_org .= "FROM system, attacks, sensors WHERE 1 = 1 ";
    if ($tsquery != "") {
      $sql_topos_org .= " AND $tsquery ";
    }
    $sql_topos_org .= "AND sensors.id = attacks.sensorid AND sensors.organisation = $q_org ";
    $sql_topos_org .= "AND attacks.source = system.ip_addr AND ";
    $sql_topos_org .= "NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)) as sub ";
  $sql_topos_org .= "GROUP BY sub.os ORDER BY total DESC LIMIT $c_topos";
  $result_topos_org = pg_query($pgconn, $sql_topos_org);

  $debuginfo[] = $sql_topos_org;
        
  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top $c_topos attacker OS's of all sensors</b>\n";
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='75%' class='datatd'>OS</td>\n";
                echo "<td width='20%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i = 0;
              $grandtotal = 0;
              while ($row = pg_fetch_assoc($result_topos)) {
                if ($i == $c_topos) {
                  break;
                }
                $os = $row['os'];
                $total = $row['total'];
                $grandtotal = $grandtotal + $total;
                $os_ar[$os] = $total;
              }
              if ($os_ar != "") {
                foreach ($os_ar as $key => $val) {
                  $i++;
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";
                    echo "<td class='datatd'>$key</td>\n";
                    $perc = round($val / $grandtotal * 100);
                    echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                  echo "</tr>\n";
                }
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($clean['org']) && $clean['org'] != 0) ) {
              echo "<b>Top $c_topos attacker OS's of your sensors</b>\n";
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                   echo "<td width='5%' class='datatd'>#</td>\n";
                   echo "<td width='75%' class='datatd'>OS</td>\n";
                   echo "<td width='20%' class='datatd'>Total</td>\n";
                echo "</tr>\n";

                $i = 0;
                $grandtotal = 0;
                $os_ar = "";
                while ($row = pg_fetch_assoc($result_topos_org)) {
                  if ($i == $c_topos) {
                    break;
                  }
                  $os = $row['os'];
                  $total = $row['total'];
                  $grandtotal = $grandtotal + $total;
                  $os_ar[$os] = $total;
                }
                if ($os_ar != "") {
                  foreach ($os_ar as $key => $val) {
                    $i ++;
                    echo "<tr class='datatr'>\n";
                      echo "<td class='datatd'>$i</td>\n";
                      echo "<td class='datatd'>$key</td>\n";
                      $perc = round($val / $grandtotal * 100);
                      echo "<td class='datatd'>$val&nbsp;(${perc}%)</td>\n";
                    echo "</tr>\n";
                  }
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";

// END of modification by bjou

########################## Top 5 Organisations
  add_to_sql("organisations.organisation", "select");
  add_to_sql("COUNT(attacks.id) as total", "select");
  add_to_sql("attacks", "table");
  add_to_sql("sensors", "table");
  add_to_sql("organisations", "table");
  add_to_sql("attacks.severity = 1", "where");
  add_to_sql("attacks.sensorid = sensors.id", "where");
  add_to_sql("sensors.organisation = organisations.id", "where");
  add_to_sql("$tsquery", "where");
  add_to_sql("organisations.organisation", "group");
  add_to_sql("total DESC LIMIT $c_toporgs OFFSET 0", "order");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();
  $sql_organisation = "SELECT $sql_select ";
  $sql_organisation .= " FROM $sql_from ";
  $sql_organisation .= " $sql_where ";
  $sql_organisation .= " GROUP BY $sql_group ORDER BY $sql_order";

  $debuginfo[] = $sql_organisation;

  $result_organisation = pg_query($pgconn, $sql_organisation);

  echo "<tr>\n";
    echo "<td>\n";
      echo "<b>Top $c_toporgs organisations</b>\n";
      echo "<table class='datatable' width='45%'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader' width='5%'>#</td>\n";
          echo "<td class='dataheader' width='70%'>Organisation</td>\n";
          echo "<td class='dataheader' width='25%'>Total exploits</td>\n";
        echo "</tr>\n";
        $i = 0;
        while ($row = pg_fetch_assoc($result_organisation)) {
          $i++;
          $db_org_name = $row['organisation'];

          $count = $row['total'];
          echo "<tr>\n";
            echo "<td class='datatd'>$i</td>\n";
            if ($s_admin == 1) {
              echo "<td class='datatd'>$db_org_name</td>\n";
            } elseif ($q_org == $db_org) {
              echo "<td class='datatd'>$db_org_name</td>\n";
            } else {
              echo "<td class='datatd'>&nbsp;</td>\n";
            }            
            echo "<td class='datatd' align='right'>" . nf($count) . "&nbsp;</td>\n";
          echo "</tr>\n";
        }
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
echo "</table>\n";
debug_sql();
?>
<?php footer(); ?>
