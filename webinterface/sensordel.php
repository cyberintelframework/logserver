<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 25-09-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress($web_port);
  header("location: ${address}login.php");
  exit;
}

$s_org = $_SESSION['s_org'];
$s_admin = $_SESSION['s_admin'];

if ($s_admin != 1) {
  $address = getaddress($web_port);
  header("location: ${address}index.php");
}

if ( ! isset($_GET['sensorid']) ) {
  echo "No sensor ID given!<br />\n";
  $err = 1;
}
else {
  $sensorid = $_GET['sensorid'];
  if ( ! is_numeric($sensorid)) {
    echo "Sensorid not a valid integer!<br />\n";
    $err = 1;
  } else {
    $sensorid = intval($_GET['sensorid']);
  }
}

if ($err != 1) {
  # Deleting attack details (table: details)
  $sql = "DELETE FROM details WHERE sensorid = $sensorid";
  $query = pg_query($pgconn, $sql);
  print "SQL: $sql<br /><br />\n";
  
  # Deleting attacks (table: attacks)
  $sql = "DELETE FROM attacks WHERE sensorid = $sensorid";
  $query = pg_query($pgconn, $sql);
  print "SQL: $sql<br /><br />\n";

  $sql_hist = "SELECT id FROM stats_history WHERE sensorid = $sensorid";
  $query_hist = pg_query($pgconn, $sql_hist);
  $row_hist = pg_fetch_assoc($query_hist);
  $histid = $row_hist['id'];
  print "SQLHIST: $sql_hist<br /><br />\n";

  # Deleting history dialogues (table: stats_history_dialogue)
  $sql = "DELETE FROM stats_history_dialogue WHERE historyid = $histid";
  $query = pg_query($pgconn, $sql);
  print "SQL: $sql<br /><br />\n";

  # Deleting history virus info (table: stats_history_virus)
  $sql = "DELETE FROM stats_history_virus WHERE historyid = $histid";
  $query = pg_query($pgconn, $sql);
  print "SQL: $sql<br /><br />\n";

  # Deleting history records (table: stats_history)
  $sql = "DELETE FROM stats_history WHERE sensorid = $sensorid";
  $query = pg_query($pgconn, $sql);
  print "SQL: $sql<br /><br />\n";

  # Deleting sensor (table: sensor)
  $sql = "DELETE FROM sensor WHERE id = $sensorid";
  $query = pg_query($pgconn, $sql);
  print "SQL: $sql<br /><br />\n";
}

pg_close($pgconn);
$address = getaddress($web_port);
header("location: ${address}index.php");
?>
