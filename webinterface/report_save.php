<?php

####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 03-07-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Fixed redirection issue
# 002 Added UTC support
# 001 Added option to always send the report
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
$q_org = intval($_SESSION['q_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_userid = intval($_SESSION['s_userid']);
$s_hash = md5($_SESSION['s_hash']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

# Retrieving posted variables from $_POST
$allowed_post = array(
        "int_rcid",
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
        "bool_active",
        "md5_hash",
        "int_always",
        "int_utc",
        "bool_public"
);
$check = extractvars($_POST, $allowed_post);
#debug_input();
$err = 0;

# Checking access
if ($s_access_user < 1) {
  header("location: index.php");
  pg_close($pgconn);
  exit;
}

# Make sure all access rights are correct. Setting up user ID.
if (isset($clean['userid'])) {
  $user_id = $clean['userid'];
  if ($s_access_user < 2) {
    $user_id = $s_userid;
    $sel_org = $s_org;
  } elseif ($s_access_user < 9) {
    $sql_login = "SELECT id FROM login WHERE organisation = $s_org AND id = $user_id";
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
    $sel_org = $s_org;
  } else {
    # s_access_user == 9
    $user_id = $clean['userid'];
    $sel_org = 0;
  }
} else {
  $user_id = $s_userid;
  $sel_org = $s_org;
}



if (isset($clean['public'])) {
  $public = "t";
} else {
  $public = "f";
}

if (!isset($clean['detail']) || !isset($clean['sdetail'])) {
  $detail = 0;
} elseif ($template == 4) {
  $detail = $clean['sdetail'];
} else {
  $detail = $clean['detail'];
}

# Checking if the logged in user actually requested this action.
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

if (!isset($clean['active'])) {
  $err = 1;
  $m = 136;
} else {
  $active = $clean['active'];
}

if (!isset($clean['rcid'])) {
  $err = 1;
  $m = 135;
} else {
  $reportid = $clean['rcid'];
  if ($s_access_user == 1) {
    $sql = "SELECT id FROM report_content WHERE id = '$reportid' AND user_id = '$user_id'";
    $result = pg_query($pgconn, $sql);
    $numrows = pg_num_rows($result);
    if ($numrows == 0) {
      $err = 1;
      $m = 101;
    }
  } elseif ($s_access_user == 2) {
    $sql = "SELECT report_content.id FROM report_content, login ";
    $sql .= " WHERE report_content.id = '$reportid' AND report_content.user_id = login.id AND login.organisation = '$s_org'";
    $result = pg_query($pgconn, $sql);
    $numrows = pg_num_rows($result);
    if ($numrows == 0) {
      $err = 1;
      $m = 101;
    }
  }
}

if (!isset($clean['always'])) {
  $always = 0;
} else {
  $always = $clean['always'];
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
  # Adding new report
  $sql = "UPDATE report_content SET user_id = '$user_id', template = '$template', sensor_id = '$sensorid', frequency = '$freq', ";
  $sql .= " interval = '$interval', priority = '$prio', subject = '$subject', operator = '$operator', threshold = '$threshold', ";
  $sql .= " severity = '$sev', active = '$active', detail = '$detail', always = '$always', utc = '$utc', public = '$public', orgid = '$q_org' WHERE id = '$reportid'";
  $debuginfo[] = $sql;
  $ec = pg_query($pgconn, $sql);
  $m = 3;
}

# Close connection and redirect
pg_close($pgconn);
#debug_sql();
if ($m == 3 || $m == 135) {
  header("location: myreports.php?int_m=$m&int_userid=$user_id");
} else {
  header("location: report_edit.php?int_m=$m&int_rcid=$reportid");
}
?>
