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
		"md5_hash",
		"intcsv_members"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking if the logged in user actually requested this action.
if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (!isset($clean['members'])) {
  if (!isset($clean['sid']) ) {
    $m = 117;
    $err = 1;
  } else {
    $sid = $clean['sid'];
  }
} else {
  $sid = 0;
  $sid_csv = $clean['members'];
}

if (!isset($clean['gid']) ) {
  $m = 150;
  $err = 1;
} else {
  $gid = $clean['gid'];
}

# Checking access
if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_user < 9) {
  if ($sid != 0) {
    $sql_check_groups = "SELECT id FROM groups WHERE id = $gid AND owner = '$s_org'";
    $sql_check_sensors = "SELECT id FROM sensors WHERE id = $sid AND organisation = '$s_org'";
  } else {
    $sql_check_groups = "SELECT id FROM groups WHERE id IN ($sid_csv) AND owner = '$s_org'";
    $sql_check_sensors = "SELECT id FROM sensors WHERE id IN ($sid_csv) AND organisation = '$s_org'";
  }
  $debuginfo[] = $sql_check_groups;
  $debuginfo[] = $sql_check_sensors;

  $result_check = pg_query($pgconn, $sql_check_groups);
  $numrows_gid = pg_num_rows($result_check_groups);

  $result_check = pg_query($pgconn, $sql_check_sensors);
  $numrows_sid = pg_num_rows($result_check_sensors);

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

header("Content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($err == 0) {
  if ($sid == 0) {
    $sql = "DELETE FROM groupmembers WHERE groupid = '$gid' AND sensorid IN ($sid_csv)";
  } else {
    $sql = "DELETE FROM groupmembers WHERE groupid = '$gid' AND sensorid = '$sid'";
  }
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  $sql = "SELECT groups.id, name, organisation FROM groups, organisations WHERE groups.id = '$gid' AND organisations.id = groups.owner";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $row_group = pg_fetch_assoc($result);

  $name = $row_group['name']; 
  $owner = $row_group['organisation'];

  $sql_count = "SELECT COUNT(id) as total FROM groupmembers WHERE groupid = '$gid'";
  $debuginfo[] = $sql_count;
  $result_count = pg_query($pgconn, $sql_count);
  $rowmembers = pg_fetch_assoc($result_count);
  $members = $rowmembers['total'];

  $m = 2;
  echo "<result>";
    echo "<status>OK</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
    echo "<data>";
      echo "<members>";
        if ($sid == 0) {
          $ar_members = split(",", $sid_csv);
          foreach ($ar_members as $key => $mid) {
            echo "<memberid>$mid</memberid>";
          }
        } else {
          echo "<memberid>$sid</memberid>";
        }
      echo "</members>";
      echo "<group gid=\"$gid\">";
        echo "<name>$name</name>";
        echo "<owner>$owner</owner>";
        echo "<members>$members</members>";
      echo "</group>";
    echo "</data>";
  echo "</result>";
} else {
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
    echo "<errno>" .$m. "</errno>";
  echo "</result>";
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>
