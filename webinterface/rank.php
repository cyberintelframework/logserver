<?php include("menu.php"); set_title("Ranking"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.05                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.05 Added intval() for $s_admin and $s_org
# 1.02.04 Added intval() for de $day and $month variables
# 1.02.03 Added some more input checks and changed organisation handling
# 1.02.02 Fixed a $_GET vulnerability
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';
include 'include/variables.inc.php';

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

### Checking browse method.
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
  $tsquery = "attacks.timestamp >= $start AND attacks.timestamp <= $end";
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
  $tsquery = "attacks.timestamp >= $start AND attacks.timestamp <= $end";
  $selweek = "selected";
}
elseif ($b == "all") {
  $searchqs = "";
  $tsquery = "";
  $adwhere = "";
  $and = "";
  $selall = "selected";
}

### Setting organisation if user is admin.
if (isset($_GET['org']) && $s_access_search == 9) {
  $s_org = intval($_GET['org']);
  if ($s_org != 0) {
    $s_org = intval($_GET['org']);
  }
}

#### Get the name of the organisation.
$sql_getorg = "SELECT organisation FROM organisations WHERE id = $s_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$orgname = pg_result($result_getorg, 0);

### Browse menu.
$today = date("U");
echo "<form name='selectorg' method='get' action='rank.php'>\n";
  if ($b != "all") {
    echo "<input type='button' value='Prev' class='button' onClick=window.location='rank.php?b=$b&amp;d=$prev&amp;org=$s_org';>\n";
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
          if ($s_org == $org_id) {
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
      echo "<input type='button' value='Next' class='button' onClick=window.location='rank.php?b=$b&amp;d=$next&amp;org=$s_org';>\n";
    }
  }
  else {
    echo "<input type='button' value='Next' class='button' disabled>\n";
  }
echo "</form>\n";

### Checking period.
if ($b == "all") {
  $periodtext = "All results";
}
else {
  $datestart = date("d-m-Y", $start);
  $dateend = date("d-m-Y", $end);
  $periodtext = "Results from $datestart to $dateend";
}
echo "&nbsp;&nbsp;<b>$periodtext</b>\n";
#echo "<br />\n";
#echo "<br />\n";

$sql_active = "SELECT Count(*) as total FROM sensors WHERE tap != ''";
$result_active = pg_query($pgconn, $sql_active);
$row = pg_fetch_assoc($result_active);
$total_active = $row['total'];

$sql_sensors = "SELECT Count(id) as total FROM sensors";
$result_sensors = pg_query($pgconn, $sql_sensors);
$row = pg_fetch_assoc($result_sensors);
$total_sensors = $row['total'];

