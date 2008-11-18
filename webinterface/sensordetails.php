<?php $tab="4.1"; $pagetitle="Sensor Details"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFids 2.00.04                  #
# Changeset 004                    #
# 17-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 004 Added md5_hash to purge.php url
# 003 Added sensor version info
# 002 Added hash check
# 001 Initial release
#############################################

# Retrieving posted variables from $_GET
$allowed_get = array(
        "int_sid",
		"int_logfilter",
		"strip_escape_html_label",
		"int_m",
		"int_dellabel",
		"md5_hash"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking $_GET'ed variables
if (isset($clean['sid'])) {
  $sid = $clean['sid'];
} else {
  $m = 110;
  $err = 1;
}

if (isset($clean['logfilter'])) {
  $logfilter = $clean['logfilter'];
} elseif (isset($c_logfilter)) {
  $logfilter = $c_logfilter;
} else {
  $logfilter = 30;
}

if ($err == 0) {
  if ($s_access_sensor < 9) {
    $sql = "SELECT id FROM sensors WHERE organisation = '$q_org'";
    $result = pg_query($pgconn, $sql);
    $num = pg_num_rows($result);
    if ($num == 0) {
      $err = 1;
      $m = 101;
    }
  }
  if ($err == 0) {
    # Updating label name if needed
    if ($s_access_sensor > 0) {
      if (isset($clean['label'])) {
        # Checking if the logged in user actually requested this action.
        if ($clean['hash'] != $s_hash) {
          $m = 116;
        } else {
          $label = $clean['label'];
          $sql = "UPDATE sensors SET label = '$label' WHERE id = $sid";
          $result = pg_query($pgconn, $sql);
          geterror(3);
        }
      } elseif (isset($clean['dellabel'])) {
        if ($clean['hash'] != $s_hash) {
          $m = 116;
        } else {
          if ($clean['dellabel'] == 1) {
            $sql = "UPDATE sensors SET label = '' WHERE id = $sid";
            $result = pg_query($pgconn, $sql);
          }
        }
      }
    }
  }
  $sql_details = "SELECT keyname, vlanid, label, remoteip, localip, tap, tapip, mac, laststart, laststop, lastupdate, uptime, status, ";
  $sql_details .= " organisations.organisation, version, rev, sensormac ";
  $sql_details .= " FROM sensors, organisations WHERE sensors.id = '$sid' AND sensors.organisation = organisations.id ";
  $result_details = pg_query($pgconn, $sql_details);
  $debuginfo[] = $sql_details;
  $num = pg_num_rows($result_details);
  if ($num == 0) {
    $err = 1;
    $m = 110;
  }
}

if (isset($clean['m'])) {
  geterror($clean['m']);
}

if ($err != 1) {
  $row = pg_fetch_assoc($result_details);

  $keyname = $row['keyname'];
  $vlanid = $row['vlanid'];
  $sensor = sensorname($keyname, $vlanid);
  $label = $row['label'];
  $remote = $row['remoteip'];
  $local = $row['localip'];
  $tap = $row['tap'];
  $tapip = $row['tapip'];
  $mac = $row['mac'];
  $start = $row['laststart'];
  $start_text = date("d-m-Y H:i:s", $start);
  $stop = $row['laststop'];
  $stop_text = date("d-m-Y H:i:s", $stop);
  $update = $row['lastupdate'];
  $update_text = date("d-m-Y H:i:s", $update);
  $totaltime_text = sec_to_string($totaltime);
  $uptime = date("U") - $start;
  if ($start != "") {
    $uptime = date("U") - $start;
  } else {
    $uptime = 0;
  }
  $uptime_text = sec_to_string($uptime);
  $status = $row['status'];
  $org = $row['organisation'];
  $sensor_rev = $row['rev'];
  $version = $row['version'];
  $sensormac = $row['sensormac'];

  # Fetching server updates revision
  $sql_rev = "SELECT value FROM serverinfo WHERE name = 'updaterev'";
  $debuginfo[] = $sql_rev;
  $result_rev = pg_query($pgconn, $sql_rev);
  $row_rev = pg_fetch_assoc($result_rev);
  $server_rev = $row_rev['value'];

  $cstatus = sensorstatus();

  $sql_attack = "SELECT timestamp FROM attacks WHERE sensorid = '$sid' ORDER BY timestamp ASC LIMIT 1";
  $debuginfo[] = $sql_attack;
  $result_attack = pg_query($pgconn, $sql_attack);
  $num_attack = pg_num_rows($result_attack);
  if ($num_attack > 0) {
    $row_attack = pg_fetch_assoc($result_attack);
    $first_attack = $row_attack['timestamp'];
    $first_attack = date("d-m-Y H:i:s", $first_attack);
  } else {
    $first_attack = "";
  }
  if ($label != "") {
    $header = $label;
  } else {
    $header = $sensor;
  }

  echo "<div class='all'>\n";
    echo "<div class='leftsmall'>\n";
      echo "<div class='block'>\n";
        echo "<div class='actionBlock'>\n";
          echo "<div class='blockHeader'>Actions</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<form name='sensoractions' method='get' action='purge.php?md5_hash=$s_hash'>\n";
              echo "Purge all events older than ";
              echo "<select name='int_time' onchange='this.form.submit();'>\n";
                echo printOption(0, "", 0);
                foreach ($v_sensor_purge_ar as $key => $val) {
                  echo printOption($key, $val, -1);
                }
              echo "</select>\n";
              echo "<input type='hidden' name='int_sid' value='$sid' />\n";
            echo "</form>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</leftsmall>
  echo "</div>\n"; #</all>

  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>$header</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr><th colspan='2'>Name</th></tr>\n";
            echo "<tr>\n";
              echo "<td width='150'>ID:</td>\n";
              echo "<td width='300'>$sid</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Sensor name:</td>\n";
              echo "<td>$sensor</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Sensor label:</td>\n";
              echo "<td>";
                if ($s_access_sensor > 0) {
                  echo "<form name='chg_label' method='get'>\n";
                    echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                    echo "<div class='aleft'>\n";
                      echo "<input type='text' name='strip_escape_html_label' value='$label' />";
                    echo "</div>\n";
                    echo "<div class='aright'>\n";
                      echo "<input type='button' onclick=\"window.location='sensordetails.php?int_sid=$sid&int_dellabel=1&md5_hash=$s_hash';\" class='button' value='Clear' />\n";
                      echo "<input type='submit' value='Save' class='button' />\n";
                    echo "</div>\n";
                    echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                  echo "</form>\n";
                } else {
                  echo "$label";
                }
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Domain:</td>\n";
              echo "<td>$org</td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='2'>Sensor side</th></tr>\n";
            echo "<tr>\n";
              echo "<td>Remote IP:</td>\n";
              echo "<td>$remote</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Local IP:</td>\n";
              echo "<td>$local</td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='2'>Server side</th></tr>\n";
            echo "<tr>\n";
              echo "<td>Device:</td>\n";
              echo "<td>$tap</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Device MAC:</td>\n";
              echo "<td>$mac</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Device IP:</td>\n";
              echo "<td>$tapip</td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='2'>Status</th></tr>\n";
            echo "<tr>\n";
              echo "<td>Last started:</td>\n";
              echo "<td>$start_text</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Last stopped:</td>\n";
              echo "<td>$stop_text</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Last updated:</td>\n";
              echo "<td>$update_text</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Status:</td>\n";
              echo "<td>";
                echo "<div class='sensorstatus'>";
                  echo "<div class='" .$v_sensorstatus_ar[$cstatus]["class"]. "'>";
                    echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[$cstatus]["text"]. "</div>";
                  echo "</div>\n";
                echo "</div>";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  $sql_count = "SELECT COUNT(id) as total FROM sensors_log WHERE sensorid = '$sid'";
  $debuginfo[] = $sql_count;
  $result_count = pg_query($pgconn, $sql_count);
  $rowcount = pg_fetch_assoc($result_count);
  $num_events = $rowcount['total'];

  $sql_events = "SELECT logmessages.log, sensors_log.timestamp, sensors_log.args FROM sensors_log, logmessages WHERE sensors_log.sensorid = '$sid' ";
  $sql_events .= " AND sensors_log.logid = logmessages.id ";
  if ($logfilter != -1) {
    $sql_events .= " AND logmessages.type >= $logfilter ";
  }
  $sql_events .= " ORDER BY sensors_log.timestamp DESC";
  $debuginfo[] = $sql_events;
  $result_events = pg_query($pgconn, $sql_events);

  echo "<div class='rightmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Sensor log</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='sensorlog'>\n"; 
          echo "<input type='hidden' name='int_sid' value='$sid' />\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th colspan='2'>Uptime</th>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td width='150'>Logging since:</td>\n";
              echo "<td width='320'>$first_attack</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Total log time:</td>\n";
              echo "<td><span id='js_total'>$totaltime_text</span></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Uptime:</td>\n";
              echo "<td><span id='js_uptime'>$uptime_text</span></td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='2'>Sensor scripts</th></tr>\n";
            echo "<tr>\n";
              echo "<td>Scripts revision:</td>\n";
              echo "<td>$sensor_rev</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td colspan='2'>\n";
                $version_ar = split(",", $version);
                echo "<textarea id='version'>";
                  foreach ($version_ar as $key => $a_ver) {
                    print "$a_ver\n";
                  }
                echo "</textarea>\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='2'>Events</th></tr>\n";
            echo "<tr>\n";
              echo "<td>Total number of events:</td>\n";
              echo "<td>";
                echo "<div class='aleft'><div class='text'>$num_events</div></div>\n";
                echo "<div class='aright'>";
                  echo "<select name='int_logfilter' class='smallselect' onchange='document.sensorlog.submit();'>";
                    echo printOption(-1, "All", $logfilter);
                    foreach ($v_logmessages_type_ar as $key => $val) {
                      echo printOption($key, "$val", $logfilter);
                    }
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td colspan='2'>\n";
                echo "<textarea id='eventlog'>";
                  while ($row_events = pg_fetch_assoc($result_events)) {
                    $ev_timestamp = $row_events['timestamp'];
                    $ev_timestamp = date("d-m-Y H:i:s", $ev_timestamp);
                    $ev_log = $row_events['log'];
                    $ev_args = $row_events['args'];
                    echo show_log_message($ev_timestamp, $ev_log, $ev_args);
                  }
                echo "</textarea>\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "</form>\n";
          echo "<input type='hidden' name='js_hidtotal' id='js_hidtotal' value='$totaltime' />\n";
          echo "<input type='hidden' name='js_hiduptime' id='js_hiduptime' value='$uptime' />\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</rightmed>

  echo "<script>\n";
  echo "startclock();\n";
  echo "</script>\n";
} else {
  geterror($m);
}

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
