<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 06-11-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.01 Code layout
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
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

if ( ! isset($_GET['userid']) ) {
  $m = 29;
  $err = 1;
} else {
  $userid = intval($_GET['userid']);
}

if ($s_access_user < 2) {
  $m = 90;
  $err = 1;
} elseif ($s_access_user < 9) {
  $sql_check = "SELECT organisation FROM login WHERE id = $userid AND organisation = $s_org";
  $result_check = pg_query($pgconn, $sql_check);
  $numrows_check = pg_num_rows($result_check);
  if ($numrows_check == 0) {
    $m = 28;
    $err = 1;
  }
}

if ($err == 0) {
  $userid = intval($_GET['userid']);
  # Mailreporting records
  // report_content_threshold
  $sql = "SELECT *, report_content.id AS report_content_id FROM report, report_content WHERE report_content.report_id = report.id AND report.user_id = '$userid' AND report_content.template = 3";
  $query = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($query)) {
    $report_content_id = $row["report_content_id"];
    $sql_template = "DELETE FROM report_template_threshold WHERE report_content_id = '$report_content_id'";
    $query_template = pg_query($pgconn, $sql_template);
  }

  // report_content
  $sql = "SELECT * FROM report WHERE user_id = '$userid'";
  $query = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($query)) {
	$report_id = $row["id"];
    $sql = "DELETE FROM report_content WHERE report_id = '$report_id'";
    $query = pg_query($pgconn, $sql);
  }

  // report
  $sql = "DELETE FROM report WHERE user_id = '$userid'";
  $query = pg_query($pgconn, $sql);

  // Login records
  $sql = "DELETE FROM login WHERE id = $userid";
  $execute = pg_query($pgconn, $sql);
  
  $m = 2;
}
pg_close($pgconn);
header("location: useradmin.php?m=$m");
?>
