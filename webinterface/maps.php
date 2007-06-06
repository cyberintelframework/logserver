<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 31-05-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

if ($c_autocomplete == 1) {
  if ($s_access_search != 9) {
    $sql_smac = "SELECT DISTINCT sourcemac FROM arp_alert, sensors WHERE arp_alert.sensorid = sensors.id AND sensors.organisation = $s_org";
    $sql_tmac = "SELECT DISTINCT targetmac FROM arp_alert, sensors WHERE arp_alert.sensorid = sensors.id AND sensors.organisation = $s_org";
    $sql_tip = "SELECT DISTINCT targetip FROM arp_alert, sensors WHERE arp_alert.sensorid = sensors.id AND sensors.organisation = $s_org";
    $sql_vir = "SELECT DISTINCT name FROM stats_virus";

    $sql_files = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
      $sql_files .= "(SELECT split_part(details.text, '/', 4) as file FROM details, sensors ";
      $sql_files .= "WHERE NOT split_part(details.text, '/', 4) = '' AND type = 4 AND sensors.id = details.sensorid ";
      $sql_files .= "AND sensors.organistation = $s_org) as sub ";
    $sql_files .= "GROUP BY sub.file";
  } else {
    $sql_smac = "SELECT DISTINCT sourcemac FROM arp_alert";
    $sql_tmac = "SELECT DISTINCT targetmac FROM arp_alert";
    $sql_tip = "SELECT DISTINCT targetip FROM arp_alert";
    $sql_vir = "SELECT DISTINCT name FROM stats_virus";

    $sql_files = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
      $sql_files .= "(SELECT split_part(details.text, '/', 4) as file FROM details, sensors ";
      $sql_files .= "WHERE NOT split_part(details.text, '/', 4) = '' AND type = 4) as sub ";
    $sql_files .= "GROUP BY sub.file";
  }
  $debuginfo[] = $sql_smac;
  $debuginfo[] = $sql_tmac;
  $debuginfo[] = $sql_tip;

  $allowed_get = array(
        "map"
  );
  $check = extractvars($_GET, $allowed_get);

  if (isset($tainted['map'])) {
    $map = $tainted['map'];
    if ($map == "search") {
      echo "var smacmap = Array();\n";
      $result = pg_query($pgconn, $sql_smac);
      while($row = pg_fetch_assoc($result)) {
        $mac = $row['sourcemac'];
        echo "smacmap['$mac'] = 0;\n";
      }

      echo "var tmacmap = Array();\n";
      $result = pg_query($pgconn, $sql_tmac);
      while($row = pg_fetch_assoc($result)) {
        $mac = $row['targetmac'];
        echo "tmacmap['$mac'] = 0;\n";
      }

      $result = pg_query($pgconn, $sql_tip);
      while($row = pg_fetch_assoc($result)) {
        $ip = $row['targetip'];
        echo "tmacmap['$ip'] = 0;\n";
      }

      echo "var filemap = Array();\n";
      $result = pg_query($pgconn, $sql_files);
      while($row = pg_fetch_assoc($result)) {
        $file = $row['file'];
        echo "filemap['$file'] = 0;\n";
      }

      echo "var virusmap = Array();\n";
      $result = pg_query($pgconn, $sql_vir);
      while($row = pg_fetch_assoc($result)) {
        $name = $row['name'];
        echo "virusmap['$name'] = 0;\n";
      }
    }
  }
}

?>