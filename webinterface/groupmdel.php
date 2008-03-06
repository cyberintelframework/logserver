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

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_sid",
		"int_gid",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (!isset($clean['sid']) ) {
  $m = 117;
  $err = 1;
} else {
  $sid = $clean['sid'];
}

if (!isset($clean['gid']) ) {
  $m = 117;
  $err = 1;
} else {
  $gid = $clean['gid'];
}

# Checking access
if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_user < 9) {
  $sql_check = "SELECT id FROM groups WHERE id = $gid AND owner = '$s_org'";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_gid = pg_num_rows($result_check);

  $sql_check = "SELECT id FROM sensors WHERE id = $sid AND organisation = '$s_org'";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_sid = pg_num_rows($result_check);

  # Don't allow deletion if:
  # The sensor is not owned by the organisation of this user
  # AND
  # The group is not owned by the organisation of this user
  # Either one of these has to be > 0
  if ($numrows_gid == 0 && $numrows_sid == 0) {
    $m = 101;
    $err = 1;
  }
}

if ($err == 0) {
  $sql = "DELETE FROM groupmembers WHERE groupid = '$gid' AND sensorid = '$sid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  $sql = "SELECT name, type FROM groups WHERE id = '$gid'";
  $result = pg_query($pgconn, $sql);
  $row = pg_fetch_assoc($result);
  $name = $row['name'];
  $type = $row['type'];

  if ($type == 0) {
    $ts = date("U");
    $sql = "INSERT INTO sensors_log (sensorid, timestamp, logid, args) ";
    $sql .= " VALUES ('$sid', '$ts', 16, '%group% = $name')";
    $execute = pg_query($pgconn, $sql);
  }
  
  $m = 2;
} else {
  echo "ERROR\n";
  geterror($m, 1);
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>
