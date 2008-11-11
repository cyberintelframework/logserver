<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 18-08-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';
include "../lang/${c_language}.php";

# Starting the session
session_start();
header("Cache-control: private");
header("Content-type: application/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $m = 100;
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
  echo "</result>";

  pg_close($pgconn);
  exit;
}

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$s_admin = $_SESSION['s_admin'];
$err = 0;

# Checking access
if ($s_admin != 1) {
  $err = 1;
  $m = 101;
}

$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "strip_html_escape_error",
                "strip_html_escape_prefix",
                "strip_html_escape_dev",
		"int_sid",
		"int_level",
                "md5_hash",
		"int_limit",
		"int_offset",
                "int_levelop",
                "int_prefixop",
                "int_devop",
                "int_sidop",
                "int_errorop"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

$operators_ar = array(
	0 => "!=",
	1 => "=",
	2 => ">",
	3 => "<"
);

# Default values
$levelop = 1;
$prefixop = 1;
$devop = 1;
$sidop = 1;
$errorop = 1;

$from = $_SESSION['s_from'];
$to = $_SESSION['s_to'];
add_to_sql("timestamp >= '$from'", "where");
add_to_sql("timestamp <= '$to'", "where");

add_to_sql("syslog.*", "select");
add_to_sql("syslog", "table");

# Getting all operators
if (isset($clean['levelop'])) {
  $levelop = $clean['levelop'];
  $levelop = $operators_ar[$levelop];
}
if (isset($clean['prefixop'])) {
  $prefixop = $clean['prefixop'];
  $prefixop = $operators_ar[$prefixop];
}
if (isset($clean['devop'])) {
  $devop = $clean['devop'];
  $devop = $operators_ar[$devop];
}
if (isset($clean['sidop'])) {
  $sidop = $clean['sidop'];
  $sidop = $operators_ar[$sidop];
}
if (isset($clean['errorop'])) {
  $errorop = $clean['errorop'];
  $errorop = $operators_ar[$errorop];
}

if (isset($clean['error']) && $clean['error'] != -1) {
  $selected_error = $clean['error'];
  add_to_sql("error $errorop '$selected_error'", "where");
}

if (isset($clean['prefix']) && $clean['prefix'] != -1) {
  $selected_prefix = $clean['prefix'];
  add_to_sql("source $prefixop '$selected_prefix'", "where");
}

if (isset($clean['dev']) && $clean['dev'] != -1) {
  $selected_dev = $clean['dev'];
  add_to_sql("device $devop '$selected_dev'", "where");
}

if (isset($clean['sid']) && $clean['sid'] != -1) {
  $selected_sid = $clean['sid'];
  add_to_sql("sensorid $sidop '$selected_sid'", "where");
}

if (isset($clean['level']) && $clean['level'] != -1) {
  $selected_level = $clean['level'];
  add_to_sql("level $levelop '$selected_level'", "where");
}

if (isset($clean['limit'])) {
  $limit = $clean['limit'];
} else {
  $limit = 20;
}

if (isset($clean['offset'])) {
  $offset = $clean['offset'];
} else {
  $offset = 0;
}

add_to_sql("timestamp DESC", "order");

prepare_sql();
$sql_count = "SELECT COUNT(sensorid) as total FROM $sql_from $sql_where";
$debuginfo[] = $sql_count;
$result_count = pg_query($pgconn, $sql_count);
$row_count = pg_fetch_assoc($result_count);
$count = $row_count['total'];

$sql = "SELECT $sql_select FROM $sql_from $sql_where ORDER BY $sql_order LIMIT $limit OFFSET $offset";
$debuginfo[] = $sql;
$result = pg_query($pgconn, $sql);

if ($err == 0) {
  $m = 9;
  $pagetop = $offset + $limit;
  $pagecounter = "$offset - $pagetop from $count";
  echo "<result>";
    echo "<status>OK</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
    echo "<data>";
      echo "<pagecounter>$pagecounter</pagecounter>";
      echo "<total>$count</total>";
      while ($row = pg_fetch_assoc($result)) {
        $level = $v_syslog_levels_ar[$row['level']];
        $ts = $row['timestamp'];
        $ts = date("d-m-Y H:i:s", $ts);
        $source = $row['source'];
        $error = $row['error'];
        $sid = $row['sensorid'];
        if ($sid != "") {
          $sql_sid = "SELECT keyname, vlanid, label FROM sensors WHERE id = '$sid'";
          $result_sid = pg_query($pgconn, $sql_sid);
          $row_sid = pg_fetch_assoc($result_sid);
          $keyname = $row_sid['keyname'];
          $vlanid = $row_sid['vlanid'];
          $label = $row_sid['label'];
          $sensor = sensorname($keyname, $vlanid, $label);
        }

        $tap = $row['device'];

        echo "<message>";
          echo "<level>$level</level>";
          echo "<ts>$ts</ts>";
          echo "<source>$source</source>";
          echo "<msg>$error</msg>";
          echo "<sid>$sid</sid>";
          echo "<sensor>$sensor</sensor>";
          echo "<device>$tap</device>";
        echo "</message>";
      }
    echo "</data>";
  echo "</result>";
} else {
  echo "<result>";
    echo "<status>FAILED</status>";
    echo "<error>" .$v_errors[$m]. "</error>";
  echo "</result>";
}

#pg_close($pgconn);
debug_sql();
?>
