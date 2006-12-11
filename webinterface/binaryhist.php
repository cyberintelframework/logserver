<?php include("menu.php"); set_title("Binary Info"); ?>
<?php

###################################
# SURFnet IDS                     #
# Version 1.04.02                 #
# 11-12-2006                      #
# Kees Trippelvitz & Peter Arts   #
###################################

#############################################
# Changelog:
# 1.04.02 Changed debug stuff
# 1.04.01 Added debugging for $sql_filename
# 1.03.02 Fixed a concatenation bug
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 added intval() to session variables + pattern match on show variable + record existancy check + pg_close
# 1.02.05 Added input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$err = 0;

if ( isset($_GET['binid']) ){
  $bin_id = intval($_GET['binid']);

  $sql_binname = "SELECT name FROM uniq_binaries WHERE id = $bin_id";
  $result_binname = pg_query($pgconn, $sql_binname);
  $bin_name = pg_result($result_binname, "name");

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_BINNAME: $sql_binname\n";
    echo "</pre>\n";
  }
} elseif (isset($_GET['binname'])) {
  $bin_name = pg_escape_string($_GET['binname']);

  $sql_binid = "SELECT id FROM uniq_binaries WHERE name = '$bin_name'";
  $result_binid = pg_query($pgconn, $sql_binid);
  $bin_id = pg_result($result_binid, "id");

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_BINID: $sql_binid\n";
    echo "</pre>\n";
  }
} else {
  $err = 1;
}

if (isset($_GET['show'])) {
  $show = $_GET['show'];
  $pattern = '/^(top|all)$/';
  if (!preg_match($pattern, $show)) {
    $show = "top";
  } else {
    $show = stripinput($_GET['show']);
  }
} else {
  $show = "top";
}

$sql_binhist = "SELECT DISTINCT timestamp FROM binaries WHERE bin = $bin_id ORDER BY timestamp";
$result_binhist = pg_query($pgconn, $sql_binhist);
$numrows_binhist = pg_num_rows($result_binhist);

$sql_bindet = "SELECT id FROM binaries_detail WHERE bin = $bin_id";
$result_bindet = pg_query($pgconn, $sql_bindet);
$numrows_bindet = pg_num_rows($result_bindet);

# Debug info
if ($debug == 1) {
  echo "<pre>";
  echo "SQL_BINHIST: $sql_binhist\n";
  echo "SQL_BINDET: $sql_bindet\n";
  echo "</pre>\n";
}

if ($numrows_binhist == 0 && $numrows_det == 0) {
  $err = 1;
  echo "<font color='red'>No record could be found for the given binary!</font>\n";
}

