<?php include("menu.php"); set_title("");?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.05                  #
# 09-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.02.05 Added intval() to session variables	                    
# 1.02.04 Removed includes and changed some input checks
# 1.02.03 New structure
# 1.02.02 Enhanced debugging           
# 1.02.01 Initial release           
#############################################

# arp_log types
# 1 = ARP query alert
# 2 = ARP reply alert

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$q_sid = 0;

# Setting organisation if user is admin.
if ($s_access_search == 9) {
  if (isset($_GET['sid'])) {
    $q_sid = intval($_GET['sid']);
  }
}
# else check for sid and ownership
elseif ($s_access_search > 0) {
  if (isset($_GET['sid'])) {
    $sid = intval($_GET['sid']);
    $sql_orgs = "SELECT id FROM sensors WHERE id = $sid AND organisation = $s_org";
    $result_orgs = pg_query($pgconn, $sql_orgs);
    $numrows_orgs = pg_num_rows($result_orgs);
    if ($numrows_orgs == 0) {
      $q_sid = $sid;
    } else {
      $q_sid = 0;
      $err = 1;
    }
  }
}

echo "<div id='submenu'>\n";
  echo "<div id='selectpage' align='left' style='float: left;'>\n";
    echo "<input class='tabsel' id='button_arp_stats' type='button' name='button_stats' value='ARP Stats' onclick='javascript: showTab(\"arp_stats\");' />\n";
    echo "<input class='tab' id='button_arp_cache' type='button' name='button_cache' value='ARP Cache' onclick='javascript: showTab(\"arp_cache\");' />\n";
    echo "<input class='tab' id='button_arp_logstats' type='button' name='button_logstats' value='ARP Log Stats' onclick='javascript: showTab(\"arp_logstats\");' />\n";
    echo "<input class='tab' id='button_arp_poison' type='button' name='button_poison' value='ARP Log Poison' onclick='javascript: showTab(\"arp_poison\");' />\n";
  echo "</div>\n";

  echo "<div class='selectsensor' align='right'>\n";
  echo "<form name='selectorg' method='get' action='arpindex.php'>\n";
    # Sensors select box (making sure it only shows sensors with data)
    if ($s_access_search == 9) {
      $sql_sensors = "SELECT DISTINCT sensors.id, sensors.keyname, organisations.organisation ";
      $sql_sensors .= " FROM sensors, organisations ";
      $sql_sensors .= " WHERE sensors.organisation = organisations.id ";
      $sql_sensors .= " AND ( ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_cache.sensorid FROM arp_cache) OR ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_stats.sensorid FROM arp_stats) OR ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_log_poison.sensorid FROM arp_log_poison) OR ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_log_stats.sensorid FROM arp_log_stats) ";
      $sql_sensors .= " ) ";
      $sql_sensors .= " ORDER BY organisations.organisation, sensors.keyname";
    } else {
      $sql_sensors = "SELECT DISTINCT sensors.id, sensors.keyname, organisations.organisation ";
      $sql_sensors .= " FROM sensors, organisations ";
      $sql_sensors .= " AND ( ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_cache.sensorid FROM arp_cache) OR ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_stats.sensorid FROM arp_stats) OR ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_log_poison.sensorid FROM arp_log_poison) OR ";
      $sql_sensors .= " sensors.id IN (SELECT DISTINCT arp_log_stats.sensorid FROM arp_log_stats) ";
      $sql_sensors .= " ) ";
      $sql_sensors .= " ORDER BY organisations.organisation, sensors.keyname";
    }

    # Debug info
    if ($debug == 1) {
      echo "<pre>";
      echo "$sql_sensors";
      echo "</pre>\n";
    }

    $result_sensors = pg_query($pgconn, $sql_sensors);
    echo "<select name='sid' onChange='javascript: this.form.submit();'>\n";
      while ($row = pg_fetch_assoc($result_sensors)) {
        $sensorid = $row['id'];
        if (empty($q_sid)) {
          $q_sid = $sensorid;
        }
        $sensor = $row['keyname'];
        $org = $row['organisation'];
        $opt_text = "$org - $sensor";
        echo "" . printOption($sensorid, $opt_text, $q_sid) . "<br />\n";
      } 
    echo "</select>&nbsp;\n";
  echo "</form>\n";
  echo "</div>\n";
echo "</div>\n";
echo "<div id='mainarp'>\n";

##########################
# ARP STATS
##########################
$sql_stats = "SELECT sensors.keyname, arp_stats.* ";
$sql_stats .= " FROM arp_stats, sensors ";
$sql_stats .= " WHERE sensors.id = arp_stats.sensorid AND sensorid = $q_sid ";
$result_stats = pg_query($pgconn, $sql_stats);
$row_stats = pg_fetch_assoc($result_stats);

# Debug info
if ($debug == 1) {
  echo "<pre>";
  echo "SQL_STATS: $sql_stats\n";
  echo "</pre>\n";
}

