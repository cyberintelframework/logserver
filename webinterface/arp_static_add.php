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
$type = "none";

# Retrieving posted variables from $_POST
$allowed_post = array(
                "mac_mac",
                "ip_ip",
                "ipv6_ip",
                "int_sid",
    	    	"md5_hash",
	    	    "strip_html_type",
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

if (isset($clean['type'])) {
  $type = $clean['type'];
} else {
  $err = 1;
  $m = 118;
}

if ($err == 0 && $type == "arp") {
  if (isset($clean['mac'])) {
    $mac = $clean['mac'];
  } else {
    $err = 1;
    $m = 120;
  }
}

if (isset($clean['ip'])) {
  $ip = $clean['ip'];
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

if ($err == 0 && $type == "arp") {
  # Checking for poisoned address
  $sql = "SELECT mac FROM arp_cache WHERE sensorid = $sid AND ip = '$ip'";
  $debuginfo[] = $sql;
  $result_check = pg_query($pgconn, $sql);
  $rows = pg_num_rows($result_check);
  if ($rows == 1) {
    $row = pg_fetch_assoc($result_check);
    $chkmac = $row['mac'];

    if ($mac != $chkmac) {
      $err = 1;
      $m = 122;
    }
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
  if ($type == "arp") {
    $sql_check = "SELECT id FROM arp_static WHERE mac = '$mac' AND sensorid = '$sid' AND ip = '$ip'";
  } elseif ($type == "dhcp") {
    $sql_check = "SELECT id FROM dhcp_static WHERE sensorid = '$sid' AND ip = '$ip'";
  } elseif ($type == "ipv6") {
    $sql_check = "SELECT id FROM ipv6_static WHERE sensorid = '$sid' AND ip = '$ip'";
  }
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
  if ($type == "arp") {
    $sql = "INSERT INTO arp_static (ip, mac, sensorid) VALUES ('$ip', '$mac', '$sid')";
    $debuginfo[] = $sql;
    $execute = pg_query($pgconn, $sql);
  } elseif ($type == "dhcp") {
    if ($all == 1) {
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
          # No errors found, insert record (including the host type)
          $sql = "INSERT INTO dhcp_static (ip, sensorid) VALUES ('$ip', '$mysid')";
          $debuginfo[] = $sql;
          $execute = pg_query($pgconn, $sql);
        }
      }
    } else {
      $sql = "INSERT INTO dhcp_static (ip, sensorid) VALUES ('$ip', '$sid')";
      $debuginfo[] = $sql;
      $execute = pg_query($pgconn, $sql);
    }
  } elseif ($type == "ipv6") {
    $sql = "INSERT INTO ipv6_static (ip, sensorid) VALUES ('$ip', '$sid')";
    $debuginfo[] = $sql;
    $execute = pg_query($pgconn, $sql);
  }
  $m = 1;
}

# Close connection and redirect
#debug_sql();
pg_close($pgconn);
header("location: arp_static.php?int_m=$m&int_sid=$sid");
?>
