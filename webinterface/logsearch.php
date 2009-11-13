<?php
####################################
# SURFids 3.00                     #
# Changeset 013                    #
# 29-10-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#########################################################################
# Changelog:
# 013 Removed unnecessary sql clause for f_attack
# 012 Changed default sorting order to timestampd
# 011 Fixed handling of Amun exploits
# 010 Fixed issue #140
# 009 Added link to sensordetails
# 008 Fixed a navigational bug
# 007 Added MAC exclusion stuff
# 005 Fixed BUG #59
# 004 Fixed BUGS #42 + #43 
# 003 Fixed a typo
# 002 Fixed bug with Criterea 
# 001 Added language support
#########################################################################

####################
# REPORT TYPE
####################
$valid_reptype = array("multi", "single");
if (in_array($_GET['reptype'], $valid_reptype)) {
  $rapport = pg_escape_string($_GET['reptype']);
} else {
  $rapport = "multi";
}

if ($rapport == "idmef") {
  $qs = $_SERVER['QUERY_STRING'];
  header("Location: logsearch_idmef.php?$qs");
  exit;
}
if ($rapport == "pdf") {
  $qs = $_SERVER['QUERY_STRING'];
  header("Location: logsearch_pdf.php?$qs");
  exit;
}

####################
# PAGE HEADER
####################
$tab = "3.7";
$pagetitle = "Search";
include 'menu.php';
contentHeader();

####################
# GEOIP MODULES
####################
if ($c_geoip_enable == 1) {
  include '../include/' .$c_geoip_module;
  $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
}