if ($err == 0) {
  $sql_bindetail = "SELECT fileinfo, filesize FROM binaries_detail WHERE bin = $bin_id";
  $result_bindetail = pg_query($pgconn, $sql_bindetail);
  $row_bindetail = pg_fetch_assoc($result_bindetail);
  $filesize = $row_bindetail['filesize'];
  $filesize = size_hum_read($filesize);
  $fileinfo = $row_bindetail['fileinfo'];

  $sql_firstseen = "SELECT attacks.timestamp, details.* ";
  $sql_firstseen .= "FROM attacks, details ";
  $sql_firstseen .= "WHERE details.attackid = attacks.id AND details.type = 8 AND details.text = '$bin_name'";
  $sql_firstseen .= "ORDER BY attacks.timestamp ASC LIMIT 1";
  $result_firstseen = pg_query($pgconn, $sql_firstseen);
  $row_firstseen = pg_fetch_assoc($result_firstseen);
  $first_seen = $row_firstseen['timestamp'];
  $first_seen = date("d-m-Y H:i:s", $first_seen);

  $sql_lastseen = "SELECT attacks.timestamp, details.* ";
  $sql_lastseen .= "FROM attacks, details ";
  $sql_lastseen .= "WHERE details.attackid = attacks.id AND details.type = 8 AND details.text = '$bin_name'";
  $sql_lastseen .= "ORDER BY attacks.timestamp DESC LIMIT 1";
  $result_lastseen = pg_query($pgconn, $sql_lastseen);
  $row_lastseen = pg_fetch_assoc($result_lastseen);
  $last_seen = $row_lastseen['timestamp'];
  $last_seen = date("d-m-Y H:i:s", $last_seen);

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_BINHIST: $sql_binhist\n";
    echo "SQL_BINDETAIL: $sql_bindetail\n";
    echo "SQL_FIRSTSEEN: $sql_firstseen\n";
    echo "SQL_LASTSEEN: $sql_lastseen\n";
    echo "</pre>\n";
  }

  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader' width='100'>Binary</td><td class='datatd'>$bin_name</td>\n";
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
 
 $sql_norman = "SELECT result FROM norman WHERE binid = $bin_id";
 $result_norman = pg_query($pgconn, $sql_norman);
 $numrows_norman = pg_num_rows($result_norman);
 
 if ($numrows_norman != 0) {
    $row_norman = pg_fetch_assoc($result_norman);
    $normanresult = $row_norman['result'];
    echo "<b>Norman Result</b><br />\n";
    echo "<pre>$normanresult</pre>";
 }
 
  
  echo "<b>Binary History</b><br />\n";
  echo "<table class='datatable' width='100%'>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' width='15%'>Timestamp</a></td>\n";

      $sql_getscanners = "SELECT id, name FROM scanners";
      $result_getscanners = pg_query($pgconn, $sql_getscanners);
      while ($row_scanners = pg_fetch_assoc($result_getscanners)) {
        $scanner_id = $row_scanners['id'];
        $scanner_name = $row_scanners['name'];
        echo "<td class='dataheader' width='15%'>$scanner_name</td>\n";
      }
      pg_result_seek($result_getscanners, 0);
    echo "</tr>\n";

  while ($row = pg_fetch_assoc($result_binhist)) {
    $timestamp = $row['timestamp'];
    $ts = date("d-m-Y H:i:s", $timestamp);

    echo "<tr class='datatr'>\n";
      echo "<td class='datatd'>$ts</td>\n";

      while ($row_scanners = pg_fetch_assoc($result_getscanners)) {
        $scanner_id = $row_scanners['id'];
        $sql_getvirus = "SELECT stats_virus.name FROM stats_virus, binaries WHERE stats_virus.id = binaries.info ";
        $sql_getvirus .= "AND binaries.scanner = $scanner_id AND binaries.bin = $bin_id AND binaries.timestamp = $timestamp";
        $result_getvirus = pg_query($pgconn, $sql_getvirus);
        $virus = pg_result($result_getvirus, "name");

        if (!isset($vir_ar[$scanner_id])) {
          if ($virus == "") {
            $virus = "Not scanned yet";
          }
          $vir_ar[$scanner_id] = $virus;
        } else {
          if ($virus == "") {
            $virus = $vir_ar[$scanner_id];
          } elseif ($vir_ar[$scanner_id] != $virus) {
            $vir_ar[$scanner_id] = $virus;
          }
        }
        $known_virus_ar[$scanner_id] = $virus;
        # Debug info
        if ($debug == 1) {
          echo "<pre>";
          echo "SQL_GETVIRUS: $sql_getvirus\n";
          echo "</pre>\n";
        }

        if ($virus == "Suspicious" || $virus == "Not scanned yet") {
          $virus_html = $virus;
        } else {
          $virus_html = "<font color='red'>" .$virus. "</font>";
        }
        echo "<td class='datatd'>$virus_html</td>\n";
      }
      pg_result_seek($result_getscanners, 0);
    echo "</tr>\n";
  }
  echo "</table>\n";
  echo "<br />\n";
 
 


 if ($show == "all") {
    $sql_filename = "SELECT DISTINCT text ";
    $sql_filename .= "FROM details ";
    $sql_filename .= "WHERE details.type = 4 AND attackid IN (SELECT DISTINCT attackid FROM details WHERE text = '$bin_name')";
  } else {
    $sql_filename = "SELECT DISTINCT text ";
    $sql_filename .= "FROM details ";
    $sql_filename .= "WHERE details.type = 4 AND attackid IN (SELECT DISTINCT attackid FROM details WHERE text = '$bin_name') LIMIT 10";
  }
  $result_filename = pg_query($pgconn, $sql_filename);

  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_FILENAME: $sql_filename\n";
    echo "</pre>\n";
  }

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
        echo "<td><a href='binaryhist.php?bin=$bin_id&show=all'>Show full list</a></td>\n";
      echo "</tr>\n";
    } else {
      echo "<tr>\n";
        echo "<td><a href='binaryhist.php?bin=$bin_id&show=top'>Show top 10</a></td>\n";
      echo "</tr>\n";
    }
  }
  echo "</table>\n";
}
pg_close($pgconn);
debug();
?>
<?php footer(); ?>
