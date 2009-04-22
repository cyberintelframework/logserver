<?php

####################################
# SURFids 3.00                     #
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
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$q_org = intval($_SESSION['q_org']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);

# Retrieving posted variables from $_POST
$allowed_post = array(
                "mac_macaddr",
                "ip_ipaddr",
                "int_sid",
    	    	"md5_hash",
	    	    "type",
                "int_all"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();

# Checking $_POST'ed and $_GET'ed variables
if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $sid = 0;
}
if ($s_access_sensor < 2) {
  $err = 1;
  $m = 101;
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

if (isset($clean['macaddr'])) {
  $mac = $clean['macaddr'];
} else {
  $err = 1;
  $m = 120;
}

if (isset($clean['ipaddr'])) {
  $ip = $clean['ipaddr'];
} else {
  $err = 1;
  $m = 121;
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];
  if ($sid == 0) {
    $err = 1;
    $m = 110;
  }
} else {
  $err = 1;
  $m = 110;
}

if ($err == 0) {
  $sql = "SELECT mac FROM arp_static WHERE sensorid = $sid AND ip = '$ip'";
  $debuginfo[] = $sql;
  $result_check = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_check);
  if ($rows == 1) {
    $err = 1;
    $m = 122;
  } 
}
if ($q_org != 0) {
  $sql = "SELECT keyname FROM sensors WHERE id = $sid AND organisation = $q_org";
  $debuginfo[] = $sql;
  $result_user = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_user);
  if ($rows == 0) {
    $err = 1;
    $m = 101;
  }
}
if ($err != 1) {
  $sql_check = "SELECT mac FROM arp_static WHERE mac = '$mac' AND sensorid = '$sid' AND ip = '$ip' ";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 1) {
    $m = 123;
    $err = 1;
  }
}

if (isset($clean['all'])) {
  $all = $clean['all'];
} else {
  $all = 0;
}

if ($err != 1) {
  if ($all == 0) {
    # No errors found, insert record (including the host type)
    $sql = "INSERT INTO arp_static (ip, mac, sensorid) ";
    $sql .= "VALUES ('$ip', '$mac', '$sid')";
    $debuginfo[] = $sql;
    $execute = pg_query($pgconn, $sql);

    if (isset($tainted['type'])) {
      $type = $tainted['type'];
      $sql = "SELECT id FROM arp_static WHERE ip = '$ip' AND mac = '$mac' AND sensorid = '$sid'";
      $debuginfo[] = $sql;
      $result = pg_query($pgconn, $sql);
      $row = pg_fetch_assoc($result);
      $id = $row['id'];

      foreach ($type as $key => $val) {
        $pattern = '/^(1|2|3|4)$/';
        if (preg_match($pattern, $val)) {
          $sql = "INSERT INTO sniff_hosttypes (staticid, type) VALUES ('$id', '$val')";
          $debuginfo[] = $sql;
          $execute = pg_query($pgconn, $sql);
        }
      }
    }
  } else {
    $sql_getkey = "SELECT keyname FROM sensors WHERE id = '$sid'";
    $debuginfo[] = $sql_getkey;
    $result = pg_query($pgconn, $sql_getkey);
    $row = pg_fetch_assoc($result);
    $keyname = $row['keyname'];
    if ($keyname != "") {
      # Get all the VLAN's of the specific keyname
      $sql_vlan = "SELECT id FROM sensors WHERE keyname = '$keyname'";
      $debuginfo[] = $sql_getvlan;
      $result_vlan = pg_query($pgconn, $sql_vlan);
      while ($row_vlan = pg_fetch_assoc($result_vlan)) {
        $mysid = $row_vlan['id'];

        ###########################
        # Do all the usual arp_static stuff
        ###########################

        # No errors found, insert record (including the host type)
        $sql = "INSERT INTO arp_static (ip, mac, sensorid) ";
        $sql .= "VALUES ('$ip', '$mac', '$mysid')";
        $debuginfo[] = $sql;
        $execute = pg_query($pgconn, $sql);

        if (isset($tainted['type'])) {
          $type = $tainted['type'];
          $sql = "SELECT id FROM arp_static WHERE ip = '$ip' AND mac = '$mac' AND sensorid = '$mysid'";
          $debuginfo[] = $sql;
          $result = pg_query($pgconn, $sql);
          $row = pg_fetch_assoc($result);
          $id = $row['id'];

          foreach ($type as $key => $val) {
            $pattern = '/^(1|2|3|4)$/';
            if (preg_match($pattern, $val)) {
              $sql = "INSERT INTO sniff_hosttypes (staticid, type) VALUES ('$id', '$val')";
              $debuginfo[] = $sql;
              $execute = pg_query($pgconn, $sql);
            }
          }
        }
        ################ END #########
      }
    }
  }

  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
debug_sql();
header("location: arp_static.php?int_m=$m&int_sid=$sid");
?>
