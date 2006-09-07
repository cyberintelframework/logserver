<?php include("menu.php"); set_title("Binary Info"); ?>
<?php

###################################
# SURFnet IDS                     #
# Version 1.02.06                 #
# 08-08-2006                      #
# Kees Trippelvitz & Peter Arts   #
###################################

#########################################################################
# Changelog:
# 1.02.06 intval() for $timestamp and $s_org
# 1.02.05 Added some input checks
# 1.02.04 Fixed a $_GET vulnerability
# 1.02.03 Initial release
#########################################################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';

$s_org = intval($_SESSION['s_org']);
$s_admin = $_SESSION['s_admin'];
$err = 0;

if ( isset($_GET['bin']) ){
  $bin = pg_escape_string($_GET['bin']);
  $bin = stripinput($bin);
}
else {
  $err = 1;
}

if (isset($_GET['show'])) {
  $show = pg_escape_string($_GET['show']);
  $show = stripinput($show);
}
else {
  $show = "top";
}

$sql_binhist = "SELECT DISTINCT timestamp FROM binaries WHERE bin = '$bin' ORDER BY timestamp";
$result_binhist = pg_query($pgconn, $sql_binhist);

$sql_bindetail = "SELECT fileinfo, filesize FROM binaries_detail WHERE bin = '$bin'";
$result_bindetail = pg_query($pgconn, $sql_bindetail);
$row_bindetail = pg_fetch_assoc($result_bindetail);
$filesize = $row_bindetail['filesize'];
$filesize = size_hum_read($filesize);
$fileinfo = $row_bindetail['fileinfo'];

$sql_firstseen = "SELECT attacks.timestamp, details.* FROM attacks, details WHERE details.attackid = attacks.id AND details.type = 8 AND details.text = '$bin' ORDER BY attacks.timestamp ASC LIMIT 1";
$result_firstseen = pg_query($pgconn, $sql_firstseen);
$row_firstseen = pg_fetch_assoc($result_firstseen);
$first_seen = $row_firstseen['timestamp'];
$first_seen = date("d-m-Y H:i:s", $first_seen);

$sql_lastseen = "SELECT attacks.timestamp, details.* FROM attacks, details WHERE details.attackid = attacks.id AND details.type = 8 AND details.text = '$bin' ORDER BY attacks.timestamp DESC LIMIT 1";
$result_lastseen = pg_query($pgconn, $sql_lastseen);
$row_lastseen = pg_fetch_assoc($result_lastseen);
$last_seen = $row_lastseen['timestamp'];
$last_seen = date("d-m-Y H:i:s", $last_seen);

echo "<table class='datatable'>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='dataheader' width='100'>Binary</td><td class='datatd'>$bin</td>\n";
  echo "</tr>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='dataheader'>Size</td><td class='datatd'>$filesize</td>\n";
  echo "</tr>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='dataheader'>Info</td><td class='datatd'>$fileinfo</td>\n";
  echo "</tr>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='dataheader'>First Seen</td><td class='datatd'>$first_seen</td>\n";
  echo "</tr>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='dataheader'>Last Seen</td><td class='datatd'>$last_seen</td>\n";
  echo "</tr>\n";
echo "</table>\n";
echo "<br />\n";

echo "<b>Binary History</b><br />\n";
echo "<table class='datatable' width='100%'>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' width='15%'>Timestamp</a></td>\n";
    echo "<td class='dataheader' width='15%'>ClamAV</a></td>\n";
    if ($bdc == 1) {
      echo "<td class='dataheader' width='15%'>BitDefender</a></td>\n";
    }
    if ($antivir == 1) {
      echo "<td class='dataheader' width='15%'>Avira</a></td>\n";
    }
  echo "</tr>\n";

while ($row = pg_fetch_assoc($result_binhist)) {
  $timestamp = intval($row['timestamp']);
  $ts = date("d-m-Y H:i:s", $timestamp);
  echo "<tr class='datatr'>\n";
    echo "<td class='datatd'>$ts</td>\n";
    $sql_gettime = "SELECT * FROM binaries WHERE timestamp = $timestamp AND bin = '$bin'";
    $result_gettime = pg_query($pgconn, $sql_gettime);
    while ($row = pg_fetch_assoc($result_gettime)) {
      $scanner = $row['scanner'];
      $info = $row['info'];
      if ($info == "Suspicious" || $info == "Not scanned yet") {
        $virus_ar[$scanner] = $info;
      }
      else {
        $virus_ar[$scanner] = "<font color='red'>" .$info. "</font>";
      }
    }
    echo "<td class='datatd'>$virus_ar[ClamAV]</td>\n";
    if ($bdc == 1) {
      echo "<td class='datatd'>$virus_ar[BitDefender]</td>\n";
    }
    if ($antivir == 1) {
      echo "<td class='datatd'>$virus_ar[Antivir]</td>\n";
    }
  echo "</tr>\n";
}
echo "</table>\n";
echo "<br />\n";
if ($show == "all") {
  $sql_filename = "SELECT DISTINCT text FROM details WHERE details.type = 4 AND attackid IN (SELECT DISTINCT attackid FROM details WHERE text = '$bin')";
}
else {
  $sql_filename = "SELECT DISTINCT text FROM details WHERE details.type = 4 AND attackid IN (SELECT DISTINCT attackid FROM details WHERE text = '$bin') LIMIT 10";
}
$result_filename = pg_query($pgconn, $sql_filename);

echo "<table class='datatable'>\n";
  echo "<tr class='datatr'>\n";
    echo "<td class='dataheader'>Filenames used</td>\n";
  echo "</tr>\n";

$filename_ar = array();
$i = 0;

while ($row_filename = pg_fetch_assoc($result_filename) ) {
  $filename = basename($row_filename['text']);

  if (!$filename_ar[$filename]) {
    $i++;
    $filename_ar[$filename] = $filename;
    echo "<tr class='datatr'>\n";
      echo "<td class='datatd'>$filename</td>\n";
    echo "</tr>\n";
  }
}
if ($i >= 10) {
  if ($show != "all") {
    echo "<tr>\n";
      echo "<td><a href='binaryhist.php?bin=$bin&show=all'>Show full list</a></td>\n";
    echo "</tr>\n";
  }
  else {
    echo "<tr>\n";
      echo "<td><a href='binaryhist.php?bin=$bin&show=top'>Show top 10</a></td>\n";
    echo "</tr>\n";
  }
}
echo "</table>\n";
?>
<?php footer(); ?>