echo "<table width='100%'>\n";
##########################
  $sql_attacks = "SELECT COUNT(attacks.severity) as total FROM attacks WHERE severity = 1 $and $tsquery";
  $result_attacks = pg_query($pgconn, $sql_attacks);
  $row = pg_fetch_assoc($result_attacks);
  $total_attacks = $row['total'];

  $sql_downloads = "SELECT COUNT(attacks.severity) as total FROM attacks WHERE severity = 32 $and $tsquery";
  $result_downloads = pg_query($pgconn, $sql_downloads);
  $row = pg_fetch_assoc($result_downloads);
  $total_downloads = $row['total'];

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
                echo "<td class='datatd' width='10%'>$total_attacks</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
                echo "<td class='datatd'>&nbsp;</td>\n";
              echo "</tr>\n";
              echo "<tr class='dataheader'>\n";
                echo "<td class='datatd'>Total downloaded malware</td>\n";
                echo "<td class='datatd'>$total_downloads</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
                echo "<td class='datatd'>&nbsp;</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%'>\n";
            if ($s_access_search != 9 || ($s_access_search == 9 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              $sql_attacks = "SELECT DISTINCT COUNT(attacks.severity) as total FROM attacks, sensors WHERE severity = 1 AND sensors.organisation = $s_org AND sensors.id = attacks.sensorid $and $tsquery";
              #echo "SQL_ATTACKS: $sql_attacks<br />\n";
              $result_attacks = pg_query($pgconn, $sql_attacks);
              $row = pg_fetch_assoc($result_attacks);
              $org_attacks = $row['total'];
              if ($org_attacks == 0) {
                $org_attacks_perc = '0';
              } 
              else {
                $org_attacks_perc = floor(($org_attacks / $total_attacks) * 100);
              }

              $sql_downloads = "SELECT DISTINCT COUNT(attacks.severity) as total FROM attacks, sensors WHERE severity = 32 AND sensors.organisation = $s_org AND sensors.id = attacks.sensorid $and $tsquery";
              $result_downloads = pg_query($pgconn, $sql_downloads);
              $row = pg_fetch_assoc($result_downloads);
              $org_downloads = $row['total'];
              if ($org_downloads == 0) {
                $org_downloads_perc = '0';
              } 
              else {
                $org_downloads_perc = floor(($org_downloads / $total_downloads) * 100);
              }

              echo "<table class='datatable' width='100%'>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td class='datatd' width='90%'>Total malicious attacks for $orgname</td>\n";
                  echo "<td class='datatd' width='10%'>$org_attacks</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;% of total malicious attacks</td>\n";
                  echo "<td class='datatd'>$org_attacks_perc%</td>\n";
                echo "</tr>\n";
                echo "<tr class='dataheader'>\n";
                  echo "<td class='datatd'>Total downloaded malware by $orgname</td>\n";
                  echo "<td class='datatd'>$org_downloads</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td class='datatd'>&nbsp;&nbsp;&nbsp;&nbsp;% of total collected malware</td>\n";
                  echo "<td class='datatd'>$org_downloads_perc%</td>\n";
                echo "</tr>\n";
              echo "</table><br />\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
##########################
  $sql_topexp = "SELECT DISTINCT details.text, COUNT(details.*) as total FROM details, attacks WHERE details.type = 1 AND attacks.id = details.attackid $and $tsquery GROUP BY details.text ORDER BY total DESC LIMIT $topexploits OFFSET 0";
  $result_topexp = pg_query($pgconn, $sql_topexp);

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
                  echo "<td class='datatd'>$i</td>\n";
                  if ($attack_url != "") {
                    echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
                  }
                  else {
                    echo "<td class='datatd'>$attack</td>\n";
                  }
                  echo "<td class='datatd'>$total</td>\n";
                echo "</tr>\n";
                $i++;
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_access_search != 9 || ($s_access_search == 9 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              $sql_topexp_org = "SELECT DISTINCT details.text, COUNT(details.*) as total FROM sensors, details, attacks WHERE details.type = 1 $and $tsquery AND sensors.id = details.sensorid AND sensors.organisation = $s_org AND attacks.id = details.attackid GROUP BY details.text ORDER BY total DESC LIMIT $topexploits OFFSET 0";
              $result_topexp_org = pg_query($pgconn, $sql_topexp_org);
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
                    echo "<td class='datatd'>$i</td>\n";
                    if ($attack_url != "") {
                      echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
                    }
                    else {
                      echo "<td class='datatd'>$attack</td>\n";
                    }
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
########################## Top 10 sensors
  $sql_top = "SELECT DISTINCT sensors.organisation, sensors.keyname, COUNT(details.*) as total FROM details, sensors, attacks WHERE details.type = 1 $and $tsquery AND details.sensorid = sensors.id AND attacks.id = details.attackid GROUP BY sensors.keyname, sensors.organisation ORDER BY total DESC";
  $result_top = pg_query($pgconn, $sql_top);
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
                $db_org = intval($row['organisation']);

                $sql_getorg = "SELECT organisation FROM organisations WHERE id = $db_org";
                $result_getorg = pg_query($pgconn, $sql_getorg);
                $db_org_name = pg_result($result_getorg, 0);

                $keyname = $row['keyname'];
                $total = $row['total'];
                $rank_ar[$keyname] = $i;
                if ($i <= $topsensors) {
                  echo "<tr class='datatr'>\n";
                    echo "<td class='datatd'>$i</td>\n";
                    if ($s_access_search == 9) {
                      echo "<td class='datatd'>$db_org_name - $keyname</td>\n";
                    }
                    elseif ($s_org == $db_org) {
                      echo "<td class='datatd'>$keyname</td>\n";
                    }
                    else {
                      echo "<td class='datatd'>&nbsp;</td>\n";
                    }
                    echo "<td class='datatd'>$total</td>\n";
                  echo "</tr>\n";
                }
                $i++;
              }
            echo "</table>\n";
          echo "</td>\n";
          echo "<td width='10%'></td>\n";
          echo "<td width='45%' valign='top'>\n";
            if ($s_access_search != 9 || ($s_access_search == 9 && isset($_GET['org']) && $_GET['org'] != 0) ) {
              $sql_top_org = "SELECT DISTINCT sensors.keyname, COUNT(details.*) as total FROM details, sensors, attacks WHERE details.type = 1 $and $tsquery AND details.sensorid = sensors.id AND sensors.organisation = $s_org AND details.attackid = attacks.id GROUP BY sensors.keyname ORDER BY total DESC LIMIT $topsensors OFFSET 0";
              $result_top_org = pg_query($pgconn, $sql_top_org);
              $numrows_top_org = pg_num_rows($result_top_org);
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
                    echo "<td class='datatd'>$i</td>\n";
                    $keyname = $row_top_org['keyname'];
                    $total = $row_top_org['total'];
                    $rank_all = $rank_ar[$keyname];
                    echo "<td class='datatd'>$rank_all</td>\n";
                    echo "<td class='datatd'>$keyname</td>\n";
                    echo "<td class='datatd'>$total</td>\n";
                  echo "</tr>\n";
                }
              echo "</table>\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
########################## Top 5 Organisations
  $sql_organisation = "SELECT sensors.organisation, COUNT(attacks.*) as total FROM attacks, sensors WHERE attacks.severity = 1 $and $tsquery AND sensors.id = attacks.sensorid GROUP BY sensors.organisation ORDER BY total DESC LIMIT $toporgs OFFSET 0";
  $result_organisation = pg_query($pgconn, $sql_organisation);

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
            echo "<td class='datatd'>$i</td>\n";
            if ($s_access_search == 9) {
              echo "<td class='datatd'>$db_org_name</td>\n";
            }
            elseif ($s_org == $db_org) {
              echo "<td class='datatd'>$db_org_name</td>\n";
            }
            else {
              echo "<td class='datatd'>&nbsp;</td>\n";
            }            
            echo "<td class='datatd'>$count</td>\n";
          echo "</tr>\n";
        }
      echo "</table>\n";
    echo "</td>\n";
  echo "</tr>\n";
echo "</table>\n";

pg_close($pgconn);
?>
<?php footer(); ?>
