<?php
# Setting up IP exclusion link and hover
$orgipadmin_link = $s_access_user > 1 ? "orgipadmin.php" : "#";
if ($filter_ip == 1) {
    $sql_exclusion = "SELECT exclusion FROM org_excl WHERE orgid = $q_org";
    $debuginfo[] = $sql_exclusion;
    $result_exclusion = pg_query($pgconn, $sql_exclusion);
    $ip_exclusionrows = pg_num_rows($result_exclusion);
    while ($row_ip = pg_fetch_assoc($result_exclusion)) {
        $ip_excl_text .= $row_ip['exclusion'] ."<br />";
    }
    if ($ip_exclusionrows > 0) {
        $ip_excl = "<a href='$orgipadmin_link' " .printover($ip_excl_text). ">" .$l['ls_ipex_on']. "</a>";
    } else { 
        $ip_excl = "<a href='$orgipadmin_link'>" .$l['ls_ipex_off']. "</a>"; 
    } 
} else {
    $ip_excl = "<a href='$orgipadmin_link'>" .$l['ls_ipex_off']. "</a>"; 
}

# Setting up MAC exclusion link and hover
if ($filter_mac == 1) {
    $sql_exclusion = "SELECT mac FROM arp_excl";
    $debuginfo[] = $sql_exclusion;
    $result_exclusion = pg_query($pgconn, $sql_exclusion);
    $mac_exclusionrows = pg_num_rows($result_exclusion);
    while ($row_mac = pg_fetch_assoc($result_exclusion)) {
        $mac_excl_text .= $row_mac['mac'] ."<br />";
    }
    if ($mac_exclusionrows > 0) {
       $mac_excl = "<a href='#' " .printover($mac_excl_text). ">" .$l['ls_macex_on']. "</a>";
    } else { 
       $mac_excl = $l['ls_macex_off'];
    }
} else {
    $mac_excl = $l['ls_macex_off'];
}

  echo "<div class='block'>";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>\n";
        echo "<div class='blockHeaderLeft'>" .$l['ls_crit']. "</div>\n";
        echo "<div class='blockHeaderRight'>\n";
          echo "<div class='searchheader'>&nbsp;<a href='logsearch.php'>" .$l['ls_clear']. "</a>&nbsp;</div>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form method='get' action='logsearch.php' name='searchform' id='searchform'>\n";
          echo "<table class='searchtable'>\n";
            ### DESTINATION INFO BLOCK
            echo "<tr class='info_dest' style='$info_dest'>\n";
              echo "<td width='20%'><b>" .$l['ls_dest']. "</b></td>\n";
              echo "<td width='30%'>\n";
                ### SENSOR
                if (isset($crit['sensorid'])) {
                    if (is_array($crit['sensorid'])) {
                        # Multiple sensors selected
                        foreach ($crit['sensorid'] as $key => $sid) {
                            $sql = "SELECT sensors.keyname, sensors.vlanid, sensors.label, organisations.organisation FROM sensors, organisations ";
                            $sql .= "WHERE organisations.id = sensors.organisation AND sensors.id = $sid $sensor_where ORDER BY sensors.keyname";
                            $debuginfo[] = $sql;
                            $query = pg_query($sql);
                            while ($sensor_data = pg_fetch_assoc($query)) {
                                $keyname = $sensor_data["keyname"];
                                $vlanid = $sensor_data["vlanid"];
                                $label = $sensor_data["label"];
                                $org = $sensor_data["organisation"];
                                if ($label != "") { 
                                    if ($q_org == 0) {
                                        $label .= " (" .$org. ")";
                                    }
                                    $name = $label;
                                } else {  
                                    $name = sensorname($keyname, $vlanid);
                                }
                                echo "$name<br />\n";
                            }
                            $sensorstring .= $sid . ",";
                        }
                        $sensorstring = trim($sensorstring, ",");
                        $graph[] = "sensorid=$sensorstring";
                    } elseif ($sensorid > 0) {
                        # Single sensor selected
                        echo "$sensorid";
                        $graph[] = "sensorid=$sensorid";
                    } else {
                        # Show search options for destination
                    }
                }

                ### IP
                if (isset($crit['dest'])) echo $crit['dest'];
                ### MAC
                if (isset($crit['destmac'])) echo $crit['destmac'];
                ### PORT
                if (isset($crit['dport'])) {
                    echo ":" .$crit['dport'];
                    $graph[] = "strip_html_escape_ports=$dport";
                }
              echo "</td>\n";
              if ($show_change == 1) {
                  echo "<td width='50%' class='aright'><a onclick='surfids_dest_change();'>" .$l['ls_change']. "</a></td>\n";
              } else {
                  echo "<td width='50%' class='aright'></td>\n";
              }
            echo "</tr>\n";

            ### DESTINATION SEARCH BLOCK
            echo "<tr class='search_dest' style='" .$search_dest. ";'>\n";
              echo "<td width='20%'><b>" .$l['ls_dest']. "</b></td>\n";
              echo "<td width='30%'></td>\n";
              if ($show_change == 1) {
                  echo "<td width='50%' class='aright'><a onclick='surfids_dest_change();'>" .$l['ls_change']. "</a></td>\n";
              } else {
                  echo "<td width='50%' class='aright'></td>\n";
              }
            echo "</tr>\n";
            echo "<tr class='search_dest' style='" .$search_dest. ";'>";
              echo "<td>" .$l['ls_address']. ":</td>";
              echo "<td>";
                echo "<select id='int_destchoice' name='int_destchoice' onchange='javascript: surfids_search_dest(this.value);' class='pers'>\n";
                  foreach ($v_search_dest_ar as $key => $val) {
                    echo printOption($key, $val, $crit['destchoice']);
                  }
                echo "</select>\n";
              echo "</td>";
              ### IP
              echo "<td id='dest' style=''>";
                echo "<input type='text' id='inet_dest' class='pers' name='inet_dest' alt='" .$l['ls_destip']. "' maxlenght=18  value='$destination_ip'/>";
              echo "</td>";

              ### SENSOR
              if ($q_org == 0) {
                $sensor_where = " ";
              } else {
                $sensor_where = " AND sensors.organisation = '$q_org'";
              }
              echo "<td id='sensor' style='display:none;' >\n";
                echo "<select name='sensorid[]' size='5' multiple='true' id='sensorid' class='pers'>\n";
                  echo printOption(0, "All sensors", $crit['sensorid']);
                  $sql = "SELECT sensors.id, sensors.keyname, sensors.vlanid, sensors.label, organisations.organisation FROM sensors, organisations ";
                  $sql .= "WHERE organisations.id = sensors.organisation $sensor_where ORDER BY sensors.keyname";
                  $debuginfo[] = $sql;
                  $query = pg_query($sql);
                  while ($sensor_data = pg_fetch_assoc($query)) {
                    $sid = $sensor_data['id'];
                    $keyname = $sensor_data["keyname"];
                    $vlanid = $sensor_data["vlanid"];
                    $label = $sensor_data["label"];
                    $org = $sensor_data["organisation"];
                    if ($label != "") { 
                      if ($q_org == 0) {
                        $label .= " (" .$org. ")";
                      }
                      $name = $label;
                    } else {  
                      $name = sensorname($keyname, $vlanid);
                    }
                    echo printOption($sid, $name, $crit['sensorid']);
                  }
                echo "</select>\n";
              echo "</td>\n";
              ### MAC
              echo "<td id='destmac' style='display:none;'>";
                echo "<input type='text' id='mac_destmac' class='pers' name='mac_destmac' alt='" .$l['ls_destmac']. "' value='$dest_mac' />";
              echo "</td>";
            echo "</tr>\n";
            ### PORT
            echo "<tr class='search_dest' style='" .$search_dest. ";'>";
              echo "<td>" .$l['ls_port']. ":</td>\n";
              echo "<td><input type='text' class='pers' name='int_dport' size='5' value='$dport' /></td>";
            echo "</tr>";
            if ($single_submit == 0) {
              echo "<tr class='search_dest' style='" .$search_dest. ";'>";
                echo "<td colspan=3><input type='submit' value='" .$l['g_submit']. "' class='sbutton fright' /></td>";
              echo "</tr>";
            }
          echo "</table>"; 
          echo "<hr>\n";

          ### SOURCE INFO BLOCK
          echo "<table class='searchtable'>\n";
            echo "<tr class='info_source' style='$info_src'>\n";
              echo "<td width='20%'><b>" .$l['ls_source']. "</b></td>\n";
              echo "<td width='30%'>\n";
                if ($crit['sourcechoice'] == 3) echo $l['ls_own'];
                if (isset($crit['source'])) echo $crit['source'];
                if (isset($$crit['sourcemac'])) echo $crit['sourcemac'];
                if (isset($crit['sport'])) echo ":" .$crit['sport'];
              echo "</td>\n";
              echo "<td width='50%' class='aright'>\n";
                if ($show_change == 1) {
                    echo "<a onclick='surfids_source_change();'>" .$l['ls_change']. "</a><br />\n";
                }
                echo "$ip_excl<br />$mac_excl";
              echo "</td>";
            echo "</tr>\n";
            ### SOURCE SEARCH BLOCK
            echo "<tr class='search_source' style='$search_src'>\n";
              echo "<td width='20%'><b>" .$l['ls_source']. "</b></td>\n";
              echo "<td width='30%'></td>\n";
              echo "<td width='50%' class='aright'>\n";
              if ($show_change == 1) {
                  echo "<a onclick='surfids_source_change();'>" .$l['ls_change']. "</a><br />\n";
              }
              echo "</td>";
            echo "</tr>\n";
            echo "<tr class='search_source' style='$search_src'>\n";
              echo "<td>" .$l['ls_address']. ":</td>";
              echo "<td>";
                echo "<select id='int_sourcechoice' name='int_sourcechoice' onchange='javascript: surfids_search_src(this.value);' class='pers'>\n";
                  foreach ($v_search_src_ar as $key=>$val) {
                    echo printOption($key, $val, $crit['sourcechoice']);
                  }
                echo "</select>\n";
              echo "</td>\n"; 
              echo "<td id='source' style=''>";
                echo "<input type='text' id='ipv4v6_source' class='pers' name='ipv4v6_source' alt='" .$l['ls_sourceip']. "' maxlenght='18' value='$source_ip' />";
              echo "</td>";
              echo "<td id='sourcemac' style='display:none;'>";
                echo "<input type='text' id='mac_sourcemac' class='pers' name='mac_sourcemac' alt='" .$l['ls_sourcemac']. "' value='$source_mac' />";
              echo "</td>\n";
              echo "<td id='ownrange' style='display:none;'>";
                $sql_ranges = "SELECT ranges FROM organisations WHERE id = $q_org";
                $debuginfo[] = $sql_ranges;
                $result_ranges = pg_query($pgconn, $sql_ranges);
                $row = pg_fetch_assoc($result_ranges);
                if ($row['ranges'] == "") {
                  echo "<input type='text' class='pers' value='" .$l['ls_noranges']. "' />";
                } else {
                  echo "<select name='inet_ownsource' id='inet_ownsource' class='pers'>\n";
                    $ranges_ar = explode(";", $row['ranges']);
                    sort($ranges_ar);
                    echo printOption("", $l['ls_allranges'], "" );
                    foreach ($ranges_ar as $range) {
                      if (trim($range) != "") {
                        echo printOption("$range", "$range", "$ownsource" );
                      }
                    }
                  echo "</select>\n"; 
                }
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr class='search_source' style='$search_src'>\n";
              echo "<td>" .$l['ls_port']. ":</td>\n";
              echo "<td colspan=2><input type='text' class='pers' name='int_sport' size='5' value='$sport' /></td>\n";
            echo "</tr>\n";
            echo "<tr class='search_source' style='$search_src'>\n";
              echo "<td>" .$l['ls_ipfilter']. ":</td>\n";
              echo "<td colspan=2>" .printradio($l['g_on'], "int_ipfilter", 1, $filter_ip). "&nbsp;&nbsp;" .printradio($l['g_off'], "int_ipfilter", 0, $filter_ip). "</td>\n";
            echo "</tr>\n";
            echo "<tr class='search_source' style='$search_src'>\n";
              echo "<td>" .$l['ls_macfilter']. ":</td>\n";
              echo "<td colspan=2>" .printradio($l['g_on'], "int_macfilter", 1, $filter_mac). "&nbsp;&nbsp;" .printradio($l['g_off'], "int_macfilter", 0, $filter_mac). "</td>\n";
            echo "</tr>\n";
            if ($single_submit == 0) {
              echo "<tr class='search_source' style='$search_src'>\n";
                echo "<td colspan=3><input type='submit' value='" .$l['g_submit']. "' class='sbutton fright' /></td>";
              echo "</tr>\n";
            }
          echo "</table>"; 
          echo "<hr>\n";

          ### CHARACTERISTICS INFO BLOCK
          echo "<table class='searchtable'>\n";
            echo "<tr>\n";
              echo "<td width='30%'><b>" .$l['ls_chars']. "</b></td>";
              echo "<td width='50%'></td>\n";
              if ($show_change == 1) {
                  echo "<td width='20%' class='aright'><a onclick='surfids_char_change();'>" .$l['ls_change']. "</a></td>\n";
              } else {
                  echo "<td width='20%' class='aright'></td>\n";
              }
            echo "</tr>\n";
            echo "<tr class='info_char'>";
              echo "<td></td>";
              echo "<td colspan=2>";
                if (isset($crit['sev'])) {
                  echo $l['ls_sev']. ": <font class='btext'>" .$v_severity_ar[$crit['sev']]. "</font><br />";
                  if (isset($crit['sevtype'])) {
                    if ($crit['sevtype'] != 0) {
                      $graph[] = "severity=" .$$crit['sev'];
                    }
                  } else {
                    $graph[] = "severity=" .$crit['sev'];
                  }
                }
                if (isset($crit['sevtype']) && $crit['sev'] == 1) {
                  echo $l['ls_sevtype']. ": <font class='btext'>" .$v_severity_atype_ar[$crit['sevtype']]. "</font><br />";
                  if ($crit['sevtype'] == 0) {
                    $graph[] = "attack=-1";
                  } else {
                    $graph[] = "sevtype=" .$crit['sevtype'];
                  }
                } elseif ($crit['sev'] == 1) {
                  $graph[] = "sevtype=-1&int_totalmal1=1";
                }
                if (isset($crit['attack']) && $crit['sev'] == 1) {
                  $sql_g = "SELECT name FROM stats_dialogue WHERE id = '" .$crit['attack']. "'";
                  $result_g = pg_query($pgconn, $sql_g);
                  $row_g = pg_fetch_assoc($result_g);
                  $expl = $row_g['name'];
                  $expl = str_replace("Dialogue", "", $expl);
                  $graph[] = "attack=" .$crit['attack'];
                  if ($expl != "") echo $l['ls_exp']. ": <font class='btext'>$expl</font><br />";
                }
                if (isset($crit['binname']) && $crit['sev'] == 32) echo $l['ls_binname']. ": <font class='btext'>" .$crit['binname']. "</font><br />";
                if (isset($crit['virustxt']) && $crit['sev'] == 32) echo $l['ls_virus']. ": <font class='btext'>" .$crit['virustxt']. "</font><br />";
                if (isset($crit['filename']) && ($crit['sev'] == 16 || $crit['sev'] == 16)) echo $l['ls_filename']. ": <font class='btext'>" .$crit['filename']. "</font><br />";
                if (isset($crit['sshversion'])) {
                    $sel_sshversion = $crit['sshversion'];
                    echo $l['ls_sshversion']. ": <font class='btext'>" .$crit['sshversion']. "</font><br />";
                } elseif (isset($crit['sshversionid'])) {
                    $sql_s = "SELECT version FROM uniq_sshversion WHERE id = '" .$crit['sshversionid']. "'";
                    $result_s = pg_query($pgconn, $sql_s);
                    $row_s = pg_fetch_assoc($result_s);
                    $sel_sshversion = $row_s['version'];
                    echo $l['ls_sshversion']. ": <font class='btext'>$sel_sshversion</font><br />";
                }
                if (isset($crit['sshuser'])) {
                    echo $l['ls_sshuser']. ": <font class='btext'>" .$crit['sshuser']. "</font><br />";
                }
                if (isset($crit['sshpass'])) {
                    echo $l['ls_sshpass']. ": <font class='btext'>" .$crit['sshpass']. "</font><br />";
                }
                if (isset($crit['sshhascommand'])) {
                    if ($crit['sshhascommand'] == 2) $shcval = $l['g_yes'];
                    elseif ($crit['sshhascommand'] == 1) $shcval = $l['g_no'];
                    if ($crit['sshhascommand'] != 0) {
                        echo $l['ls_sshhascommand']. ": <font class='btext'>$shcval</font><br />";
                    }
                }
                if (isset($crit['sshlogin'])) {
                    if ($crit['sshlogin'] == 2) $shcval = $l['g_yes'];
                    elseif ($crit['sshlogin'] == 1) $shcval = $l['g_no'];
                    if ($crit['sshlogin'] != 0) {
                        echo $l['ls_sshlogin']. ": <font class='btext'>$shcval</font><br />";
                    }
                }
                if (isset($crit['sshcommand'])) {
                    echo $l['ls_sshcommand']. ": <font class='btext'>" .$crit['sshcommand']. "</font><br />";
                }
              echo "</td>\n";
            echo "</tr>\n";
            ### CHARACTERISTICS SEARCH BLOCK
            echo "<tr class='search_char' style='" .$search_char. "'>\n";
              echo "<td>" .$l['ls_sev']. ":</td>\n";
              echo "<td colspan=2>\n";
                echo "<select id='int_sev' name='int_sev' class='pers' onchange='javascript: surfids_search_severity(this.value);'>\n";
                  if(!isset($crit['sev'])) $crit['sev'] = -1;
                  echo printOption(-1, "", $crit['sev']);
                  foreach ($v_severity_ar as $index => $severity) {
                    echo printOption($index, $severity, $crit['sev']);
                  }
                echo "</select>\n";
              echo "</td>";
            echo "</tr>";
            echo "<tr class='search_char' id='sevtype' style='display:none;'>\n";
              echo "<td>" .$l['ls_att_type']. ": </td>";
              echo "<td colspan=2>";
                echo "<select id='int_sevtype' name='int_sevtype' onchange='javascript: surfids_search_sevtype(this.value);' class='pers'>\n";
                  if(!isset($crit['sevtype'])) $crit['sevtype'] = -1;
                  if ($crit['sev'] != 1) $crit['sevtype'] = -1;
                  echo printOption(-1, $l['g_all'], $crit['sevtype']);
                  foreach ($v_severity_atype_ar as $index => $sevtype) {
                    echo printOption($index, $sevtype, $crit['sevtype']);
                  }
                echo "</select>\n";
              echo "</td>";
            echo "</tr>\n";
            echo "<tr class='search_char' id='attacktype' style='display:none;'>\n";
              echo "<td>" .$l['ls_exp']. ":</td>";
              echo "<td colspan=2>"; 
                if ($crit['sevtype'] != 0 && $crit['sevtype'] != 5) $crit['attack'] = -1;
                echo "<select name='int_attack' id='int_attack' class='pers'>";
                  echo printOption(-1, "All exploits", $crit['attack']);
                  $sql = "SELECT name, id FROM stats_dialogue ORDER BY name";
                  $debuginfo[] = $sql;
                  $query = pg_query($sql);
                  while ($row = pg_fetch_assoc($query)) {
                    $name = str_replace("Dialogue", "", $row["name"]);
                    echo printOption($row["id"], $name, $crit['attack']);
                  }
                echo "</select>";
              echo "</td>\n";
            echo "</tr>\n";
            ### VIRUS
            echo "<tr class='search_char' id='virus' style='display:none;'>\n";
              echo "<td>" .$l['ls_virus']. ":</td>";
              echo "<td colspan=2>\n";
                echo "<input type='text' class='pers' name='strip_html_escape_virustxt' id='strip_html_escape_virustxt' value='" .$crit['virustxt']. "' />" .$l['ls_wildcard']. " %\n";
              echo "</td>\n";
            echo "</tr>\n";
            ### FILENAME
            echo "<tr class='search_char' id='filename' style='display:none;'>\n";
              echo "<td>" .$l['ls_filename']. ":</td>";
              echo "<td colspan=2>\n";
                echo "<input type='text' class='pers' id='strip_html_escape_filename' name='strip_html_escape_filename' value='" .$crit['filename']. "' />" .$l['ls_wildcard']. " %\n";
              echo "</td>\n";
            echo "</tr>\n";
            ### BINARY
            echo "<tr class='search_char' id='binary' style='display:none;'>\n";
              echo "<td>" .$l['ls_binname']. ":</td>";
              echo "<td colspan=2><input type='text' class='pers' id='strip_html_escape_binname' name='strip_html_escape_binname' value='" .$crit['binname']. "' />" .$l['ls_wildcard']. " %</td>";
            echo "</tr>";
            ### SSH VERSION
            echo "<tr class='search_char' id='sshversion' style='display:none;'>\n";
              echo "<td>" .$l['ls_sshversion']. ":</td>";
              echo "<td colspan=2><input type='text' class='pers' id='strip_html_escape_sshversion' name='strip_html_escape_sshversion' value='" .$sel_sshversion. "' /></td>\n";
            echo "</tr>";
            ### SSH USER
            echo "<tr class='search_char' id='sshuser' style='display:none;'>\n";
              echo "<td>" .$l['ls_sshuser']. ":</td>";
              echo "<td colspan=2><input type='text' class='pers' id='strip_html_escape_sshuser' name='strip_html_escape_sshuser' value='" .$crit['sshuser']. "' /></td>\n";
            echo "</tr>";
            ### SSH PASS
            echo "<tr class='search_char' id='sshpass' style='display:none;'>\n";
              echo "<td>" .$l['ls_sshpass']. ":</td>";
              echo "<td colspan=2><input type='text' class='pers' id='strip_html_escape_sshpass' name='strip_html_escape_sshpass' value='" .$crit['sshpass']. "' /></td>\n";
            echo "</tr>";
            ### SSH HAS COMMANDS (yes/no/all)
            echo "<tr class='search_char' id='sshhascommand' style='display:none;'>\n";
              echo "<td>" .$l['ls_sshhascommand']. ":</td>";
              echo "<td colspan=2>" .printradio($l['g_yes'], "int_sshhascommand", 2, $crit['sshhascommand']). "&nbsp;&nbsp;" .printradio($l['g_no'], "int_sshhascommand", 1, $crit['sshhascommand']). "&nbsp;&nbsp;" .printradio($l['g_both'], "int_sshhascommand", 0, $crit['sshhascommand']). "</td>\n";
            echo "</tr>";
            ### SSH SUCCESSFUL LOGIN (yes/no/all)
            echo "<tr class='search_char' id='sshlogin' style='display:none;'>\n";
              echo "<td>" .$l['ls_sshlogin']. ":</td>";
              echo "<td colspan=2>" .printradio($l['g_yes'], "int_sshlogin", 2, $crit['sshlogin']). "&nbsp;&nbsp;" .printradio($l['g_no'], "int_sshlogin", 1, $crit['sshlogin']). "&nbsp;&nbsp;" .printradio($l['g_both'], "int_sshlogin", 0, $crit['sshlogin']). "</td>\n";
            echo "</tr>";
            ### SSH COMMAND
            echo "<tr class='search_char' id='sshcommand' style='display:none;'>\n";
              echo "<td>" .$l['ls_sshcommand']. ":</td>";
              echo "<td colspan=2><input type='text' class='pers' id='strip_html_escape_sshcommand' name='strip_html_escape_sshcommand' value='" .$crit['sshcommand']. "' /></td>\n";
            echo "</tr>";
#          echo "</div>";
            echo "<tr class='search_char' style='$search_char'>\n";
              echo "<td colspan=3><input type='submit' value='" .$l['g_submit']. "' class='sbutton fright' /></td>";
            echo "</tr>\n";
          echo "</table>";
        echo "</form>";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>

?>
