<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
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

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  pg_close($pgconn);
  $address = getaddress();
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

$allowed_get = array(
                "int_userid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (!isset($clean['userid']) ) {
  $m = 96;
  $err = 1;
} else {
  $userid = $clean['userid'];
}

if ($s_access_user < 2) {
  $m = 91;
  $err = 1;
} elseif ($s_access_user < 9) {
  $sql_check = "SELECT organisation FROM login WHERE id = $userid AND organisation = $s_org";
  $debuginfo[] = $sql_check;
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 0) {
    $m = 91;
    $err = 1;
  }
}

if ($err == 0) {
  # Mailreporting records
  // report_content_threshold
  $sql = "SELECT id FROM report_content WHERE report_content.user_id = '$userid' AND report_content.template = 3";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($query)) {
    $report_content_id = $row["id"];
    $sql_template = "DELETE FROM report_template_threshold WHERE report_content_id = '$report_content_id'";
    $debuginfo[] = $sql_template;
    $query_template = pg_query($pgconn, $sql_template);
  }

  // report_content
  $sql = "DELETE FROM report_content WHERE user_id = '$userid'";
  $debuginfo[] = $sql;
  $query = pg_query($pgconn, $sql);

  // Login records
  $sql = "DELETE FROM login WHERE id = $userid";
  $debuginfo[] = $sql;
  $execute = pg_query($pgconn, $sql);
  
  $m = 2;
}
$debuginfo[] = "M: $m";
pg_close($pgconn);
debug_sql();
header("location: useradmin.php?int_m=$m");
?>
