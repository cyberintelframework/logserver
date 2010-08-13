<?php

####################################
# SURFids 3.00                     #
# Changeset 006                    #
# 17-11-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 006 Fixed bug with wrong access check (s_admin instead of s_access_search)
# 005 Fixed bug with multiple GeoIP includes
# 004 Fixed bug #74 again
# 003 Added ARP exclusion stuff
# 002 Fixed bug #74
# 001 Initial release
#############################################

if ($c_geoip_enable == 1) {
  include_once '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}
 
echo "<div class='block'>\n";
  echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>" .$l['in_attackers']. "</div>\n";
    echo "<div class='blockContent'>\n";
      echo "<table class='datatable' width='100%'>\n";
        echo "<tr>\n";
          echo "<th class='dataheader' width='50%'>" .$l['g_ip']. "</th>\n";
          echo "<th class='dataheader' width='35%'>" .$l['in_lastseen']. "</th>";
          echo "<th class='dataheader' width='15%'>" .$l['in_totalhits']. "</th>\n";
        echo "</tr>\n";

        #### Get the data for todays attackers and display it.
        $query = "attacks.sensorid = sensors.id ";
        $query .= "AND timestamp >= $from AND timestamp <= $to AND severity = 1 ";
        $query .= "AND NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org) ";

        # MAC Exclusion stuff
        add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

        if ($q_org != 0) {
          $query .= " AND sensors.organisation = '$q_org' ";
        }

        $sql_attack_countqry = "SELECT count(attacks.id), source FROM attacks, sensors WHERE $query GROUP BY source ORDER BY count DESC LIMIT 10";

        $debuginfo[] = $sql_attack_countqry;
        $result_countqry = pg_query($pgconn, $sql_attack_countqry);
        while ($row = pg_fetch_assoc($result_countqry)) {
          $source = $row['source'];
          $sql_attack_ls = "SELECT timestamp FROM attacks, sensors WHERE source = '$source' AND attacks.sensorid = sensors.id ";
          if ($s_access_search < 9) {
            $sql_attack_ls .= " AND sensors.organisation = '$q_org' ";
          }
          $sql_attack_ls .= " ORDER BY timestamp DESC LIMIT 1";
          $debuginfo[] = $sql_attack_ls;
          $result_ls = pg_query($pgconn, $sql_attack_ls);
          $lsdb = pg_fetch_assoc($result_ls);
          $ls = $lsdb['timestamp'];

          $chk = date("w", $ls);
          $cur = date("w");
          $chk_d = date("d", $ls);
          $cur_d = date("d");
          $chk_u = date("U", $ls);
          $cur_u = date("U");
          $ls = date($c_date_format, $ls);
          if (($cur_u - $chk_u) > 604800) {
            $dif = "NAN";
          } else {
            $dif = $cur - $chk;
            if ($dif < 0) {
              $dif = 7 + $dif;
            } elseif ($dif == 0) {
              if ($cur_d != $chk_d) {
                $dif = "NAN";
              }
            }
          }

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
                echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" class='warning' />$source</a>&nbsp;&nbsp;";
                echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
              } else {
                echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" />$source</a>";
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
          echo "<td style='background-color: $v_indexcolors[0]; text-align: center;' width='80'>" .$l['in_today']. "</td>\n";
          $count = count($v_indexcolors) - 1;
          foreach ($v_indexcolors as $key => $value) {
            if ($key != 0 && $key != $count) {
              echo "<td style='background-color: $value; width: 10px;'>&nbsp;</td>\n";
            }
          }
          echo "<td style='background-color: $v_indexcolors[$count]; text-align: center;' width='80'>" .$l['in_6']. "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
    echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</block>
echo "</div>\n"; #</dataBlock>
reset_sql();
?>
