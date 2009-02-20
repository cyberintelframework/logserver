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
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);
$s_admin = $_SESSION['s_admin'];
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
$errorop = 1;

add_to_sql("syslog.*", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors.id as sid", "select");
add_to_sql("syslog", "table");
#add_to_sql("sensors", "table");

#add_to_sql("sensors.keyname = syslog.keyname", "where");

$from = $_SESSION['s_from'];
$to = $_SESSION['s_to'];
add_to_sql("timestamp >= epoch_to_ts($from)", "where");
add_to_sql("timestamp <= epoch_to_ts($to)", "where");

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
if (isset($clean['errorop'])) {
  $errorop = $clean['errorop'];
  $errorop = $operators_ar[$errorop];
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

if ($s_admin != 1) {
    if ($s_access_sensor < 9) {
        if (isset($clean['sid']) && $clean['sid'] != -1) {
            $sel_sid = $clean['sid'];

            $sql = "SELECT id FROM sensors WHERE organisation = '$q_org' AND id = $sel_sid ";
            $result = pg_query($pgconn, $sql);
            $num = pg_num_rows($result);
            if ($num == 0) {
                $err = 1;
                $m = 101;
            }
        } else {
            $m = 110;
            $err = 1;
        }
    }
}

if ($err == 0) {
  add_to_sql("timestamp ASC", "order");

  if ($s_admin == 1 || $s_access_sensor == 9) {
    if (isset($clean['error']) && $clean['error'] != -1) {
      $sel_error = $clean['error'];
      add_to_sql("error $errorop '$sel_error'", "where");
    }

    if (isset($clean['prefix']) && $clean['prefix'] != -1) {
      $sel_prefix = $clean['prefix'];
      add_to_sql("source $prefixop '$sel_prefix'", "where");
    }

    if (isset($clean['dev']) && $clean['dev'] != -1) {
      $sel_dev = $clean['dev'];
      add_to_sql("device $devop '$sel_dev'", "where");
    }

    if (isset($clean['level']) && $clean['level'] != -1) {
      $sel_level = $clean['level'];
      add_to_sql("level $levelop '$sel_level'", "where");
    }

    if (isset($clean['sid']) && $clean['sid'] != -1) {
      $sel_sid = $clean['sid'];
      if ($sel_sid != "unknown") {
        $sql_get = "SELECT keyname, vlanid FROM sensors WHERE id = $sel_sid ";
        $res_get = pg_query($pgconn, $sql_get);
        $row_get = pg_fetch_assoc($res_get);
        $keyname = $row_get['keyname'];
        $vlanid = $row_get['vlanid'];

        add_to_sql("sensors.keyname = '$keyname' AND (vlanid = $vlanid OR vlanid = 0) ", "where");
      } else {
        add_to_sql("syslog.keyname = 'unknown'", "where");
      }
    }
  } else {
    if ($sel_sid != "unknown") {
      $sql_get = "SELECT keyname, vlanid FROM sensors WHERE id = $sel_sid ";
      $res_get = pg_query($pgconn, $sql_get);
      $row_get = pg_fetch_assoc($res_get);
      $keyname = $row_get['keyname'];
      $vlanid = $row_get['vlanid'];
      add_to_sql("sensors.keyname = '$keyname' AND (vlanid = $vlanid OR vlanid = 0) ", "where");
    } else {
      add_to_sql("syslog.keyname = 'unknown'", "where");
    }
    add_to_sql("level >= 1", "where");
  }

  prepare_sql();

#  $sql_count = "SELECT COUNT(syslog.keyname) as total FROM $sql_from $sql_where";
#  $debuginfo[] = $sql_count;
#  $result_count = pg_query($pgconn, $sql_count);
#  $row_count = pg_fetch_assoc($result_count);
#  $count = $row_count['total'];

#  $sql = "SELECT $sql_select FROM $sql_from $sql_where ORDER BY $sql_order LIMIT $limit OFFSET $offset";
#  $debuginfo[] = $sql;
#  $result = pg_query($pgconn, $sql);

  $sql_count = "SELECT COUNT(syslog.keyname) as total ";
  $sql_count .= " FROM $sql_from ";
  $sql_count .= "LEFT JOIN sensors ";
  $sql_count .= " ON sensors.keyname = syslog.keyname AND sensors.vlanid = syslog.vlanid ";
  $sql_count .= " $sql_where";
  $debuginfo[] = $sql_count;
  $result_count = pg_query($pgconn, $sql_count);
  $row_count = pg_fetch_assoc($result_count);
  $count = $row_count['total'];

  $sql = "SELECT $sql_select FROM $sql_from ";
  $sql .= "LEFT JOIN sensors ";
  $sql .= " ON sensors.keyname = syslog.keyname AND sensors.vlanid = syslog.vlanid ";
  $sql .= " $sql_where ORDER BY $sql_order LIMIT $limit OFFSET $offset";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);

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
        $ts = strtotime($row['timestamp']);
        $ts = date($c_date_format, $ts);
        $source = $row['source'];
        $pid = $row['pid'];
        $error = $row['error'];
        $args = htmlentities($row['args']);
        $sid = $row['sid'];
        $tap = $row['device'];
        $vlanid = $row['vlanid'];
        $keyname = $row['keyname'];
        $sensor = sensorname($keyname, $vlanid);

        echo "<message>";
          echo "<level>$level</level>";
          echo "<ts>$ts</ts>";
          echo "<source>$source</source>";
          echo "<pid>$pid</pid>";
          echo "<msg>$error</msg>";
          echo "<args>$args</args>";
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
