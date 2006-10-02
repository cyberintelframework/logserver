<?php

####################################
# SURFnet IDS                      #
# Version 1.02.03                  #
# 09-08-2006                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 1.02.03 intval() to session variables
# 1.02.02 Added logged in check and exit failsafe
# 1.02.01 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

# starting session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

# getting session info
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

# checking access
if ($s_access_search == 0) {
  header("location: index.php");
  exit;
}
elseif ($s_access_search < 9) {
  $sql_sensors = "SELECT * FROM sensors WHERE organisation = $s_org";
}
elseif ($s_access_search == 9) {
  $sql_sensors = "SELECT * FROM sensors";
}
else {
  header("location: index.php");
  exit;
}
$result_sensors = pg_query($pgconn, $sql_sensors);

while ($row = pg_fetch_assoc($result_sensors)) {
  $id = $row['id'];
  $arpkey = "arp_" . $id;
  $thresholdkey = "threshold_" . $id;
  $arp = pg_escape_string($_POST[$arpkey]);
  $threshold = intval($_POST[$thresholdkey]);
  $sql_updatestatus = "UPDATE sensors SET arp = $arp, arp_threshold = $threshold WHERE id = $id";
  $result_updatestatus = pg_query($pgconn, $sql_updatestatus);
  $m = 13;
}
pg_close($pgconn);
header("location: arpadmin.php?m=$m");
?>
