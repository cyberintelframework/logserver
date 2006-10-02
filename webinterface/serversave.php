<?php

####################################
# SURFnet IDS                      #
# Version 1.02.02                  #
# 28-07-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.02 Added some more input checks + login check
# 1.02.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
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

$f_server = stripinput(trim(pg_escape_string($_POST['f_server'])));
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
