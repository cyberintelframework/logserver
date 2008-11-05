<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 15-02-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.10.01 Initial release
#############################################

echo "<div class='block'>";
  echo "<div class='actionBlock'>\n";
    echo "<div class='blockHeader'>\n";
      echo "" .$l['ls_search']. "\n";
    echo "</div>\n";
    echo "<div class='blockContent'>\n";
      echo "<form method='get' action='logsearch.php' name='searchform' id='searchform'>\n";
        echo "<table class='actiontable'>\n";
          echo "<tr>\n";
            echo "<td width='18%'>" .$l['ls_dest']. ":</td>\n";
            echo "<th width='65%'>\n";
              echo "" .$l['ls_all']. "\n";
            echo "</th>\n";
            echo "<td width='17%' class='aright'><a onclick='\$(\"#search_dest\").toggle();'>" .$l['ls_change']. "</a></td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<table class='searchtable' id='search_dest' style='display: none;'>";
          echo "<tr>";
            echo "<td>Address:</td>";
            echo "<td>";
              echo "<select name='int_destchoice' onchange='javascript: sh_search_dest(this.value);'>\n";
                foreach ($v_search_dest_ar as $key=>$val) {
                  echo printOption($key, $val, $f_destchoice);
                }
              echo "</select>\n";
            echo "</td>";
            echo "<td id='dest' style=''>";
            if ($c_autocomplete == 1) { 
              echo "<input type='text' id='inet_dest' name='inet_dest' alt='" .$l['ls_destip']. "' onkeyup='searchSuggest(1);' autocomplete='off' value='$destination_ip' />";
              echo "<div id='search_suggest'>\n";
                echo "<div id='search_suggest_1' class='search_suggest'></div>\n";
              echo "</div>\n";
            } else {
              echo "<input type='text' id='inet_dest' name='inet_dest' alt='" .$l['ls_destip']. "' maxlenght=18  value='$destination_ip'/>";
            } 
          echo "</td>";
          $select_size = 5;
          if ($q_org == 0) {
            $sensor_where = " ";
          } else {
            $sensor_where = " AND sensors.organisation = '$q_org'";
          }
          $sql = "SELECT COUNT(id) FROM sensors WHERE 1=1 $sensor_where";
          $debuginfo[] = $sql;
          $query = pg_query($sql);
          $nr_rows = intval(@pg_result($query, 0));
          if ($nr_rows < $select_size) {
            $select_size = ($nr_rows + 1);
          }
          if ($nr_rows > 1) {
            echo "<td id='sensor' style='display:none;' >\n";
              echo "<select name='sensorid[]' size='$select_size' multiple='true' id='sensorid'>\n";
                echo printOption(0, "All sensors", $ar_sensorid);
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
                  echo printOption($sid, $name, $ar_sensorid);
                }
              echo "</select>\n";
            echo "</td>\n";
          }
          echo "<td id='destmac' style='display:none;'>";
            if ($c_autocomplete == 1) {
              echo "<input type='text' id='mac_destmac' name='mac_destmac' alt='" .$l['ls_destmac']. "' onkeyup='searchSuggest(2);' autocomplete='off' value='$dest_mac' />";
              echo "<div id='search_suggest'>\n";
                echo "<div id='search_suggest_2' class='search_suggest'></div>\n";
              echo "</div>\n"; 
            } else {
              echo "<input type='text' id='mac_destmac' name='mac_destmac' alt='" .$l['ls_destmac']. "' value='$dest_mac' />";
            }
          echo "</td>";
        echo "</tr>\n";
        echo "<tr>\n";
          echo "<td>" .$l['ls_port']. ":</td>\n";
          echo "<td><input type='text' name='int_dport' size='5' value='$dport' /></td>";
        echo "</tr>";
        echo "<tr>\n";
          echo "<td><input type='submit' value='" .$l['g_submit']. "' class='sbutton' /></td>";
        echo "</tr>";
      echo "</table>"; 
      echo "<hr>\n";
      echo "<table class='actiontable'>\n";
        $sql_exclusion = "SELECT exclusion FROM org_excl WHERE orgid = $q_org";
        $result_exclusion = pg_query($pgconn, $sql_exclusion);
        $query = pg_query($sql_exclusion);
        $debuginfo[] = $sql_exclusion;
        $nr_exclusionrows = intval(@pg_result($query, 0));
        if ($nr_exclusionrows > 1) {
          $ip_excl = "<a href='orgipadmin.php'>" .$l['ls_ipex_on']. "</a>";
        } else { 
          $ip_excl = "<a href='orgipadmin.php'>IP Exclusion off</a>"; 
        } 
        echo "<tr>\n";
          echo "<td width='18%'>" .$l['ls_source']. ":</td>\n";
          echo "<th width='52%'>\n";
             echo "" .$l['ls_all']. "\n";
          echo "</th>\n";
          echo "<td width='30%' class='aright'>";
            if ($nr_exclusionrows > 1) echo " (" .$l['ls_ipex_on']. ") ";
            else echo " (" .$l['ls_ipex_off']. ") ";
            echo "<a onclick='\$(\"#search_source\").toggle();'>" .$l['ls_change']. "</a>\n";
          echo "</td>\n";
        echo "</tr>\n";
      echo "</table>\n";
      echo "<table class='searchtable' id='search_source' style='display: none;'>";
        echo "<tr>\n";
          echo "<td>" .$l['ls_address']. ":</td>";
          echo "<td>";
            echo "<select name='int_sourcechoice' onchange='javascript: sh_search_src(this.value);'>\n";
              foreach ($v_search_src_ar as $key=>$val) {
                echo printOption($key, $val, $f_sourcechoice);
              }
            echo "</select>\n";
          echo "</td>\n"; 
          echo "<td id='source' style=''>";
            if ($c_autocomplete == 1) { 
              echo "<input type='text' id='inet_source' name='inet_source' alt='" .$l['ls_sourceip']. "' onkeyup='searchSuggest(3);' autocomplete='off' value='$source_ip' />";
              echo "<div id='search_suggest'>\n";
                echo "<div id='search_suggest_3' class='search_suggest'></div>\n";
              echo "</div>\n"; 
            } else { 
              echo "<input type='text' id='inet_source' name='inet_source' alt='" .$l['ls_sourceip']. "' maxlenght='18' value='$source_ip' />";
            }
          echo "</td>";
          echo "<td id='sourcemac' style='display:none;'>";
            if ($c_autocomplete == 1) {
              echo "<input type='text' id='mac_sourcemac' name='mac_sourcemac' alt='" .$l['ls_sourcemac']. "' onkeyup='searchSuggest(4);' autocomplete='off' value='$source_mac' />";
              echo "<div id='search_suggest'>\n";
                echo "<div id='search_suggest_4' class='search_suggest'></div>\n";
              echo "</div>\n"; 
            } else { 
              echo "<input type='text' id='mac_sourcemac' name='mac_sourcemac' alt='" .$l['ls_sourcemac']. "' value='$source_mac' />";
            }
          echo "</td>\n";
          echo "<td id='ownrange' style='display:none;'>";
            $sql_ranges = "SELECT ranges FROM organisations WHERE id = $q_org";
            $debuginfo[] = $sql_ranges;
            $result_ranges = pg_query($pgconn, $sql_ranges);
            $row = pg_fetch_assoc($result_ranges);
            if ($row['ranges'] == "") {
              echo "<input type='text' value='" .$l['ls_noranges']. "' />";
            } else {
              echo "<select name='inet_ownsource' id='inet_ownsource'>\n";
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
        echo "<tr>\n";
          echo "<td>" .$l['ls_port']. ":</td>\n";
          echo "<td><input type='text' name='int_sport' size='5' value='$sport' /></td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
          echo "<td></td>\n";
          echo "<td>$ip_excl</td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
          echo "<td><input type='submit' value='" .$l['g_submit']. "' class='sbutton' /></td>";
        echo "</tr>\n";
      echo "</table>"; 
      echo "<hr>\n";
      echo "<table class='actiontable'>\n";
        echo "<tr>\n";
          echo "<td width='18%'>" .$l['ls_chars']. ":</td>";
          echo "<td width='65%'></td>\n";
          echo "<td width='17%' class='aright'><a onclick='\$(\"#search_charac\").toggle();'>" .$l['ls_change']. "</a></td>\n";
        echo "</tr>\n";
      echo "</table>\n";
      echo "<table class='actiontable'>\n";
        echo "<tr>";
          echo "<td width='18%'></td>";
          echo "<td width='80%'>";
          echo "</td>\n";
          echo "<td width='2%'></td>\n";
        echo "</tr>\n";
      echo "</table>\n";
      echo "<table class='searchtable' id='search_charac' style='display: none;'>\n";
        echo "<tr id='sev' style=''>\n";
          echo "<td>" .$l['ls_sev']. ":</td>\n";
          echo "<td>\n";
            echo "<select id='int_sev' name='int_sev' onchange='javascript: sh_search_charac(this.value);'>\n";
              if(!isset($f_sev)) $f_sev=-1;
                echo printOption(-1, "", $f_sev);
                foreach ($v_severity_ar as $index=>$severity) {
                  echo printOption($index, $severity, $f_sev);
                }
              echo "</select>\n";
            echo "</td>";
        echo "</tr>";
        echo "<div id='charac_details' style='display: none;'>\n";
          echo "<tr id='sevtype' style='display: none;'>\n";
            echo "<td>" .$l['ls_att_type']. ": </td>";
            echo "<td>";
              echo "<select id='int_sevtype' name='int_sevtype' onchange='javascript: sh_search_charac_sevtype(this.value);'>\n";
                if(!isset($f_sevtype)) $f_sevtype=-1;
                if ($f_sev != 1) $f_sevtype=-1;
                echo printOption(-1, $l['g_all'], $f_sevtype);
                foreach ($v_severity_atype_ar as $index=>$sevtype) {
                  echo printOption($index, $sevtype, $f_sevtype);
                }
              echo "</select>\n";
            echo "</td>";
          echo "</tr>\n";
          echo "<tr id='attacktype' style='display:none;'>\n";
            echo "<td>" .$l['ls_exp']. ":</td>";
            echo "<td>";
              echo "<select name='int_attack' id='int_attack'>";
                if ($f_sevtype != 0) $f_attack=-1;
                echo printOption(-1, "All exploits", $f_attack);
                $sql = "SELECT * FROM stats_dialogue ORDER BY name";
                $debuginfo[] = $sql;
                $query = pg_query($sql);
                while ($row = pg_fetch_assoc($query)) {
                  $name = str_replace("Dialogue", "", $row["name"]);
                  echo printOption($row["id"], $name, $f_attack);
                }
              echo "</select>";
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr id='virus' style='display:none;'>\n";
            echo "<td>" .$l['ls_virus']. ":</td>";
            echo "<td>\n";
              if ($c_autocomplete == 1) { 
                echo "<input type='text' id='strip_html_escape_virustxt' name='strip_html_escape_virustxt' onkeyup='searchSuggest(5);' autocomplete='off' value='$f_virus_txt' />" .$l['ls_wildcard']. " %";
                echo "<div id='search_suggest'>\n";
                  echo "<div id='search_suggest_5' class='search_suggest'></div>\n";
                echo "</div>\n"; 
              } else {
                echo "<input type='text' name='strip_html_escape_virustxt' id='strip_html_escape_virustxt' value='$f_virus_txt' />" .$l['ls_wildcard']. " %\n";
              }
          echo "</td>\n";
        echo "</tr>\n";
        echo "<tr id='filename' style='display:none;'>\n";
          echo "<td>" .$l['ls_filename']. ":</td>";
          echo "<td>\n";
            if ($c_autocomplete == 1) { 
              echo "<input type='text' id='strip_html_escape_filename' name='strip_html_escape_filename' onkeyup='searchSuggest(6);' autocomplete='off' value='$f_filename' />" .$l['ls_wildcard']. " %";
              echo "<div id='search_suggest'>\n";
                echo "<div id='search_suggest_6' class='search_suggest'></div>\n";
              echo "</div>\n"; 
            } else {
              echo "<input type='text' id='strip_html_escape_filename' name='strip_html_escape_filename' value='$f_filename' />" .$l['ls_wildcard']. " %\n";
            }
          echo "</td>\n";
        echo "</tr>\n";
        echo "<tr id='binary' style='display:none;'>\n";
          echo "<td>Binary:</td>";
          echo "<td><input type='text' id='strip_html_escape_binname' name='strip_html_escape_binname' value='$f_binname' />" .$l['ls_wildcard']. " %</td>";
        echo "</tr>";
      echo "</table>";
      echo "<table class='actiontable'>";
        echo "<tr><td class='aright'><input type='submit' value='" .$l['g_submit']. "' class='sbutton' /></td></tr>";
      echo "</table>";
      echo "</form>";
    echo "</div>\n"; #</blockContent>
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n"; #</actionBlock>
echo "</div>\n"; #</block>

reset_sql();
?>
