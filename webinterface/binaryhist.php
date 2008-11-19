<?php $tab="3.4"; $pagetitle="Binary Info"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFids 2.10                     #
# Changeset 003                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 003 Fixed a bug with long virusnames
# 002 Added last scanned timestamp
# 001 Added language support
#############################################

$err = 0;
# Retrieving posted variables from $_GET
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
  $m = 124;
  geterror($m);
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
    $m = 124;
    geterror($m);
  }
} else {
  geterror($m);
}

if ($err == 0) {
  $sql_bindetail = "SELECT fileinfo, filesize, last_scanned FROM binaries_detail WHERE bin = $bin_id";
  $result_bindetail = pg_query($pgconn, $sql_bindetail);
  $row_bindetail = pg_fetch_assoc($result_bindetail);
  $filesize = $row_bindetail['filesize'];
  $filesize = size_hum_read($filesize);
  $fileinfo = $row_bindetail['fileinfo'];
  $last_scanned = $row_bindetail['last_scanned'];
  if ("$last_scanned" == "") {
    $last_scanned = $l['mr_never'];
  }

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

  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['bh_binary_info']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<td><b>" .$l['bh_binary']. "</b></td>";
              echo "<td>";
                echo "$bin_name";
                if (file_exists("$c_surfidsdir/binaries/$bin_name") && $s_admin == 1 && $c_download_binaries == 1) {
                  echo "[<a href='download.php?md5_binname=$bin_name'>". $l['bh_download']. "</a>]\n";
                }
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td><b>" .$l['bh_size']. "</b></td><td>$filesize</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td><b>" .$l['g_info']. "</b></td><td>$fileinfo</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td><b>" .$l['bh_first_seen']. "</b></td><td>$first_seen</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td><b>" .$l['bh_last_seen']. "</b></td><td>$last_seen</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td><b>" .$l['bh_last_scanned']. "</b></td><td>$last_scanned</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>
 
  $sql_norman = "SELECT result FROM norman WHERE binid = $bin_id";
  $result_norman = pg_query($pgconn, $sql_norman);
  $numrows_norman = pg_num_rows($result_norman);
  $debuginfo[] = $sql_norman;
 
  if ($numrows_norman != 0) {
    $row_norman = pg_fetch_assoc($result_norman);
    $normanresult = $row_norman['result'];
    echo "<div class='centerbig'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>" .$l['bh_norman']. "</div>\n";
          echo "<div class='blockContent'>\n";
              echo "<pre>$normanresult</pre>";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</centerbig>
  }

  if ($c_cws == 1) {
    $sql_cwsandbox = "SELECT result FROM cwsandbox WHERE binid = $bin_id";
    $result_cwsandbox = pg_query($pgconn, $sql_cwsandbox);
    $numrows_cwsandbox = pg_num_rows($result_cwsandbox);
    $debuginfo[] = $sql_cwsandbox;
    if ($numrows_cwsandbox != 0) {
      $row_cwsandbox = pg_fetch_assoc($result_cwsandbox);
      $cwsandboxresult = $row_cwsandbox['result'];

      echo "<div class='centerbig'>\n";
        echo "<div class='block'>\n";
          echo "<div class='dataBlock'>\n";
            echo "<div class='blockHeader'>" .$l['bh_cws']. "</div>\n";
            echo "<div class='blockContent'>\n";
              echo "<div id='cwsandbox'>";
                echo "$cwsandboxresult";
              echo "</div>\n";
            echo "</div>\n"; #</blockContent>
            echo "<div class='blockFooter'></div>\n";
          echo "</div>\n"; #</dataBlock>
        echo "</div>\n"; #</block>
      echo "</div>\n"; #</centerbig>
    }
  }

  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['bh_binaryhist']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='15%'>Timestamp</a></th>\n";
              $sql_getscanners = "SELECT id, name FROM scanners";
              $debuginfo[] = $sql_getscanners;
              $result_getscanners = pg_query($pgconn, $sql_getscanners);
              while ($row_scanners = pg_fetch_assoc($result_getscanners)) {
                $scanner_id = $row_scanners['id'];
                $scanner_name = $row_scanners['name'];
                echo "<th width='15%'>$scanner_name</th>\n";
              }
              pg_result_seek($result_getscanners, 0);
            echo "</tr>\n";

            while ($row = pg_fetch_assoc($result_binhist)) {
              $timestamp = $row['timestamp'];
              $ts = date("d-m-Y H:i:s", $timestamp);
              echo "<tr>\n";
                echo "<td>$ts</td>\n";
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
                    if (strlen($virus) > 23) {
                      $virustext = substr($virus, 0, 20) ."...";
                      $virus_html = "<font class='warning' " .printover($virus). ">$virustext</font>";
                    } else {
                      $virus_html = "<font class='warning'>$virus</font>";
                    }
                  }
                  echo "<td>$virus_html</td>\n";
                }
                pg_result_seek($result_getscanners, 0);
              echo "</tr>\n";
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>

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

  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['bh_filenames']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            $filename_ar = array();
            $i = 0;
            while ($row_filename = pg_fetch_assoc($result_filename) ) {
              $filename = basename($row_filename['text']);
              if (!$filename_ar[$filename]) {
                $i++;
                $filename_ar[$filename] = $filename;
                echo "<tr>\n";
                  echo "<td>$filename</td>\n";
                echo "</tr>\n";
              }
            }
            if ($i >= 10) {
              if ($show != "all") {
                echo "<tr>\n";
                  echo "<td><a href='binaryhist.php?int_binid=$bin_id&show=all'>" .$l['bh_full']. "</a></td>\n";
                echo "</tr>\n";
              } else {
                echo "<tr>\n";
                  echo "<td><a href='binaryhist.php?int_binid=$bin_id&show=top'>" .$l['bh_top10']. "</a></td>\n";
                echo "</tr>\n";
              }
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>
}
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
