<?php
####################################
# SURFnet IDS                      #
# Version 2.00.03                  #
# 10-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#########################################################################
# Changelog:
# 2.00.04 No additional info for ARP attacks
# 2.00.03 Fixed a bug in the navigation
# 2.00.02 Changed the info you see with Rogue DHCP attacks
# 2.00.01 version 2.00 
# 1.04.30 Fixed a bug with the wrong checks for binname
# 1.04.29 Changed source and destination IP address search fields
# 1.04.28 Removed PDF generation stuff (this will be redone)
# 1.04.27 Fixed a bug with pdf 
# 1.04.26 Fixed a bug with binname when there was no uniq_binaries record
# 1.04.25 Added IP exclusion stuff
# 1.04.24 Removed the fix
# 1.04.23 Fix for newer PostgreSQL versions
# 1.04.22 Fixed a bug with organisation ranges arrays
# 1.04.21 Fixed severity check
# 1.04.20 Removed chartof variable
# 1.04.19 Fixed typo
# 1.04.18 Removed libchart stuff, modified search results to include cross organisation attacks
# 1.04.17 Fixed bug with destination port
# 1.04.16 Added censorip stuff.
# 1.04.15 Fixed some layout stuff
# 1.04.14 Fixed a bug with md5_binname
# 1.04.13 add_to_sql()
# 1.04.12 Fixed bug with timestamps and multiple sensors
# 1.04.11 Fixed bug with rendering time
# 1.04.10 Fixed a bug with severity 1 and additional info; Added ORDER BY for pof
# 1.04.09 Changed strip_html_escape_bin to strip_html_escape_binname
# 1.04.08 Changed data input handling
# 1.04.07 Bugfix with binaries table linking
# 1.04.06 Changed debug stuff
# 1.04.05 Changed binary search method conform database changes
# 1.04.04 Added personal searchtemplate button for charts
# 1.04.03 Added some default values for ts_start
# 1.04.02 Added source and destination empty check
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.16 Changed the way graphs are generated
# 1.02.15 strip_tags("ts_start"), 
# 1.02.14 Moved the libchart directory to the surfnetids root dir
# 1.02.13 Added some text layout to the idmef report
# 1.02.12 Fixed typo + intval() for session variables
# 1.02.11 Fixed typo
# 1.02.10 Removed includes
# 1.02.09 Enhanced debugging
# 1.02.08 Fixed a bug with the destination address search
# 1.02.07 Added debugging option
# 1.02.06 Bugfix organisation_id in query string
# 1.02.05 Added Classification and additional info to the IDMEF report
# 1.02.04 Multiple sensor-select
# 1.02.03 Query tuning
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
$tab = "3.5";
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
		"int_interval"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

