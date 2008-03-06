<?php

####################################
# SURFnet IDS 2.10.00              #
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
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_gid",
                "int_sid",
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
if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $m = 110;
  $err = 1;
}

if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
}

if ($err != 1) {
  $sql = "SELECT id FROM groupmembers WHERE sensorid = '$sid' AND groupid = '$gid'";
  $debuginfo[] = $sql;
  $result_user = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_user);
  if ($rows == 1) {
    $m = 151;
    $err = 1;
  }
}

if ($err != 1) {
  $sql = "SELECT type, owner FROM groups WHERE id = '$gid'";
  $debuginfo[] = $sql;
  $result_check = pg_query($pgconn, $sql);
  $row_check = pg_fetch_assoc($result_check);
  $type = $row_check['type'];
  $owner = $row_check['owner'];
  if ($type == 0 && $owner != $s_org && $s_access_user != 9) {
    $m = 101;
    $err = 1;
  }
}

if ($err != 1) {
  $sql = "INSERT INTO groupmembers (groupid, sensorid) ";
  $sql .= "VALUES ('$gid', '$sid')";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  $m = 1;

  $sql = "SELECT keyname, label, vlanid, sensorid, groups.owner, organisations.id as orgid, organisations.organisation, groups.type";
  $sql .= " FROM sensors, groupmembers, groups, organisations ";
  $sql .= " WHERE groupid = '$gid' AND sensorid = '$sid' AND sensorid = sensors.id ";
  $sql .= " AND groupmembers.groupid = groups.id AND sensors.organisation = organisations.id";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $row = pg_fetch_assoc($result);

  $sid = $row['sensorid'];
  $keyname = $row['keyname'];
  $vlanid = $row['vlanid'];
  $label = $row['label'];
  $sensor = sensorname($keyname, $vlanid, $label);
  $owner = $row['owner'];
  $org = $row['orgid'];
  $orgname = $row['organisation'];
  $type = $row['type'];

  if ($type == 0) {
    $ts = date("U");
    $sql = "INSERT INTO sensors_log (sensorid, timestamp, logid, args) ";
    $sql .= " VALUES ('$sid', '$ts', 15, '%group% = $name')";
    $execute = pg_query($pgconn, $sql);
  }

  echo "<tr id='sensor$sid'>\n";
    echo "<td>$sensor - $orgname</td>\n";
    echo "<td>";
      if ($org == $s_org || $s_access_user == 9 || $owner == $s_org) {
        echo "[<a onclick=\"submitform('', 'groupmdel.php?int_gid=$gid&int_sid=$sid&md5_hash=$s_hash', 'u', 'sensor$sid');\">" .$l['g_remove_l']. "</a>]\n";
      }
    echo "</td>\n";
  echo "</tr>\n";
} else {
  echo "ERROR\n";
  geterror($m, 1);
}

#echo "M: $m<br />\n";
# Close connection and redirect
pg_close($pgconn);
#$c_debug_sql = 1;
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>
