<?php
####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.01 Code layout
# 1.04.00 initial release
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
                "int_org",
		"int_to",
		"int_from"
);
$check = extractvars($_GET, $allowed_get);

if (isset($clean['org'])) {
  $int_org = $clean['org'];
} else {
  $int_org = intval($s_org);
}

if ($int_org == 0 && $s_admin == 1) {$orgquery = "";}
elseif ($int_org != 0 && $s_org == $int_org) {$orgquery = "sensors.organisation = '$int_org' AND";}
elseif ($int_org != 0 && $s_admin == 1) {$orgquery = "sensors.organisation = '$int_org' AND";}
else { $err=1; }

if (isset($clean['to']) && isset($clean['from'])) {
  $start = $clean['from'];
  $end = $clean['to'];
  $tsquery = "timestamp >= $start AND timestamp <= $end AND";
}
$query = false;
 
if ( ($st = @stat("data.cache.xml")) != false ) {
  if ($st['mtime'] < ( time(0) - 900 )) {
    $query = true;
  }
} else {
  $query = true;
}
 
if ( $query == true && $err == 0) {
  $f = fopen("/tmp/data.cache.xml","w+");  // change this path
  $mytime = time(0) - 24 * 3600 * 9;

  $query = "SELECT DISTINCT attacks.source, COUNT(attacks.source) as count FROM sensors, attacks WHERE $orgquery attacks.sensorid = sensors.id AND $tsquery attacks.severity = '1'  AND sensors.id = attacks.sensorid GROUP BY attacks.source ORDER BY count DESC"; 
  $r_hit = pg_query($pgconn, $query);
  if (pg_num_rows($r_hit)) {
    fwrite($f,'<?xml version="1.0" encoding="ISO-8859-1"?>');
    fwrite($f,"\n");
    fwrite($f,"<markers>\n");
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
      $line ='   <marker lat="'.$lat.'" lng="'.$lng.'" count="'.$count.'" country="'.$country.'" city="'.$city.'" />'."\n";
      fwrite($f,$line);
    }		
    fwrite($f,"</markers>\n");
  }
  fclose($f);
} else {
  $f = fopen("/tmp/data.cache.xml","w+");  // change this path
  $mytime = time(0) - 24 * 3600 * 9;
  fwrite($f,"<markers>\n");
  fwrite($f,"</markers>\n");
  fclose($f);
}

$f = fopen("/tmp/data.cache.xml","r");
$contents = fread($f, filesize("/tmp/data.cache.xml"));
trim($contents);
echo $contents; 
fclose($f);
?>
