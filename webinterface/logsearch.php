<?php
####################################
# SURFnet IDS                      #
# Version 1.04.30                  #
# 18-07-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#########################################################################
# Changelog:
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

### Set report type.
$valid_reptype = array("multi", "single", "idmef");
if (in_array($_GET['reptype'], $valid_reptype)) $rapport = pg_escape_string($_GET['reptype']);
else $rapport = "multi";

# Check the type of report
$ar_non_headers = array("idmef");
if (in_array($rapport, $ar_non_headers)) {
  # Type of report = idmef, we need to include all the stuff that is
  # normally done in menu.php
  session_start();
  if (intval(@strlen($_SESSION["s_user"])) == 0) {
    # User not logged in
    header("Location: /login.php");
    exit;
  }
  include '../include/config.inc.php';
  include '../include/connect.inc.php';
  include '../include/functions.inc.php';
  include '../include/variables.inc.php';

  if ($rapport == "idmef") {
    # Setting headers
    header("Content-type: text/xml");
	  
    header("Cache-control: private");
    $fn = "SURFnet_IDMEF_" . date("d-m-Y_H:i:s") . "_" . ucfirst($_SESSION['s_user']) . ".xml";
    header("Content-disposition: attachment; filename=$fn");
  }
} else {
  include 'menu.php';
  set_title("Search");

  ### GEOIP STUFF
  if ($c_geoip_enable == 1) {
    include '../include/' .$c_geoip_module;
    $gi = geoip_open("../include/" .$c_geoip_data, GEOIP_STANDARD);
  }
}

# Retrieving posted variables from $_GET
$allowed_get = array(
                "reptype",
		"net_searchnet",
		"ip_searchip",
		"int_org",
		"sensorid",
		"mac_sourcemac",
		"ip_sourceip",
		"int_sport",
		"mac_destmac",
		"ip_destip",
		"int_dport",
		"tsselect",
		"strip_html_escape_tsstart",
		"strip_html_escape_tsend",
		"int_sev",
		"int_sevtype",
		"strip_html_escape_binname",
		"int_attack",
		"strip_html_escape_virustxt",
		"strip_html_escape_filename",
		"int_from",
		"int_to",
		"int_charttype",
		"order",
		"orderm",
		"int_page",
		"int_c",
		"int_binid",
		"int_ownranges"
);
$check = extractvars($_GET, $allowed_get);
if ($rapport != "idmef") {
  debug_input();
}

if ((!in_array($rapport, $ar_non_headers)) && $rapport != "idmef") {
  echo "<div id=\"search_wait\">Search is being processed...<br /><br />Please be patient.</div>\n";
}

if ($c_searchtime == 1) {
  $timestart = microtime_float();
}
 
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$search = $clean['search'];
reset_sql();

### Set organisation
if ($s_access_search == 9) {
  if (isset($clean['org'])) {
    $q_org = $clean['org'];
  }
} else {
  $q_org = $s_org;
}

if (isset($clean['ownranges'])) {
  $ownranges = $clean['ownranges'];
} else {
  $ownranges = 0;
}

### Checking for admin.
#if ($s_access_search < 9) {
#  # User does not have admin search rights
#  add_to_sql("sensors.organisation = '" . intval($s_org) . "'", "where");
#} elseif ($q_org > 0) {
#  add_to_sql("sensors.organisation = '" . intval($q_org) . "'", "where");
#}

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
  }
} else $sensorid = intval($tainted['sensorid']);

