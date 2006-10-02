<?php

#########################################
# SURFnet IDS    
# Version 1.02.03
# 08-09-2006     
# Jan van Lith & Kees Trippelvitz
#########################################

#############################################
# Changelog:
# 1.02.03 Added setcookie, and session sql query
# 1.02.02 added session_unset() and session_destroy()
# 1.02.01 Initial release
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';

$remoteip = pg_escape_string($_SERVER['REMOTE_ADDR']);
if (preg_match($ipregexp, $remoteip)) {
  $sql_del_session = "DELETE FROM sessions WHERE ip = '$remoteip'";
  $result_del_session = pg_query($sql_del_session);
}

$_SESSION['s_org'] = NULL;
$_SESSION['s_access'] = NULL;
$_SESSION['s_admin'] = NULL;
$_SESSION['s_user'] = NULL;
$_SESSION['s_userid'] = NULL;
$_SESSION['search_num_rows'] = NULL;
$_SESSION['s_total_search_records'] = NULL;

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (isset($_COOKIE[session_name()])) {
   setcookie(session_name(), '', time()-42000, '/');
}

session_unset();
session_destroy();
$_SESSION = array();

header("location: login.php");
?>
