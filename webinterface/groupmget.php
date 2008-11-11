<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';
include "../lang/${c_language}.php";

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
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_GET
$allowed_get = array(
        "int_gid",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Checking if the logged in user actually requested this action.                                    
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking MD5sums
if (isset($clean['gid'])) {
  $gid = $clean['gid'];
} else {
  $m = 150;
  $err = 1;
}

if ($s_access_sensor < 2) {
  $m = 101;
  $err = 1;
}

if ($err != 1) {
  $sql = "SELECT owner FROM groups WHERE id = '$gid'";
  $debuginfo[] = $sql;
  $result_check = pg_query($pgconn, $sql);
  $row_check = pg_fetch_assoc($result_check);
  $owner = $row_check['owner'];
  if ($owner != $s_org && $s_access_sensor != 9) {
    $m = 101;
    $err = 1;
  }
}

header("Content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($err != 1) {
  $sql = "SELECT sensorid, sensors.keyname, sensors.vlanid, label FROM groupmembers, sensors WHERE sensorid = sensors.id AND groupid = '$gid'";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);

  echo "<result>";
    echo "<status>OK</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
    echo "<data>";
      echo "<gid>$gid</gid>";
      while ($row = pg_fetch_assoc($result)) {
        $sid = $row['sensorid'];
        $keyname = $row['keyname'];
        $vlanid = $row['vlanid'];
        $label = $row['label'];
        $sensor = sensorname($keyname, $vlanid, $label);
        echo "<sensor sid=\"$sid\" name=\"$sensor\" />";
      }
    echo "</data>";
  echo "</result>";
} else {
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
    echo "<info>" .$s_access_sensor. "</info>";
  echo "</result>";
}

#echo "M: $m<br />\n";
# Close connection and redirect
pg_close($pgconn);
#$c_debug_sql = 1;
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>
