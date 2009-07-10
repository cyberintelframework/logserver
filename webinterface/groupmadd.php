<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 23-06-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added check on organisation sensors add
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
                "int_org",
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
  $org = 0;
} elseif (isset($clean['org']) && $s_access_user > 1) {
  $org = $clean['org'];
  $sid = 0;
} else {
  $m = 110;
  $err = 1;
}

if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
}

if ($err != 1 && $sid != 0) {
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
  $sql = "SELECT owner FROM groups WHERE id = '$gid'";
  $debuginfo[] = $sql;
  $result_check = pg_query($pgconn, $sql);
  $row_check = pg_fetch_assoc($result_check);
  $owner = $row_check['owner'];
  if ($owner != $s_org && $s_access_user != 9) {
    $m = 101;
    $err = 1;
  }
}

header("Content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($err != 1) {
  if ($org == 0) {
    # Inserting member
    $sql = "INSERT INTO groupmembers (groupid, sensorid) ";
    $sql .= "VALUES ('$gid', '$sid')";
    $debuginfo[] = $sql;
    $execute = pg_query($pgconn, $sql);
    $m = 1;

    # Getting sensor info
    $sql = "SELECT keyname, label, vlanid";
    $sql .= " FROM sensors ";
    $sql .= " WHERE id = '$sid' ";
    $debuginfo[] = $sql;
    $result = pg_query($pgconn, $sql);
    $row = pg_fetch_assoc($result);

    $keyname = $row['keyname'];
    $vlanid = $row['vlanid'];
    $label = $row['label'];
    $sensor = sensorname($keyname, $vlanid, $label);

    # Getting group info
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

    echo "<result>";
      echo "<status>OK</status>";
      echo "<error>" .$v_errors[$m]. "</error>";
      echo "<data>";
        echo "<sensor sid=\"$sid\" name=\"$sensor\" />";
        echo "<group gid=\"$gid\">";
          echo "<name>$name</name>";
          echo "<owner>$owner</owner>";
          echo "<members>$members</members>";
        echo "</group>";
      echo "</data>";
    echo "</result>";
  } else {
    $m = 1;
    echo "<result>";
      echo "<status>OK</status>";
      echo "<data>";

        if ($s_access_user < 9) {
          $sql = "SELECT id, keyname, vlanid, label FROM sensors WHERE organisation = '$org'";
        } else {
          $sql = "SELECT id, keyname, vlanid, label FROM sensors WHERE organisation = '$s_org'";
        }
        $debuginfo[] = $sql;
        $result = pg_query($pgconn, $sql);
 
        $count = 0;
        while($row = pg_fetch_assoc($result)) {
          $sid = $row['id'];
          $keyname = $row['keyname'];
          $vlanid = $row['vlanid'];
          $label = $row['label'];
          $sensor = sensorname($keyname, $vlanid, $label);

          $sql_check = "SELECT sensorid FROM groupmembers WHERE groupid = $gid AND sensorid = $sid";
          $debuginfo[] = $sql_check;
          $c = pg_query($pgconn, $sql_check);
          $chk = pg_num_rows($c);
          if ($chk == 0) {
            $count++;
            $sql_insert = "INSERT INTO groupmembers (groupid, sensorid) ";
            $sql_insert .= "VALUES ('$gid', '$sid')";
            $debuginfo[] = $sql_insert;
            $ec = pg_query($pgconn, $sql_insert);

            echo "<sensor sid=\"$sid\" name=\"$sensor\" />";
          }
        }

        # Getting group info
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
 
        echo "<group gid=\"$gid\">";
          echo "<name>$name</name>";
          echo "<owner>$owner</owner>";
          echo "<members>$members</members>";
        echo "</group>";
      echo "</data>";
      $error = str_replace("%1%", $count, $v_errors[11]);
      if ($count == 1) {
        $error = str_replace("%2%", "record", $error);
      } else {
        $error = str_replace("%2%", "records", $error);
      }
      echo "<error>" .$error. "</error>";
    echo "</result>";
  }
} else {
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
  echo "</result>";
}

#echo "M: $m<br />\n";
# Close connection and redirect
pg_close($pgconn);
#$c_debug_sql = 1;
#debug_sql();
#header("location: groupadmin.php?int_m=$m");
?>
