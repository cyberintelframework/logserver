<?php

####################################
# SURFnet IDS                      #
# Version 1.02.07                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.07 Removed intval() for $s_access
# 1.02.06 intval() for s_org, s_admin and s_access and pg_escape_string for $action
# 1.02.05 Fixed an SQL injection vulnerability
# 1.02.04 Added a failsafe to prevent users access when the header redirection fails
# 1.02.03 Added login check
# 1.02.02 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $absfile = $_SERVER['SCRIPT_NAME'];
  $file = basename($absfile);
  $dir = str_replace($file, "", $absfile);
  $dir = ltrim($dir, "/");
  $https = $_SERVER['HTTPS'];
  if ($https == "") {
    $http = "http";
  }
  else {
    $http = "https";
  }
  $servername = $_SERVER['SERVER_NAME'];
  $address = "$http://$servername:$web_port/$dir";
  header("location: ${address}login.php");
  $err = 1;
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = $s_access{0};

if ($s_access_sensor == 0) {
  $m = 90;
  header("location: sensorstatus.php?selview=$selview&m=$m");
  $err = 1;
  exit;
}
if ($err != 1) {
  if ($s_admin == 1) {
    $sql_sensors = "SELECT keyname, ssh FROM sensors";
  }
  else {
    $sql_sensors = "SELECT keyname, ssh FROM sensors WHERE organisation = $s_org";
  }
  $result_sensors = pg_query($pgconn, $sql_sensors);

  if (isset($_GET['selview'])) {
    $selview = intval($_GET['selview']);
  }

  while ($row = pg_fetch_assoc($result_sensors)) {
    $keyname = pg_escape_string($row['keyname']);
    $ssh = $row['ssh'];
    $formkey = "f_" . $keyname;
    if ($s_admin == 1) {
      $serverkey = "server_" . $keyname;
      $server = pg_escape_string(stripinput($_POST[$serverkey]));
    }
    $action = pg_escape_string(stripinput($_POST[$formkey]));
    $tapkey = "tapip_" . $keyname;
    if (isset($_POST[$tapkey])) {
      $tapip = pg_escape_string(stripinput($_POST[$tapkey]));
      if (preg_match($ipregexp, $tapip)) {
        $sql_checkip = "SELECT tapip FROM sensors WHERE tapip = '$tapip' AND NOT keyname = '$keyname'";
        $result_checkip = pg_query($pgconn, $sql_checkip);
        $checkip = pg_num_rows($result_checkip);
        if ($checkip > 0) {
          $m = 91;
          break;
        }
        else {
          $sql_updatestatus = "UPDATE sensors SET action = '$action', tapip = '$tapip' WHERE keyname = '$keyname'";
          $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
          $m = 1;
        }
      }
      else {
        $m = 92;
        break;
      }
    }
    else {
      $sql_updatestatus = "UPDATE sensors SET action = '" .$action. "' WHERE keyname = '$keyname'";
      $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
      $m = 1;
    }
  }
}
if ($m != 1) {
  header("location: sensorstatus.php?selview=$selview&m=$m&key=$keyname");
}
else {
  header("location: sensorstatus.php?selview=$selview&m=$m");
}
pg_close($pgconn);
?>
