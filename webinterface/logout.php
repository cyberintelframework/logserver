<?php

#########################################
# SURFnet IDS    
# Version 1.04.02
# 06-11-2006     
# Jan van Lith & Kees Trippelvitz
#########################################

#############################################
# Changelog:
# 1.04.02 Changed data input handling
# 1.04.01 Added pg_close
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added setcookie, and session sql query
# 1.02.02 added session_unset() and session_destroy()
# 1.02.01 Initial release
#############################################

session_start();
header("Cache-control: private");

include '../include/config.inc.php';
include '../include/connect.inc.php';

# Deleting the session info from the database
$remoteip = pg_escape_string($_SERVER['REMOTE_ADDR']);
$ipregexp = '/^([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
$ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
$ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))';
$ipregexp .= '\.([0-9]|[1-9][0-9]|1([0-9][0-9])|2([0-4][0-9]|5[0-5]))$/';
if (preg_match($ipregexp, $remoteip)) {
  $sql_del_session = "DELETE FROM sessions WHERE ip = '$remoteip'";
  $result_del_session = pg_query($sql_del_session);
}

# Resetting all session variables to NULL
$_SESSION['s_org'] = NULL;
$_SESSION['s_access'] = NULL;
$_SESSION['s_admin'] = NULL;
$_SESSION['s_user'] = NULL;
$_SESSION['s_userid'] = NULL;
$_SESSION['s_hash'] = NULL;
$_SESSION['search_num_rows'] = NULL;
$_SESSION['s_total_search_records'] = NULL;

# If it's desired to kill the session, also delete the session cookie.
# Note: This will destroy the session, and not just the session data!
if (isset($_COOKIE[session_name()])) {
   setcookie(session_name(), '', time()-42000, '/');
}

# Some precautions if the previous steps didn't work
session_unset();
session_destroy();
$_SESSION = array();

# Close the connection to the database and redirect us to the login page
pg_close($pgconn);
header("location: login.php");
?>