echo "<div id=\"search_wait\">Search is being processed...<br /><br />Please be patient.</div>\n";
if ($c_searchtime == 1) {
  $timestart = microtime_float();
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(timestampa|timestampd|severitya|severityb|sourcea|sourced|sporta|sportd|desta|destd|dporta|dportd|sensorida|sensoridd)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
} else {
  $sql_sort = " timestamp ASC";
  $sort = "timestampa";
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
# Type of attack
####################
if ($f_attack > 0) {
  add_to_sql("details", "table");
  add_to_sql("stats_dialogue", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 1", "where");
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
if (!empty($f_filename)) {
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
if ($f_sourcechoice == 3 && $source_ip == "") {
  add_to_sql(gen_org_sql(1), "where");
} else {
  add_to_sql(gen_org_sql(), "where");
}

add_to_sql("attacks", "table");
add_to_sql("attacks.*", "select");
add_to_sql("sensors.keyname", "select");
add_to_sql("sensors.vlanid", "select");
add_to_sql("sensors.label", "select");
add_to_sql("sensors", "table");
add_to_sql("attacks.sensorid = sensors.id", "where");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");

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
        echo "<div class='blockHeaderLeft'>Criteria</div>\n";
        echo "<div class='blockHeaderRight'>\n";
          echo "<div class='searchheader'>&nbsp;<a href='logsearch.php'>clear</a>&nbsp;</div>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form method='get' action='logsearch.php' name='searchform' id='searchform'>\n";
          echo "<table class='actiontable'>\n";
            echo "<tr>\n";
              echo "<td width='18%'>Destination:</td>\n";
              echo "<th width='65%'>\n";
                if (!isset($destination_ip) && !isset($dest_mac) && $sensorid == 0) echo "ALL";
                if (isset($sensorid)) {
                  if ($sensorid == 0) {} 
                  elseif ($sensorid > 0) echo "$sensorid";
                  else {
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
                    }
                  }
                }
	        if (isset($destination_ip)) echo "$destination_ip";
                if (isset($dest_mac)) echo "$dest_mac";
                if (isset($dport)) echo ":$dport";
              echo "</th>\n";
              echo "<td width='17%' class='aright'><a onclick='\$(\"#search_dest\").toggle();'>change</a></td>\n";
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
              if ($f_destchoice == 1) {
                echo "<td id='dest' style=''>";
              } else {
                echo "<td id='dest' style='display:none;'>";
              }
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='inet_dest' name='inet_dest' alt='Destination IP' onkeyup='searchSuggest(1);' autocomplete='off' value='$destination_ip' />";
                  echo "<div id='search_suggest'>\n";
                    echo "<div id='search_suggest_1'></div>\n";
                  echo "</div>\n";
                } else {
                  echo "<input type='text' id='inet_dest' name='inet_dest' maxlenght=18  value='$destination_ip'/>";
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
              if ($f_destchoice == 2) {
                echo "<td id='destmac' style=''>";
              } else {
                echo "<td id='destmac' style='display:none;'>";
              }
              if ($c_autocomplete == 1) {
                echo "<input type='text' id='mac_destmac' name='mac_destmac' alt='Destination MAC' onkeyup='searchSuggest(2);' autocomplete='off' value='$dest_mac' />";
                echo "<div id='search_suggest'>\n";
                  echo "<div id='search_suggest_2'></div>\n";
                echo "</div>\n"; 
              } else {
                echo "<input type='text' id='mac_destmac' name='mac_destmac' value='$dest_mac' />";
              }
            echo "</td>";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>Port:</td>\n";
            echo "<td><input type='text' name='int_dport' size='5' value='$dport' /></td>";
          echo "</tr>";
          echo "<tr>\n";
            echo "<td><input type='submit' value='Submit' class='sbutton' /></td>";
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
            $ip_excl = "<a href='orgipadmin.php'>IP Exclusion on</a>";
          } else { 
            $ip_excl = "<a href='orgipadmin.php'>IP Exclusion off</a>"; 
          } 
          echo "<tr>\n";
            echo "<td width='18%'>Source:</td>\n";
            echo "<th width='52%'>\n";
              if (!isset($source_ip) && !isset($source_mac)) echo "ALL ";
              if ($f_sourcechoice == 3 && !isset($source_ip)) echo "Own Ranges";
              if (isset($source_ip)) echo "$source_ip";
              if (isset($source_mac)) echo "$source_mac";
              if (isset($sport)) echo ":$sport";
            echo "</th>\n";
            echo "<td width='30%' class='aright'>";
              if ($nr_exclusionrows > 1) echo " (IP Exclusion ON) ";
              else echo " (IP Exclusion OFF) ";
	    echo "<a onclick='\$(\"#search_source\").toggle();'>change</a></td>\n";
          echo "</tr>\n";
	echo "</table>\n";
        echo "<table class='searchtable' id='search_source' style='display: none;'>";
          echo "<tr>\n";
            echo "<td>Address:</td>";
            echo "<td>";
              echo "<select name='int_sourcechoice' onchange='javascript: sh_search_src(this.value);'>\n";
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
                echo "<input type='text' id='inet_source' name='inet_source' alt='Source IP' onkeyup='searchSuggest(3);' autocomplete='off' value='$source_ip' />";
                echo "<div id='search_suggest'>\n";
                  echo "<div id='search_suggest_3'></div>\n";
                echo "</div>\n"; 
               } else { 
                echo "<input type='text' id='inet_source' name='inet_source' maxlenght='18' value='$source_ip' />";
              }
            echo "</td>";
            if ($f_sourcechoice == 2) {
              echo "<td id='sourcemac' style=''>";
            } else {
              echo "<td id='sourcemac' style='display:none;'>";
            }
              if ($c_autocomplete == 1) {
                echo "<input type='text' id='mac_sourcemac' name='mac_sourcemac' alt='Source MAC' onkeyup='searchSuggest(4);' autocomplete='off' value='$source_mac' />";
                echo "<div id='search_suggest'>\n";
                  echo "<div id='search_suggest_4'></div>\n";
                echo "</div>\n"; 
              } else { 
                echo "<input type='text' id='mac_sourcemac' name='mac_sourcemac' value='$source_mac' />";
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
                echo "<input type='text' value='No ranges present' />";
              } else {
                echo "<select name='inet_source' id='inet_source'>\n";
                  $ranges_ar = explode(";", $row['ranges']);
                  sort($ranges_ar);
                  echo printOption("", "All ranges", "" );
                  foreach ($ranges_ar as $range) {
                    if (trim($range) != "") {
                      echo printOption("$range", "$range", "$source_ip" );
                    }
                  }
                echo "</select>\n"; 
              }
            echo "</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td>Port:</td>\n";
            echo "<td><input type='text' name='int_sport' size='5' value='$sport' /></td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td></td>\n";
            echo "<td>$ip_excl</td>\n";
          echo "</tr>\n";
          echo "<tr>\n";
            echo "<td><input type='submit' value='Submit' class='sbutton' /></td>";
          echo "</tr>\n";
        echo "</table>"; 
        echo "<hr>\n";
        echo "<table class='actiontable'>\n";
          echo "<tr>\n";
            echo "<td width='18%'>Characteristics: </td>";
            echo "<td width='65%'></td>\n";
            echo "<td width='17%' class='aright'><a onclick='\$(\"#search_charac\").toggle();'>change</a></td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<table class='actiontable'>\n";
          echo "<tr>";
            echo "<td width='18%'></td>";
            echo "<td width='80%'>";
              if (isset($f_sev)) echo "Severity: <font class='btext'>$v_severity_ar[$f_sev]</font><br />";
              if (isset($f_sevtype)) echo "Severity Type: <font class='btext'>$v_severity_atype_ar[$f_sevtype]</font><br />";
              if (isset($f_attack)) {
                $sql_g = "SELECT name FROM stats_dialogue WHERE id = '$f_attack'";
                $result_g = pg_query($pgconn, $sql_g);
                $row_g = pg_fetch_assoc($result_g);
                $expl = $row_g['name'];
                $expl = str_replace("Dialogue", "", $expl);
                if ($expl != "") echo "Exploit: <font class='btext'>$expl</font><br />";
              }
              if (isset($f_binname)) echo "Binary Name: <font class='btext'>$f_binname</font><br />";
              if (isset($f_virus_txt)) echo "Virus: <font class='btext'>$f_virus_txt</font><br />";
              if (isset($f_filename)) echo "Filename: <font class='btext'>$f_filename</font><br />";
            echo "</td>\n";
            echo "<td width='2%'></td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<table class='searchtable' id='search_charac' style='display: none;'>\n";
          echo "<tr id='sev' style=''>\n";
            echo "<td>Severity:</td>\n";
            echo "<td>\n";
              echo "<select id='int_sev' name='int_sev' onchange='javascript: sh_search_charac(this.value);'>\n";
                if(!isset($f_sev)) $f_sev=-1;
                  echo printOption(-1, "", -1);
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
              echo "<td>Attack-type: </td>";
              echo "<td>";
                echo "<select id='int_sevtype' name='int_sevtype' onchange='javascript: sh_search_charac_sevtype(this.value);'>\n";
                  if(!isset($f_sevtype)) $f_sevtype=-1;
                  echo printOption(-1, "All", -1);
                  foreach ($v_severity_atype_ar as $index=>$sevtype) {
                    echo printOption($index, $sevtype, $f_sevtype);
                  }
                echo "</select>\n";
              echo "</td>";
            echo "</tr>\n";
            if ($f_sevtype == 0) {
              echo "<tr id='attacktype' style=''>\n";
            } else {
              echo "<tr id='attacktype' style='display:none;'>\n";
            }
              echo "<td>Exploit: </td>";
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
            if ($f_sev == 32) {
              echo "<tr id='virus' style=''>\n";
            } else {
              echo "<tr id='virus' style='display:none;'>\n";
            }
              echo "<td>Virus: </td>";
              echo "<td>\n";
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='strip_html_escape_virustxt' name='strip_html_escape_virustxt' alt='Search Criteria' onkeyup='searchSuggest(5);' autocomplete='off' value='$f_virus_txt' />Wildcard is %";
                  echo "<div id='search_suggest'>\n";
                    echo "<div id='search_suggest_5\"></div>\n";
                  echo "</div>\n"; 
                } else {
                  echo "<input type='text' name='strip_html_escape_virustxt' id='strip_html_escape_virustxt' value='$f_virus_txt' /> Wildcard is %\n";
                }
              echo "</td>\n";
            echo "</tr>\n";
            if ($f_sev == 32 || $f_sev == 16) {
              echo "<tr id='filename' style=''>\n";
            } else {
              echo "<tr id='filename' style='display:none;'>\n";
            }
              echo "<td>Filename:</td>";
              echo "<td>\n";
                if ($c_autocomplete == 1) { 
                  echo "<input type='text' id='strip_html_escape_filename' name='strip_html_escape_filename' alt='Search Criteria' onkeyup='searchSuggest(6);' autocomplete='off' value='$f_filename' /> Wildcard is %";
                  echo "<div id='search_suggest'>\n";
                    echo "<div id='search_suggest_6'></div>\n";
                  echo "</div>\n"; 
                } else {
                  echo "<input type='text' id='strip_html_escape_filename' name='strip_html_escape_filename' value='$f_filename' /> Wildcard is %\n";
                }
              echo "</td>\n";
            echo "</tr>\n";
            if ($f_sev == 32) {
              echo "<tr id='binary' style=''>\n";
            } else {
              echo "<tr id='binary' style='display:none;'>\n";
            }
              echo "<td>Binary:</td>";
              echo "<td><input type='text' id='strip_html_escape_binname' name='strip_html_escape_binname' value='$f_binname' /> Wildcard is %</td>";
            echo "</tr>";
          echo "</div>";
        echo "<tr>\n";
          echo "<td><input type='submit' value='Submit' class='sbutton' /></td>";
        echo "</tr>\n";
        echo "</table>";
        echo "</form>";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftsmall>

echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>Actions</div>\n";
      echo "<div class='blockContent'>\n";
        $qs = $_SERVER['QUERY_STRING'];
        echo "<a href='logsearch_pdf.php?$qs'>Save as PDF</a><br />";
        echo "<a href='logsearch_idmef.php?$qs'>Save as IDMEF</a><br />";
        echo "<a onclick='\$(\"#templates\").toggle();'>Save as search template</a>";
        echo "<div id='templates' style='display: none;'>\n";
          echo "<form name='temp' action='template_add.php?$qs' method='post'>\n";
            echo "<table class='searchtable'>\n"; 
              echo "<tr>\n";
                echo "<td>Template title</td>\n";
                echo "<td><input type='text' name='strip_html_escape_temptitle' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>Timespan options</td>\n";
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
                  $from_date = date("d-m-Y H:i", $from);
                  $to_date = date("d-m-Y H:i", $to);
                  echo printRadio("$perstr", int_timespan, 1, 0) . "<br />";
                  echo printRadio("$from_date - $to_date", int_timespan, 2, 0) . "<br />";
                  echo printRadio("Don't save timespan info", int_timespan, 3, 3) . "<br />";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td></td>\n";
                echo "<td><input type='submit' value='Save' class='sbutton' /></td>\n";
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
          echo "<div><h3>No matching results found!</h3></div>\n";
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
$url = preg_replace('/&$/', '', $url);
$url = str_replace("&int_page=" . $clean["page"], "", $url);
if ($page_nr == 1) $nav .= "<img src='images/selector_arrow_left.gif'>\n";
else $nav .= "<a href=\"$url&int_page=" . ($page_nr - 1) . "\"><img src='images/selector_arrow_left.gif'></a>&nbsp;<a href='$url&int_page=1'>1</a>&nbsp;..\n";
for ($i = ($page_nr - 2); $i <= ($page_nr + 2); $i++) {
  if (($i > 0) && ($i <= $last_page)) {
    if (($i == $page_nr) && ($i == ($last_page - 1)))  $nav .= "<font class='btext'><font size='3'>$i</font></font>\n"; 
    elseif ($i == $page_nr)  $nav .= "<font class='btext'><font size='3'>$i</font></font>&nbsp;"; 
    elseif ($i == 1) $nav .= "\n";
    elseif ($i == $last_page) $nav .= "\n";
    elseif ($i == ($last_page - 1)) $nav .= "<a href=\"$url&int_page=$i\">$i</a>\n";
    else  $nav .= "<a href=\"$url&int_page=$i\">$i</a>&nbsp;";
  }
}
if ($page_nr < $last_page) $nav .= "..&nbsp;<a href='$url&int_page=$last_page'>$last_page</a>&nbsp;<a href=\"$url&int_page=" . ($page_nr + 1) . "\"><img src='images/selector_arrow_right.gif'></a>\n";
else $nav .= "<img src='images/selector_arrow_right.gif'>\n";
if ($rapport == "single") $nav .= "&nbsp;<a href='$url&reptype='>Multi</a>&nbsp;\n";
if ($rapport == "multi") $nav .= "&nbsp;<a href='$url&reptype=single'>All</a>&nbsp;\n";

