<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.04 Changed stuff relating to the reports (report_content)
# 1.04.03 Changed data input handling
# 1.04.02 Added debug info
# 1.04.01 Released as 1.04.01
# 1.03.02 Removed and changed some stuff referring to the report table
# 1.03.01 Released as part of the 1.03 package
# 1.02.03 Added some more input checks and login check
# 1.02.02 Remove mailreporting records
# 1.02.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_userid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking $_GET'ed variables
if (!isset($clean['userid']) ) {
  $m = 139;
  $err = 1;
} else {
  $userid = $clean['userid'];
}

# Checking access
if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
} elseif ($s_access_user < 9) {
  $sql_check = "SELECT organisation FROM login WHERE id = $userid AND organisation = $s_org";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 0) {
    $m = 101;
    $err = 1;
  }
}

if ($err == 0) {
  # Deleting all mailreports from the user
#  $sql = "DELETE FROM report_content WHERE user_id = '$userid'";
#  $debuginfo[] = $sql;
#  $query = pg_query($pgconn, $sql);

  # Deleting all page configs from the user
#  $sql = "DELETE FROM pageconf WHERE userid = '$userid'";
#  $debuginfo[] = $sql;
#  $query = pg_query($pgconn, $sql);

  # Deleting the actual user records
  $sql = "DELETE FROM login WHERE id = $userid";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  
  $m = 2;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: useradmin.php?int_m=$m");
?>