if ($row_stats != NULL) {
  $ts = $row_stats['timestamp'];
  $ts = date("d-m-Y H:i:s", $ts);
  $avg_query = $row_stats['avg_query'];
  $avg_reply = $row_stats['avg_reply'];
  $query_time = $row_stats['query_time'];
  $sensor = $row_stats['keyname'];

  echo "<div id='arp_stats' style='z-index: 9;'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' width='200'>Sensor</td>\n";
      echo "<td class='datatd' width='200'>$sensor</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader'>Statistics last updated</td>\n";
      echo "<td class='datatd'>$ts</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader'>Average queries (per minute)</td>\n";
      echo "<td class='datatd'>$avg_query</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='dataheader'>Average replies (per minute)</td>\n";
      echo "<td class='datatd'>$avg_reply</td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</div>\n";
}

##########################
# ARP CACHE
##########################
#echo "<h4>ARP Cache</h4>\n";
echo "<div id='arp_cache' style='display: none; z-index: 0;'>\n";
echo "<table class='datatable'>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' width='200'>Last Modified</td>\n";
    echo "<td class='dataheader' width='150'>MAC address</td>\n";
    echo "<td class='dataheader' width='150'>IP address</td>\n";
    echo "<td class='dataheader'># of queries (last $arp_mon_stats_period minutes)</td>\n";
    echo "<td class='dataheader'># of replies (last $arp_mon_stats_period  minutes)</td>\n";
  echo "</tr>\n";

  $sql_getcache = "SELECT * FROM arp_cache WHERE sensorid = $q_sid";
  $result_getcache = pg_query($pgconn, $sql_getcache);

  while($row = pg_fetch_assoc($result_getcache)) {
    $ts = $row['timestamp'];
    $ts = date("d-m-Y H:i:s", $ts);
    $mac = $row['mac'];
    $ip = $row['ip'];
    $query_count = $row['query_count'];
    $reply_count = $row['reply_count'];
    if ($reply_count == "") { $reply_count = 0; }
    if ($query_count == "") { $query_count = 0; }
    echo "<tr>\n";
      echo "<td class='datatd'>$ts</td>\n";
      echo "<td class='datatd'>$mac</td>\n";
      echo "<td class='datatd'>$ip</td>\n";
      echo "<td class='datatd'>$query_count</td>\n";
      echo "<td class='datatd'>$reply_count</td>\n";
    echo "</tr>\n";
  }
echo "</table>\n";
echo "</div>\n";

# Debug info
if ($debug == 1) {
  echo "<pre>";
  echo "$sql_getcache";
  echo "</pre>\n";
}


##########################
# ARP LOGS
##########################

echo "<div id='arp_logstats' style='display: none; z-index: 0;'>\n";
echo "<table class='datatable'>\n";
  $sql_getlog_stats = "SELECT * FROM arp_log_stats WHERE sensorid = $q_sid";
  $result_getlog_stats = pg_query($pgconn, $sql_getlog_stats);

  while($row = pg_fetch_assoc($result_getlog_stats)) {
    $timestamp = $row['timestamp'];
    $ts = date("d-m-Y H:i:s", $timestamp);
    $type = $row['type'];
    $threshold = $row['threshold'];
    $average = $row['average'];
    $count = $row['count'];
    $time = $row['time'];

    if ($type == 1) {
      $optype = "queries";
    } elseif ($type == 2) {
      $optype = "replies";
    }
    $alert = "ARP $optype threshold exceeded\n";
    $detail = "The ARP $optype threshold was $threshold based on an average of $average per $time minutes. The actual amount of ARP $optype measured over the last $time minutes was $count.\n";

    echo "<tr>\n";
      echo "<td class='dataheader' width='200'>$ts</td>\n";
      echo "<td class='dataheader' width='400'>$alert</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='datatd' colspan='2'>$detail</td>\n";
    echo "</tr>\n";
    echo "<tr><td class='datatd' colspan='2'>&nbsp;</td></tr>\n";
  }
echo "</table>\n";
echo "</div>\n";

echo "<div id='arp_poison' style='display: none; z-index: 0;'>\n";
echo "<table class='datatable'>\n";
  $sql_poison = "SELECT * FROM arp_log_poison WHERE sensorid = $q_sid";
  $result_poison = pg_query($pgconn, $sql_poison);

  while($row = pg_fetch_assoc($result_poison)) {
    $timestamp = $row['timestamp'];
    $ts = date("d-m-Y H:i:s", $timestamp);
    $ip = $row['ip'];
    $old_mac = $row['old_mac'];
    $new_mac = $row['new_mac'];

    $alert = "ARP anomaly detected\n";
    $detail = "The new MAC address ($new_mac) of $ip conflicts with the statically set MAC address ($old_mac).\n";

    echo "<tr>\n";
      echo "<td class='dataheader' width='200'>$ts</td>\n";
      echo "<td class='dataheader' width='400'>$alert</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td class='datatd' colspan='2'>$detail</td>\n";
    echo "</tr>\n";
    echo "<tr><td class='datatd' colspan='2'>&nbsp;</td></tr>\n";
  }
echo "</table>\n";
echo "</div>\n";

echo "</div>\n";

pg_close($pgconn);
?>
<?php footer(); ?>