####################
# WHEN timestamping stuff
####################
$ts_select = $tainted['tsselect'];
$ar_valid_values = array("H", "D", "T", "W", "M", "Y");
if (in_array($ts_select, $ar_valid_values)) {
  $dt = time();
  $date_min = 60;
  $date_hour = 60 * $date_min;
  $date_day = 24 * $date_hour;
  $date_week = 7 * $date_day;
  $date_month = 31 * $date_day;
  $date_year = 365 * $date_day;
  $dt_sub = 0;
  // determine substitute value
  //"H", "D", "T", "W", "M", "Y"
  switch ($ts_select) {
    case "Y":
      $dt_sub = $date_year;
      break;
    case "M":
      $dt_sub = $date_month;
      break;
    case "W":
      $dt_sub = $date_week;
      break;
    case "D":
      $dt_sub = $date_day;
      break;
    case "H":
      $dt_sub = $date_hour;
      break;
    case "T":
      // today
      $dt = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
      break;
  }
  if ($dt_sub > 0) $dt -= $dt_sub;
  $ts_start = date("d-m-Y H:i:s", $dt);
  $ts_end = date("d-m-Y H:i:s", time());
} else {
  $ts_start = $clean["tsstart"];
  $ts_end = $clean["tsend"];
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
} else {
  $f_binname = "";
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
if (isset($clean['sourceip'])) {
  $source_ip = $clean['sourceip'];
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
if (isset($clean['searchnet'])) {
  # Input from other page
  $input = $clean['searchnet'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source <<= '$input'", "where");
} elseif (isset($clean['searchip'])) {
  # Input from other page
  $input = $clean['searchip'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.source = '$input'", "where");
}

####################
# Destination IP address
####################
if (isset($clean['destmac'])) {
  $dest_mac = $clean['destmac'];
  add_to_sql("attacks", "table");
  add_to_sql("attacks.dst_mac = '$dest_mac'", "where");
}
if (isset($clean['destip'])) {
  $destination_ip = $clean['destip'];
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
if (!empty($ts_start)) {
  $ts_start = getepoch($ts_start);
  # Expect: 24-05-2006 11:30 (dd-mm-yyyy hh:mm)
  add_to_sql("attacks", "table");
  add_to_sql("attacks.timestamp >= '$ts_start'", "where");
} elseif (isset($clean['from'])) {
  add_to_sql("attacks", "table");
  $ts_start = $clean['from'];
  add_to_sql("attacks.timestamp >= '$ts_start'", "where");
}

####################
# End timestamp
####################
if (!empty($ts_end)) {
  # Expect: 24-05-2006 11:30 (dd-mm-yyyy hh:mm)
  $ts_end = getepoch($ts_end);
  add_to_sql("attacks", "table");
  add_to_sql("attacks.timestamp <= '$ts_end'", "where");
} elseif (isset($clean['to'])) {
  add_to_sql("attacks", "table");
  $ts_end = $clean['to'];
  add_to_sql("attacks.timestamp <= '$ts_end'", "where");
}

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
  add_to_sql("details.text LIKE '%$f_filename%'", "where");
  add_to_sql("details.text", "select");
}

####################
# Binary Name
####################
if (!empty($f_binname)) {
  add_to_sql("details", "table");
#  add_to_sql("uniq_binaries", "table");
  add_to_sql("details.type = 8", "where");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.text LIKE '$f_binname'", "where");
#  add_to_sql("uniq_binaries.name LIKE '$f_binname'", "where");
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
if (isset($q_org)) {
  if ($q_org != 0) {
    $sql_getranges = "SELECT ranges FROM organisations WHERE id = $q_org";
    $result_getranges = pg_query($pgconn, $sql_getranges);
    $temp = pg_fetch_assoc($result_getranges);
    $orgranges = $temp['ranges'];
    $orgranges = rtrim($orgranges, ";");
    $orgranges_ar = explode(";", $orgranges);
    $tmp_sql = "(sensors.organisation = $q_org";
    foreach ($orgranges_ar as $key => $value) {
      if ($value != "") {
        if ($key != (count($orgranges_ar) - 1)) {
          $ranges_sql .= "attacks.source <<= '$value' OR ";
        } else {
          $ranges_sql .= "attacks.source <<= '$value'";
        }
      }
    }
    if ($ranges_sql != "") {
      $tmp_sql .= " OR ($ranges_sql)";
    }
    $tmp_sql .= ")";
    add_to_sql($tmp_sql, "where");
  }
}

if ($rapport == "idmef") {
  add_to_sql("sensors.keyname", "select");
  add_to_sql("sensors.vlanid", "select");
  add_to_sql("attacks.*", "select");
  add_to_sql("sensors", "table");
  add_to_sql("attacks", "table");
  add_to_sql("sensors.id = attacks.sensorid", "where");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

  prepare_sql();

  ### Prepare final SQL query
  $sql = "SELECT $sql_select ";
  $sql .= " FROM $sql_from ";
  $sql .= " $sql_where ";
  $sql .= " $sql_group ";
    
  $result = pg_query($sql);

  echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
  echo "<!DOCTYPE IDMEF-Message PUBLIC \"-//IETF//DTD RFC XXXX IDMEF v1.0//EN\" \"idmef-message.dtd\">\n";
  echo "<idmef:IDMEF-Message version=\"1.0\" xmlns:idmef=\"http://iana.org/idmef\">\n";
  flush();
  while ($row = pg_fetch_assoc($result)) {
    flush();
    $id = intval($row['id']);
    $keyname = $row['keyname'];
    $vlanid = $row['vlanid'];
    if ($vlanid != 0) {
      $keyname = "$keyname-$vlanid";
    }
    $timestamp = $row['timestamp'];
    $source = $row['source'];
    $sport = intval($row['sport']);
    $dest = $row['dest'];
    $dport = intval($row['dport']);
    $sev = intval($row['severity']);
    $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
    $result_details = pg_query($pgconn, $sql_details);
    $numrows_details = pg_num_rows($result_details);

    $sql_sev = "SELECT txt FROM severity WHERE val = '$sev'";
    $result_sev = pg_query($pgconn, $sql_sev);
    $row_sev = pg_fetch_assoc($result_sev);
    $sev_text = $row_sev['txt'];

    if ($numrows_details != 0) {
      if ($sev == 1) {
        $dia_ar = array('attackid' => $id, 'type' => 1);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $text = $dia_result_ar[0]['text'];
        $attack = $v_attacks_ar[$text]["Attack"];
      } elseif ($sev == 16) {
        $dia_ar = array('attackid' => $id);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $text = $dia_result_ar[0]['text'];
        $malware = basename($text);
      }
    }
    echo "<idmef:Alert messageid=\"$id\">\n";
    echo "  <idmef:Analyzer analyzerid=\"$keyname\">\n";
    echo "  </idmef:Analyzer>\n";
    echo "  <idmef:CreateTime>$timestamp</idmef:CreateTime>\n";
    echo "  <idmef:Classification ident=\"$sev\" text=\"$sev_text\"></idmef:Classification>\n";
    echo "  <idmef:Source>\n";
    echo "    <idmef:Node>\n";
    echo "      <idmef:Address category=\"ipv4-addr\">\n";
    echo "        <idmef:address>$source</idmef:address>\n";
    echo "      </idmef:Address>\n";
    echo "    </idmef:Node>\n";
    echo "    <idmef:Service>\n";
    echo "      <idmef:port>$sport</idmef:port>\n";
    echo "    </idmef:Service>\n";
    echo "  </idmef:Source>\n";
    echo "  <idmef:Target>\n";
    echo "    <idmef:Node>\n";
    echo "      <idmef:Address category=\"ipv4-addr\">\n";
    echo "        <idmef:address>$dest</idmef:address>\n";
    echo "      </idmef:Address>\n";
    echo "    </idmef:Node>\n";
    echo "    <idmef:Service>\n";
    echo "      <idmef:port>$dport</idmef:port>\n";
    echo "    </idmef:Service>\n";
    echo "  </idmef:Target>\n";

    if ($sev == 1 && $attack != "") {
      echo "  <idmef:AdditionalData type=\"string\" meaning=\"attack-type\">\n";
      echo "    <idmef:string>$attack</idmef:string>\n";
      echo "  </idmef:AdditionalData>\n";
    } elseif ($sev == 16 && $malware != "") {
      echo "  <idmef:AdditionalData type=\"string\" meaning=\"file-offered\">\n";
      echo "    <idmef:string>$malware</idmef:string>\n";
      echo "  </idmef:AdditionalData>\n";
    }
    echo "</idmef:Alert>\n";
  }
  echo "</idmef:IDMEF-Message>\n";
  exit;
}

add_to_sql("attacks", "table");
add_to_sql("attacks.*", "select");
add_to_sql("sensors.keyname", "select");
add_to_sql("sensors.vlanid", "select");
add_to_sql("sensors", "table");
add_to_sql("attacks.sensorid = sensors.id", "where");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

prepare_sql();

#########################
### Prepare sql-ORDER BY
#########################
$order_by_tbl = array(	"id"		=> "attacks.id", 
			"timestamp"	=> "attacks.timestamp", 
			"severity"	=> "attacks.severity", 
			"source"	=> "attacks.source",
			"dest"		=> "attacks.dest",
			"keyname"	=> "sensors.id");
if (isset($tainted['order'])) {
  $sql_order_by = $order_by_tbl[$tainted['order']];
}
if (empty($sql_order_by) || !isset($sql_order_by)) $sql_order_by = $order_by_tbl["id"];
# Order method (ascending or descending, default ASC)
if (isset($tainted['orderm'])) {
  if ($tainted['orderm'] == "DESC") $asc_desc = "DESC";
  else $asc_desc = "ASC";
} else $asc_desc = "ASC";
if ($asc_desc == "ASC") $order_m_url[$tainted['order']] = "&orderm=DESC";
add_to_sql($sql_order_by, "order");

#########################

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

if ($num_rows == 0) {
  # If there are no search results
  debug_sql();
  echo "<p>No matching results found!</p>\n";
  ?>
  <script language="javascript" type="text/javascript">
  document.getElementById('search_wait').style.display='none';
  </script>
  <?
  footer();
  exit;
}
### Prepare sql-LIMIT
if ($rapport == "single") $per_page = $num_rows;
else $per_page = 20;

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

### Navigation
$nav = "Result page: ";
$url = $_SERVER['REQUEST_URI'];
$url = str_replace("&int_page=" . $clean["page"], "", $url);
for ($i = ($page_nr - 3); $i <= ($page_nr + 3); $i++) {
  if (($i > 0) && ($i <= $last_page)) {
    if ($i == $page_nr) $nav .= "<b>&laquo;$i&raquo;</b>&nbsp;";
    else $nav .= "<a href=\"$url&int_page=$i\">$i</a>&nbsp;";
  }
}

# Setting up the navigation html
$nav .= "<br />\n";
if ($page_nr == 1) $nav .= "&lt;&lt;&nbsp;First&nbsp;&nbsp;";
else $nav .= "<a href=\"$url&int_page=1\">&lt;&lt;&nbsp;First</a>&nbsp;&nbsp;";
if ($page_nr == 1) $nav .= "&lt;&nbsp;Prev&nbsp;&nbsp;";
else $nav .= "<a href=\"$url&int_page=" . ($page_nr - 1) . "\">&lt;&nbsp;Prev</a>&nbsp;&nbsp;";
$nav .= "<a href=\"search.php\">Search</a>";
if ($page_nr < $last_page) $nav .= "&nbsp;&nbsp;<a href=\"$url&int_page=" . ($page_nr + 1) . "\">Next&nbsp;&gt;</a>\n";
else $nav .= "&nbsp;&nbsp;Next&nbsp;&gt;\n";
if ($page_nr == $last_page) $nav .= "&nbsp;&nbsp;Last&nbsp;&gt;&gt;";
else $nav .= "&nbsp;&nbsp;<a href=\"$url&int_page=$last_page\">Last&nbsp;&gt;&gt;</a>";

# XML IDMEF logging button
$idmef_url = $_SERVER['REQUEST_URI'];
if (intval(strpos($idmef_url, "reptype")) == 0) $idmef_url .= "&reptype=idmef";
else $idmef_url = str_replace("reptype=" . $tainted["reptype"], "&reptype=idmef", $idmef_url);
echo "<div id=\"xml_idmef\"><a href=\"$idmef_url\" title=\"Download these results as IDMEF format XML file\"><img src=\"./images/xml.png\" border=\"0\" width=\"48\" height=\"52\"></a><br>IDMEF</div>\n";

# Personal search templates
echo "<div id=\"personal_searchtemplate\"><a href=\"#\" onclick=\"submitSearchTemplateFromResults('" . $_SERVER['QUERY_STRING'] . "');\"><img src='images/searchtemplate_add.png' alt='Add this search query to my personal search templates' title='Add this search query to my personal search templates' border='0'></a><br>Search-<br>template</div>\n";

flush();

prepare_sql();

### Prepare final SQL query
$sql =  " SELECT $sql_select";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
if ($sql_order) {
  $sql .= " ORDER BY $sql_order $asc_desc ";
}
$sql .= " $sql_limit ";
$debuginfo[] = $sql;
$result = pg_query($sql);

if ($last_page > 1) $page_lbl = "pages";
else $page_lbl = "page";
echo "<p>Results <b>$first_result</b> - <b>$last_result</b> of <b>" . number_format($num_rows, 0, ".", ",") . "</b> in <b>" . number_format($last_page, 0, ".", ",") . "</b> $page_lbl.</p>\n";

if ($rapport == "multi") {
  echo "<div id=\"lognav\" align=\"center\">$nav</div>\n";
  echo "<br />\n";
}

$url = $_SERVER['REQUEST_URI'];
$ar_search = array("&order=" . $tainted["order"], "&orderm=" . $tainted["orderm"]);
$url = str_replace($ar_search, "", $url);
echo "<table class='datatable' width='100%'>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' width='3%'><a href=\"$url&order=id" . $order_m_url["id"] . "\">ID</a></td>\n";
    echo "<td class='dataheader' width='12%'><a href=\"$url&order=timestamp" . $order_m_url["timestamp"] . "\">Timestamp</a></td>\n";
    echo "<td class='dataheader' width='20%'><a href=\"$url&order=severity" . $order_m_url["severity"] . "\">Severity</a></td>\n";
    echo "<td class='dataheader' width='19%'><a href=\"$url&order=source" . $order_m_url["source"] . "\">Source</a></td>\n";
    echo "<td class='dataheader' width='16%'><a href=\"$url&order=dest" . $order_m_url["dest"] . "\">Destination</a></td>\n";
    echo "<td class='dataheader' width='8%'><a href=\"$url&order=keyname" . $order_m_url["keyname"] . "\">Sensor</a></td>\n";
    echo "<td class='dataheader' width='12%'>Additional Info</td>\n";
  echo "</tr>\n";

while ($row = pg_fetch_assoc($result)) {
  flush();
  $id = pg_escape_string($row['id']);
  $ts = date("d-m-Y H:i:s", $row['timestamp']);
  $smac = $row['src_mac'];
  $sev = $row['severity'];
  $sevtype = $row['atype'];
  if ($sev == 1) {
    $sevtext = "$v_severity_ar[$sev] - $v_severity_atype_ar[$sevtype]";
  } else {
    $sevtext = "$v_severity_ar[$sev]";
  }
  if ($sevtype == 10) {
    $source = $row['src_mac'];
    $sport = $row['sport'];
    $dest = $row['dest'];
    $dport = $row['dport'];
    $smac = "";
  } else {
    $source = $row['source'];
    $sport = $row['sport'];
    $dest = $row['dest'];
    $dport = $row['dport'];
  }
  $sensorid = $row['sensorid'];
  $vlanid = $row['vlanid'];
  $sensorname = $row['keyname'];
  if ($vlanid != 0){ $sensorname = "$sensorname-$vlanid";}

  $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
  $result_details = pg_query($pgconn, $sql_details);
  $numrows_details = pg_num_rows($result_details);
  $debuginfo[] = $sql_details;

  if ($c_enable_pof == 1 && $sevtype != 10) {
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
    if ($numrows_details != 0) {
      echo "<td class='datatd'><a href='logdetail.php?int_id=$id'>$id</a></td>\n";
    } else {
      echo "<td class='datatd'>$id</td>\n";
    }
    echo "<td class='datatd'>$ts</td>\n";
    echo "<td class='datatd'>$sevtext</td>\n";
    echo "<td class='datatd'>";
    if ($numrows_finger != 0) {
      $osimg = "$c_surfidsdir/webinterface/images/$os.gif";
      if (file_exists($osimg)) {
        echo "<img src='images/$os.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
      } else {
        echo "<img src='images/Blank.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;";
      }
    } else {
      echo "<img src='images/Blank.gif' alt='No info' title='No info' />&nbsp;";
    }

    if ($c_geoip_enable == 1) {
      $record = geoip_record_by_addr($gi, $source);
      $countrycode = strtolower($record->country_code);
      $cimg = "$c_surfidsdir/webinterface/images/worldflags/flag_" .$countrycode. ".gif";
      if (file_exists($cimg)) {
        $country = $record->country_name;
        echo "<img src='images/worldflags/flag_" .$countrycode. ".gif' onmouseover='return overlib(\"$country\");' onmouseout='return nd();' />&nbsp;";
      } else {
        echo "<img src='images/worldflags/flag.gif' onmouseover='return overlib(\"No Country Info\");' onmouseout='return nd();' style='width: 18px;' />&nbsp;";
      }
    }
    if ($sport == 0) {
      $sp = "";
    } else {
      $sp = ":$sport";
    }
    if ($sevtype == 10 || $sevtype == 11) {
      echo "$source$sp</td>\n";
    } else {
      echo "<a href='whois.php?ip_ip=$source'>$source$sp</a></td>\n";
    }
    $dest = censorip($dest, $orgranges_ar);
    if ($dport == 0) {
      echo "<td class='datatd'>$dest</td>\n";
    } else {
      echo "<td class='datatd'>$dest:$dport</td>\n";
    }
    echo "<td class='datatd'>$sensorname</td>\n";
    if ($numrows_details != 0) {
      if ($sev == 1 && $sevtype == 0) {
        $dia_ar = array('attackid' => $id, 'type' => 1);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $text = $dia_result_ar[0]['text'];
        $attack = $v_attacks_ar[$text]["Attack"];
        $attack_url = $v_attacks_ar[$text]["URL"];
        echo "<td class='datatd'>";
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

        echo "<td class='datatd'>$module";
	if ($smac != "") {
          echo "<br />$smac";
        }
        echo "</td>\n";
      } elseif ($sev == 16) {
        $row_details = pg_fetch_assoc($result_details);
        $text = $row_details['text'];
        $file = basename($text);
        if ($smac != "") {
          echo "<td class='datatd'>$file<br />$smac</td>\n";
        } else {
          echo "<td class='datatd'>$file</td>\n";
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

        echo "<td class='datatd'>";
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
          echo "<td class='datatd'>Source MAC: $smac</td>\n";
        } else {
          echo "<td class='datatd'></td>\n";
        }
      }
    } else {
      if ($smac != "") {
        echo "<td class='datatd'>$smac</td>\n";
      } else {
        echo "<td class='datatd'></td>\n";
      }
    }
  echo "</tr>\n";
}
echo "</table>\n";

if ($rapport == "multi") {
  echo "<br />\n";
  echo "<div id=\"lognav\" align=\"center\">$nav</div>\n";
  echo "<br />\n";
}

pg_close($pgconn);
debug_sql();

# Search time stuff
if ($c_searchtime == 1) {
  $timeend = microtime_float();
  $gen = $timeend - $timestart;
  $mili_gen = number_format(($gen * 1000), 0);
  echo "<br />Page rendered in $mili_gen ms.<br />";
}

?>
<script language="javascript" type="text/javascript">
document.getElementById('search_wait').style.display='none';
</script>

<?php footer(); ?>
