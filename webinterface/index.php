<?php $tab="1"; $pagetitle="Home"; include("menu.php"); contentHeader();

####################################
# SURFnet IDS                      #
# Version 2.00.02                  #
# 11-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 2.00.02 Fixed bug with attacks.dport=0
# 2.00.01 version 2.00
# 1.04.07 Fixed a port link when today was selected
# 1.04.06 Fixed some layout issues
# 1.04.05 Added dropdown box
# 1.04.04 Added empty flag for unknown countries
# 1.04.03 Added geoip and p0f stuff
# 1.04.02 Added some graphs and stats 
# 1.04.01 Added changelog and GD check
#############################################

session_start();
### GEOIP STUFF
if ($c_geoip_enable == 1) {
  include '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

$tsquery = "timestamp >= $from AND timestamp <= $to";

add_to_sql("attacks", "table");
add_to_sql("$tsquery", "where");
if ($q_org != 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = attacks.sensorid", "where");
  add_to_sql(gen_org_sql(), "where");
}
add_to_sql("DISTINCT attacks.severity", "select");
add_to_sql("COUNT(attacks.severity) as total", "select");
add_to_sql("attacks.severity", "group");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

prepare_sql();

$sql_severity = "SELECT $sql_select ";
$sql_severity .= " FROM $sql_from ";
$sql_severity .= " $sql_where ";
$sql_severity .= " GROUP BY $sql_group ";
$debuginfo[] = $sql_severity;
$result_severity = pg_query($pgconn, $sql_severity);
$num = pg_num_rows($result_severity);

echo "<div class='all'>\n";
echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>Attacks</div>\n";
      echo "<div class='blockContent'>\n";
        if ($num > 0) {
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='80%'>Detected connections</td>\n";
              echo "<th width='20%'>Statistics</td>\n";
            echo "</tr>\n";

            $sql_type = "SELECT DISTINCT attacks.atype, COUNT(attacks.atype) as total ";
            $sql_type .= "FROM $sql_from ";
            $sql_type .= "$sql_where ";
            $sql_type .= " AND severity = 1 ";
            $sql_type .= "GROUP BY atype ";

            while($row = pg_fetch_assoc($result_severity)) {
              $severity = $row['severity'];
              $count = $row['total'];
              $description = $v_severity_ar[$severity];
              if ($severity == 0 || $severity == 16) {
                echo "<tr>\n";
                  echo "<td>$description " .printhelp($severity). "</td>\n";
                  echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
                echo "</tr>\n";
              } elseif ($severity == 1) {
                echo "<tr>\n";
                  echo "<td>$description " .printhelp($severity). "</td>\n";
                  echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
                echo "</tr>\n";

                $debuginfo[] = $sql_type;
                $result_type = pg_query($pgconn, $sql_type);

                while ($row_type = pg_fetch_assoc($result_type)) {
                  $atype = $row_type['atype'];
                  $total = $row_type['total'];
                  $desc = $v_severity_atype_ar[$atype];
          
                  echo "<tr>\n";
                    echo "<td class='indented'>$desc</td>\n";
                      echo "<td>" .downlink("logsearch.php?int_sev=$severity&int_sevtype=$atype", nf($total)). "</td>\n";
                  echo "</tr>\n";
                }
              } elseif ($severity == 32) {
                echo "<tr>\n";
                  echo "<td>$description " .printhelp($severity). "</td>\n";
                  echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
                echo "</tr>\n";
              } else {
                echo "<tr>\n";
                  echo "<td>" .downlink("logsearch.php?int_sev=$severity", nf($count)). "</td>\n";
                echo "</tr>\n";
              }
            }
          echo "</table>\n";
        } else {
          echo "<font class='warning'>No records found!</font>\n";
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftmed>

reset_sql();
### Making sure the correct organisation is set.
if ($q_org != 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.organisation = $q_org", "where");
  add_to_sql("attacks.sensorid = sensors.id", "where");
}

### Checking for period.
add_to_sql("attacks", "table");
add_to_sql("attacks.id = details.attackid", "where");
add_to_sql("attacks.timestamp >= $from", "where");
add_to_sql("attacks.timestamp <= $to", "where");

echo "<div class='rightmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>Exploits</div>\n";
      echo "<div class='blockContent'>\n";
        if ($err != 1) {
          ######### Table for Malicious attacks (SEV: 1) #############
          add_to_sql("attacks.id = details.attackid", "where");
          add_to_sql("attacks.severity = 1", "where");
          add_to_sql("details.type = 1", "where");
#          add_to_sql("details.text = stats_dialogue.name", "where");
          if ($q_org != 0) {
            add_to_sql(gen_org_sql(), "where");
            add_to_sql("sensors", "table");
            add_to_sql("attacks.sensorid = sensors.id", "where");
          }
#          add_to_sql("stats_dialogue", "table");
          add_to_sql("details", "table");
          add_to_sql("attacks", "table");
          add_to_sql("COUNT(DISTINCT details.attackid) as total", "select");
          add_to_sql("details.text", "select");
#          add_to_sql("stats_dialogue.id", "select");
          add_to_sql("details.text", "group");
#          add_to_sql("stats_dialogue.id", "group");
          add_to_sql("total", "order");

          # IP Exclusion stuff
          add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

          prepare_sql();

          ### Admin check.
          $sql_count = "SELECT $sql_select ";
          $sql_count .= "FROM $sql_from ";
          $sql_count .= " $sql_where ";
          if ($sql_group) {
            $sql_count .= " GROUP BY $sql_group ";
  	  }
          if ($sql_order) {
            $sql_count .= " ORDER BY $sql_order DESC ";
          }
          $debuginfo[] = "$sql_count";
          $result_count = pg_query($pgconn, $sql_count);
          $numrows_count = pg_num_rows($result_count);

          if ($numrows_count > 0) {
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th width='80%'>Malicious attacks</th>\n";
                echo "<th width='20%'>Statistics</th>\n";
              echo "</tr>\n";

              $total = 0;
              while ($row = pg_fetch_assoc($result_count)) {
                $id = $row['id'];
                $dia = $row['text'];
                $count = $row['total'];
                $total = $total + $count;
                $attack = $v_attacks_ar[$dia]["Attack"];
                $attack_url = $v_attacks_ar[$dia]["URL"];
                echo "<tr>\n";
                  if ($attack_url != "") {
                    echo "<td><a href='$attack_url' target='new'>$attack</a></td>\n";
                  } else { 
                    echo "<td>$attack</td>\n";
                  }
                  echo "<td>" .downlink("logsearch.php?int_sev=1&int_sevtype=0&int_attack=$id", nf($count)). "</td>\n";
                echo "</tr>\n";
              }
              echo "<tr class='bottom'>\n";
                echo "<td>Total</td>\n";
                echo "<td>" .downlink("logsearch.php?int_sev=1&int_sevtype=0", nf($total)). "</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          } else {
            echo "<font class='warning'>No records found!</font>\n";
          }
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>
echo "</div>\n"; #</all>

echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Attackers</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable' width='100%'>\n";
            echo "<tr>\n";
              echo "<th class='dataheader' width='50%'>IP Address</th>\n";
              echo "<th class='dataheader' width='35%'>Last Seen</th>";
              echo "<th class='dataheader' width='15%'>Total Hits</th>\n";
            echo "</tr>\n";
    
            #### Get the data for todays attackers and display it.
            $query = "attacks.sensorid = sensors.id ";
            $query .= "AND timestamp >= $from AND timestamp <= $to ";
	    $query .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org) ";
            if ($q_org != 0) {
              $query .= " AND sensors.organisation = '$q_org' ";
            }

            $sql_attack_countqry = "SELECT count(attacks.id), source FROM attacks, sensors WHERE $query GROUP BY source ORDER BY count DESC LIMIT 10";

            $debuginfo[] = $sql_attack_countqry;
            $result_countqry = pg_query($pgconn, $sql_attack_countqry);
            while ($row = pg_fetch_assoc($result_countqry)) {
              $source = $row['source'];
              $sql_attack_ls = "SELECT timestamp FROM attacks, sensors WHERE source = '$source' AND attacks.sensorid = sensors.id ";
              if ($s_admin != 1) {
                $sql_attack_ls .= " AND sensors.organisation = '$q_org' ";
              }
              $sql_attack_ls .= " ORDER BY timestamp DESC LIMIT 1";
              $debuginfo[] = $sql_attack_ls;
              $result_ls = pg_query($pgconn, $sql_attack_ls);
              $lsdb = pg_fetch_assoc($result_ls);
              $ls = $lsdb['timestamp'];

              $chk = date("U", $ls);
              $cur = date("U");
              $dif = $cur - $chk;
              $dif = round($dif / (3600 * 24));
              $ls = date("d-m-Y H:i:s", $ls);

              echo "<tr>\n";
                echo "<td>";
                  if ($c_enable_pof == 1) {
                    $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$source. "' ORDER BY last_tstamp DESC";
                    $result_finger = pg_query($pgconn, $sql_finger);
                    $numrows_finger = pg_num_rows($result_finger);

                    $fingerprint = pg_result($result_finger, 0);
                    $finger_ar = explode(" ", $fingerprint);
                    $os = $finger_ar[0];
                  } else {
                    $numrows_finger = 0;
                  }
                  if ($numrows_finger != 0) {
                    echo printosimg($os, $fingerprint). "&nbsp;";
                  } else {
                    echo printosimg("Blank", "No info"). "&nbsp;";
                  }
                  if ($c_geoip_enable == 1) {
                    $record = geoip_record_by_addr($gi, $source);
                    $countrycode = strtolower($record->country_code);
                    $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
                    if (file_exists($cimg)) {
                      $country = $record->country_name;
                      echo printflagimg($country, $countrycode);
                    } else {
                      echo printflagimg("none", "");
                    }
                  }
                  $sql_ranges = "SELECT ranges FROM organisations WHERE id = $q_org";
                  $debuginfo[] = $sql_ranges;
                  $result_ranges = pg_query($pgconn, $sql_ranges);
                  $rowrange = pg_fetch_assoc($result_ranges);
                  $ranges_ar = explode(";", $rowrange['ranges']);
                  if (matchCIDR($source, $ranges_ar)) {
                    echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 500, 500);\" class='warning' />$source</a>&nbsp;&nbsp;";
                    echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
                  } else {
                    echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 500, 500);\" />$source</a>";
                  }
                echo "</td>\n";
                echo "<td style='background-color: $v_indexcolors[$dif];'>$ls</td>\n";
                echo "<td>" .downlink("logsearch.php?inet_source=$source", $row[count]). "</td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
          echo "<br />\n";
          echo "<table>\n";
            echo "<tr>\n";
              echo "<td>Last Seen: </td>\n";
              echo "<td style='background-color: $v_indexcolors[0]; text-align: center;' width='80'>Today</td>\n";
              $count = count($v_indexcolors) - 1;
              foreach ($v_indexcolors as $key => $value) {
                if ($key != 0 && $key != $count) {
                  echo "<td style='background-color: $value; width: 10px;'>&nbsp;</td>\n";
                }
              }
              echo "<td style='background-color: $v_indexcolors[$count]; text-align: center;' width='80'>7 days ago</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</leftmed>

  echo "<div class='rightmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Ports</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable' width='100%'>\n";
            echo "<tr>\n";
              echo "<th width='40%'>Destination Ports</th>\n";
              echo "<th width='45%'>Description</th>\n";
              echo "<th width='100%'>Total Hits</th>\n";
            echo "</tr>\n";

            $queryport = "attacks.sensorid = sensors.id ";
            $queryport .= "AND timestamp >= $from AND timestamp <= $to ";
            $queryport .= "AND NOT attacks.dport = 0 ";
	    $queryport .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org) ";
            if ($q_org != 0) {
              $queryport .= "AND sensors.organisation = '$q_org' ";
            }
            $sql_port_countqry = "SELECT attacks.dport, count(attacks.dport) as total FROM attacks,sensors ";
            $sql_port_countqry .= "WHERE $queryport GROUP BY attacks.dport ORDER BY total DESC LIMIT 10 OFFSET 0";
            $result_portcountqry = pg_query($pgconn, $sql_port_countqry);
            while ($row = pg_fetch_assoc($result_portcountqry)) {
              echo "<tr>\n";
                echo "<td>$row[dport]</td>\n";
                echo "<td><a target='_blank' href='http://www.iss.net/security_center/advice/Exploits/Ports/$row[dport]'>".getPortDescr($row[dport])."</a></td>\n";
                echo "<td>" .downlink("logsearch.php?int_dport=$row[dport]", $row[total]). "</td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</rightmed>
echo "</div>\n"; # close all

debug_sql();
?>
<?php footer(); ?>
