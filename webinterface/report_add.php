<?php

####################################
# SURFids 2.04                     #
# Changeset 002                    #
# 26-05-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added UTC & always send options
# 001 version 2.00
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

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
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_hash = md5($_SESSION['s_hash']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

# Retrieving posted variables from $_POST
$allowed_post = array(
		"int_userid",
		"strip_html_escape_subject",
		"int_priority",
		"int_freqattack",
		"int_freqsensor",
		"int_intervalday",
		"int_intervalweek",
		"int_intervalthresh",
		"int_operator",
		"int_threshold",
		"int_sensorid",
		"int_template",
		"int_sevattack",
		"int_sevsensor",
		"int_detail",
		"int_sdetail",
                "int_filter",
                "md5_hash",
                "int_always",
                "int_utc"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();
$err = 0;

# Make sure all access rights are correct
if (isset($clean['userid'])) {
  $user_id = $clean['userid'];
  if ($s_access_user < 1) {
    header("location: index.php");
    pg_close($pgconn);
    exit;
  } elseif ($s_access_user < 2) {
    $user_id = $s_userid;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT id FROM login WHERE organisation = $s_org AND id = $user_id";
    $debuginfo[] = $sql_login;
    $result_login = pg_query($pgconn, $sql_login);
    $numrows_login = pg_num_rows($result_login);
    if ($numrows_login == 0) {
      $err = 1;
      $m = 101;
    } else {
      $user_id = $clean['userid'];
    }
  } else {
    $user_id = $clean['userid'];
  }
} else {
  $user_id = $s_userid;
}

if ($clean['hash'] != $s_hash) {
  $err = 1;
  $m = 116;
}

# Checking $_POST'ed variables
if (!isset($clean['subject'])) {
  $err = 1;
  $m = 128;
} else {
  $subject = $clean['subject'];
}

if (!isset($clean['priority'])) {
  $err = 1;
  $m = 129;
} else {
  $prio = $clean['priority'];
}

if (!isset($clean['template'])) {
  $err = 1;
  $m = 130;
} else {
  $template = $clean['template'];
}

if (isset($clean['utc'])) {
  $utc = $clean['utc'];
} else {
  $utc = 0;
}

if (!isset($clean['sevattack']) || !isset($clean['sevsensor'])) {
  $err = 1;
  $m = 131;
} else {
  if ($template == 4) {
    $sev = $clean['sevsensor'];
  } elseif ($template == 1 || $template == 2) {
    if ($detail == 4) {
      $sev = $clean['filter'];
    } else {
      $sev = $clean['sevattack'];
    }
  }
}

if (!isset($clean['freqattack']) || !isset($clean['freqsensor'])) {
  $err = 1;
  $m = 132;
} else {
  if ($template == 4) {
    $freq = $clean['freqsensor'];
  } else {
    $freq = $clean['freqattack'];
  }
  if ($freq == 2) {
    if (!isset($clean['intervalday'])) {
      $err = 1;
      $m = 133;
    } else {
      $interval = $clean['intervalday'];
    }
  } elseif ($freq == 3) {
    if (!isset($clean['intervalweek'])) {
      $err = 1;
      $m = 133;
    } else {
      $interval = $clean['intervalweek'];
    }
  } elseif ($freq == 4) {
    if (!isset($clean['operator']) || !isset($clean['threshold']) || !isset($clean['intervalthresh'])) {
      $err = 1;
      $m = 134;
    } else {
      $operator = $clean['operator'];
      $threshold = $clean['threshold'];
      $interval = $clean['intervalthresh'];
    }
  }
}

if (!isset($clean['sensorid'])) {
  $err = 1;
  $m = 110;
} else {
  $sensorid = $clean['sensorid'];
}

if (!isset($clean['detail']) || !isset($clean['sdetail'])) {
  $detail = 0;
} elseif ($template == 4) {
  $detail = $clean['sdetail'];
} else {
  $detail = $clean['detail'];
}

if (isset($clean['always'])) {
  $always = $clean['always'];
} else {
  $always = 0;
}

# Setting some default values if the variables don't exist
if (!$interval) {
  $interval = -1;
}

if (!$operator) {
  $operator = -1;
}

if ("$threshold" == "") {
  $threshold = -1;
}

if ("$sev" == "") {
  $sev = -1;
}

if ($err == 0) {
  $sql = "INSERT INTO report_content (user_id, template, sensor_id, frequency, interval, priority, subject, operator, threshold,  severity, detail, always, utc) ";
  $sql .= " VALUES ('$user_id', '$template', '$sensorid', '$freq', '$interval', '$prio', '$subject', '$operator', '$threshold', '$sev',  '$detail', '$always', '$utc')";
  $debuginfo[] = $sql;
  $ec = pg_query($pgconn, $sql);
  $m = 1;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
if ($m == 1) {
  header("location: myreports.php?int_m=$m");
} else {
  header("location: report_new.php?int_m=$m");
}
?>
