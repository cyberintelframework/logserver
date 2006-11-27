<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 10-10-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.03.01 Released as part of the 1.03 package
# 1.02.02 Added some more input checks + login check
# 1.02.01 Initial release
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$err = 0;

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

$f_server = stripinput(trim(pg_escape_string($_POST['f_server'])));
if ($f_server == "") {
  $err = 1;
  $m = 31;
}

if ($err != 1) {
  $sql_save = "INSERT INTO servers (server) VALUES ('$f_server')";
  $execute_save = pg_query($pgconn, $sql_save);
  $m = 6;
}
pg_close($pgconn);
header("location: serveradmin.php?m=$m");
?>
