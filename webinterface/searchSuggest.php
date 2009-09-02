<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Replaced addslashes with pg_escape_string
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

# Retrieving posted variables from $_GET
$allowed_get = array(
        "search",
	"int_type"
);
$check = extractvars($_GET, $allowed_get);

$type = $clean['type'];
if (isset($tainted['search']) && $tainted['search'] != '') {
  $q = pg_escape_string($tainted['search']);
  if ($c_autocomplete == 1) {
    if ($s_access_search != 9) {
      $sql_smac1 = "SELECT DISTINCT src_mac FROM attacks, sensors WHERE attacks.sensorid = sensors.id AND sensors.organisation = $s_org AND src_mac::char varying LIKE '$q%' LIMIT $c_suggest_limit ";
      $sql_smac2 = "SELECT DISTINCT mac FROM sensors WHERE sensors.organisation = $s_org AND mac::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_dmac = "SELECT DISTINCT dst_mac FROM attacks, sensors WHERE attacks.sensorid = sensors.id AND sensors.organisation = $s_org AND dst_mac::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_dda = "SELECT DISTINCT dest FROM attacks, sensors WHERE attacks.sensorid = sensors.id AND sensors.organisation = $s_org AND dest::char varying LIKE '$q%' LIMIT $c_suggest_limit ";
      $sql_dsa = "SELECT DISTINCT source FROM attacks, sensors WHERE attacks.sensorid = sensors.id AND sensors.organisation = $s_org AND source::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_vir = "SELECT name FROM stats_virus WHERE name LIKE '$q%' LIMIT $c_suggest_limit";

      $sql_files = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
        $sql_files .= "(SELECT split_part(details.text, '/', 4) as file FROM details, sensors ";
        $sql_files .= "WHERE NOT split_part(details.text, '/', 4) = '' AND type = 4 AND split_part(details.text, '/', 4) LIKE '$q%' AND sensors.id = details.sensorid ";
        $sql_files .= "AND sensors.organisation = $s_org) as sub ";
      $sql_files .= "GROUP BY sub.file LIMIT $c_suggest_limit";
    } else {
      $sql_smac1 = "SELECT DISTINCT src_mac FROM attacks WHERE src_mac::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_smac2 = "SELECT DISTINCT mac FROM sensors WHERE mac::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_dmac = "SELECT DISTINCT dst_mac FROM attacks WHERE dst_mac::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_dda = "SELECT DISTINCT dest FROM attacks WHERE dest::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_dsa = "SELECT DISTINCT source FROM attacks WHERE source::char varying LIKE '$q%' LIMIT $c_suggest_limit";
      $sql_vir = "SELECT name FROM stats_virus WHERE name LIKE '$q%' LIMIT $c_suggest_limit";

      $sql_files = "SELECT DISTINCT sub.file, COUNT(sub.file) as total FROM ";
        $sql_files .= "(SELECT split_part(details.text, '/', 4) as file FROM details, sensors ";
        $sql_files .= "WHERE NOT split_part(details.text, '/', 4) = '' AND type = 4 AND split_part(details.text, '/', 4) LIKE '$q%') as sub ";
      $sql_files .= "GROUP BY sub.file LIMIT $c_suggest_limit";
    }
    $debuginfo[] = $sql_smac;
    $debuginfo[] = $sql_dmac;
    $debuginfo[] = $sql_vir;
    $debuginfo[] = $sql_files;
    $debuginfo[] = $sql_dsa;
    $debuginfo[] = $sql_dda;

    if ($type == "1") {
      $result = pg_query($pgconn, $sql_dda);
      while($row = pg_fetch_assoc($result)) {
        $dest = $row['dest'];
        echo "$dest\n";
      }
    }
    if ($type == "2") {
      $result = pg_query($pgconn, $sql_dmac);
      while($row = pg_fetch_assoc($result)) {
        $mac = $row['dst_mac'];
        echo "$mac\n";
      }
    }
    if ($type == "3") {
      $result = pg_query($pgconn, $sql_dsa);
      while($row = pg_fetch_assoc($result)) {
        $source = $row['source'];
        echo "$source\n";
      }
    }
    if ($type == "4") {
      $result = pg_query($pgconn, $sql_smac1);
      while($row = pg_fetch_assoc($result)) {
        $mac = $row['src_mac'];
        echo "$mac\n";
      }
      $result = pg_query($pgconn, $sql_smac2);
      while($row = pg_fetch_assoc($result)) {
        $mac = $row['mac'];
        echo "$mac\n";
      }
    }
    if ($type == "5") {
      $result = pg_query($pgconn, $sql_vir);
      while($row = pg_fetch_assoc($result)) {
        $name = $row['name'];
        echo "$name\n";
      }
    }
    if ($type == "6") {
      $result = pg_query($pgconn, $sql_files);
      while($row = pg_fetch_assoc($result)) {
        $file = $row['file'];
        echo "$file\n";
      }
    }
  }
}

// Set output to "no suggestion" if no hint were found
// or to the correct values
if ($mac == "" && $file == "" && $name == "" && $source == "" && $dest == "") {
  $response="";
}

//output the response
echo "$response\n";
?>
