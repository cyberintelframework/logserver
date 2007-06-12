<?php

####################################
# SURFnet IDS                      #
# Version 1.04.05                  #
# 19-03-2007                       #
# Peter Arts                       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.05 Added hash check
# 1.04.04 Fixed a bug with weekday count
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug stuff
# 1.04.01 Released as 1.04.01
# 1.03.01 Split up report.php into seperate files
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_hash = md5($_SESSION['s_hash']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

### Extracting GET variables
$allowed_get = array(
                "int_userid"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

// Make sure all access rights are correct
if (isset($clean['userid'])) {
  $user_id = $clean['userid'];
  if ($s_access_user < 1) {
    header("location: index.php");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT * FROM login WHERE organisation = $s_org AND id = $user_id";
    $debuginfo[] = $sql_login;
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      $m = geterror(91);
      echo $m;
      footer();
      exit;
    } else {
      $user_id = $clean['userid'];
    }
  } else {
    $user_id = $clean['userid'];
  }
} else {
  $user_id = $s_userid;
}

### Extracting POST variables
$allowed_post = array(
		"strip_html_escape_subject",
		"int_priority",
		"int_frequency",
		"int_intervalday",
		"int_intervalweek",
		"int_operator",
		"int_threshold",
		"int_sensorid",
		"int_template",
		"int_severity",
		"md5_hash"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();
$err = 0;

if (!isset($clean['subject'])) {
  $err = 1;
  $m = 90;
} else {
  $subject = $clean['subject'];
}

if (!isset($clean['priority'])) {
  $err = 1;
  $m = 91;
} else {
  $prio = $clean['priority'];
}

if (!isset($clean['frequency'])) {
  $err = 1;
  $m = 92;
} else {
  $freq = $clean['frequency'];
  if ($freq == 2) {
    if (!isset($clean['intervalday'])) {
      $err = 1;
      $m = 93;
    } else {
      $interval = $clean['intervalday'];
    }
  } elseif ($freq == 3) {
    if (!isset($clean['intervalweek'])) {
      $err = 1;
      $m = 93;
    } else {
      $interval = $clean['intervalweek'];
    }
  } elseif ($freq == 4) {
    if (!isset($clean['operator']) || !isset($clean['threshold'])) {
      $err = 1;
      $m = 94;
    } else {
      $operator = $clean['operator'];
      $threshold = $clean['threshold'];
    }
  }
}

if (!isset($clean['sensorid'])) {
  $err = 1;
  $m = 95;
} else {
  $sensorid = $clean['sensorid'];
}

if (!isset($clean['template'])) {
  $err = 1;
  $m = 96;
} else {
  $template = $clean['template'];
  if ($template == 1 || $template == 2) {
    if (!isset($clean['severity'])) {
      $err = 1;
      $m = 97;
    } else {
      $sev = $clean['severity'];
    }
  }
}

if (!$interval) {
  $interval = -1;
}

if (!$operator) {
  $operator = -1;
}

if (!$threshold) {
  $threshold = -1;
}

if (!$severity) {
  $sev = -1;
}

if ($err == 0) {
  $sql = "INSERT INTO report_content (user_id, template, sensor_id, frequency, interval, priority, subject, operator, threshold, severity) ";
  $sql .= " VALUES ('$user_id', '$template', '$sensorid', '$freq', '$interval', '$prio', '$subject', '$operator', '$threshold', '$sev')";
  $debuginfo[] = $sql;
  $ec = pg_query($pgconn, $sql);
  $m = 5;
}

pg_close($pgconn);
debug_sql();
if ($m == 5) {
  header("location: mailadmin.php?int_m=$m");
} else {
  header("location: report_new.php?int_m=$m");
}
?>
