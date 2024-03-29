<?php
####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 21-12-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Fixed bug #206 (empty result set)
# 002 Removed the need for a file in /tmp
# 001 version 2.00
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}
	
header('Content-Type: text/xml');

if ($c_geoip_enable == 1) {
  include ('../include/' .$c_geoip_module);
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

# Retrieving some session variables
$s_admin = intval($_SESSION['s_admin']);
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "q_org",
                "int_to",
                "int_from",
                "int_sev",
                "int_atype",
                "int_own"
);
$check = extractvars($_GET, $allowed_get);

# Checking access
$q_org = intval($_SESSION['q_org']);

$orgquery = "";
if ($q_org != 0) {
  $orgquery = "sensors.organisation = '$q_org' AND";
}

if (isset($clean['to']) && isset($clean['from'])) {
  $start = $clean['from'];
  $end = $clean['to'];
  $tsquery = "timestamp >= $start AND timestamp <= $end AND";
}

if (isset($clean['sev'])) {
    $sev = $clean['sev'];
} else {
    $sev = 1;
}

if (isset($clean['atype'])) {
    $atype = $clean['atype'];
}

if (isset($clean['own'])) {
    $own = $clean['own'];
} else {
    $own = 0;
}

if ( $err == 0) {
  $query = "SELECT DISTINCT attacks.source, COUNT(attacks.source) as count FROM sensors, attacks ";
  $query .= "WHERE $orgquery attacks.sensorid = sensors.id AND $tsquery attacks.severity = $sev ";
  if ($sev != 0 && isset($clean['atype'])) {
    $query .= " AND attacks.atype = $atype ";
  }
  if ($own == 1) {
    $query .= " AND ". gen_org_sql(1) ." ";
  }
  $query .= "AND sensors.id = attacks.sensorid GROUP BY attacks.source ORDER BY count DESC";
  $r_hit = pg_query($pgconn, $query);
  echo "<?xml version='1.0' encoding='ISO-8859-1'?>";
  echo "<markers>";
  if (pg_num_rows($r_hit)) {
    $ar_latlng = array();
    while ($hit = pg_fetch_assoc($r_hit)) {
      $source = $hit['source'];
      $count = $hit['count'];
      $record = geoip_record_by_addr($gi, $source);
      $country = $record->country_name;
      $city = $record->city;
      if ($city == "") {
        $city = "Unkown";
      }
      $lat = $record->latitude;
      $lng = $record->longitude;
      if ($country != "") {
        if ($ar_latlng["$lat+$lng"]) {
          $count = $ar_latlng["$lat+$lng"]["count"] + $count;
        }
        $ar_latlng["$lat+$lng"] = array (
		count => "$count",
		country => "$country",
	        city => "$city",	
        );
      }
    }
    foreach ($ar_latlng as $key=>$val) {
      $tmp = explode("+", $key);
      $lat = $tmp[0];
      $lng = $tmp[1];
      $count = $val["count"];
      $country = $val["country"];
      $city = $val["city"];
      $line = '<marker lat="' .$lat. '" lng="' .$lng. '" count="' .$count. '" country="' .$country. '" city="' .$city. '" />';
      echo $line;
      flush();
    }		
  }
  echo "</markers>";
} else {
  echo "<?xml version='1.0' encoding='ISO-8859-1'?>";
  echo "<markers>";
  echo "</markers>";
}
?>
