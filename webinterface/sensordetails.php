<?php $tab="4.1"; $pagetitle="Sensor Details"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 2.10                     #
# Changeset 006                    #
# 03-09-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 006 Fixed sensorstatus calc bug
# 005 Added network config
# 004 Changed sensor $status stuff
# 003 Fixed uptime when uptime = NULL
# 002 Removed some status leds
# 001 Modified info message number for changing label
#############################################

# Retrieving posted variables from $_GET
$allowed_get = array(
        "int_sid",
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

if ($err == 0) {
  if ($s_access_sensor < 9) {
    $sql = "SELECT id FROM sensors WHERE organisation = '$q_org' AND id = $sid ";
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
  $sql_details = "SELECT sensors.keyname, vlanid, label, remoteip, localip, tap, tapip, mac, laststart, laststop, lastupdate, uptime, status, ";
  $sql_details .= " organisations.organisation, rev, sensormac, sensortype, mainconf, sensortype, permanent ";
  $sql_details .= " FROM sensors, organisations, sensor_details WHERE sensors.id = '$sid' AND sensors.organisation = organisations.id ";
  $sql_details .= " AND sensors.keyname = sensor_details.keyname ";
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

  # Name
  $keyname = $row['keyname'];
  $vlanid = $row['vlanid'];
  $sensor = sensorname($keyname, $vlanid);
  $label = $row['label'];
  $org = $row['organisation'];
  $sensor_rev = $row['rev'];

  # Sensor side
  $remote = $row['remoteip'];
  $local = $row['localip'];
  $sensormac = $row['sensormac'];
  $sensortype = $row['sensortype'];
  $mainconf = $row['mainconf'];
  $configtype = "dhcp";
  if ($mainconf != "dhcp") {
    $configtype = "static";
  }

  # Server side
  $tap = $row['tap'];
  $tapip = $row['tapip'];
  $mac = $row['mac'];

  # Status
  $start = $row['laststart'];
  $start_text = date($c_date_format, $start);
  $stop = $row['laststop'];
  $stop_text = date($c_date_format, $stop);
  $update = $row['lastupdate'];
  $update_text = date($c_date_format, $update);
  $status = $row['status'];
  $permanent = $row['permanent'];
  if ($permanent == 1) {
    $sensortype = "Permanent";
  }
  $cstatus = sensorstatus($status, $update, $uptime, $permanent);

  # Uptime
  if ($start != "") {
    $uptime = date("U") - $start;
  } else {
    $uptime = 0;
  }
  $uptime_text = sec_to_string($uptime);

  $sql_attack = "SELECT timestamp FROM attacks WHERE sensorid = '$sid' ORDER BY timestamp ASC LIMIT 1";
  $debuginfo[] = $sql_attack;
  $result_attack = pg_query($pgconn, $sql_attack);
  $num_attack = pg_num_rows($result_attack);
  if ($num_attack > 0) {
    $row_attack = pg_fetch_assoc($result_attack);
    $first_attack = $row_attack['timestamp'];
    $first_attack = date($c_date_format, $first_attack);
  } else {
    $first_attack = "";
  }
  if ($label != "") {
    $header = $label;
  } else {
    $header = $sensor;
  }

#  echo "<div class='all'>\n";
#    echo "<div class='leftsmall'>\n";
#      echo "<div class='block'>\n";
#        echo "<div class='actionBlock'>\n";
#          echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
#          echo "<div class='blockContent'>\n";
#            echo "<form name='sensoractions' method='get' action='purge.php'>\n";
#              echo $l['sd_purge']. " ";
#              echo "<select name='int_time' onchange='this.form.submit();'>\n";
#                echo printOption(0, "", 0);
#                foreach ($v_sensor_purge_ar as $key => $val) {
#                  echo printOption($key, $val, -1);
#                }
#              echo "</select>\n";
#              echo "<input type='hidden' name='int_sid' value='$sid' />\n";
#            echo "</form>\n";
#          echo "</div>\n"; #</blockContent>
#          echo "<div class='blockFooter'></div>\n";
#        echo "</div>\n"; #</dataBlock>
#      echo "</div>\n"; #</block>
#    echo "</div>\n"; #</leftsmall>
#  echo "</div>\n"; #</all>

  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>$header</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr><th colspan='4'>" .$l['sd_name']. "</th></tr>\n";
            echo "<tr>\n";
              echo "<td width='25%'>" .$l['g_id']. ":</td>\n";
              echo "<td width='75%' colspan='3'>$sid</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" ,$l['sd_sensorname']. ":</td>\n";
              echo "<td colspan='3'>$sensor</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_label']. ":</td>\n";
                if ($s_access_sensor > 0) {
                  echo "<form name='chg_label' method='get'>\n";
                    echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                    echo "<td colspan='3'><input type='text' name='strip_escape_html_label' value='$label' />";
                    echo "<input type='button' onclick=\"window.location='sensordetails.php?int_sid=$sid&int_dellabel=1&md5_hash=$s_hash';\" class='button' value='" .$l['sd_clear']. "' />\n";
                    echo "<input type='submit' value='" .$l['g_update']. "' class='button' /></td>\n";
                    echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                  echo "</form>\n";
                } else {
                  echo "<td colspan='3'>$label</td>";
                }
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['g_domain']. ":</td>\n";
              echo "<td colspan='3'>$org</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_sensortype']. ":</td>\n";
              echo "<td colspan='3'>$sensortype</td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='4'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='4'>" .$l['sd_networkconfig']. "</th></tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_networkrev']. ":</td>\n";
              echo "<td colspan='3'>$sensor_rev</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_configtype']. ":</td>\n";
              echo "<td colspan='3'>$configtype</td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='4'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='4'>" .$l['sd_sensorside']. "</th></tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_rip']. ":</td>\n";
              echo "<td colspan='3'>$remote</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_lip']. ":</td>\n";
              echo "<td colspan='3'>$local</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_sensormac']. ":</td>\n";
              echo "<td colspan='3'>$sensormac</td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='4'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='4'>" .$l['sd_serverside']. "</th></tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_device']. ":</td>\n";
              echo "<td colspan='3'>$tap</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_devmac']. ":</td>\n";
              echo "<td colspan='3'>$mac</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_devip']. ":</td>\n";
              echo "<td colspan='3'>$tapip</td>\n";
            echo "</tr>\n";

            echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
            echo "<tr>\n";
              echo "<th colspan='2'>" .$l['sd_uptime']. "</th>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td width='150'>" .$l['sd_since']. ":</td>\n";
              echo "<td width='320'>$first_attack</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_uptime']. ":</td>\n";
              echo "<td><span id='js_uptime'>$uptime_text</span></td>\n";
            echo "</tr>\n";
            echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
            echo "<tr><th colspan='2'>" .$l['sd_status']. "</th></tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_started']. ":</td>\n";
              echo "<td>$start_text</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_stopped']. ":</td>\n";
              echo "<td>$stop_text</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_updated']. ":</td>\n";
              echo "<td>$update_text</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['sd_status']. ":</td>\n";
              echo "<td>";
                echo "<div class='sensorstatus'>";
                  echo "<div class='" .$v_sensorstatus_ar[$cstatus]["class"]. "'>";
                    echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[$cstatus]["text"]. "</div>";
                  echo "</div>\n";
                echo "</div>";
              echo "</td>\n";
            echo "</tr>\n";

          echo "</table>\n";
          echo "<input type='hidden' name='js_hiduptime' id='js_hiduptime' value='$uptime' />\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
#  echo "</div>\n"; #</leftmed>

#  echo "<div class='leftmed'>\n";
  echo "</div>\n"; #</leftmed>

  if ($vlanid == 0) {
    $sql_count = "SELECT COUNT(id) as total FROM syslog WHERE keyname = '$keyname' AND vlanid = 0";
  } else {
    $sql_count = "SELECT COUNT(id) as total FROM syslog WHERE keyname = '$keyname' AND (vlanid = 0 OR vlanid = $vlanid)";
  }
  $debuginfo[] = $sql_count;
  $result_count = pg_query($pgconn, $sql_count);
  $rowcount = pg_fetch_assoc($result_count);
  $num_events = $rowcount['total'];

  $sql_events = "SELECT timestamp, ts_to_epoch(timestamp) as ts, source, error, args, level, device, pid, vlanid ";
  $sql_events .= " FROM syslog WHERE keyname = '$keyname' AND ";
  if ($vlanid == 0) {
    $sql_events .= " vlanid = 0 ";
  } else {
    $sql_events .= " (vlanid = 0 OR vlanid = $vlanid) ";
  }
  $sql_events .= " AND level >= 1 ";
  $sql_events .= " ORDER BY timestamp DESC";
  $debuginfo[] = $sql_events;
  $result_events = pg_query($pgconn, $sql_events);

  echo "<div class='rightmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['sd_members']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='150'>" .$l['ga_group']. "</th>\n";
              echo "<th width='300'>" .$l['g_actions']. "</th>\n";
            echo "</tr>\n";

            $sql = "SELECT groups.id, groups.name FROM groupmembers, groups WHERE groups.id = groupmembers.groupid AND groupmembers.sensorid = '$sid'";
            $debuginfo[] = $sql;
            $result = pg_query($pgconn, $sql);

            while ($row = pg_fetch_assoc($result)) {
              $name = $row['name'];
              $gid = $row['id'];
              echo "<tr>\n";
                echo "<td>$name</td>\n";
                echo "<td>[<a href='groupedit.php?int_gid=$gid'>" .$l['g_edit']. "</a>]</td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</rightmed>

  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>";
          echo "<div class='headerTab headerTabSel' id='headerTab1'><a onclick='showHeaderTab(1);'>" .$l['sd_sensorlog']. "</a></div>";
          echo "<div class='headerTab' id='headerTab2'><a onclick='showHeaderTab(2);'>" .$l['sd_sensornotes']. "</a></div>";
        echo "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<div class='subContent' id='sub1'>\n";

          echo "<form name='sensorlog'>\n"; 
          echo "<input type='hidden' name='int_sid' value='$sid' />\n";
          echo "<table class='datatable'>\n";
            echo "<tr><th colspan='2'>" .$l['sd_events']. "</th></tr>\n";
            echo "<tr>\n";
              echo "<td width='20%'>" .$l['sd_totalevents']. ":</td>\n";
              echo "<td width='80%'>";
                echo "<div class='fleft'><div class='vtext'>$num_events</div></div>\n";
                echo "<div class='aright'>";
#                  echo "<input type='button' name='reloadlog' onclick='reload_sensor_log();' value='Reload' class='button' />\n";
                echo "</div>\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td colspan='2'>\n";
                echo "<textarea id='eventlog'>";
                  while ($row_events = pg_fetch_assoc($result_events)) {
                    $ev_timestamp = $row_events['ts'];
                    $ev_timestamp = date($c_date_format, $ev_timestamp);
                    $ev_args = $row_events['args'];
                    echo show_log_message($ev_timestamp, $ev_args);
                  }
                echo "</textarea>\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "</form>\n";
          echo "</div>\n"; #</subContent1>
          echo "<div class='subContent' id='sub2' style='display:none;'>";
            echo "<form name='noteform' action='note_add.php' method='post'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>";
                echo "<th width='130'>" .$l['ls_timestamp']. "</th>\n";
                if ($sensortype == "vlan") {
                  echo "<th width='520'>" .$l['sd_note']. "</th>\n";
                  echo "<th width='120'>" .$l['g_vlan']. "</th>\n";
                } else {
                  echo "<th width='640' colspan=2>" .$l['sd_note']. "</th>\n";
                }
                echo "<th width='80'>" .$l['g_type']. "</th>\n";
                echo "<th width='100'>" .$l['g_action']. "</th>\n";
              echo "</tr>\n";

              $sql = "SELECT id, ts, note, type FROM sensor_notes WHERE keyname = '$keyname' AND (vlanid IS NULL OR vlanid = '$vlanid')";
              if ($s_access_sensor != 9) {
                $sql .= " AND NOT admin = 1 ";
              }
              $sql .= " ORDER BY ts DESC ";
              $result = pg_query($pgconn, $sql);
              while ($row = pg_fetch_assoc($result)) {
                $nid = $row['id'];
                $ts = $row['ts'];
                $date = date($c_date_format, $ts);
                $note = $row['note'];
                $type = $row['type'];

                echo "<tr>\n";
                  echo "<td>$date</td>\n";
                  echo "<td colspan=2>$note</td>\n";
                  echo "<td>$v_note_types_ar[$type]</td>\n";
                  echo "<td><a href='note_del.php?int_sid=$sid&int_nid=$nid&md5_hash=$s_hash'>[" .$l['g_delete']. "]</a></td>\n";
                echo "</tr>\n";
              }

              echo "<tr>\n";
                echo "<td></td>\n";
                if ($sensortype == "vlan") {
                  echo "<td><input type='text' size=71 name='strip_html_escape_note' /></td>\n";
                  echo "<td>";
                    echo "<select name='int_all'>\n";
                      foreach ($v_note_all_ar as $key => $val) {
                        echo printOption($key, $val, $def);
                      }
                    echo "</select>\n";
                  echo "</td>\n";                    
                } else {
                  echo "<td colspan=2><input type='text' size=89 name='strip_html_escape_note' /></td>\n";
                  echo "<input type='hidden' name='int_all' value='0'>\n";
                }
                echo "<td>\n";
                  echo "<select name='int_type'>\n";
                    foreach ($v_note_types_ar as $key => $val) {
                      echo printOption($key, $val, -1);
                    }
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td><input type='submit' name='submit' class='button' value='" .$l['g_add']. "' /></td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' name='int_sid' value='$sid' />\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "</form>\n";
          echo "</div>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>

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
