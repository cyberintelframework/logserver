<?php include("menu.php"); set_title("Ranking"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 16-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
# Contribution by Bjoern Weiland   #
####################################

####################################
# Changelog:
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

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
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
$db_org_name = pg_result($result_getorg, 0);

### Default browse method is weekly.
if (isset($_GET['b'])) {
  $b = pg_escape_string($_GET['b']);
  $pattern = '/^(weekly|daily|monthly|all)$/';
  if (!preg_match($pattern, $b)) {
    $b = "weekly";
  }
} else {
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
  $day = intval($day);
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
  $dateqs = "&amp;from=$start&amp;to=$end";
  $tsquery = "timestamp >= $start AND timestamp <= $end";
}

echo "Checking organisation ranges for attacks sourced by these ranges.<br /><br />\n";
### BROWSE MENU
$today = date("U");
echo "<form name='selectorg' method='get' action='rank.php?org=$q_org'>\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='rank.php?b=$b&amp;i=$prev&amp;org=$q_org';>\n";
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
    } else {
      echo "<input type='button' value='Next' class='button' onClick=window.location='rank.php?b=$b&amp;i=$next&amp;org=$q_org';>\n";
    }
  } else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";

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

echo "QORG: $q_org<br />\n";

$sql_getorg = "SELECT organisations.organisation FROM organisations, sensors WHERE sensors.organisation = organisations.id AND organisations.id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$row = pg_fetch_assoc($result_getorg);
$orgname = $row['organisation'];

