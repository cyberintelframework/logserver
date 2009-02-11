<?php

####################################
# SURFids 2.10                     #
# Changeset 004                    #
# 18-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 004 Added ARP exclusion stuff
# 003 Fixed sensorstatus RSS
# 002 Added s_access_user check
# 001 Added language support
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';
include_once '../include/rss_generator.inc.php';

# Starting the session
session_start();

$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});

# Redirect user if he doesn't have the right access
if ($s_access_user < 1) {
  header("location: index.php");
  pg_close($pgconn);
  exit;
}

# Including language file
include "../lang/${c_language}.php";

$allowed_get = array(
	"int_rcid"
);
$check = extractvars($_GET, $allowed_get);

# Make sure the user re-authenticates everytime
if ($_SESSION['re-auth']) {
  unset($_SESSION['re-auth']);
  header("WWW-Authenticate: Basic realm=\"Authenticate RSS\"");
  header("HTTP/1.0 401 Unauthorized");
} else {
  $_SESSION['re-auth'] = 1;
}
$user = $_SERVER['PHP_AUTH_USER'];

# Declare timespans
$minute = 60;
$hour = (60 * $minute);
$day = (24 * $hour);
$week = (7 * $day);

$sql_check = "SELECT login.id as uid, organisations.id as oid, organisations.organisation FROM login, organisations ";
$sql_check .= " WHERE login.organisation = organisations.id AND username = '$user'";
$debuginfo[] = $sql_check;
$result_check = pg_query($pgconn, $sql_check);
$numrows_check = pg_numrows($result_check);
if ($numrows_check == 0) {
  $err = 1;
  $m = 101;
  $info = "Unauthorized to view this page!";
} else {
  $row_check = pg_fetch_assoc($result_check);
  $uid = $row_check['uid'];
  $org = $row_check['oid'];
  $orgname = $row_check['organisation'];
  if ($orgname == "ADMIN") {
    $s_admin = 1;
  } else {
    $s_admin = 0;
  }
}

if ($err == 0) {
  if (!isset($clean['rcid'])) {
    $err = 1;
    $m = 143;
    $info = "Could not find feed!";
  } else {
    $rcid = $clean['rcid'];
    $sql_getfeed = "SELECT id, subject, template, sensor_id, severity, last_sent, detail ";
    $sql_getfeed .= " FROM report_content WHERE id = '$rcid' AND user_id = '$uid' AND active = TRUE";
    $debuginfo[] = $sql_getfeed;
    $result_getfeed = pg_query($pgconn, $sql_getfeed);
    $numrows_getfeed = pg_numrows($result_getfeed);
    if ($numrows_getfeed == 0) {
      $err = 1;
      $m = 143;
      $info = "Could not find feed!";
    }
  }
}

