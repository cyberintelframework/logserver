<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 19-11-2009                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';
include_once '../include/rss_generator.inc.php';

# Starting the session
session_start();

# Including language file
include "../lang/${c_language}.php";

$allowed_get = array(
	"int_rcid"
);
$check = extractvars($_GET, $allowed_get);

# Declare timespans
$minute = 60;
$hour = (60 * $minute);
$day = (24 * $hour);
$week = (7 * $day);

if ($err == 0) {
  if (!isset($clean['rcid'])) {
    $err = 1;
    $m = 143;
    $info = "Could not find feed!";
  } else {
    $rcid = $clean['rcid'];
    $sql_getfeed = "SELECT id, subject, template, sensor_id, severity, last_sent, detail, orgid ";
    $sql_getfeed .= " FROM report_content WHERE id = '$rcid' AND active = TRUE AND public = TRUE";
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
  $q_org = $row_feed['orgid'];

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
  if ($q_org == 0) {
    $andorg = "";
  } else {
    $andorg = "AND sensors.organisation = '$q_org'";
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

    if ($q_org != 0) {
      # IP Exclusion stuff
      $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org) ";
    }
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

      $pattern = '/^.*Dialogue$/';
      if (preg_match($pattern, $text)) {
        $text = str_replace("Dialogue", "", $text);
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
      if ($detail == 11) {
        $title = "New $att detected (attack ID: $attackid)!";
        $item->title = "$title";
        $item->description = "$description";
      } else {
        $title = $description;
        $item->title = "$title";
      }
      $item->link = $link;
      $rss_ts = date("r", $ts);
      $item->pubDate = "$rss_ts";
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

        if ($q_org != 0) {
          # IP Exclusion stuff
          $sql .= " AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $org) ";
        }
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
        if ($detail == 11) {
          $title = "New $att detected (attack ID: $attackid)!";
          $item->title = "$title";
          $item->description = "$description";
        } else {
          $title = $description;
          $item->title = "$title";
        }
        $item->link = $link;
        $rss_ts = date("r", $ts);
        $item->pubDate = "$rss_ts";
        $rss_channel->items[] = $item;
      }
    } else {
      $item = new rssGenerator_item();
      $item->title = $l['rf_noranges'];
      $rss_ts = date("r", $ts);
      $item->pubDate = "$rss_ts";
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
      $rss_ts = date("r", $ts);
      $item->pubDate = "$rss_ts";
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
