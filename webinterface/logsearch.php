<?php
####################################
# SURFids 3.00                     #
# Changeset 014                    #
# 13-11-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#########################################################################
# Changelog:
# 014 Fixed sorting of attacks with the same timestmap (#178)
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
# Default censor value
####################
# Retrieving cookie variables from $_COOKIE[SURFids]
$allowed_cookie = array(
            "int_dcensor" 
);
$check = extractvars($_COOKIE[SURFids], $allowed_cookie);

if (isset($clean['dcensor'])) {
    $d_censor = $clean['dcensor'];
} else {
    $sql = "SELECT d_censor FROM login WHERE id = '$s_userid'";
    $res = pg_query($pgconn, $sql);
    $row = pg_fetch_assoc($res);
    $d_censor = $row['d_censor'];
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
		"ipv4v6_source",
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
		"int_allexploits",
        "strip_html_escape_sshversion",
        "strip_html_escape_sshuser",
        "strip_html_escape_sshpass",
        "int_sshhascommand",
        "int_sshlogin",
        "strip_html_escape_sshcommand",
        "int_attackid"
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

######################################
# Setting up criteria array
######################################

foreach ($clean as $search => $searchval) {
    if ($searchval != "-1") {
        $crit[$search] = $searchval;
    }
}

if (isset($tainted['sensorid'])) {
    if (is_array($tainted['sensorid'])) {
        $sensorid = -1;
    } elseif ($tainted['sensorid'] > 0) {
        $sensorid = $tainted['sensorid'];
    }
    $crit['sensorid'] = $tainted['sensorid'];
}

#echo "<pre>\n";
#print_r($crit);
#echo "</pre>\n";

####################
# Sensor ID's
####################
if ($sensorid > 0) {
  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = '" .$crit['sensorid']. "'", "where");
} elseif ($sensorid == -1) {
  # multiple sensors
  add_to_sql("sensors", "table");

  # Removing 0 values
  $crit['sensorid'] = array_diff($crit['sensorid'], array(0));

  $count = count($crit['sensorid']);
  if ($count != 0) {
    $tmp_where = "sensors.id IN (";
    for ($i = 0; $i < $count; $i++) {
      if ($i != ($count - 1)) {
        $tmp_where .= $crit['sensorid'][$i]. ", ";
      } else {
        $tmp_where .= $crit['sensorid'][$i];
      }
    }
    $tmp_where .= ")";
    add_to_sql($tmp_where, "where");
  }
}

####################
# Group
####################
if (isset($crit['gid'])) {
  $gid = $crit['gid'];
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
if (isset($crit['sourcemac'])) {
  $source_mac = $crit['sourcemac'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.src_mac = '$source_mac'", "where");
}
if (isset($crit['source'])) {
  $source_ip = $crit['source'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source <<= '$source_ip'", "where");
}
if (isset($crit['ownsource'])) {
  $ownsource = $crit['ownsource'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source <<= '$ownsource'", "where");
}
if (isset($crit['sport'])) {
  $sport = $crit['sport'];
  if ($sport != 0) {
    add_to_sql("attacks", "table");
    add_to_sql("attacks.sport = '$sport'", "where");
  }
}

####################
# Destination IP address
####################
if (isset($crit['destmac'])) {
  $dest_mac = $crit['destmac'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.dst_mac = '$dest_mac'", "where");
}
if (isset($crit['dest'])) {
  $destination_ip = $crit['dest'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.dest <<= '$destination_ip'", "where");
}
if (isset($crit['dport'])) {
  $dport = $crit['dport'];
  if ($dport != 0) {
    add_to_sql("attacks", "table");
    add_to_sql("attacks.dport = '$dport'", "where");
  }
}

####################
# Start timestamp
####################
add_to_sql("attacks", "table");
if (!isset($crit['attackid'])) {
  add_to_sql("attacks.timestamp >= '$from'", "where");
}

####################
# End timestamp
####################
add_to_sql("attacks", "table");
if (!isset($crit['attackid'])) {
  add_to_sql("attacks.timestamp <= '$to'", "where");
}

####################
# Severity
####################
if (isset($crit['sev'])) {
  add_to_sql("attacks", "table");
  add_to_sql("attacks.severity = '" .$crit['sev']. "'", "where");
}

####################
# Severity type
####################
if (isset($crit['sevtype'])) {
  add_to_sql("attacks", "table");
  add_to_sql("attacks.atype = '" .$crit['sevtype']. "'", "where");
}

####################
# All exploits
####################
if (isset($crit['allexploits'])) {
  add_to_sql("attacks", "table");
  add_to_sql("details", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type IN (1, 80)", "where");
}

####################
# Type of attack
####################
if ($crit['attack'] > 0) {
  add_to_sql("details", "table");
  add_to_sql("stats_dialogue", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.text = stats_dialogue.name", "where");
  add_to_sql("stats_dialogue.id = '" .$crit['attack']. "'", "where");
}

####################
# Type of virus
####################
if (isset($crit['virustxt'])) {
  add_to_sql("binaries", "table");
  add_to_sql("details", "table");
  add_to_sql("stats_virus", "table");
  add_to_sql("uniq_binaries", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 8", "where");
  add_to_sql("details.text = uniq_binaries.name", "where");
  add_to_sql("uniq_binaries.id = binaries.bin", "where");
  add_to_sql("binaries.info = stats_virus.id", "where");
  add_to_sql("stats_virus.name LIKE '" .$crit['virustxt']. "'", "where");
  add_to_sql("details.text", "select");
}

####################
# Filename
####################
if (isset($crit['filename'])) {
  add_to_sql("details", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 4", "where");
  add_to_sql("details.text LIKE '%" .$crit['filename']. "'", "where");
  add_to_sql("details.text", "select");
}

####################
# Binary Name
####################
if (isset($crit['binname'])) {
  add_to_sql("details", "table");
  add_to_sql("details.type = 8", "where");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.text LIKE '" .$crit['binname']. "'", "where");
}

####################
# Binary ID
####################
if (isset($crit['binid'])) {
  add_to_sql("details", "table");
  add_to_sql("uniq_binaries", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 8", "where");
  add_to_sql("details.text = uniq_binaries.name", "where");
  add_to_sql("uniq_binaries.id = " .$crit['binid'], "where");
}

####################
# Ranges
####################
if (!isset($crit['gid'])) {
  if ($crit['sourcechoice'] == 3 && $crit['ownsource'] == "") {
    add_to_sql(gen_org_sql(1), "where");
  } else {
    add_to_sql(gen_org_sql(), "where");
  }
}

####################
# SSH has command
####################
if (isset($crit['sshhascommand'])) {
  $crit['sev'] = 1;
  $crit['sevtype'] = 7;

  # 2 = yes
  # 1 = no
  # 0 = both
  $subquery = "SELECT DISTINCT attacks.id FROM attacks, ssh_command WHERE attacks.timestamp < $to AND attacks.timestamp > $from AND atype = 7";
  $subquery .= " AND ssh_command.attackid = attacks.id";
  if ($crit['sshhascommand'] == 2) {
    add_to_sql("attacks.id IN ($subquery)", "where");
  } elseif ($crit['sshhascommand'] == 1) {
    add_to_sql("NOT attacks.id IN ($subquery)", "where");
  }
}

####################
# SSH successful login
####################
if (isset($crit['sshlogin'])) {
  # 2 = yes
  # 1 = no
  # 0 = both
  if ($crit['sshlogin'] == 2) {
    $crit['sev'] = 1;
    $crit['sevtype'] = 7;

    add_to_sql("ssh_logins", "table");
    add_to_sql("attacks.atype = 7", "where");
    add_to_sql("attacks.id = ssh_logins.attackid", "where");
    add_to_sql("ssh_logins.type = TRUE", "where");
  } elseif ($crit['sshlogin'] == 1) {
    $crit['sev'] = 1;
    $crit['sevtype'] = 7;

    add_to_sql("ssh_logins", "table");
    add_to_sql("attacks.atype = 7", "where");
    add_to_sql("attacks.id = ssh_logins.attackid", "where");
    add_to_sql("ssh_logins.type = FALSE", "where");
  }
}

####################
# SSH version
####################
if (isset($crit['sshversion'])) {
  $crit['sev'] = 1;
  $crit['sevtype'] = 7;

  add_to_sql("ssh_version", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_version.attackid", "where");
  if (strpos($crit['sshversion'], "%") === true) {
      add_to_sql("ssh_version.version LIKE '" .$crit['sshversion']. "'", "where");
  } else {
      add_to_sql("ssh_version.version = '" .$crit['sshversion']. "'", "where");
  }
}

####################
# SSH command
####################
if (isset($crit['sshcommand'])) {
  $crit['sev'] = 1;
  $crit['sevtype'] = 7;

  add_to_sql("ssh_command", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_command.attackid", "where");
  if (strpos($crit['sshcommand'], "%") === true) {
      add_to_sql("ssh_command.command LIKE '" .$crit['sshcommand']. "'", "where");
  } else {
      add_to_sql("ssh_command.command = '" .$crit['sshcommand']. "'", "where");
  }
}

####################
# SSH user
####################
if (isset($crit['sshuser'])) {
  $crit['sev'] = 1;
  $crit['sevtype'] = 7;

  add_to_sql("ssh_logins", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_logins.attackid", "where");
  add_to_sql("ssh_logins.sshuser = '" .$crit['sshuser']. "'", "where");
}

####################
# SSH pass
####################
if (isset($crit['sshpass'])) {
  $crit['sev'] = 1;
  $crit['sevtype'] = 7;

  add_to_sql("ssh_logins", "table");
  add_to_sql("attacks.atype = 7", "where");
  add_to_sql("attacks.id = ssh_logins.attackid", "where");
  add_to_sql("ssh_logins.sshpass = '" .$crit['sshpass']. "'", "where");
}

####################
# Attack ID
####################
if (isset($crit['attackid'])) {
  add_to_sql("attacks.id = ". $crit['attackid'], "where");
}

####################
# General query stuff
####################
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
if (!isset($_SESSION["search_num_rows"]) || (intval($_SESSION["search_num_rows"]) == 0) || ($clean['page'] == 0) || $c_search_cache == 0) {
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
if ($c_search_cache == 1) {
  $num_rows = intval($_SESSION["search_num_rows"]);
}

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

############################
# SEARCH CRITERIA INCLUDE
############################

# Setting up search include stuff
$search_dest = 'display: none;';
$search_src = 'display: none;';
$search_char = 'display: none;';
$info_dest = '';
$info_src = '';
$info_char = '';
$show_change = 1;
$single_submit = 0;

echo "<script type='text/javascript' src='${address}include/surfids_search${min}.js'></script>\n";
echo "<div class='leftmed'>\n";
include_once 'sinclude.php';
echo "</div>\n";


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
$url = $_SERVER['PHP_SELF'];
$qs = urldecode($_SERVER['QUERY_STRING']);
$url = $url . "?" . $qs;
$url = preg_replace('/&$/', '', $url);
$url = str_replace("&int_page=" . $clean["page"], "", $url);
$url = str_replace("?int_page=" . $clean["page"], "", $url);
$url = trim($url, "?");

$nav = printNav($page_nr, $last_page, $url);

if (strstr($url, "?")) {
    $oper = "&";
} else {
    $oper = "?";
}

$url = str_replace("reptype=single&", "", $url);
$url = str_replace("&reptype=single", "", $url);
$url = str_replace("reptype=&", "", $url);
$url = str_replace("&reptype=", "", $url);

# Handling report types
if ($rapport == "single") $nav .= "&nbsp;<a href='${url}${oper}reptype='>" .$l['ls_multi']. "</a>&nbsp;\n";
if ($rapport == "multi") $nav .= "&nbsp;<a href='${url}${oper}reptype=single'>" .$l['g_all']. "</a>&nbsp;\n";

####################
# BUILDING SEARCH QUERY
####################
#flush();
prepare_sql();

# Bugfix #178
$sql_sort .= ", severity ASC";

$sql =  "SELECT $sql_select";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
$sql .= " ORDER BY $sql_sort ";
if ($rapport == "multi") {
    $sql .= " $sql_limit ";
}
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
    echo "<th width='21%'>" .printsort($l['ls_sev'], "severity"). "</th>\n";
    echo "<th width='19%'>" .printsort($l['ls_source'], "source"). "</th>\n";
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

  if ($sevtype == 7) {
    $sql_details = "SELECT attacks.id FROM attacks ";
	$sql_details .= " LEFT JOIN ssh_command ON attacks.id = ssh_command.attackid ";
	$sql_details .= " LEFT JOIN ssh_logins ON attacks.id = ssh_logins.attackid ";
	$sql_details .= " LEFT JOIN ssh_version ON attacks.id = ssh_version.attackid ";
	$sql_details .= " WHERE attacks.id = $id";
  } else {
    $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
  }
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
    	echo "<td><a onclick=\"popit('" ."logdetail.php?int_id=$id&int_atype=$sevtype". "', 409, 700);\">$sevtext</a></td>\n";
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
    } elseif ($sevtype == 12) {
      echo "$source</td><td></td>\n";
    } else {
      if (matchCIDR($source, $ranges_ar)) {
        if ($d_censor == 2) {
          $t_source = censorip($source, $d_censor);
        } else {
          $t_source = $source;
        }
        echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" class='warning' />$t_source</a>&nbsp;&nbsp;";
        echo "<img src='images/ownranges.jpg' ".printover("IP from your own ranges!") ."></td>\n";
      } else {
        if ($d_censor == 2) {
          $t_source = censorip($source, $d_censor);
        } else {
          $t_source = $source;
        }
        echo "<a onclick=\"popUp('" ."whois.php?ip_ip=$source". "', 800, 500);\" />$t_source</a>&nbsp;&nbsp;</td>";
      }
      echo "<td>$sport</td>\n";
    }
    $dest = censorip($dest, $d_censor);
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
          $dia_ar = array('attackid' => $id, 'type' => 84);
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

debug_sql();
pg_close($pgconn);
?>
<script language="javascript" type="text/javascript">
document.getElementById('search_wait').style.display='none';
</script>

<?php footer(); ?>
