<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 26-07-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.03 Added some more input checks
# 1.02.02 Added logged in check
# 1.02.01 Initial release
#############################################

#########################################################################
# Changelog:
# 1.02.02 Added login check
# 1.02.01 Initial release
#########################################################################

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
  exit;
}

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];
$s_userid = $_SESSION['s_userid'];
$err = 0;

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

$f_server = trim(pg_escape_string($_POST['f_server']));
$f_server = stripinput($f_server);
if ($f_server == "") {
  $err = 1;
  $m = 93;
}

if ($err != 1) {
  $sql_save = "INSERT INTO servers (server) VALUES ('$f_server')";
  $execute_save = pg_query($pgconn, $sql_save);
  $m = 10;
}
pg_close($pgconn);
header("location: serveradmin.php?m=$m");
?>
