<?php

####################################
# SURFids 2.00.04                  #
# Changeset 002                    #
# 13-12-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Fixed bug #61
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_userid = intval($_SESSION['s_userid']);
$s_hash = md5($_SESSION['s_hash']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$to = $_SESSION['s_to'];
$from = $_SESSION['s_from'];
$err = 0;

# Retrieving posted variables from $_POST
$allowed_post = array(
		"md5_hash",
		"int_timespan",
		"strip_html_escape_temptitle"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();
$err = 0;

$user_id = $s_userid;

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking $_POST'ed variables
if (!isset($clean['temptitle'])) {
  $err = 1;
  $m = 128;
} else {
  $subject = $clean['temptitle'];
}

$prio = 2;
$template = 6;
$sev = -1;
$freq = -1;
$sensorid = -1;
$threshold = -1;
$operator = -1;
$detail = -1;

if (isset($clean['timespan'])) {
  $timespan = $clean['timespan'];
  if ($timespan == 1) {
    $interval = $to - $from;
    $db_to = -1;
    $db_from = -1;
  } elseif ($timespan == 2) {
    $interval = -1;
    $db_to = $to;
    $db_from = $from;
  } else {
    $interval = -1;
    $db_to = -1;
    $db_from = -1;
  }
} else {
  $interval = -1;
  $db_to = -1;
  $db_from = -1;
}

if (isset($_SERVER['QUERY_STRING'])) {
  $qs = $_SERVER['QUERY_STRING'];
  $qs = urldecode($qs);

  # Removing int_to and int_from
  $pattern = '/int_to=[0-9]*&/';
  $qs = preg_replace($pattern, "", $qs);
  $pattern = '/int_from=[0-9]*&/';
  $qs = preg_replace($pattern, "", $qs);

  # Removing strip_html_escape_temptitle
  $pattern = '/strip_html_escape_temptitle=[0-9a-zA-Z]*&/';
  $qs = preg_replace($pattern, "", $qs);

  # Removing int_timespan
  $pattern = '/int_timespan=[0-9]*&/';
  $qs = preg_replace($pattern, "", $qs);

  # Removing int_selperiod
  $pattern = '/int_selperiod=[0-9]*&/';
  $qs = preg_replace($pattern, "", $qs);

  # Removing empty sensorid array
  $qs = str_replace("&sensorid[]=0", "", $qs);

  # Removing any empty variables
  $pattern = '/[a-zA-Z_]*=&/';
  $qs = preg_replace($pattern, "", $qs);

  # Removing trailing empty variable
  $pattern = '/&[a-zA-Z_]*=$/';
  $qs = preg_replace($pattern, "", $qs);

  # Validating querystring input
  $qs = pg_escape_string(strip_tags(htmlentities($qs)));
} else {
  $err = 1;
  $m = 142;
}

if ($err == 0) {
  $sql = "INSERT INTO report_content ";
  $sql .= "(user_id, template, sensor_id, frequency, interval, priority, subject, operator, threshold, severity, detail, qs, from_ts, to_ts) ";
  $sql .= " VALUES ";
  $sql .= "('$user_id', '$template', '$sensorid', '$freq', '$interval', '$prio', '$subject', '$operator', '$threshold', '$sev', '$detail', '$qs', '$db_from', '$db_to')";
  $debuginfo[] = $sql;
  $ec = pg_query($pgconn, $sql);
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
header("location: myreports.php?int_m=$m");
?>
