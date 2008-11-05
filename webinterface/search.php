<?php $tab="3.5"; $pagetitle="Search"; include("menu.php"); contentHeader();

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 25-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 2.10.01 Added language support
# 2.00.01 version 2.00
# 1.04.13 Fixed some autocomplete stuff
# 1.04.12 Changed source IP and destination IP address search fields
# 1.04.11 Removed PDF option (is being redone)
# 1.04.10 Added autocomplete function
# 1.04.09 Removed chartof stuff
# 1.04.08 Removed libchart stuff & fixed sensors query
# 1.04.07 Fixed a bug with the destination radiobutton
# 1.04.06 Changed strip_html_escape_bin to strip_html_escape_binname
# 1.04.05 Changed data input handling
# 1.04.04 Query tuning
# 1.04.03 Added Searchtemplates
# 1.04.02 Added VLAN support 
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 Removed includes
# 1.02.05 Added search templates
# 1.02.04 Added multiple sensor select
# 1.02.03 Added IDMEF to the report types
#############################################

if (isset($_SESSION['s_total_search_records'])) {
  unset($_SESSION['s_total_search_records']);
}
$_SESSION["search_num_rows"] = 0;
unset($_SESSION["search_num_rows"]);

echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>" .$l['ls_crit']. "</div>\n";
      echo "<div class='blockContent padding'>\n";
        echo "<form method='get' action='logsearch.php' name='searchform' id='searchform'>\n";
          echo "<table class='searchtable'>\n";
            echo "<tr>";
              echo "<td width='90'><b>" .$l['ls_dest']. "</b></td>";
            echo "</tr>";
            echo "<tr>";
              echo "<td>" .$l['ls_address']. ":</td>";
              echo "<td>";
                echo "<select name='int_destchoice' onchange='javascript: sh_search_dest(this.value);'>\n";
                  foreach ($v_search_dest_ar as $key=>$val) {
                    echo printOption($key, $val, -1);
                  }
                echo "</select>\n";
              echo "</td>";
              echo "<td id='dest' style='display:;'>";
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='inet_dest' name='inet_dest' alt='" .$l['ls_destip']. "' onkeyup='searchSuggest(1);' autocomplete='off' />";
                  echo "<div id='search_suggest_1' class='search_suggest'></div>\n";
                } else { 
                  echo "<input type='text' id='inet_dest' name='inet_dest' alt='" .$l['ls_destip']. "' maxlenght=18 />";
                } 
              echo "</td>";
              $select_size = 5;
              if ($q_org == 0) {
                $where = " ";
              } else {
                $where = " AND sensors.organisation = '$q_org'";
              }
              $sql = "SELECT COUNT(id) FROM sensors WHERE 1=1 $where";
              $debuginfo[] = $sql;
              $query = pg_query($sql);
              $nr_rows = intval(@pg_result($query, 0));
              if ($nr_rows < $select_size) {
                $select_size = ($nr_rows + 1);
              }
              echo "<td id='sensor' style='display:none;' >\n";
                echo "<select name='sensorid[]' size='$select_size' multiple='true' id='sensorid'>\n";
                  echo printOption(0, $l['ls_allsensors'], $sensorid);
                  $sql = "SELECT sensors.id, sensors.keyname, sensors.vlanid, sensors.label, organisations.organisation FROM sensors, organisations ";
                  $sql .= "WHERE organisations.id = sensors.organisation $where ORDER BY sensors.keyname";
                  $debuginfo[] = $sql;
                  $query = pg_query($sql);
                  while ($sensor_data = pg_fetch_assoc($query)) {
                    $sid = $sensor_data['id'];
                    $keyname = $sensor_data["keyname"];
                    $label = $sensor_data["label"];
                    $vlanid = $sensor_data["vlanid"];
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
              echo "<td id='destmac' style='display:none;'>";
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='mac_destmac' name='mac_destmac' alt='" .$l['ls_destmac']. "' onkeyup='searchSuggest(2);' autocomplete='off' />";
                  echo "<div id='search_suggest_2' class='search_suggest'></div>\n";
                } else { 
                  echo "<input type='text' id='mac_destmac' name='mac_destmac' alt='" .$l['ls_destmac']. "' value='' />";
                } 
              echo "</td>";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['ls_port']. ":</td>\n";
              echo "<td><input type='text' name='int_dport' size='5' /></td>";
            echo "</tr>";
          echo "</table>"; 
          echo "<hr>"; 
          echo "<table class='searchtable'>"; 
            echo "<tr>\n";
              echo "<td width='90'><b>" .$l['ls_source']. "</b></td>";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['ls_address']. ":</td>";
              echo "<td>";
                echo "<select name='int_sourcechoice' onchange='javascript: sh_search_src(this.value);'>\n";
                  foreach ($v_search_src_ar as $key=>$val) {
                    echo printOption($key, $val, -1);
                  }
                echo "</select>\n";
              echo "</td>\n"; 
              echo "<td id='source'> ";
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='inet_source' name='inet_source' alt='" .$l['ls_sourceip']. "' onkeyup='searchSuggest(3);' autocomplete='off' />";
                  echo "<div id='search_suggest_3' class='search_suggest'></div>\n";
                } else { 
                  echo "<input type='text' id='inet_source' name='inet_source' alt='" .$l['ls_sourceip']. "' maxlenght=18 />";
                } 
              echo "</td>";
              echo "<td id='sourcemac' style='display:none;'>";
                if ($c_autocomplete == 1) {
                  echo "<input type='text' id='mac_sourcemac' name='mac_sourcemac' alt='" .$l['ls_sourcemac']. "' onkeyup='searchSuggest(4);' autocomplete='off' />";
                  echo "<div id='search_suggest_4' class='search_suggest'></div>\n";
                } else { 
                  echo "<input type='text' id='mac_sourcemac' name='mac_sourcemac' alt='" .$l['ls_sourcemac']. "' value='' />";
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
                        echo printOption("$range", "$range", "" );
                      }
                    }
                  echo "</select>\n"; 
                }
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>" .$l['ls_port']. ":</td>\n";
              echo "<td><input type='text' name='int_sport' size='5' /></td>\n";
            echo "</tr>\n";

            $sql_exclusion = "SELECT exclusion FROM org_excl WHERE orgid = $q_org";
            $result_exclusion = pg_query($pgconn, $sql_exclusion);
            $query = pg_query($sql_exclusion);
            $debuginfo[] = $sql_exclusion;
            $nr_exclusionrows = intval(@pg_result($query, 0));
            if ($nr_exclusionrows > 1) {
              $ip_excl = "<a href='orgipadmin.php'>" .$l['ls_ipex_on']. "</a>";
            } else { 
              $ip_excl = "<a href='orgipadmin.php'>" .$l['ls_ipex_off']. "</a>"; 
            } 
            echo "<tr>\n";
              echo "<td></td>\n";
              echo "<td>$ip_excl</td>\n";
            echo "</tr>\n";
          echo "</table>"; 
          echo "<hr>"; 
          echo "<table class='searchtable'>"; 
            echo "<tr>\n";
              echo "<td width='90'><b>" .$l['ls_chars']. "</b></td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Severity:</td>\n";
              echo "<td>\n";
                echo "<select name='int_sev' onchange='javascript: sh_search_charac(this.value);'>\n";
                  $f_sev = -1;
                  echo printOption(-1, "", $f_sev);
                  foreach ( $v_severity_ar as $index=>$severity ) {
                    echo printOption($index, $severity, $f_sev);
                  }
                echo "</select>\n";
              echo "</td>";
            echo "</tr>\n";
          echo "</table>\n";
          echo "<br />"; 
          echo "<table class='searchtable'>\n";
            echo "<tr>\n";
              echo "<td width='90'></td>\n";
              echo "<td>\n";
                echo "<div id='charac_details' style='display:none;'>\n"; 
                  echo "<div class='details'>\n"; 
                    echo "<div class='detailsHeader'>" .$l['ls_details']. "</div>\n"; 
                    echo "<div class='detailsContent'>\n"; 
                      echo "<table class='searchtable'>\n";
                        echo "<tr id='sevtype' style=''>\n";
                          echo "<td>" .$l['ls_att_type']. ": </td>";
                          echo "<td>";
                            echo "<select id='int_sevtype' name='int_sevtype' onchange='javascript: sh_search_charac_sevtype(this.value);'>\n";
                              $f_sevtype = -1;
                              echo printOption(-1, $l['g_all'], $f_sevtype);
                              foreach ( $v_severity_atype_ar as $index=>$sevtype ) {
                                if ($sevtype == 1 && $c_enable_argos == 1) {
                                  echo printOption($index, $sevtype, $f_sevtype);
                                } elseif (($sevtype == 10 || $sevtype == 11) && $c_enable_arp) {
                                  echo printOption($index, $sevtype, $f_sevtype);
                                } elseif ($sevtype == 0) {
                                  echo printOption($index, $sevtype, $f_sevtype);
                                }
                              }
                            echo "</select>\n";
                          echo "</td>";
                        echo "</tr>\n";
                        echo "<tr id='attacktype' style='display:none;'>\n";
                          echo "<td>" .$l['ls_exp']. ":</td>";
                          echo "<td>";
                            echo "<select name='int_attack' id='int_attack'>";
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
                        echo "<tr id='virus' style=''>";
                          echo "<td>" .$l['ls_virus']. ":</td>";
                          echo "<td>\n";
                            if ($c_autocomplete == 1) { 
                              echo "<input type='text' id='strip_html_escape_virustxt' name='strip_html_escape_virustxt' alt='" .$l['ls_search_crit']. "' onkeyup='searchSuggest(5);' autocomplete='off' />" .$l['ls_wildcard']. " %";
                              echo "<div id='search_suggest_5' class='search_suggest'></div>\n";
                            } else { 
                              echo "<input type='text' id='strip_html_escape_virustxt' name='strip_html_escape_virustxt' value='' />" .$l['ls_wildcard']. " %\n";
                            }
                          echo "</td>\n";
                        echo "</tr>\n";
                        echo "<tr id='filename' style=''>";
                          echo "<td>" .$l['ls_filename']. ":</td>";
                          echo "<td>\n";
                            if ($c_autocomplete == 1) { 
                              echo "<input type='text' id='strip_html_escape_filename' name='strip_html_escape_filename' alt='Search Criteria' onkeyup='searchSuggest(6);' autocomplete='off' />" .$l['ls_wildcard']. " %";
                              echo "<div id='search_suggest_6' style='display: none;' class='search_suggest'></div>\n";
                            } else {
                              echo "<input type='text' id='strip_html_escape_filename' name='strip_html_escape_filename' value='' />" .$l['ls_wildcard']. " %\n";
                            }
                          echo "</td>\n";
                        echo "</tr>\n";
                        echo "<tr id='binary' style=''>";
                          echo "<td>" .$l['ls_binname']. ":</td>";
                          echo "<td><input type='text' id='strip_html_escape_binname' name='strip_html_escape_binname' value='$f_bin' />" .$l['ls_wildcard']. " %</td>";
                        echo "</tr>";
                      echo "</table>\n";
                    echo "</div>\n"; #</detailsContent>
                    echo "<div class='detailsFooter'></div>\n"; 
                  echo "</div>\n"; #</details>
                echo "</div>\n"; #</charac_details>
              echo "</td>";
            echo "</tr>";
          echo "</table>";
          echo "<table class='searchtable'>";
            echo "<tr>";
              echo "<td width='90'></td>";
              echo "<td width='350'></td>";
              echo "<td><input type='submit' value='" .$l['ls_search']. "' class='sbutton' /><input type='button' value='" .$l['ls_clear']. "' class='sbuttonneg' onclick=\"window.location='search.php'\" /></td>";
            echo "</tr>\n";
          echo "</table>";
        echo "</form>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>
debug_sql();
?>
<?php footer(); ?>
