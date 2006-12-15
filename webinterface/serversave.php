<?php

####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 15-12-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.04.02 Changed data input handling
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.02 Added some more input checks + login check
# 1.02.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$err = 0;

$allowed_post = array(
                "strip_html_escape_server"
);
$check = extractvars($_POST, $allowed_post);
debug_input();

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

$f_server = $clean['server'];
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
header("location: serveradmin.php?int_m=$m");
?>