echo "<table width='100%'>\n";
##########################
  add_db_table("attacks");
  add_db_table("sensors");
  $where[] = "attacks.severity = 1";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_attacks = "SELECT DISTINCT COUNT(attacks.severity) as total ";
  $sql_attacks .= " FROM $sql_from ";
  $sql_attacks .= " $sql_where ";

  $result_attacks = pg_query($pgconn, $sql_attacks);
  $row = pg_fetch_assoc($result_attacks);
  $total_attacks = $row['total'];

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  add_db_table("attacks");
  add_db_table("sensors");
  $where[] = "attacks.severity = 32";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_downloads = "SELECT DISTINCT COUNT(attacks.severity) as total ";
  $sql_downloads .= " FROM $sql_from ";
  $sql_downloads .= " $sql_where ";

  $result_downloads = pg_query($pgconn, $sql_downloads);
  $row = pg_fetch_assoc($result_downloads);
  $total_downloads = $row['total'];

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_ATTACKS: $sql_attacks\n";
    echo "SQL_DOWNLOADS: $sql_downloads";
    echo "</pre>\n";
  }

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
                echo "<td class='datatd' width='90%'>Total malicious attacks</td>\n";
                echo "<td class='datatd' width='10%' align='right'>" . nf($total_attacks) . "&nbsp;</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
                echo "<td class='datatd'>&nbsp;</td>\n";
              echo "</tr>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td class='datatd'>Total downloaded malware</td>\n";
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
            if ($s_admin != 1 || ($s_admin == 1 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              add_db_table("attacks");
              add_db_table("sensors");
              $where[] = "attacks.severity = 1";
              $where[] = "sensors.organisation = $q_org";
              $where[] = "sensors.id = attacks.sensorid";
              $where[] = "$tsquery";
              prepare_sql();
              $sql_attacks = "SELECT DISTINCT COUNT(attacks.severity) as total ";
              $sql_attacks .= " FROM $sql_from ";
              $sql_attacks .= " $sql_where ";
              $result_attacks = pg_query($pgconn, $sql_attacks);

              # Resetting the sql generation arrays
              $where = array();
              $db_table = array();

              $row = pg_fetch_assoc($result_attacks);
              $org_attacks = $row['total'];
              if ($org_attacks == 0) {
                $org_attacks_perc = '0';
              } else {
                $org_attacks_perc = floor(($org_attacks / $total_attacks) * 100);
              }

              add_db_table("attacks");
              add_db_table("sensors");
              $where[] = "attacks.severity = 32";
              $where[] = "sensors.organisation = $q_org";
              $where[] = "sensors.id = attacks.sensorid";
              $where[] = "$tsquery";
              prepare_sql();
              $sql_downloads = "SELECT DISTINCT COUNT(attacks.severity) as total ";
              $sql_downloads .= " FROM $sql_from ";
              $sql_downloads .= " $sql_where ";
              $result_downloads = pg_query($pgconn, $sql_downloads);

              # Resetting the sql generation arrays
              $where = array();
              $db_table = array();

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
  add_db_table("attacks");
  add_db_table("details");
  $where[] = "details.type = 1";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_topexp = "SELECT DISTINCT details.text, COUNT(details.id) as total ";
  $sql_topexp .= " FROM $sql_from ";
  $sql_topexp .= " $sql_where ";
  $sql_topexp .= " GROUP BY details.text ORDER BY total DESC LIMIT $topexploits OFFSET 0 ";
  $result_topexp = pg_query($pgconn, $sql_topexp);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  add_db_table("attacks");
  add_db_table("details");
  add_db_table("sensors");
  $where[] = "details.type = 1";
  $where[] = "sensors.organisation = $q_org";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_topexp_org = "SELECT DISTINCT details.text, COUNT(details.id) as total ";
  $sql_topexp_org .= " FROM $sql_from ";
  $sql_topexp_org .= " $sql_where ";
  $sql_topexp_org .= " GROUP BY details.text ORDER BY total DESC LIMIT $topexploits OFFSET 0 ";

  $result_topexp_org = pg_query($pgconn, $sql_topexp_org);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_TOPEXP: $sql_topexp\n";
    echo "SQL_TOPEXP_ORG: $sql_topexp_org";
    echo "</pre>\n";
  }

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top $topexploits exploits of all sensors</b>\n";
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='85%' class='datatd'>Exploit</td>\n";
                echo "<td width='10%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i=1;
              while ($row = pg_fetch_assoc($result_topexp)) {
                $exploit = $row['text'];
                $attack = $attacks_ar[$exploit]["Attack"];
                $attack_url = $attacks_ar[$exploit]["URL"];
                $total = $row['total'];
                echo "<tr class='datatr'>\n";
                  echo "<td class='datatd' align='right'>$i.&nbsp;</td>\n";
                  if ($attack_url != "") {
                    echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
                  } else {
                    echo "<td class='datatd'>$attack</td>\n";
                  }
                  echo "<td class='datatd' align='right'>" . nf($total) . "&nbsp;</td>\n";
                echo "</tr>\n";
                $i++;
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              echo "<b>Top $topexploits exploits of your sensors</b>\n";
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td width='5%' class='datatd'>#</td>\n";
                  echo "<td width='85%' class='datatd'>Exploit</td>\n";
                  echo "<td width='10%' class='datatd'>Total</td>\n";
                echo "</tr>\n";
                $i = 1;
                while ($row = pg_fetch_assoc($result_topexp_org)) {
                  $exploit = $row['text'];
                  $attack = $attacks_ar[$exploit]["Attack"];
                  $attack_url = $attacks_ar[$exploit]["URL"];
                  $total = $row['total'];
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd' align='right'>$i.&nbsp;</td>\n";
                    if ($attack_url != "") {
                      echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
                    } else {
                      echo "<td class='datatd'>$attack</td>\n";
                    }
                    echo "<td class='datatd' align='right'>" . nf($total) . "&nbsp;</td>\n";
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
########################## Top 10 sensors
  add_db_table("attacks");
  add_db_table("details");
  add_db_table("sensors");
  $where[] = "details.type = 1";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_top = "SELECT DISTINCT sensors.organisation, sensors.keyname, COUNT(details.*) as total ";
  $sql_top .= " FROM $sql_from ";
  $sql_top .= " $sql_where ";
  $sql_top .= " GROUP BY sensors.keyname, sensors.organisation ORDER BY total DESC";

  $result_top = pg_query($pgconn, $sql_top);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  add_db_table("attacks");
  add_db_table("details");
  add_db_table("sensors");
  $where[] = "details.type = 1";
  $where[] = "sensors.organisation = $q_org";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_top_org = "SELECT DISTINCT sensors.keyname, COUNT(details.*) as total ";
  $sql_top_org .= " FROM $sql_from ";
  $sql_top_org .= " $sql_where ";
  $sql_top_org .= " GROUP BY sensors.keyname ORDER BY total DESC LIMIT $topsensors OFFSET 0";

  $result_top_org = pg_query($pgconn, $sql_top_org);
  $numrows_top_org = pg_num_rows($result_top_org);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_TOP: $sql_top\n";
    echo "SQL_TOP_ORG: $sql_top_org";
    echo "</pre>\n";
  }

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%' valign='top'>\n";
            echo "<b>Top $topsensors sensors</b>\n";
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

                $keyname = $row['keyname'];
                $total = $row['total'];
                $rank_ar[$keyname] = $i;
                if ($i <= $topsensors) {
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
            if ($s_admin != 1 || ($s_admin == 1 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              echo "<b>Top $topsensors sensors of $orgname</b>\n";
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

 ########################## Top 10 ports // START of modification by bjou
  add_db_table("attacks");
  $where[] = "$tsquery";
  prepare_sql();
  $sql_topports = "SELECT DISTINCT attacks.dport, COUNT(attacks.dport) as total ";
  $sql_topports .= " FROM $sql_from ";
  $sql_topports .= " $sql_where ";
  $sql_topports .= " GROUP BY attacks.dport ORDER BY total DESC LIMIT 10 OFFSET 0 "; // change LIMIT into variable to be read from conf
  $result_topports = pg_query($pgconn, $sql_topports);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  add_db_table("attacks");
  add_db_table("sensors");
  $where[] = "sensors.id = attacks.sensorid";
  $where[] = "sensors.organisation = $q_org";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_topports_org = "SELECT DISTINCT attacks.dport, COUNT(attacks.dport) as total ";
  $sql_topports_org .= " FROM $sql_from ";
  $sql_topports_org .= " $sql_where ";
  $sql_topports_org .= " GROUP BY attacks.dport ORDER BY total DESC LIMIT 10 OFFSET 0 ";  // change LIMIT into variable to be read from conf

  $result_topports_org = pg_query($pgconn, $sql_topports_org);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_TOPPORTS: $sql_topports\n";
    echo "SQL_TOPPORTS_ORG: $sql_topports_org";
    echo "</pre>\n";
  }

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top 10 ports of all sensors</b>\n"; // change this into variable to be read from conf
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='15%' class='datatd'>Port</td>\n";
                echo "<td width='70%' class='datatd'>Port Description</td>\n";
                echo "<td width='10%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i=1;
              while ($row = pg_fetch_assoc($result_topports)) {
                $port = $row['dport'];
                $total = $row['total'];
                echo "<tr class='datatr'>\n";
                  echo "<td class='datatd'>$i</td>\n";
                  echo "<td class='datatd'><a href='logsearch.php?d_radio=A&destination_port=$port&order_m=DESC$dateqs'>$port</a></td>\n";
                  echo "<td class='datatd'><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$port'>".getPortDescr($port)."</a></td>\n";
                  
                  echo "<td class='datatd'>$total</td>\n";
                echo "</tr>\n";
                $i++;
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              echo "<b>Top 10 ports of your sensors</b>\n"; // change this into variable to be read from conf
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td width='5%' class='datatd'>#</td>\n";
                  echo "<td width='15%' class='datatd'>Port</td>\n";
                  echo "<td width='70%' class='datatd'>Port Description</td>\n";
                  echo "<td width='10%' class='datatd'>Total</td>\n";
                echo "</tr>\n";
                $i = 1;
                while ($row = pg_fetch_assoc($result_topports_org)) {
                  $port = $row['dport'];
                  $total = $row['total'];
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";
                        echo "<td class='datatd'><a href='logsearch.php?d_radio=A&destination_port=$port&order_m=DESC$dateqs'>$port</a></td>\n";
                        echo "<td class='datatd'><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$port'>".getPortDescr($port)."</a></td>\n";
                        echo "<td class='datatd'>$total</td>\n";
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

 ########################## Top 10 source addresses
  add_db_table("attacks");
  $where[] = "$tsquery";
  prepare_sql();
  $sql_topsource = "SELECT DISTINCT attacks.source, COUNT(attacks.source) as total ";
  $sql_topsource .= " FROM $sql_from ";
  $sql_topsource .= " $sql_where ";
  $sql_topsource .= " GROUP BY attacks.source ORDER BY total DESC LIMIT 10 OFFSET 0 "; // change LIMIT into variable to be read from conf
  $result_topsource = pg_query($pgconn, $sql_topsource);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  add_db_table("attacks");
  add_db_table("sensors");
  $where[] = "sensors.id = attacks.sensorid";
  $where[] = "sensors.organisation = $q_org";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_topsource_org = "SELECT DISTINCT attacks.source, COUNT(attacks.source) as total ";
  $sql_topsource_org .= " FROM $sql_from ";
  $sql_topsource_org .= " $sql_where ";
  $sql_topsource_org .= " GROUP BY attacks.source ORDER BY total DESC LIMIT 10 OFFSET 0 ";  // change LIMIT into variable to be read from conf

  $result_topsource_org = pg_query($pgconn, $sql_topsource_org);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_TOPSOURCE: $sql_topsource\n";
    echo "SQL_TOPSOURCE_ORG: $sql_topsource_org";
    echo "</pre>\n";
  }

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top 10 source addresses of all sensors</b>\n";// change this into variable to be read from conf
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='85%' class='datatd'>Address</td>\n";
                echo "<td width='10%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $i=1;
              while ($row = pg_fetch_assoc($result_topsource)) {
                $source = $row['source'];
                $total = $row['total'];
                echo "<tr class='datatr'>\n";
                  echo "<td class='datatd'>$i</td>\n";
                  echo "<td class='datatd'><a href='whois.php?ip=$source'>$source</a></td>\n";
                  echo "<td class='datatd'>$total</td>\n";
                echo "</tr>\n";
                $i++;
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              echo "<b>Top 10 source addresses of your sensors</b>\n";  // change this into variable to be read from conf
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td width='5%' class='datatd'>#</td>\n";
                  echo "<td width='85%' class='datatd'>Address</td>\n";
                  echo "<td width='10%' class='datatd'>Total</td>\n";
                echo "</tr>\n";
                $i = 1;
                while ($row = pg_fetch_assoc($result_topsource_org)) {
                  $source = $row['source'];
                  $total = $row['total'];
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";
                        echo "<td class='datatd'><a href='whois.php?ip=$source'>$source</a></td>\n";
                        echo "<td class='datatd'>$total</td>\n";
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

 ########################## Top 10 Filenames
  add_db_table("details");
  $where[] = "$tsquery";
  $where[] = "type = 4";
  prepare_sql();
  $sql_topfiles = "SELECT DISTINCT text, COUNT(details.text) as total ";
  $sql_topfiles .= " FROM $sql_from ";
  $sql_topfiles .= " $sql_where ";
  $sql_topfiles .= " GROUP BY text ORDER BY total DESC OFFSET 0";
  $result_topfiles = pg_query($pgconn, $sql_topfiles);

  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();

  add_db_table("details");
  add_db_table("sensors");
  $where[] = "sensors.id = details.sensorid";
  $where[] = "sensors.organisation = $q_org";
  $where[] = "type = 4";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_topfiles_org = "SELECT DISTINCT text, COUNT(details.text) as total ";
  $sql_topfiles_org .= " FROM $sql_from ";
  $sql_topfiles_org .= " $sql_where ";
  $sql_topfiles_org .= " GROUP BY text ORDER BY total DESC OFFSET 0";
  $result_topfiles_org = pg_query($pgconn, $sql_topfiles_org);
  
  # Resetting the sql generation arrays
  $where = array();
  $db_table = array();
        
  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_TOPFILES: $sql_topfiles\n";
    echo "SQL_TOPFILES_ORG: $sql_topfiles_org";
    echo "</pre>\n";
  }

  echo "<tr>\n";
    echo "<td>\n";
      echo "<table width='100%'>\n";
        echo "<tr>\n";
          echo "<td width='45%'>\n";
            echo "<b>Top 10 filenames of all sensors</b>\n"; // change this into variable to be read from conf
            echo "<table class='datatable' width='100%'>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td width='5%' class='datatd'>#</td>\n";
                echo "<td width='20%' class='datatd'>Filename</td>\n";
                echo "<td width='65%' class='datatd'>Binary</td>\n";
                echo "<td width='10%' class='datatd'>Total</td>\n";
              echo "</tr>\n";
              $filenameArray = array();
              while ($row = pg_fetch_assoc($result_topfiles)) {
                $url = $row['text'];
                $total = $row['total'];
                $array = @parse_url($url);
                $filename = trim($array['path'],'/');
                
                if (strlen($filename) > 0 && !array_key_exists($filename, $filenameArray)) {
                  $filenameArray[$filename] = $total;
                }
                elseif (array_key_exists($filename, $filenameArray)) {
                  $filenameArray[$filename] += $total;
                }
              }
              arsort($filenameArray);
              $i=1;
              foreach ($filenameArray as $file => $count) {
                if ($i==11) break; // change this into variable+1 to be read from conf
                
                # Query preparation                
                add_db_table("details");
                $where[] = "$tsquery";
                $where[] = "type = 8";
                $where[] = "attackid in (SELECT attackid FROM details WHERE type = 4 AND text LIKE '%$file')";
                prepare_sql();
                $sql_topbin = "SELECT DISTINCT text ";
                $sql_topbin .= " FROM $sql_from ";
                $sql_topbin .= " $sql_where ";
                $result_topbin = pg_query($pgconn, $sql_topbin);
                
                # Resetting the sql generation arrays
                $where = array();
                $db_table = array();
                
                $row = pg_fetch_assoc($result_topbin);
                $bin = $row['text'];
                                 
                echo "<tr class='datatr'>\n";
                  echo "<td class='datatd'>$i</td>\n";
                  echo "<td class='datatd'><a href='logsearch.php?d_radio=A&f_filename=$file&order_m=DESC$dateqs'>$file</a></td>\n";
                  echo "<td class='datatd'><a href='binaryhist.php?bin=$bin'>$bin</a></td>\n";
                  echo "<td class='datatd'>$count</td>\n";
                echo "</tr>\n";
               $i++;
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_admin != 1 || ($s_admin == 1 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              echo "<b>Top 10 filenames of your sensors</b>\n";// change this into variable to be read from conf
              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                   echo "<td width='5%' class='datatd'>#</td>\n";
                   echo "<td width='20%' class='datatd'>Filename</td>\n";
                   echo "<td width='65%' class='datatd'>Binary</td>\n";
                   echo "<td width='10%' class='datatd'>Total</td>\n";
                echo "</tr>\n";
                 $filenameArray = array();
                  while ($row = pg_fetch_assoc($result_topfiles_org)) {
                    $url = $row['text'];
                    $total = $row['total'];
                    $array = @parse_url($url);
                    $filename = trim($array['path'],'/');
                    if (strlen($filename) > 0 && !array_key_exists($filename, $filenameArray)) {
                      $filenameArray[$filename] = $total;
                    }
                    elseif (array_key_exists($filename, $filenameArray)) {
                      $filenameArray[$filename] += $total;
                    }
                  }
                  arsort($filenameArray);
                  $i=1;
                  foreach ($filenameArray as $file => $count) {
                    if ($i==11) break;  // change this into variable to be read from conf
                    
                    # Query preparation                
                    add_db_table("details");
                    $where[] = "$tsquery";
                    $where[] = "type = 8";
                    $where[] = "attackid in (SELECT attackid FROM details WHERE type = 4 AND text LIKE '%$file')";
                    prepare_sql();
                    $sql_topbin_org = "SELECT DISTINCT text ";
                    $sql_topbin_org .= " FROM $sql_from ";
                    $sql_topbin_org .= " $sql_where ";
                    $result_topbin_org = pg_query($pgconn, $sql_topbin_org);
                
                    # Resetting the sql generation arrays
                    $where = array();
                    $db_table = array();
                
                    $row = pg_fetch_assoc($result_topbin_org);
                    $bin = $row['text'];
                    echo "<tr class='datatr'>\n";
                      echo "<td class='datatd'>$i</td>\n";
                      echo "<td class='datatd'><a href='logsearch.php?d_radio=A&f_filename=$file&order_m=DESC$dateqs'>$file</a></td>\n";
                      echo "<td class='datatd'><a href='binaryhist.php?bin=$bin'>$bin</a></td>\n";
                      echo "<td class='datatd'>$count</td>\n";
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

// END of modification by bjou

########################## Top 5 Organisations
  add_db_table("attacks");
  add_db_table("sensors");
  $where[] = "attacks.severity = 1";
  $where[] = "$tsquery";
  prepare_sql();
  $sql_organisation = "SELECT sensors.organisation, COUNT(attacks.*) as total ";
  $sql_organisation .= " FROM $sql_from ";
  $sql_organisation .= " $sql_where ";
  $sql_organisation .= " GROUP BY sensors.organisation ORDER BY total DESC LIMIT $toporgs OFFSET 0";

  $result_organisation = pg_query($pgconn, $sql_organisation);

  # Debug info 
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_ORGANISATION: $sql_organisation";
    echo "</pre>\n";
  }

  echo "<tr>\n";
    echo "<td>\n";
      echo "<b>Top $toporgs organisations</b>\n";
      echo "<table class='datatable' width='45%'>\n";
        echo "<tr>\n";
          echo "<td class='dataheader' width='5%'>#</td>\n";
          echo "<td class='dataheader' width='70%'>Organisation</td>\n";
          echo "<td class='dataheader' width='25%'>Total exploits</td>\n";
        echo "</tr>\n";
        $i = 0;
        while ($row = pg_fetch_assoc($result_organisation)) {
          $i++;
          $db_org = $row['organisation'];

          $sql_getorg = "SELECT organisation FROM organisations WHERE id = $db_org";
          $result_getorg = pg_query($pgconn, $sql_getorg);
          $db_org_name = pg_result($result_getorg, 0);

          $count = $row['total'];
          echo "<tr>\n";
            echo "<td class='datatd' align='right'>$i.&nbsp;</td>\n";
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
?>
<?php footer(); ?>
