<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 08-01-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

header("Content-type: text/xml");
$fn = "SURFnet_IDMEF_" . date("d-m-Y_H:i:s") . "_" . ucfirst($_SESSION['s_user']) . ".xml";
#header("Content-disposition: attachment; filename=$fn");

$orderby = "ORDER BY keyname ASC";
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

$allowed_get = array(
                "sort",
                "int_selview"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Link tables tap and mac with the associated sensor
if ($s_access_sensor < 9) {
  $where = "WHERE organisation = " . $s_org;
  $and = "AND";
} else {
  $where = "";
  $and = "WHERE";
}

if (isset($tainted['sort'])) {
  $sort = $tainted['sort'];
  $pattern = '/^(tap|lastupdate|laststart|sensor)$/';
  if (!preg_match($pattern, $sort)) {
    $sort = "sensor";
  }
  if ($sort == "tap") {
    $orderby = "ORDER BY tap ASC";
  } elseif ($sort == "lastupdate") {
    $orderby = "ORDER BY lastupdate ASC";
  } elseif ($sort == "laststart") {
    $orderby = "ORDER BY laststart ASC";
  } elseif ($sort == "sensor") {
    $orderby = "ORDER BY keyname ASC";
  }
}

if (isset($clean['selview'])) {
  $selview = $clean['selview'];
} elseif (isset($c_selview)) {
  $selview = intval($c_selview);
}

if ($selview == "0") {
  $sql_sensors = "SELECT * FROM sensors $where $orderby";
} elseif ($selview == "1") {
  $sql_sensors = "SELECT * FROM sensors $where $and status = 0 $orderby";
} elseif ($selview == "2") {
  $sql_sensors = "SELECT * FROM sensors $where $and status = 1 $orderby";
} elseif ($selview == "3") {
  $now = time();
  $upd = $now - 3600;
  $sql_sensors = "SELECT * FROM sensors $where $and lastupdate < $upd AND NOT status = 0 $orderby";
} else {
  $sql_sensors = "SELECT * FROM sensors $where $orderby";
}
$debuginfo[] = $sql_sensors;
$result_sensors = pg_query($pgconn, $sql_sensors);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<statusinfo>";
while ($row = pg_fetch_assoc($result_sensors)) {
  $now = time();
  $sid = $row['id'];
  $sensor = $row['keyname'];
  $remote = $row['remoteip'];
  $local = $row['localip'];
  $tap = $row['tap'];
  $tapip = $row['tapip'];
  $update = $row['lastupdate'];
  $start = $row['laststart'];
  $stop = $row['laststop'];
  $mac = $row['mac'];
  $action = $row['action'];
  $ssh = $row['ssh'];
  $status = $row['status'];
  $uptime = $row['uptime'];
  $server = $row['server'];
  $netconf = $row['netconf'];
  $vlanid = $row['vlanid'];
  $diffstart = 0;
  $diffupdate = 0;
  if ($update != "") {
    $diffupdate = $now - $update;
  }
  if (!empty($start)) {
    $diffstart = $now - $start;
  }
  if ($s_access_sensor == 9) {
    $org = $row['organisation'];
    $sql_getorg = "SELECT organisation FROM organisations WHERE id = " .$org;
    $debuginfo[] = $sql_getorg;
    $result_getorg = pg_query($pgconn, $sql_getorg);
    $org = pg_result($result_getorg, 0);
  }
  if ($status == 1) {
    $uptime = $diffstart + $uptime;
  }

  echo "<sensor id=\"$sid\">\n";
  echo "  <name>$sensor</name>\n";
  echo "  <remoteip>$remote</remoteip>\n";
  echo "  <localip>$local</localip>\n";
  echo "  <interface>";
  echo "    <name>$tap</name>";
  echo "    <address>$tapip</address>";
  echo "    <mac>$mac</mac>";
  echo "  </interface>";
  echo "  <timestamps>";
  echo "    <start>$start</start>";
  echo "    <stop>$stop</stop>";
  echo "    <update>$update</update>";
  echo "  </timestamps>";
  echo "  <uptime>$uptime</uptime>";
  echo "  <status>$status</status>";
  echo "  <organisation>$org</organisation>";
  echo "</sensor>";
}
echo "</statusinfo>\n";

#debug_sql();
?>

