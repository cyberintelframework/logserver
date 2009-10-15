<?php

####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 15-10-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Tuned SQL query (#185)
# 002 Fixed #156
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
#  $m = 100;
#  echo "<result>";
#    echo "<status>FAILED</status>";
#    echo "<error>" .$v_errors[$m]. "</error>";
#  echo "</result>";

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

# Retrieving posted variables from $_POST
$tainted_post['int_page'] = $_POST['page'];
$tainted_post['int_rp'] = $_POST['rp'];
$tainted_post['strip_html_escape_sortname'] = $_POST['sortname'];
$tainted_post['strip_html_escape_sortorder'] = $_POST['sortorder'];
$tainted_post['strip_html_escape_query'] = $_POST['query'];
$tainted_post['strip_html_escape_qtype'] = $_POST['qtype'];

$allowed_post = array(
                "strip_html_escape_sortname",
                "ascdesc_sortorder",
                "strip_html_escape_query",
                "strip_html_escape_qtype",
                "int_rp",
                "int_page"
);
$check = extractvars($tainted_post, $allowed_post);
debug_input();

add_to_sql("syslog.*", "select");
add_to_sql("ts_to_epoch(syslog.timestamp) as ts", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors.id as sid", "select");
add_to_sql("syslog", "table");

$from = $_SESSION['s_from'];
$to = $_SESSION['s_to'];
add_to_sql("ts_to_epoch(timestamp) >= $from", "where");
add_to_sql("ts_to_epoch(timestamp) <= $to", "where");

if ($s_admin != 1) {
  $err = 1;
  $m = 101;
}

$rp = 20;
if (isset($clean['rp'])) {
  $rp = $clean['rp'];
}

$page = 1;
if (isset($clean['page'])) {
  $page = $clean['page'];
}

$sortorder = "asc";
if (isset($clean['sortorder'])) {
  $sortorder = $clean['sortorder'];
}

$sortname = "ts";
if (isset($clean['sortname'])) {
  $sortname = $clean['sortname'];
}

if ($err == 0) {
  add_to_sql("timestamp ASC", "order");

  $query = "";
  $qtype = "";
  if (isset($clean['query'])) {
    $query = $clean['query'];
  }

  if (isset($clean['qtype'])) {
    $qtype = $clean['qtype'];
    $pattern = '/(level|source|pid|error|args|dev|sensor)/';
    if (!preg_match($pattern, $qtype)) {
      $qtype = "";
    }
  }

  if ($query != "" && $qtype != "") {
    if ($qtype == "level") {
      $query = ucfirst($query);
      $int_level = array_search($query, $v_syslog_levels_ar, TRUE);
      if ($int_level != "") {
        add_to_sql("level = $int_level", "where");
      }
    } else {
      add_to_sql("$qtype LIKE '$query'", "where");
    }
  }

  prepare_sql();
  $offset = ($page * $rp) - $rp;

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
  $sql .= " $sql_where ORDER BY $sql_order OFFSET $offset LIMIT $rp";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);

  echo "<rows>";
    echo "<page>$page</page>";
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
      $tap = $row['device'] ? $row['device'] : "unknown";
      $vlanid = $row['vlanid'];
      $keyname = $row['keyname'];
      $sensor = sensorname($keyname, $vlanid);

      echo "<row>";
        echo "<cell>$level</cell>";
        echo "<cell>$ts</cell>";
        echo "<cell>$source</cell>";
        echo "<cell>$pid</cell>";
        echo "<cell>$error</cell>";
        echo "<cell>$args</cell>";
        echo "<cell>$sensor</cell>";
        echo "<cell>$tap</cell>";
      echo "</row>";
    }
  echo "</rows>";
}

#pg_close($pgconn);
debug_sql();
?>
