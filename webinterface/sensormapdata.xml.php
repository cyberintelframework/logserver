<?php
####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 07-01-2010                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Fixed same bug as in #206
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
		"int_from"
);
$check = extractvars($_GET, $allowed_get);

# Checking access
$q_org = intval($_SESSION['q_org']);

if (isset($clean['to']) && isset($clean['from'])) {
  $start = $clean['from'];
  $end = $clean['to'];
  $tsquery = "timestamp >= $start AND timestamp <= $end AND";
}
 
if ( $err == 0) {
  echo "<?xml version='1.0' encoding='ISO-8859-1'?>";
  echo "<markers>";

  $query = "SELECT DISTINCT remoteip, COUNT(remoteip) as count FROM sensors ";
  $query .= " LEFT JOIN sensor_details ON sensors.keyname = sensor_details.keyname ";
  $query .= " WHERE NOT status = 3 ";
  if ($q_org != 0) {
    $query .= "AND organisation = '$q_org' ";
  }
  $query .= "GROUP BY remoteip ORDER BY count DESC";
  $r_hit = pg_query($pgconn, $query);
  if (pg_num_rows($r_hit)) {
    $ar_latlng = array();
    while ($hit = pg_fetch_assoc($r_hit)) {
      $rip = $hit['remoteip'];
      $count = $hit['count'];
      $record = geoip_record_by_addr($gi, $rip);
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
    echo "</markers>";
  }
} else {
  echo "<?xml version='1.0' encoding='ISO-8859-1'?>";
  echo "<markers>";
  echo "</markers>";
}
?>