if ($err == 0) {
  $row_feed = pg_fetch_assoc($result_getfeed);
  $subject = $row_feed['subject'];
  $template = $row_feed['template'];
  $sensorid = $row_feed['sensor_id'];
  $severity = $row_feed['severity'];
  $lastsent = $row_feed['last_sent'];
  $detail = $row_feed['detail'];

  ################################
  # General RSS stuff
  ################################
  $time = date($c_date_format);
  $rss_channel = new rssGenerator_channel();
  $rss_channel->title = "SURF IDS RSS Feed";
  $rss_channel->link = "$c_webinterface_prefix/rssfeed.php?int_rcid=$rcid";
  $rss_channel->description = "$subject";
  $rss_channel->language = "EN-en";
  $rss_channel->generator = "SURF IDS RSS Feed ". $l['rf_generator'];
  $rss_channel->webMaster = 'ids-beheer@surfnet.nl';

  ################################
  # RSS FEED
  ################################
  # Setting up the sensor ID sql stuff
  if ($sensorid > -1) {
    $andsensor = " AND sensors.id = '$sensorid'";
    $urlsensor = "&sensorid[]=$sensorid";
  } else {
    $andsensor = "";
    $urlsensor = "";
  }

  # Setting up the severity sql stuff
  if ($severity > -1) {
    $andsev = " AND attacks.severity = $severity";
    $urlsev = "&int_sev=$severity";
  } else {
    $andsev = "";
    $urlsev = "";
  }

  # Setting up the organisation sql stuff
  if ($s_admin == 1) {
    $andorg = "";
  } else {
    $andorg = "AND sensors.organisation = '$org'";
  }

  # Setting up timestamping
  if ("$lastsent" == "") {
    $ts_end = date("U");
    $ts_start = $ts_end - (5 * $minute);
  } else {
    $ts_end = date("U");
    $ts_start = $lastsent;
  }

  if ($template == 1) {
    $sql = "SELECT attacks.id, attacks.source, attacks.severity, attacks.timestamp, attacks.dest, details.text, sensors.keyname, sensors.vlanid ";
    $sql .= "FROM attacks ";
    $sql .= " INNER JOIN sensors ";
    $sql .= " ON attacks.sensorid = sensors.id ";
    $sql .= " LEFT JOIN details ";
    $sql .= " ON attacks.id = details.attackid ";
    $sql .= " WHERE (details.type IN (1,4,8) OR details.type IS NULL) ";

    # IP Exclusion stuff
    $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
    # MAC Exclusion stuff
    $sql .= " AND (attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))" ;

    $sql .= " $andorg $andsensor $andsev ";
    $sql .= " ORDER BY attacks.id DESC ";
    $sql .= " LIMIT 10 ";
    $debuginfo[] = $sql;
    $result = pg_query($pgconn, $sql);

    while ($row = pg_fetch_assoc($result)) {
      $attackid = $row['id'];
      $source = $row['source'];
      $dest = $row['dest'];
      $text = $row['text'];
      $sev = $row['severity'];
      $keyname = $row['keyname'];
      $vlanid = $row['vlanid'];
      if ($vlanid != 0) {
        $keyname = "$keyname-$vlanid";
      }
      $ts = $row['timestamp'];
      $att = strtolower($v_severity_ar[$sev]);
      $title = "New $att detected (attack ID: $attackid)!";

      $pattern = '/^.*Dialogue$/';
      if (preg_match($pattern, $text)) {
        $text = $v_attacks_ar[$text]["Attack"];
      }

      if ($text != "") {
        $description = "$source -> $keyname ($dest) - $text";
      } else {
        $description = "$source -> $keyname ($dest)";
      }
      $addurl = "?int_from=$ts&int_to=$ts" . $urlsensor . $urlsev;
      $addurl = htmlentities($addurl);
      $link = "${c_webinterface_prefix}/logsearch.php${addurl}";

      $item = new rssGenerator_item();
      $item->title = "$title";
      if ($detail == 11) {
        $item->description = "$description";
      }
      $item->link = $link;
      $item->pubDate = "$ts";
      $rss_channel->items[] = $item;
    }
  } elseif ($template == 2) {
    $sql_getranges = "SELECT ranges FROM organisations, login WHERE login.id = '$uid' AND login.organisation = organisations.id";
    $debuginfo[] = $sql_getranges;
    $result_getranges = pg_query($pgconn, $sql_getranges);
    $row_ranges = pg_fetch_assoc($result_getranges);
    $ranges = $row_ranges['ranges'];
    if ($ranges != "") {
      $ranges = preg_replace("/(;)$/", "", $ranges);
      $ranges_ar = split(";", $ranges);

      foreach ($ranges_ar as $key=>$val) {
        $sql = "SELECT attacks.id, attacks.source, attacks.severity, attacks.timestamp, attacks.dest, details.text, sensors.keyname, sensors.vlanid ";
        $sql .= "FROM attacks ";
        $sql .= " INNER JOIN sensors ";
        $sql .= " ON attacks.sensorid = sensors.id ";
        $sql .= " LEFT JOIN details ";
        $sql .= " ON attacks.id = details.attackid ";
        $sql .= " WHERE (details.type IN (1,4,8) OR details.type IS NULL) ";

        # IP Exclusion stuff
        $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
        # MAC Exclusion stuff
        $sql .= " (attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl)) ";

        $sql .= " AND attacks.source <<= '$val' ";
        $sql .= " $andorg $andsensor $andsev ";
        $sql .= " ORDER BY attacks.id DESC ";
        $sql .= " LIMIT 10 ";
        $debuginfo[] = $sql;
        $result = pg_query($pgconn, $sql);

        while ($row = pg_fetch_assoc($result)) {
          $attackid = $row['id'];
          $attack_ar[$attackid] = $row;
        }
      }
      krsort($attack_ar);
      $i = 0;
      foreach ($attack_ar as $key => $row) {
        $i++;
        if ($i > 10) {
          break;
        }
        $attackid = $row['id'];
        $source = $row['source'];
        $dest = $row['dest'];
        $text = $row['text'];
        $sev = $row['severity'];
        $keyname = $row['keyname'];
        $vlanid = $row['vlanid'];
        if ($vlanid != 0) {
          $keyname = "$keyname-$vlanid";
        }
        $ts = $row['timestamp'];

        $att = strtolower($v_severity_ar[$sev]);
        $title = $l['rf_new']. " $att " .$l['rf_detected']. ": $attackid)!";
        if ($text != "") {
          $description = "$source -> $keyname ($dest) - $text";
        } else {
          $description = "$source -> $keyname ($dest)";
        }
        $addurl = "?int_from=$ts&int_to=$ts" . $urlsensor . $urlsev;
        $addurl = str_replace("&", "&amp;", $addurl);
        $link = "${c_webinterface_prefix}/logsearch.php${addurl}";

        $item = new rssGenerator_item();
        $item->title = "$title";
        if ($detail == 11) {
          $item->description = "$description";
        }
        $item->link = $link;
        $item->pubDate = "$ts";
        $rss_channel->items[] = $item;
      }
    } else {
      $item = new rssGenerator_item();
      $item->title = $l['rf_noranges'];
      $item->pubDate = "$ts";
      $rss_channel->items[] = $item;
    }
  } elseif ($template == 4) {
    $sql = "SELECT id, keyname, vlanid, status, label, netconf, tapip, tap, lastupdate FROM sensors WHERE status IN (0,1) $andorg $andsensor";
    $result = pg_query($pgconn, $sql);
    while ($row = pg_fetch_assoc($result)) {
      $sid = $row['id'];
      $keyname = $row['keyname'];
      $vlanid = $row['vlanid'];
      $label = $row['label'];
      $lastupdate = $row['lastupdate'];
      $status = $row['status'];
      $tap = $row['tap'];
      $tapip = $row['tapip'];

      $sensor = sensorname($keyname, $vlanid, $label);
      $ts = date("U");

      # Setting status correctly
      if (($netconf == "vlans" || $netconf == "static") && (empty($tapip) || $tapip == "")) {
        $status = 5;
      } elseif ($diffupdate <= 3600 && $status == 1 && !empty($tap)) {
        $status = 1;
      } elseif ($diffupdate > 3600 && $status == 1) {
        $status = 4;
      } elseif ($status == 1 && empty($tap)) {
        $status = 6;
      }

      $sql_rev = "SELECT value FROM serverinfo WHERE name = 'updaterev'";
      $debuginfo[] = $sql_rev;
      $result_rev = pg_query($pgconn, $sql_rev);
      $row_rev = pg_fetch_assoc($result_rev);
      $server_rev = $row_rev['value'];
      if ($server_rev != $sensorrev) {
        $status = 7;
      }

      $statustext = $v_sensorstatus_ar[$status]["text"];

      $item = new rssGenerator_item();
      $item->title = "$sensor is $statustext";
      $item->description = "Status code: $status";
      $item->pubDate = "$ts";
      $rss_channel->items[] = $item;
    }
  }

  ################################
  # Generating the RSS feed
  ################################

  $sql_update = "UPDATE report_content SET last_sent = '$ts_end' WHERE id = '$rcid'";
  $debuginfo[] = $sql_update;
  $result = pg_query($pgconn, $sql_update);

  #debug_sql();

  # Creating and showing the result
  $rss_feed = new rssGenerator_rss();
  $rss_feed->encoding = 'UTF-8';
  $rss_feed->version = '2.0';
  header('Content-Type: text/xml');
  echo $rss_feed->createFeed($rss_channel);
} else {
  ################################
  # General RSS stuff
  ################################
  $time = date($c_date_format);
  $rss_channel = new rssGenerator_channel();
  $rss_channel->title = "SURF IDS RSS Feed";
  $rss_channel->link = "$c_webinterface_prefix/rssfeed.php?int_rcid=$rcid";
  $rss_channel->description = "Error: $info";
  $rss_channel->language = "EN-en";
  $rss_channel->generator = "SURF IDS RSS Feed " .$l['rf_generator'];
  $rss_channel->webMaster = 'ids-beheer@surfnet.nl';

  # Creating and showing the result
  $rss_feed = new rssGenerator_rss();
  $rss_feed->encoding = 'UTF-8';
  $rss_feed->version = '2.0';
  header('Content-Type: text/xml');
  echo $rss_feed->createFeed($rss_channel);
}

?>
