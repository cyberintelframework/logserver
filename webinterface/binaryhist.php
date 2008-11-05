<?php include("menu.php"); set_title("Binary Info"); ?>
<?php

###################################
# SURFnet IDS                     #
# Version 1.04.07                 #
# 09-08-2007                      #
# Kees Trippelvitz & Peter Arts   #
###################################

#############################################
# Changelog:
# 1.04.07 Fixed int_binid bug
# 1.04.06 Added check for binary history info
# 1.04.05 Added download option for binaries
# 1.04.04 Changed data input handling
# 1.04.03 Fixed typo
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
$s_admin = intval($_SESSION['s_admin']);
$err = 0;

$allowed_get = array(
                "int_binid",
                "md5_binname",
		"show"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['binid']) ){
  $bin_id = $clean['binid'];

  $sql_binname = "SELECT name FROM uniq_binaries WHERE id = $bin_id";
  $result_binname = pg_query($pgconn, $sql_binname);
  $bin_name = pg_result($result_binname, "name");
  $debuginfo[] = $sql_binname;
} elseif (isset($clean['binname'])) {
  $bin_name = $clean['binname'];

  $sql_binid = "SELECT id FROM uniq_binaries WHERE name = '$bin_name'";
  $result_binid = pg_query($pgconn, $sql_binid);
  $bin_id = pg_result($result_binid, "id");
  $debuginfo[] = $sql_binid;
} else {
  $err = 1;
}

if ($bin_id == "") {
  $err = 1;
  echo "<font color='red'>No binary info found!</font><br />\n";
}

if (isset($tainted['show'])) {
  $show = $tainted['show'];
  $pattern = '/^(top|all)$/';
  if (!preg_match($pattern, $show)) {
    $show = "top";
  } else {
    $show = $tainted['show'];
  }
} else {
  $show = "top";
}

if ($err == 0) {
  $sql_binhist = "SELECT DISTINCT timestamp FROM binaries WHERE bin = $bin_id ORDER BY timestamp";
  $result_binhist = pg_query($pgconn, $sql_binhist);
  $numrows_binhist = pg_num_rows($result_binhist);

  $sql_bindet = "SELECT id FROM binaries_detail WHERE bin = $bin_id";
  $result_bindet = pg_query($pgconn, $sql_bindet);
  $numrows_bindet = pg_num_rows($result_bindet);

  $debuginfo[] = $sql_binhist;
  $debuginfo[] = $sql_bindet;

  if ($numrows_binhist == 0 && $numrows_bindet == 0) {
    $err = 1;
    $m = 91;
    $m = geterror($m);
    echo $m;
  }
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
  $sql_firstseen .= "WHERE details.attackid = attacks.id AND details.type = 8 AND details.text = '$bin_name' ";
  $sql_firstseen .= "ORDER BY attacks.timestamp ASC LIMIT 1";
  $result_firstseen = pg_query($pgconn, $sql_firstseen);
  $row_firstseen = pg_fetch_assoc($result_firstseen);
  $first_seen = $row_firstseen['timestamp'];
  $first_seen = date("d-m-Y H:i:s", $first_seen);

  $sql_lastseen = "SELECT attacks.timestamp, details.* ";
  $sql_lastseen .= "FROM attacks, details ";
  $sql_lastseen .= "WHERE details.attackid = attacks.id AND details.type = 8 AND details.text = '$bin_name' ";
  $sql_lastseen .= "ORDER BY attacks.timestamp DESC LIMIT 1";
  $result_lastseen = pg_query($pgconn, $sql_lastseen);
  $row_lastseen = pg_fetch_assoc($result_lastseen);
  $last_seen = $row_lastseen['timestamp'];
  $last_seen = date("d-m-Y H:i:s", $last_seen);

  $debuginfo[] = $sql_bindetail;
  $debuginfo[] = $sql_firstseen;
  $debuginfo[] = $sql_lastseen;

  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader' width='100'>Binary</td><td class='datatd'>";
        echo "$bin_name";
        if (file_exists("$c_surfidsdir/binaries/$bin_name") && $s_admin == 1 && $c_download_binaries == 1) {
          echo "&nbsp;&nbsp;[<a href='download.php?md5_binname=$bin_name'>download</a>]\n";
        }
      echo "</td>\n";
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
  $debuginfo[] = $sql_norman;
 
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
      $debuginfo[] = $sql_getscanners;
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
        $debuginfo[] = $sql_getvirus;

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
  $debuginfo[] = $sql_filename;

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
        echo "<td><a href='binaryhist.php?int_binid=$bin_id&show=all'>Show full list</a></td>\n";
      echo "</tr>\n";
    } else {
      echo "<tr>\n";
        echo "<td><a href='binaryhist.php?int_binid=$bin_id&show=top'>Show top 10</a></td>\n";
      echo "</tr>\n";
    }
  }
  echo "</table>\n";
}
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