####################
# DATA INPUT
####################
# Retrieving posted variables from $_GET
$allowed_get = array(
        "reptype",
		"int_org",
		"sensorid",
		"mac_sourcemac",
		"inet_source",
		"inet_ownsource",
		"int_sport",
		"mac_destmac",
		"inet_dest",
		"int_dport",
		"int_sev",
		"int_sevtype",
		"strip_html_escape_binname",
		"int_attack",
		"strip_html_escape_virustxt",
		"strip_html_escape_filename",
		"int_from",
		"int_to",
		"int_charttype",
		"sort",
		"int_page",
		"int_binid",
		"int_sourcechoice",
		"int_destchoice",
		"int_interval",
		"int_gid",
		"int_macfilter",
		"int_ipfilter",
        "int_allexploits"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

echo "<div id=\"search_wait\">" .$l['ls_process']. "<br /><br />" .$l['gm_patient']. ".</div>\n";
if ($c_searchtime == 1) {
  $timestart = microtime_float();
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(timestampa|timestampd|severitya|severityb|sourcea|sourced|sporta|sportd|desta|destd|dporta|dportd|sensorida|sensoridd)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
} else {
  $sql_sort = " timestamp DESC";
  $sort = "timestampd";
}

###################
# FILTERS
###################
if (isset($clean['macfilter'])) {
  $filter_mac = $clean['macfilter'];
} else {
  $filter_mac = 1;
}

if (isset($clean['ipfilter'])) {
  $filter_ip = $clean['ipfilter'];
} else {
  $filter_ip = 1;
}

###################
# INTERVAL
###################
if (isset($clean['interval'])) {
  $int = $clean['interval'];
  $to = date("U");
  $from = $to - $int;
  $_SESSION['s_to'] = $to;
  $_SESSION['s_from'] = $from;
}

### Setting values from searchform
if (@is_array($tainted["sensorid"])) {
  if ($tainted['sensorid'][0] != 0) {
    $sensorid = -1;
    $ar_sensorid = array();
    foreach ($tainted["sensorid"] as $sid) {
      $ar_sensorid[] = intval($sid);
    }
  } else {
    $sensorid = 0;
    $ar_sensorid[] = $sensorid;
  }
} else {
  $sensorid = intval($tainted['sensorid']);
  $ar_sensorid[] = $sensorid;
}

####################
# Severity
####################
if (isset($clean['sev'])) {
  $f_sev = $clean['sev'];
  if (!array_key_exists($f_sev, $v_severity_ar)) {
    unset($f_sev);
  }
}

####################
# Severity Type
####################
if (isset($clean['sevtype'])) {
  $f_sevtype = $clean['sevtype'];
  if (!array_key_exists($f_sevtype, $v_severity_atype_ar)) {
    unset($f_sevtype);
  }
}

####################
# Binary name
####################
$bin_pattern = '/^[a-zA-Z0-9%]{1,33}$/';
if (preg_match($bin_pattern, $clean['binname'])) {
  $f_binname = $clean['binname'];
}

####################
# Binary ID
####################
$f_binid = $clean['binid'];

####################
# Attack type
####################
$f_attack = $clean['attack'];

####################
# Virus type
####################
$f_virus_txt = $clean['virustxt'];

####################
# Filename
####################
$f_filename = $clean['filename'];

####################
# Report type
####################
$f_reptype = $rapport;

####################
# Choice Types
####################
$f_destchoice = $clean['destchoice'];
if (!isset($clean['destchoice'])) $f_destchoice = 1;
$f_sourcechoice = $clean['sourcechoice'];
if (!isset($clean['sourcechoice'])) $f_sourcechoice = 1;

####################
# Sensor ID's
####################
if ($sensorid > 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = '$sensorid'", "where");
} elseif ($sensorid == -1) {
  # multiple sensors
  add_to_sql("sensors", "table");
  $count = count($ar_sensorid);
  $tmp_where = "sensors.id IN (";
  for ($i = 0; $i < $count; $i++) {
    if ($i != ($count - 1)) {
      $tmp_where .= "$ar_sensorid[$i], ";
    } else {
      $tmp_where .= "$ar_sensorid[$i]";
    }
  }
  $tmp_where .= ")";
  add_to_sql($tmp_where, "where");
}

####################
# Group
####################
if (isset($clean['gid'])) {
  $gid = $clean['gid'];
  $sql_gid = "SELECT sensorid FROM groupmembers WHERE groupid = '$gid'";
  $result_gid = pg_query($pgconn, $sql_gid);
  $i = 0;
  $tmp_where = "sensors.id IN (";
  $num_gid = pg_num_rows($result_gid);
  while ($row_gid = pg_fetch_assoc($result_gid)) {
    $i++;
    $group_sid = $row_gid['sensorid'];
    if ($i != $num_gid) {
      $tmp_where .= "$group_sid, ";
    } else {
      $tmp_where .= "$group_sid";
    }
  }
  $tmp_where .= ")";
  add_to_sql($tmp_where, "where");
}

####################
# Source IP address
####################
if (isset($clean['sourcemac'])) {
  $source_mac = $clean['sourcemac'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.src_mac = '$source_mac'", "where");
}
if (isset($clean['source'])) {
  $source_ip = $clean['source'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source <<= '$source_ip'", "where");
}
if (isset($clean['ownsource'])) {
  $ownsource = $clean['ownsource'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source <<= '$ownsource'", "where");
}
if (isset($clean['sport'])) {
  $sport = $clean['sport'];
  if ($sport != 0) {
    add_to_sql("attacks", "table");
    add_to_sql("attacks.sport = '$sport'", "where");
  }
}

####################
# Destination IP address
####################
if (isset($clean['destmac'])) {
  $dest_mac = $clean['destmac'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.dst_mac = '$dest_mac'", "where");
}
if (isset($clean['dest'])) {
  $destination_ip = $clean['dest'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.dest <<= '$destination_ip'", "where");
}
if (isset($clean['dport'])) {
  $dport = $clean['dport'];
  if ($dport != 0) {
    add_to_sql("attacks", "table");
    add_to_sql("attacks.dport = '$dport'", "where");
  }
}

####################
# Start timestamp
####################
add_to_sql("attacks", "table");
add_to_sql("attacks.timestamp >= '$from'", "where");

####################
# End timestamp
####################
add_to_sql("attacks", "table");
add_to_sql("attacks.timestamp <= '$to'", "where");

####################
# Severity
####################
if (isset($f_sev)) {
  add_to_sql("attacks", "table");
  add_to_sql("attacks.severity = '$f_sev'", "where");
}

####################
# Severity type
####################
if (isset($f_sevtype)) {
  add_to_sql("attacks", "table");
  add_to_sql("attacks.atype = '$f_sevtype'", "where");
}

####################
# All exploits
####################
if (isset($clean['allexploits'])) {
  add_to_sql("attacks", "table");
  add_to_sql("details", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type IN (1, 80)", "where");
}

####################
# Type of attack
####################
if ($f_attack > 0) {
  add_to_sql("details", "table");
  add_to_sql("stats_dialogue", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.text = stats_dialogue.name", "where");
  add_to_sql("stats_dialogue.id = '$f_attack'", "where");
}

####################
# Type of virus
####################
if (!empty($f_virus_txt)) {
  add_to_sql("binaries", "table");
  add_to_sql("details", "table");
  add_to_sql("stats_virus", "table");
  add_to_sql("uniq_binaries", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 8", "where");
  add_to_sql("details.text = uniq_binaries.name", "where");
  add_to_sql("uniq_binaries.id = binaries.bin", "where");
  add_to_sql("binaries.info = stats_virus.id", "where");
  add_to_sql("stats_virus.name LIKE '$f_virus_txt'", "where");
  add_to_sql("details.text", "select");
}

####################
# Filename
####################
if ("$f_filename" != "") {
  add_to_sql("details", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 4", "where");
  add_to_sql("details.text LIKE '%$f_filename'", "where");
  add_to_sql("details.text", "select");
}

####################
# Binary Name
####################
if (!empty($f_binname)) {
  add_to_sql("details", "table");
  add_to_sql("details.type = 8", "where");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.text LIKE '$f_binname'", "where");
}

####################
# Binary ID
####################
if (!empty($f_binid)) {
  add_to_sql("details", "table");
  add_to_sql("uniq_binaries", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 8", "where");
  add_to_sql("details.text = uniq_binaries.name", "where");
  add_to_sql("uniq_binaries.id = $f_binid", "where");
}

####################
# Ranges
####################
if (!isset($clean['gid'])) {
  if ($f_sourcechoice == 3 && $ownsource == "") {
    add_to_sql(gen_org_sql(1), "where");
  } else {
    add_to_sql(gen_org_sql(), "where");
  }
}

add_to_sql("attacks", "table");
add_to_sql("attacks.*", "select");
add_to_sql("sensors.keyname", "select");
add_to_sql("sensors.vlanid", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors", "table");
add_to_sql("attacks.sensorid = sensors.id", "where");

if ($filter_ip == 1) {
  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
}
if ($filter_mac == 1) {
  # MAC Exclusion stuff
  add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");
}

prepare_sql();

####################
# BUILDING COUNT QUERY
####################
if (!isset($_SESSION["search_num_rows"]) || (intval($_SESSION["search_num_rows"]) == 0) || ($clean['page'] == 0)) {
  ### Prepare count SQL query
  $sql_select = "COUNT(attacks.id) AS total";
  $sql_count = "SELECT $sql_select ";
  $sql_count .= " FROM $sql_from ";
  $sql_count .= " $sql_where ";
  $debuginfo[] = $sql_count;

  # SQL-count query
  $query_count = pg_query($sql_count);
  # Don't use pg_num_rows, slow's down factor 2-4!
  $num_rows = pg_result($query_count, 0);
  ### Check for config option.
  if ($c_search_cache == 1) {
    $_SESSION["search_num_rows"] = $num_rows;
  }
}
$num_rows = intval($_SESSION["search_num_rows"]);

####################
# HITS PER PAGE (SQL LIMIT)
####################
if ($rapport == "single") {
  $per_page = $num_rows;
} else {
  $per_page = 20;
}

####################
# CALC FIRST/LAST PAGE
####################
$last_page = ceil($num_rows / $per_page);
if (isset($clean['page'])) {
  $page_nr = $clean['page'];
  if ($page_nr <= $last_page) {
    $offset = ($page_nr - 1) * $per_page;
  } else {
    $page_nr = 1;
    $offset = 0;
  }
} else {
  $page_nr = 1;
  $offset = 0;
}
$sql_limit = "LIMIT $per_page OFFSET $offset";
$first_result = number_format($offset, 0, ".", ",");
if ($first_result == 0) $first_result++;
$last_result = ($offset + $per_page);
if ($last_result > $num_rows) $last_result = $num_rows;
$last_result = number_format($last_result, 0, ".", ",");

####################
# SEARCH CRITERIA
####################
echo "<div class='leftmed'>";
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
          echo "<table class='actiontable'>\n";
            echo "<tr>\n";
              echo "<td width='18%'>" .$l['ls_dest']. ":</td>\n";
              echo "<th width='65%'>\n";
                if (!isset($destination_ip) && !isset($dest_mac) && $sensorid == 0) echo $l['ls_all'];
                if (isset($sensorid)) {
                  if ($sensorid == 0) {} 
                  elseif ($sensorid > 0) {
                    echo "$sensorid";
                    $graph[] = "sensorid=$sensorid";
                  } else {
                    foreach ($ar_sensorid as $key=>$sid) {
                      if ($q_org == 0) {
                        $sensor_where = " ";
                      } else {
                        $sensor_where = " AND sensors.organisation = '$q_org'";
                      }
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
                  }
                }
                if (isset($destination_ip)) echo "$destination_ip";
                if (isset($dest_mac)) echo "$dest_mac";
                if (isset($dport)) {
                  echo ":$dport";
                  $graph[] = "strip_html_escape_ports=$dport";
                }
              echo "</th>\n";
              echo "<td width='17%' class='aright'><a onclick='\$(\"#search_dest\").toggle();'>" .$l['ls_change']. "</a></td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "<table class='searchtable' id='search_dest' style='display: none;'>";
            echo "<tr>";
              echo "<td>Address:</td>";
              echo "<td>";
                echo "<select name='int_destchoice' onchange='javascript: sh_search_dest(this.value);' class='pers'>\n";
                  foreach ($v_search_dest_ar as $key=>$val) {
                    echo printOption($key, $val, $f_destchoice);
                  }
                echo "</select>\n";
              echo "</td>";
              if ($f_destchoice == 1) {
                echo "<td id='dest' style=''>";
              } else {
                echo "<td id='dest' style='display:none;'>";
              }
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='inet_dest' class='pers' name='inet_dest' alt='" .$l['ls_destip']. "' onkeyup='searchSuggest(1);' autocomplete='off' value='$destination_ip' />";
                  echo "<div id='search_suggest'>\n";
                    echo "<div id='search_suggest_1' class='search_suggest'></div>\n";
                  echo "</div>\n";
                } else {
                  echo "<input type='text' id='inet_dest' class='pers' name='inet_dest' alt='" .$l['ls_destip']. "' maxlenght=18  value='$destination_ip'/>";
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
                if ($f_destchoice == 3) {
                  echo "<td id='sensor' style='' >\n";
                } else {
                  echo "<td id='sensor' style='display:none;' >\n";
                }
                  echo "<select name='sensorid[]' size='$select_size' multiple='true' id='sensorid' class='pers'>\n";
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
              if ($f_destchoice == 2) {
                echo "<td id='destmac' style=''>";
              } else {
                echo "<td id='destmac' style='display:none;'>";
              }
              if ($c_autocomplete == 1) {
                echo "<input type='text' id='mac_destmac' class='pers' name='mac_destmac' alt='" .$l['ls_destmac']. "' onkeyup='searchSuggest(2);' autocomplete='off' value='$dest_mac' />";
                echo "<div id='search_suggest'>\n";
                  echo "<div id='search_suggest_2' class='search_suggest'></div>\n";
                echo "</div>\n"; 
              } else {
                echo "<input type='text' id='mac_destmac' class='pers' name='mac_destmac' alt='" .$l['ls_destmac']. "' value='$dest_mac' />";
              }
            echo "</td>";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>" .$l['ls_port']. ":</td>\n";
            echo "<td><input type='text' class='pers' name='int_dport' size='5' value='$dport' /></td>";
          echo "</tr>";
          echo "<tr>\n";
            echo "<td><input type='submit' value='" .$l['g_submit']. "' class='sbutton' /></td>";
          echo "</tr>";
        echo "</table>"; 
        echo "<hr>\n";
        echo "<table class='actiontable'>\n";
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

          echo "<tr>\n";
            echo "<td width='18%'>" .$l['ls_source']. ":</td>\n";
            echo "<th width='52%'>\n";
              if (!isset($source_ip) && !isset($source_mac) && !isset($ownsource)) echo $l['ls_all']. " ";
              if ($f_sourcechoice == 3 && !isset($ownsource)) echo $l['ls_own'];
              if (isset($source_ip)) echo "$source_ip";
              if (isset($ownsource)) echo "$ownsource";
              if (isset($source_mac)) echo "$source_mac";
              if (isset($sport)) echo ":$sport";
            echo "</th>\n";
            echo "<td width='30%' class='aright'>$ip_excl<br />$mac_excl<br />";
            echo "<a onclick='\$(\"#search_source\").toggle();'>" .$l['ls_change']. "</a></td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<table class='searchtable' id='search_source' style='display: none;'>";
          echo "<tr>\n";
            echo "<td>" .$l['ls_address']. ":</td>";
            echo "<td>";
              echo "<select name='int_sourcechoice' onchange='javascript: sh_search_src(this.value);' class='pers'>\n";
                foreach ($v_search_src_ar as $key=>$val) {
                  echo printOption($key, $val, $f_sourcechoice);
                }
              echo "</select>\n";
            echo "</td>\n"; 
            if ($f_sourcechoice == 1) {
              echo "<td id='source' style=''>";
            } else {
              echo "<td id='source' style='display:none;'>";
            }
              if ($c_autocomplete == 1) { 
                echo "<input type='text' id='inet_source' class='pers' name='inet_source' alt='" .$l['ls_sourceip']. "' onkeyup='searchSuggest(3);' autocomplete='off' value='$source_ip' />";
                echo "<div id='search_suggest'>\n";
                  echo "<div id='search_suggest_3' class='search_suggest'></div>\n";
                echo "</div>\n"; 
               } else { 
                echo "<input type='text' id='inet_source' class='pers' name='inet_source' alt='" .$l['ls_sourceip']. "' maxlenght='18' value='$source_ip' />";
              }
            echo "</td>";
            if ($f_sourcechoice == 2) {
              echo "<td id='sourcemac' style=''>";
            } else {
              echo "<td id='sourcemac' style='display:none;'>";
            }
              if ($c_autocomplete == 1) {
                echo "<input type='text' id='mac_sourcemac' class='pers' name='mac_sourcemac' alt='" .$l['ls_sourcemac']. "' onkeyup='searchSuggest(4);' autocomplete='off' value='$source_mac' />";
                echo "<div id='search_suggest'>\n";
                  echo "<div id='search_suggest_4' class='search_suggest'></div>\n";
                echo "</div>\n"; 
              } else { 
                echo "<input type='text' id='mac_sourcemac' class='pers' name='mac_sourcemac' alt='" .$l['ls_sourcemac']. "' value='$source_mac' />";
              }
            echo "</td>\n";
            if ($f_sourcechoice == 3) {
              echo "<td id='ownrange' style=''>";
            } else {
              echo "<td id='ownrange' style='display:none;'>";
            }
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
          echo "<tr>\n";
            echo "<td>" .$l['ls_port']. ":</td>\n";
            echo "<td><input type='text' class='pers' name='int_sport' size='5' value='$sport' /></td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>" .$l['ls_ipfilter']. ":</td>\n";
            echo "<td>" .printradio($l['g_on'], "int_ipfilter", 1, $filter_ip). "&nbsp;&nbsp;" .printradio($l['g_off'], "int_ipfilter", 0, $filter_ip). "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>" .$l['ls_macfilter']. ":</td>\n";
            echo "<td>" .printradio($l['g_on'], "int_macfilter", 1, $filter_mac). "&nbsp;&nbsp;" .printradio($l['g_off'], "int_macfilter", 0, $filter_mac). "</td>\n";
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
              if (isset($f_sev)) {
                echo $l['ls_sev']. ": <font class='btext'>$v_severity_ar[$f_sev]</font><br />";
                if (isset($f_sevtype)) {
                  if ($f_sevtype != 0) {
                    $graph[] = "severity=$f_sev";
                  }
                } else {
                  $graph[] = "severity=$f_sev";
                }
              }
              if (isset($f_sevtype) && $f_sev == 1) {
                echo $l['ls_sevtype']. ": <font class='btext'>$v_severity_atype_ar[$f_sevtype]</font><br />";
                if ($f_sevtype == 0) {
                  $graph[] = "attack=-1";
                } else {
                  $graph[] = "sevtype=$f_sevtype";
                }
              } elseif ($f_sev == 1) {
                $graph[] = "sevtype=-1&int_totalmal1=1";
              }
              if (isset($f_attack) && $f_sev == 1) {
                $sql_g = "SELECT name FROM stats_dialogue WHERE id = '$f_attack'";
                $result_g = pg_query($pgconn, $sql_g);
                $row_g = pg_fetch_assoc($result_g);
                $expl = $row_g['name'];
                $expl = str_replace("Dialogue", "", $expl);
                $graph[] = "attack=$f_attack";
                if ($expl != "") echo $l['ls_exp']. ": <font class='btext'>$expl</font><br />";
              }
              if (isset($f_binname) && $f_sev == 32) echo $l['ls_binname']. ": <font class='btext'>$f_binname</font><br />";
              if (isset($f_virus_txt) && $f_sev == 32) echo $l['ls_virus']. ": <font class='btext'>$f_virus_txt</font><br />";
              if (isset($f_filename) && ($f_sev == 16 || $f_sev == 16)) echo $l['ls_filename']. ": <font class='btext'>$f_filename</font><br />";
            echo "</td>\n";
            echo "<td width='2%'></td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<table class='searchtable' id='search_charac' style='display: none;'>\n";
          echo "<tr id='sev' style=''>\n";
            echo "<td>" .$l['ls_sev']. ":</td>\n";
            echo "<td>\n";
              echo "<select id='int_sev' name='int_sev' class='pers' onchange='javascript: sh_search_charac(this.value);'>\n";
                if(!isset($f_sev)) $f_sev=-1;
                  echo printOption(-1, "", $f_sev);
                  foreach ($v_severity_ar as $index=>$severity) {
                    echo printOption($index, $severity, $f_sev);
                  }
                echo "</select>\n";
              echo "</td>";
            echo "</tr>";
          echo "<div id='charac_details' style='display: none;'>\n";
            if ($f_sev == 1) {
              echo "<tr id='sevtype' style=''>\n";
            } else {
              echo "<tr id='sevtype' style='display: none;'>\n";
            }
              echo "<td>" .$l['ls_att_type']. ": </td>";
              echo "<td>";
                echo "<select id='int_sevtype' name='int_sevtype' onchange='javascript: sh_search_charac_sevtype(this.value);' class='pers'>\n";
                  if(!isset($f_sevtype)) $f_sevtype=-1;
                  if ($f_sev != 1) $f_sevtype=-1;
                  echo printOption(-1, $l['g_all'], $f_sevtype);
                  foreach ($v_severity_atype_ar as $index=>$sevtype) {
                    echo printOption($index, $sevtype, $f_sevtype);
                  }
                echo "</select>\n";
              echo "</td>";
            echo "</tr>\n";
            if ($f_sevtype == 0 && $f_sev == 1) {
              echo "<tr id='attacktype' style=''>\n";
            } else {
              echo "<tr id='attacktype' style='display:none;'>\n";
            }
              echo "<td>" .$l['ls_exp']. ":</td>";
              echo "<td>";
                echo "<select name='int_attack' id='int_attack' class='pers'>";
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
            if ($f_sev == 32) {
              echo "<tr id='virus' style=''>\n";
            } else {
              echo "<tr id='virus' style='display:none;'>\n";
            }
              echo "<td>" .$l['ls_virus']. ":</td>";
              echo "<td>\n";
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='strip_html_escape_virustxt' class='pers' name='strip_html_escape_virustxt' onkeyup='searchSuggest(5);' autocomplete='off' value='$f_virus_txt' />" .$l['ls_wildcard']. " %";
                  echo "<div id='search_suggest'>\n";
                    echo "<div id='search_suggest_5' class='search_suggest'></div>\n";
                  echo "</div>\n"; 
                } else {
                  echo "<input type='text' class='pers' name='strip_html_escape_virustxt' id='strip_html_escape_virustxt' value='$f_virus_txt' />" .$l['ls_wildcard']. " %\n";
                }
              echo "</td>\n";
            echo "</tr>\n";
            if ($f_sev == 32 || $f_sev == 16) {
              echo "<tr id='filename' style=''>\n";
            } else {
              echo "<tr id='filename' style='display:none;'>\n";
            }
              echo "<td>" .$l['ls_filename']. ":</td>";
              echo "<td>\n";
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' class='pers' id='strip_html_escape_filename' name='strip_html_escape_filename' onkeyup='searchSuggest(6);' autocomplete='off' value='$f_filename' />" .$l['ls_wildcard']. " %";
                  echo "<div id='search_suggest'>\n";
                    echo "<div id='search_suggest_6' class='search_suggest'></div>\n";
                  echo "</div>\n"; 
                } else {
                  echo "<input type='text' class='pers' id='strip_html_escape_filename' name='strip_html_escape_filename' value='$f_filename' />" .$l['ls_wildcard']. " %\n";
                }
              echo "</td>\n";
            echo "</tr>\n";
            if ($f_sev == 32) {
              echo "<tr id='binary' style=''>\n";
            } else {
              echo "<tr id='binary' style='display:none;'>\n";
            }
              echo "<td>Binary:</td>";
              echo "<td><input type='text' class='pers' id='strip_html_escape_binname' name='strip_html_escape_binname' value='$f_binname' />" .$l['ls_wildcard']. " %</td>";
            echo "</tr>";
          echo "</div>";
        echo "<tr>\n";
          echo "<td><input type='submit' value='" .$l['g_submit']. "' class='sbutton' /></td>";
        echo "</tr>\n";
        echo "</table>";
        echo "</form>";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftsmall>

if (count($graph) != 0) {
  foreach ($graph as $key=>$val) {
    if ($key == 0) {
      $g = "?$val";
    } else {
      $g .= "&$val";
    }
  }
  if ($g != "") {
    $g .= "&int_type=1";
    $time = $to - $from;
    if ($time < 72001) {
      $g .= "&int_interval=3600";
    } elseif ($time < 1728001) {
      $g .= "&int_interval=86400";
    } else {
      $g .= "&int_interval=604800";
    }
  }
}

echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
      echo "<div class='blockContent'>\n";
        $qs = $_SERVER['QUERY_STRING'];
        echo "<a href='logsearch_pdf.php?$qs'>" .$l['ls_saveas']. " PDF</a><br />";
        echo "<a href='logsearch_idmef.php?$qs'>" .$l['ls_saveas']. " IDMEF</a><br />";

        echo "<a href='plotter.php$g'>" .$l['ls_graphit']. "</a><br />";


        echo "<a onclick='\$(\"#templates\").toggle();'>" .$l['ls_saveas']. " " .$l['ls_stemp']. "</a>";
        echo "<div id='templates' style='display: none;'>\n";
          echo "<form name='temp' action='template_add.php?$qs' method='post'>\n";
            echo "<table class='searchtable'>\n"; 
              echo "<tr>\n";
                echo "<td>" .$l['ls_temp_title']. "</td>\n";
                echo "<td><input type='text' name='strip_html_escape_temptitle' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['ls_time_options']. "</td>\n";
                echo "<td>";
                  $per = $to - $from;
                  if ($per <= 3600) {
                    $hours = floor($per / 3600);
                    $per = $sec % 3600;
                    $minutes = floor($per / 60);
                    $perstr = "$hours hour(s)";
                    if ($minutes != 0) {
                      $perstr = " $minutes minute(s)";
                    }
                  } else {
                    $days = floor($per / 86400);
                    $perstr = "$days day(s)";
                  }
                  $from_date = date($c_date_format, $from);
                  $to_date = date($c_date_format, $to);
                  echo printRadio("$perstr", int_timespan, 1, 0) . "<br />";
                  echo printRadio("$from_date - $to_date", int_timespan, 2, 0) . "<br />";
                  echo printRadio($l['ls_dontsave'], int_timespan, 3, 3) . "<br />";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td></td>\n";
                echo "<td><input type='submit' value='" .$l['g_submit']. "' class='sbutton' /></td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' value='$s_hash' name='md5_hash' />\n";
          echo "</form>\n";
        echo "</div>\n";
      echo "</div>\n"; 
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; 
  echo "</div>\n"; 
echo "</div>\n"; 

####################
# CHECKING RESULTS
####################
if ($num_rows == 0) {
  # If there are no search results
  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>\n";
          echo "<div class='blockHeaderLeft'>$navheader</div>\n";
          echo "<div class='blockHeaderRight'>";
            echo "<div class='searchnav'>$nav</div>\n";
          echo "</div>\n";
        echo "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<div><h3>" .$l['ls_noresults']. "</h3></div>\n";
        echo "</div>\n";
      echo "</div>\n";
    echo "</div>\n";
  echo "</div>\n";
  debug_sql();
  ?>
  <script language="javascript" type="text/javascript">
  document.getElementById('search_wait').style.display='none';
  </script>
  <?
  footer();
  exit;
}

####################
# NAVIGATION
####################
$nav = "";
$url = $_SERVER['PHP_SELF'];
$qs = urldecode($_SERVER['QUERY_STRING']);
$url = $url . "?" . $qs;
$url = preg_replace('/&$/', '', $url);
$url = str_replace("&int_page=" . $clean["page"], "", $url);
$url = str_replace("?int_page=" . $clean["page"], "", $url);
$url = trim($url, "?");
$count = substr_count($url, "?");
if ($count == 0) {
  $oper = "?";
} else {
  $oper = "&";
}
if ($page_nr == 1) $nav .= "<img src='images/selector_arrow_left.gif'>\n";
else $nav .= "<a href=\"${url}${oper}int_page=" . ($page_nr - 1) . "\"><img src='images/selector_arrow_left.gif'></a>&nbsp;<a href='${url}${oper}int_page=1'>1</a>&nbsp;..\n";
for ($i = ($page_nr - 2); $i <= ($page_nr + 2); $i++) {
  if (($i > 0) && ($i <= $last_page)) {
    if (($i == $page_nr) && ($i == ($last_page - 1)))  $nav .= "<font class='btext'><font size='3'>$i</font></font>\n"; 
    elseif ($i == $page_nr)  $nav .= "<font class='btext'><font size='3'>$i</font></font>&nbsp;"; 
    elseif ($i == 1) $nav .= "\n";
    elseif ($i == $last_page) $nav .= "\n";
    elseif ($i == ($last_page - 1)) $nav .= "<a href=\"${url}${oper}int_page=$i\">$i</a>\n";
    else  $nav .= "<a href=\"${url}${oper}int_page=$i\">$i</a>&nbsp;";
  }
}
if ($page_nr < $last_page) $nav .= "..&nbsp;<a href='${url}${oper}int_page=$last_page'>$last_page</a>&nbsp;<a href=\"${url}${oper}int_page=" . ($page_nr + 1) . "\"><img src='images/selector_arrow_right.gif'></a>\n";
else $nav .= "<img src='images/selector_arrow_right.gif'>\n";
if ($rapport == "single") $nav .= "&nbsp;<a href='${url}${oper}reptype='>" .$l['ls_multi']. "</a>&nbsp;\n";
if ($rapport == "multi") $nav .= "&nbsp;<a href='${url}${oper}reptype=single'>" .$l['g_all']. "</a>&nbsp;\n";

####################
# BUILDING SEARCH QUERY
####################
#flush();
prepare_sql();

$sql_sort .= ", severity ASC";

$sql =  "SELECT $sql_select";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
$sql .= " ORDER BY $sql_sort ";
$sql .= " $sql_limit ";
$debuginfo[] = $sql;
$result = pg_query($sql);

####################
# BUILDING TABLE HEADER
####################
if ($last_page > 1) $page_lbl = $l['ls_pages'];
else $page_lbl = $l['ls_page'];
$navheader = "<font class='btext'>" .$l['ls_results']. "</font> (page $page_nr:  $first_result - $last_result of " . number_format($num_rows, 0, ".", ",") . ")\n";

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>\n";
        echo "<div class='blockHeaderLeft'>$navheader</div>\n";
        echo "<div class='blockHeaderRight'>";
          echo "<div class='searchnav'>$nav</div>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";

echo "<table class='datatable'>\n";
  echo "<tr>\n";
    echo "<th width='14%'>" .printsort($l['ls_timestamp'], "timestamp"). "</th>\n";
    echo "<th width='23%'>" .printsort($l['ls_sev'], "severity"). "</th>\n";
    echo "<th width='17%'>" .printsort($l['ls_source'], "source"). "</th>\n";
    echo "<th width='5%'>" .printsort($l['ls_port'], "sport"). "</th>\n";
    echo "<th width='12%'>" .printsort($l['ls_dest'], "dest"). "</th>\n";
    echo "<th width='5%'>" .printsort($l['ls_port'], "dport"). "</th>\n";
    echo "<th width='8%'>" .printsort($l['g_sensor'], "sensorid"). "</th>\n";
    echo "<th width='16%'>" .$l['ls_additional']. "</th>\n";
  echo "</tr>\n";

while ($row = pg_fetch_assoc($result)) {
  flush();
  $id = pg_escape_string($row['id']);
  $ts = date("d-m-Y H:i:s", $row['timestamp']);
  $sev = $row['severity'];
  $sevtype = $row['atype'];
  if ($sev == 1) {
    $sevtext = "$v_severity_ar[$sev] - $v_severity_atype_ar[$sevtype]";
  } else {
    $sevtext = "$v_severity_ar[$sev]";
  }
  $smac = $row['src_mac'];
  $source = $row['source'];
  $sport = $row['sport'];
  $dmac = $row['dst_mac'];
  $dest = $row['dest'];
  $dport = $row['dport'];
  $sensorid = $row['sensorid'];
  $vlanid = $row['vlanid'];
  $sensorname = $row['keyname'];
  $labelsensor = $row['label'];
  if ($vlanid != 0) { $sensorname = "$sensorname-$vlanid";}

  $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
  $result_details = pg_query($pgconn, $sql_details);
  $numrows_details = pg_num_rows($result_details);
  $debuginfo[] = $sql_details;

  if ($c_enable_pof == 1) {
    $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$source. "' ORDER BY last_tstamp DESC";
    $result_finger = pg_query($pgconn, $sql_finger);
    $numrows_finger = pg_num_rows($result_finger);
    $debuginfo[] = $sql_finger;

    $fingerprint = pg_result($result_finger, 0);
    $finger_ar = explode(" ", $fingerprint);
    $os = $finger_ar[0];
  } else {
    $numrows_finger = 0;
  }

  echo "<tr>\n";
    echo "<td>$ts</td>\n";
    if ($numrows_details != 0) {
    	echo "<td><a onclick=\"popit('" ."logdetail.php?int_id=$id". "', 209, 500);\">$sevtext</a></td>\n";
    } else {
      echo "<td>$sevtext</td>\n";
    }
    echo "<td>";
    if ($numrows_finger != 0) {
      echo printosimg($os, $fingerprint);
    } else {
      echo printosimg("Blank", $l['ls_noinfo']);
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
    if ($sport == 0) {
      $sport = "";
    }
   
    if ($sevtype == 10 || $sevtype == 11) {
      echo "$smac</td>\n";
      echo "<td>$sport</td>\n";
    } else {
     if (matchCIDR($source, $ranges_ar)) {
      echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" class='warning' />$source</a>&nbsp;&nbsp;";
      echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
     } else {
      echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" />$source</a>&nbsp;&nbsp;";
     }
      echo "<td>$sport</td>\n";
    }
    $dest = censorip($dest, $orgranges_ar);
    if ($dport == 0) {
      echo "<td>$dest</td><td></td>\n";
    } else {
      echo "<td>$dest</td><td>$dport</td>\n";
    }
    if ($labelsensor != "") {
      $name = $labelsensor;
    } else {
      $name = $sensorname;
    }
    echo "<td><a href='sensordetails.php?int_sid=$sensorid'>$name</a></td>\n";

    if ($numrows_details != 0) {
      if ($sev == 1 && ($sevtype == 0 || $sevtype == 5)) {
        if ($sevtype == 5) {
          $dia_ar = array('attackid' => $id, 'type' => 80);
          $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
          $text = $dia_result_ar[0]['text'];
        } else {
          $dia_ar = array('attackid' => $id, 'type' => 1);
          $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
          $text = $dia_result_ar[0]['text'];
        }
        if (strpos($text, "Vulnerability") == False) {
          # Handling Nepenthes detail records
          $attack = str_replace("Dialogue", "", $text);
        } else {
          # Handling Amun detail records
          $text = str_replace("Vulnerability", "", $text);
          $attack = trim($text);
        }
        echo "<td>";
        if ($attack != "") {
          echo "$attack<br />";
        }
        if ($smac != "") {
          echo "$smac";
        }
        echo "</td>\n";

      } elseif ( ( $sev == 1 || $sev == 0 ) && $sevtype == 2) {
        $dia_ar = array('attackid' => $id, 'type' => 40);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $module = $dia_result_ar[0]['text'];

        echo "<td>$module</td>";
      } elseif ($sev == 1 && $sevtype == 1) {
        $dia_ar = array('attackid' => $id, 'type' => 20);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $module = $dia_result_ar[0]['text'];

        echo "<td>$module";
		if ($smac != "") {
          echo "<br />$smac";
        }
        echo "</td>\n";
      } elseif ($sev == 1 && $sevtype == 11) {
        echo "<td>Source IP: $source</td>\n";
      } elseif ($sev == 16) {
        $row_details = pg_fetch_assoc($result_details);
        $text = $row_details['text'];
        $file = basename($text);
        if ($smac != "") {
          echo "<td>$file<br />$smac</td>\n";
        } else { 
          echo "<td>$file</td>\n";
        }
      } elseif ($sev == 32) {
        $dia_ar = array('attackid' => $id, 'type' => 8);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $bin = $dia_result_ar[0]['text'];

        $sql_bin = "SELECT uniq_binaries.id, uniq_binaries.name FROM binaries, uniq_binaries WHERE uniq_binaries.name = '$bin' ";
        $sql_bin .= " AND binaries.bin = uniq_binaries.id ";
        $sql_bin .= " ORDER BY timestamp LIMIT 1";
        $result_bin = pg_query($pgconn, $sql_bin);
        $numrows_bin = pg_num_rows($result_bin);
        $row_bin = pg_fetch_assoc($result_bin);
    	$debuginfo[] = $sql_bin;

        echo "<td>";
        if ($numrows_bin != 0) {
          $binid = $row_bin['id'];
          echo "<a href='binaryhist.php?int_binid=$binid'>Info</a>";
        } else {
          echo "Suspicious";
        }
        if ($smac != "") {
          echo "<br />$smac";
        }
        echo "</td>\n";
      } else {
        if ($smac != "") {
          echo "<td>" .$l['ls_sourcemac']. ": $smac</td>\n";
        } else { 
          echo "<td></td>\n";
        }
      }
    } else {
      if ($smac == "") {
        echo "<td></td>\n";
      } elseif ($sevtype == 11) {
        echo "<td>" .$l['ls_sourceip']. ": $source</td>\n";
      } elseif ($sevtype == 10) {
        echo "<td></td>\n";
      } else {
        echo "<td>$smac</td>\n";
      }
    }
  echo "</tr>\n";
}
echo "</table>\n";

echo "</div>\n"; #</blockContent>
echo "<div class='blockFooter'></div>";
echo "</div>\n"; #</datablock>
echo "</div>\n"; #</block>
echo "</div>\n"; #</centerbig>

# Search time stuff
if ($c_searchtime == 1) {
  $timeend = microtime_float();
  $gen = $timeend - $timestart;
  $mili_gen = number_format(($gen * 1000), 0);
  echo "<div class='all'>\n";
  echo "<div class='textcenter'>\n";
    echo $l['ls_rendered']. " $mili_gen ms.";
  echo "</div>\n";
  echo "</div>\n";
}

pg_close($pgconn);
debug_sql();
?>
<script language="javascript" type="text/javascript">
document.getElementById('search_wait').style.display='none';
</script>

<?php footer(); ?>