####################
# BUILDING SEARCH QUERY
####################
#flush();
prepare_sql();

$sql =  " SELECT $sql_select";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
$sql .= " ORDER BY $sql_sort ";
$sql .= " $sql_limit ";
$debuginfo[] = $sql;
$result = pg_query($sql);

####################
# BUILDING TABLE HEADER
####################
if ($last_page > 1) $page_lbl = "pages";
else $page_lbl = "page";
$navheader = "<font class='btext'>Results</font> (page $page_nr:  $first_result - $last_result of " . number_format($num_rows, 0, ".", ",") . ")\n";

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
    echo "<th width='14%'>" .printsort("Timestamp", "timestamp"). "</th>\n";
    echo "<th width='23%'>" .printsort("Severity", "severity"). "</th>\n";
    echo "<th width='17%'>" .printsort("Source", "source"). "</th>\n";
    echo "<th width='5%'>" .printsort("Port", "sport"). "</th>\n";
    echo "<th width='12%'>" .printsort("Destination", "dest"). "</th>\n";
    echo "<th width='5%'>" .printsort("Port", "dport"). "</th>\n";
    echo "<th width='8%'>" .printsort("Sensor", "sensorid"). "</th>\n";
    echo "<th width='16%'>Additional Info</th>\n";
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
      echo printosimg("Blank", "No Info");
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
      echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 500, 500);\" class='warning' />$source</a>&nbsp;&nbsp;";
      echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
     } else {
      echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 500, 500);\" />$source</a>&nbsp;&nbsp;";
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
    echo "<td>$name</td>\n";

    if ($numrows_details != 0) {
      if ($sev == 1 && $sevtype == 0) {
        $dia_ar = array('attackid' => $id, 'type' => 1);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $text = $dia_result_ar[0]['text'];
        $attack = $v_attacks_ar[$text]["Attack"];
        $attack_url = $v_attacks_ar[$text]["URL"];
        echo "<td>";
        if ($attack_url != "") {
          echo "<a href='$attack_url' target='new'>";
        }
        if ($attack != "") {
          echo "$attack<br />";
        }
        if ($attack_url != "") {
          echo "</a>";
        }
        if ($smac != "") {
          echo "$smac";
        }
        echo "</td>\n";
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
          echo "<td>Source MAC: $smac</td>\n";
        } else { 
          echo "<td></td>\n";
        }
      }
    } else {
      if ($smac == "") {
        echo "<td></td>\n";
      } elseif ($sevtype == 11) {
        echo "<td>Source IP: $source</td>\n";
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
    echo "Page rendered in $mili_gen ms.";
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
