<?php
####################################
# SURFnet IDS                      #
# Version 1.02.16                  #
# 26-09-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#########################################################################
# Changelog:
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

if ($_GET['f_reptype'] == "idmef") {
	session_start();
	if (intval(@strlen($_SESSION["s_user"])) == 0) {
		// User not logged in
		header("Location: /login.php");
		exit;
	}
        include 'include/config.inc.php';
        include 'include/connect.inc.php';
        include 'include/functions.inc.php';

	header("Content-type: text/xml");
  
  	header("Cache-control: private");
  	$fn = "SURFnet_IDMEF_" . date("d-m-Y_H:i:s") . "_" . ucfirst($_SESSION['s_user']) . ".xml";
  	header("Content-disposition: attachment; filename=$fn");
}
else {
  include("menu.php");
  set_title("Search");
}
if (($_GET["f_reptype"] != "chart_sensor") && ($_GET["f_reptype"] != "chart_attack") && ($_GET["f_reptype"] != "idmef")) {
	echo "<div id=\"search_wait\">Search is being processed...<br /><br />Please be patient.</div>\n";
}

#include 'include/config.inc.php';
#include 'include/connect.inc.php';
#include 'include/functions.inc.php';
#include 'include/variables.inc.php';

if ($searchtime == 1) {
  $timestart = microtime_float();
}
 
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$search = pg_escape_string(trim($_GET['f_search']));
$where = array();

### Set organisation
if ($s_access_search == 9) {
	if (isset($_GET['org'])) $q_org = intval($_GET['org']);
}

### Set report type.
$valid_reptype = array("multi", "single", "chart_sensor", "chart_attack", "idmef");
if (in_array($_GET['f_reptype'], $valid_reptype)) $rapport = pg_escape_string($_GET['f_reptype']);
else $rapport = "multi";

### Checking for admin.
if ($s_access_search < 9) $where[] = "sensors.organisation = '" . intval($s_org) . "'";
elseif ($q_org > 0) $where[] = "sensors.organisation = '" . intval($q_org) . "'";

### Setting values from searchform
if (@is_array($_GET["sensorid"])) {
	$sensorid = -1;
	$ar_sensorid = array();
	foreach ($_GET["sensorid"] as $sid) {
		$ar_sensorid[] = intval($sid);
	}
} else $sensorid = intval($_GET["sensorid"]);

$source_ip = $_GET["source_ip"];
$full_source_ip = "";
foreach ($source_ip as $key=>$val) {
	$val = intval(trim($val));
	if ($key > 0) $full_source_ip .= ".";
	$full_source_ip .= $val;
}
if ($full_source_ip == "0.0.0.0") $full_source_ip = -1;
elseif (ip2long($full_source_ip) === -1) $full_source_ip = -2;

if ($_GET["s_radio"] == "A") {
	$source_port = intval($_GET["source_port"]);
	$source_mask = -1;
} else {
	$source_port = -1;
	$source_mask = intval($_GET["source_mask"]);
}
$destination_ip = $_GET["destination_ip"];
$full_destination_ip = "";
foreach ($destination_ip as $key=>$val) {
	$val = intval(trim($val));
	if ($key > 0) $full_destination_ip .= ".";
	$full_destination_ip .= $val;
}
if ($full_destination_ip == "0.0.0.0") $full_destination_ip = -1;
elseif (ip2long($full_destination_ip) === -1) $full_destination_ip = -2;

if ($_GET["d_radio"] == "A") {
	$destination_port = intval($_GET["destination_port"]);
	$destination_mask = -1;
} else {
	$destination_port = -1;
	$destination_mask = intval($_GET["destination_mask"]);
}
$ts_start = trim(pg_escape_string(strip_tags($_GET["ts_start"])));
$ts_end = trim(pg_escape_string(strip_tags($_GET["ts_end"])));
if (isset($_GET["f_sev"])) {
	$f_sev = intval($_GET["f_sev"]);
	if ($f_sev < 0) unset($f_sev);
}
$f_bin = trim(pg_escape_string(strip_tags($_GET["f_bin"])));
$f_attack = intval($_GET["f_attack"]);
if ($f_attack == 0 && isset($_GET["f_attack"])) {
	// Using old attack (name instead of id), lookup id
	$query = pg_query("SELECT id FROM stats_dialogue WHERE name = '" . pg_escape_string($_GET["f_attack"]) . "'");
	$f_attack = intval(@pg_result($query, 0));
}
$f_virus = intval($_GET["f_virus"]);
$f_virus_txt = trim(pg_escape_string(strip_tags($_GET["f_virus_txt"])));
$f_filename = trim(pg_escape_string(strip_tags($_GET["f_filename"])));
$f_reptype = pg_escape_string(strip_tags($_GET["f_reptype"]));

$db_table = array("sensors", "attacks");
$where[] = "attacks.sensorid = sensors.id";

### Limit sensor:
if ($sensorid > 0) {
	add_db_table("sensors");
	$where[] = "sensors.id = '$sensorid'";
} elseif ($sensorid == -1) {
	// multiple sensors
	if (!((@count($ar_sensorid) == 1) && ($ar_sensorid[0] == 0))) {
		add_db_table("sensors");
		$tmp_where = "sensors.id = '" . $ar_sensorid[0] . "'";
		for ($i = 1; $i < count($ar_sensorid); $i++) {
			$tmp_where .= " OR sensors.id = '" . $ar_sensorid[$i] . "'";
		}
		$where[] = $tmp_where;
	} // else: all sensors
}

### Limit source address
if ($full_source_ip > 0) {
	add_db_table("attacks");
	if ($source_mask > 0) {
		// Network address
		$source_ip = $full_source_ip . "/" . $source_mask;
		$where[] = "attacks.source <<= '$source_ip'";
	} else {
		$source_ip = $full_source_ip;
		$where[] = "attacks.source = '$source_ip'";
		if ($source_port > 0) $where[] = "attacks.sport = '$source_port'";
	}
} elseif ($source_port > 0) {
	add_db_table("attacks");
	$where[] = "attacks.sport = '$source_port'"; // Just search for portnumber
} elseif (isset($_GET["f_search"])) {
	// Input from other page
	$input = trim(pg_escape_string(strip_tags($_GET["f_search"])));
	if (!empty($input)) {
		add_db_table("attacks");
		if (strstr($input, "/") === false) {
			// no slash found, assume source_ip
			$where[] = "attacks.source = '$input'";
		} else {
			// slash found, assume network
			$where[] = "attacks.source <<= '$input'";
		}
	}
}

### Limit destination address
if ($full_destination_ip > 0) {
	add_db_table("attacks");
	if ($destination_mask > 0) {
		// Network address
		$destination_ip = $full_destination_ip . "/" . $destination_mask;
		$where[] = "attacks.dest <<= '$destination_ip'";
	} else {
		$destination_ip = $full_destination_ip;
		$where[] = "attacks.dest = '$destination_ip'";
		if ($destination_port > 0) $where[] = "attacks.dport = '$destination_port'";
	}
} elseif ($destination_port > 0) {
	add_db_table("attacks");
	$where[] = "attacks.dport = '$destination_port'"; // Just search for portnumber
}

### Limit timestamp_start:
if (!empty($ts_start)) {
	// Expect: 24-05-2006 11:30 (dd-mm-yyyy hh:mm)
	list($date, $time) = explode(" ", $ts_start);
	list($day, $mon, $year) = explode("-", $date);
	list($hour, $min) = explode(":", $time);
	// Date MUST BE valid
	$day = intval($day);
	$mon = intval($mon);
	$year = intval($year);
	if (($day > 0) && ($mon > 0) && ($year > 0)) {
		if (checkdate($mon, $day, $year)) {
			// Valid date, check time
			$hour = intval($hour);
			$min = intval($min);
			if (!(($minute >= 0) && ($min < 60) && ($hour >= 0) && ($hour < 24))) {
				// Invalid time, generate midnight (0:00)
				$hour = $min = 0;
			}
			$ts_start = mktime($hour, $min, 0, $mon, $day, $year);
			add_db_table("attacks");
			$where[] = "attacks.timestamp >= '$ts_start'";
			//echo date("d-m-y H:i", $ts_start);
		}		
		// else: Incomplete date, ignore this timestamp
	}
} elseif (isset($_GET["from"])) {
	add_db_table("attacks");
	$ts_start = intval($_GET["from"]);
	$where[] = "attacks.timestamp >= '$ts_start'";
}

### Limit timestamp_end:
if (!empty($ts_end)) {
	// Expect: 24-05-2006 11:30 (dd-mm-yyyy hh:mm)
	list($date, $time) = explode(" ", $ts_end);
	list($day, $mon, $year) = explode("-", $date);
	list($hour, $min) = explode(":", $time);
	// Date MUST BE valid
	$day = intval($day);
	$mon = intval($mon);
	$year = intval($year);
	if (($day > 0) && ($mon > 0) && ($year > 0)) {
		if (checkdate($mon, $day, $year)) {
			// Valid date, check time
			$hour = intval($hour);
			$min = intval($min);
			if (!(($minute >= 0) && ($min < 60) && ($hour >= 0) && ($hour < 24))) {
				// Invalid time, generate midnight-1 (23:59)
				$hour = 23;
				$min = 59;
			}
			$ts_end = mktime($hour, $min, 0, $mon, $day, $year);
			add_db_table("attacks");
			$where[] = "attacks.timestamp <= '$ts_end'";
		}		
		// else: Incomplete date, ignore this timestamp
	}
} elseif (isset($_GET["to"])) {
	add_db_table("attacks");
	$ts_end = intval($_GET["to"]);
	$where[] = "attacks.timestamp <= '$ts_end'";
}

### Limit severity
if (isset($f_sev)) {
	add_db_table("attacks");
	$where[] = "attacks.severity = '$f_sev'";
}

### Limit attack
if ($f_attack > 0) {
	add_db_table("details");
	add_db_table("stats_dialogue");
	$where[] = "details.type = 1";
	$where[] = "details.text = stats_dialogue.name";
	$where[] = "stats_dialogue.id = '$f_attack'";
}

### Limit virusname (select OR match string)
if ($f_virus > 0) {
	// From selectbox
	add_db_table("binaries");
	add_db_table("details"); // for binaries, don't remove!
	add_db_table("stats_virus");
	$where[] = "binaries.info = stats_virus.name";
	$where[] = "stats_virus.id = '$f_virus'";
} elseif (!empty($f_virus_txt)) {
	// From inputbox
	add_db_table("binaries");
	add_db_table("details"); // for binaries, don't remove!
	$where[] = "binaries.info LIKE '%$f_virus_txt%'";
}

### Limit filename
if (!empty($f_filename)) {
	add_db_table("details");
	$where[] = "details.type = 4";
	$where[] = "details.text LIKE '%$f_filename%'";
}

### Limit binary
if (!empty($f_bin)) {
	add_db_table("details");
	$where[] = "details.type = 8";
	$where[] = "details.text = '$f_bin'";
}

if ($rapport == "idmef") {
    prepare_sql();

    $select = "SELECT * ";
    ### Prepare final SQL query
    $sql =  $select;
    $sql .= " FROM $sql_from ";
    $sql .= " $sql_where ";
    $sql .= $group_by;
    
    $result = pg_query($sql);

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<!DOCTYPE IDMEF-Message PUBLIC \"-//IETF//DTD RFC XXXX IDMEF v1.0//EN\" \"idmef-message.dtd\">\n";
    echo "<idmef:IDMEF-Message version=\"1.0\" xmlns:idmef=\"http://iana.org/idmef\">\n";
    flush();
    while ($row = pg_fetch_assoc($result)) {
      flush();
      $id = intval($row['id']);
      $keyname = $row['keyname'];
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
          $attack = $attacks_ar[$text]["Attack"];
        }
        elseif ($sev == 16) {
          $dia_ar = array('attackid' => $id);
          $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
          $text = $dia_result_ar[0]['text'];
          $malware = basename($text);
        }
        elseif ($sev == 32) {
          $dia_ar = array('attackid' => $id, 'type' => 8);
          $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
          $bin = $dia_result_ar[0]['text'];

          $sql_getbin = "SELECT * FROM binaries WHERE bin = '$bin' AND scanner = 'ClamAV' ORDER BY timestamp DESC LIMIT 1";
          $result_getbin = pg_query($sql_getbin);
          $row_getbin = pg_fetch_assoc($result_getbin);
          $clamav = $row_getbin['info'];
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
      }
      elseif ($sev == 16 && $malware != "") {
      echo "  <idmef:AdditionalData type=\"string\" meaning=\"file-offered\">\n";
      echo "    <idmef:string>$malware</idmef:string>\n";
      echo "  </idmef:AdditionalData>\n";
      }
      elseif ($sev == 32 && $clamav != "") {
      echo "  <idmef:AdditionalData type=\"string\" meaning=\"ClamAV-scaninfo\">\n";
      echo "    <idmef:string>$clamav</idmef:string>\n";
      echo "  </idmef:AdditionalData>\n";
      }
      echo "</idmef:Alert>\n";
    }
    echo "</idmef:IDMEF-Message>\n";
    exit;
}

if ($rapport == "chart_sensor") {
	$type = abs(intval($_GET["f_chart_type"]));
	if ($type > 2) $type = 0;
	$chartorg = $s_org;
	
	$f_chart_of = $_GET["f_chart_of"];
	if ($f_chart_of == "attack") {
		$select = " SELECT DISTINCT details.text, COUNT(details.*) as total ";
		add_db_table("details");
		$where[] = "details.type = 1";
		$group_by = " GROUP BY details.text, details.type";
		$label = "Attacks";
	} elseif ($f_chart_of == "severity") {
		$select = "SELECT DISTINCT severity.txt, COUNT(attacks.*) as total";
		add_db_table("severity");
		$where[] = "attacks.severity = severity.val";
		$group_by = "GROUP BY severity.txt";
		$label = "Severity";
	} elseif ($f_chart_of == "virus") {
		$select = "SELECT DISTINCT stats_virus.name, count(binaries.info) as total";
		add_db_table("binaries");
		add_db_table("stats_virus");
		add_db_table("details");
		$where[] = "binaries.info = stats_virus.name";
		$where[] = "stats_virus.name NOT LIKE 'Suspicious'";
		$group_by = "GROUP BY stats_virus.name ORDER BY total DESC LIMIT 15 OFFSET 0";
		
	} else exit("Invalid data supplied");
	
	if ($sensorid == 0) $label .= " for ALL sensors";
	else {
		// lookup keyname:
		$query = pg_query("SELECT keyname FROM sensors WHERE id = '" . $sensorid . "'");
		$label .= " for " . pg_result($query, 0);
	}
	
	prepare_sql();
	
	### Prepare final SQL query
	$sql =  $select;
	$sql .= " FROM $sql_from ";
	$sql .= " $sql_where ";
	$sql .= $group_by;
        $_SESSION['chartsql'] = $sql;
	
        $title = "Searchresults: $label";
#        $result_chart = pg_query($pgconn, $sql);

//        require_once("../libchart/libchart.php");
//	$img = makeChart($type, $title, $sql, $chartorg);

        echo "<img alt='Chart' src='logsearchchart.php?type=$type&amp;org=$chartorg' />\n";
	footer();
	exit;
}
if ($rapport == "chart_attack") {
	$type = abs(intval($_GET["f_chart_type"]));
	if ($type > 2) $type = 0;
	$chartorg = $s_org;
	
	$select = " SELECT DISTINCT sensors.keyname, COUNT(details.*) AS total ";
	add_db_table("details");
	$where[] = "details.type = 1";
	$group_by = " GROUP BY sensors.keyname";
	
	if ($f_attack <= 0) $label .= "ALL attacks";
	else {
		// lookup attackname:
		$query = pg_query("SELECT name FROM stats_dialogue WHERE id = '" . $f_attack . "'");
		$name = pg_result($query, 0);
		$name = str_replace("Dialogue", "", $name);
		$label .= "Attack " . $name;
	}
	
	prepare_sql();
	
	### Prepare final SQL query
	$sql =  $select;
	$sql .= " FROM $sql_from ";
	$sql .= " $sql_where ";
	$sql .= $group_by;
        $_SESSION['chartsql'] = $sql;
	
        $title = "Searchresults: $label";
        echo "<img alt='Chart' src='logsearchchart.php?type=$type&amp;org=$chartorg' />\n";

//        require_once("../libchart/libchart.php");
//        $img = makeChart($type, $title, $sql, $chartorg);
//        echo "<img alt='Chart' src='$img' />\n";
	footer();
	exit;
}

prepare_sql();

### Prepare sql-ORDER BY
$order_by_tbl = array(	"id"		=> "attacks.id", 
						"timestamp"	=> "attacks.timestamp", 
						"severity"	=> "attacks.severity", 
						"source"	=> "attacks.source",
						"dest"		=> "attacks.dest",
						"keyname"	=> "sensors.id");
if (isset($_GET["order"])) {
	$sql_order_by = $order_by_tbl[$_GET["order"]];
}
if (empty($sql_order_by) || !isset($sql_order_by)) $sql_order_by = $order_by_tbl["id"];
// Order method (ascending or descending, default ASC)
if (isset($_GET["order_m"])) {
	if ($_GET["order_m"] == "DESC") $asc_desc = "DESC";
	else $asc_desc = "ASC";
} else $asc_desc = "ASC";
if ($asc_desc == "ASC") $order_m_url[$_GET["order"]] = "&order_m=DESC";

if (!isset($_SESSION["search_num_rows"]) || (intval($_SESSION["search_num_rows"]) == 0) || (intval($_GET["page"]) == 0)) {
	### Prepare count SQL query
	$sql_count =  " SELECT COUNT(attacks.id) AS total ";
	$sql_count .= " FROM $sql_from ";
	$sql_count .= " $sql_where ";

	// SQL-count query
	$query_count = pg_query($sql_count);
	// Don't use pg_num_rows, slow's down factor 2-4!
	$num_rows = pg_result($query_count, 0);
    ### Check for config option.
    if ($search_cache == 1) {
    	$_SESSION["search_num_rows"] = $num_rows;
    }
}
$num_rows = intval($_SESSION["search_num_rows"]);

if ($num_rows == 0) {
        # Debug info
        if ($debug == 1) {
          echo "<pre>";
          echo "SQL_COUNT: $sql_count<br />\n";
          echo "</pre>\n";
        }
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
if (isset($_GET["page"])) {
	$page_nr = intval($_GET["page"]);
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
$url = str_replace("&page=" . $_GET["page"], "", $url);
for ($i = ($page_nr - 3); $i <= ($page_nr + 3); $i++) {
	if (($i > 0) && ($i <= $last_page)) {
		if ($i == $page_nr) $nav .= "<b>&laquo;$i&raquo;</b>&nbsp;";
		else $nav .= "<a href=\"$url&page=$i\">$i</a>&nbsp;";
	}
}
$nav .= "<br />\n";
if ($page_nr == 1) $nav .= "&lt;&lt;&nbsp;First&nbsp;&nbsp;";
else $nav .= "<a href=\"$url&page=1\">&lt;&lt;&nbsp;First</a>&nbsp;&nbsp;";
if ($page_nr == 1) $nav .= "&lt;&nbsp;Prev&nbsp;&nbsp;";
else $nav .= "<a href=\"$url&page=" . ($page_nr - 1) . "\">&lt;&nbsp;Prev</a>&nbsp;&nbsp;";
$nav .= "<a href=\"search.php\">Search</a>";
if ($page_nr < $last_page) $nav .= "&nbsp;&nbsp;<a href=\"$url&page=" . ($page_nr + 1) . "\">Next&nbsp;&gt;</a>\n";
else $nav .= "&nbsp;&nbsp;Next&nbsp;&gt;\n";
if ($page_nr == $last_page) $nav .= "&nbsp;&nbsp;Last&nbsp;&gt;&gt;";
else $nav .= "&nbsp;&nbsp;<a href=\"$url&page=$last_page\">Last&nbsp;&gt;&gt;</a>";

// XML IDMEF logging button
$idmef_url = $_SERVER['REQUEST_URI'];
if (intval(strpos($idmef_url, "f_reptype")) == 0) $idmef_url .= "&f_reptype=idmef";
else $idmef_url = str_replace("f_reptype=" . $_GET["f_reptype"], "&f_reptype=idmef", $idmef_url);
echo "<div id=\"xml_idmef\" <a href=\"$idmef_url\" title=\"Download these results as IDMEF format XML file\"><img src=\"./images/xml.png\" border=\"0\" width=\"48\" height=\"52\"></a><br>IDMEF</div>\n";

// Personal search templates
echo "<div id=\"personal_searchtemplate\" <a href=\"#TODO\"><img src='/images/searchtemplate_add.png' alt='Add this search query to my personal search templates' title='Add this search query to my personal search templates' border='0'></a><br></div>\n";

flush();

/*
echo "Normal sql from: $sql_from<br>";
// replace sql_from with optimized 'order by' method
list($order_tbl, $order_field) = explode(".", $sql_order_by);
$optimized = "(SELECT * FROM $order_tbl ORDER BY $order_field $asc_desc) AS $order_tbl";
$sql_from = str_replace($order_tbl, $optimized, $sql_from);
echo "Optimized sql from: $sql_from<br>";
*/

//$sql_from = str_replace("attacks", "attacks_source_asc", $sql_from);

### Prepare final SQL query
$sql =  " SELECT DISTINCT attacks.id AS attacks_id, *";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
$sql .= " ORDER BY $sql_order_by $asc_desc ";
$sql .= " $sql_limit ";

# Debug info
if ($debug == 1) {
  echo "<pre>";
  echo "SQL: $sql";
  echo "</pre>";
}

$result = pg_query($sql);

if ($last_page > 1) $page_lbl = "pages";
else $page_lbl = "page";
echo "<p>Results <b>$first_result</b> - <b>$last_result</b> of <b>" . number_format($num_rows, 0, ".", ",") . "</b> in <b>" . number_format($last_page, 0, ".", ",") . "</b> $page_lbl.</p>\n";

if ($rapport == "multi") {
	echo "<div id=\"lognav\" align=\"center\">$nav</div>\n";
	echo "<br />\n";
}

$url = $_SERVER['REQUEST_URI'];
$ar_search = array("&order=" . $_GET["order"], "&order_m=" . $_GET["order_m"]);
$url = str_replace($ar_search, "", $url);
echo "<table class='datatable' width='100%'>\n";
  echo "<tr>\n";
    echo "<td class='dataheader' width='5%'><a href=\"$url&order=id" . $order_m_url["id"] . "\">ID</a></td>\n";
    echo "<td class='dataheader' width='15%'><a href=\"$url&order=timestamp" . $order_m_url["timestamp"] . "\">Timestamp</a></td>\n";
    echo "<td class='dataheader' width='20%'><a href=\"$url&order=severity" . $order_m_url["severity"] . "\">Severity</a></td>\n";
    echo "<td class='dataheader' width='20%'><a href=\"$url&order=source" . $order_m_url["source"] . "\">Source</a></td>\n";
    echo "<td class='dataheader' width='17%'><a href=\"$url&order=dest" . $order_m_url["dest"] . "\">Destination</a></td>\n";
    echo "<td class='dataheader' width='8%'><a href=\"$url&order=keyname" . $order_m_url["keyname"] . "\">Sensor</a></td>\n";
    echo "<td class='dataheader' width='15%'>Additional Info</td>\n";
  echo "</tr>\n";

while ($row = pg_fetch_assoc($result)) {
  flush();
  $id = pg_escape_string($row['attacks_id']);
  $ts = date("d-m-Y H:i:s", $row['timestamp']);
  $sev = $row['severity'];
  $severity = $severity_ar[$sev];
  $source = $row['source'];
  $sport = $row['sport'];
  $dest = $row['dest'];
  $dport = $row['dport'];
  $sensorid = $row['sensorid'];
  $sensorname = $row['keyname'];
  $smac = $row['src_mac'];

  $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
  $result_details = pg_query($pgconn, $sql_details);
  $numrows_details = pg_num_rows($result_details);

  if ($enable_pof == 1) {
    $sql_finger = "SELECT name FROM system WHERE ip_addr = '" .$source. "'";
    $result_finger = pg_query($pgconn, $sql_finger);
    $numrows_finger = pg_num_rows($result_finger);

    $fingerprint = pg_result($result_finger, 0);
    $finger_ar = explode(" ", $fingerprint);
    $os = $finger_ar[0];
  }
  else {
    $numrows_finger = 0;
  }

  echo "<tr>\n";
    if ($numrows_details != 0) {
      echo "<td class='datatd'><a href='logdetail.php?id=$id'>$id</a></td>\n";
    }
    else {
      echo "<td class='datatd'>$id</td>\n";
    }
    echo "<td class='datatd'>$ts</td>\n";
    echo "<td class='datatd'>$severity</td>\n";
    if ($numrows_finger != 0) {
      $img = "$surfidsdir/webinterface/images/$os.gif";
      if (file_exists($img)) {
        echo "<td class='datatd'><img src='images/$os.gif' alt='$fingerprint' title='$fingerprint' />&nbsp;<a href='whois.php?ip=$source'>$source:$sport</a></td>\n";
      }
      else {
        echo "<td class='datatd'><img src='images/Blank.gif' alt='$fingerprint' title='$fingerprint' />&nbsp;<a href='whois.php?ip=$source'>$source:$sport</a></td>\n";
      }
    }
    else {
      echo "<td class='datatd'><img src='images/Blank.gif' alt='No info' title='No info' />&nbsp;<a href='whois.php?ip=$source'>$source:$sport</a></td>\n";
    }
    if ($hide_dest_ip == 1 && $s_admin == 0) {
      $range_check = matchCIDR($dest, $ranges_ar);
      if ($range_check == 1) {
        echo "<td class='datatd'>$dest:$dport</td>\n";
      }
      else {
        echo "<td class='datatd'>&lt;hidden&gt;</td>\n";
      }
    }
    else {
      echo "<td class='datatd'>$dest:$dport</td>\n";
    }
    echo "<td class='datatd'>$sensorname</td>\n";
    if ($numrows_details != 0) {
      if ($sev == 1) {
        $dia_ar = array('attackid' => $id, 'type' => 1);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $text = $dia_result_ar[0]['text'];
        $attack = $attacks_ar[$text]["Attack"];
        $attack_url = $attacks_ar[$text]["URL"];
        if ($smac != "") {
          echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a><br />$smac</td>\n";
        } else {
          echo "<td class='datatd'><a href='$attack_url' target='new'>$attack</a></td>\n";
        }
      }
      elseif ($sev == 16) {
        $row_details = pg_fetch_assoc($result_details);
        $text = $row_details['text'];
        $file = basename($text);
        if ($smac != "") {
          echo "<td class='datatd'>$file<br />$smac</td>\n";
        } else {
          echo "<td class='datatd'>$file</td>\n";
        }
      }
      elseif ($sev == 32) {
        $dia_ar = array('attackid' => $id, 'type' => 8);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $bin = $dia_result_ar[0]['text'];

        $sql_bin = "SELECT info FROM binaries WHERE bin = '$bin' AND scanner = 'ClamAV' ORDER BY timestamp LIMIT 1";
        $result_bin = pg_query($pgconn, $sql_bin);
        $numrows_bin = pg_num_rows($result_bin);
        $row_bin = pg_fetch_assoc($result_bin);

        echo "<td class='datatd'>";
        if ($numrows_bin != 0) {
          $info = $row_bin['info'];
          echo "<a href='binaryhist.php?bin=$bin'>$info</a>";
        }
        else {
          echo "Suspicious";
        }
        if ($smac != "") {
          echo "<br />$smac";
        }
        echo "</td>\n";
      }
      else {
        if ($smac != "") {
          echo "<td class='datatd'>Source MAC: $smac</td>\n";
        } else {
          echo "<td class='datatd'></td>\n";
        }
      }
    }
    else {
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

if ($searchtime == 1) {
  $timeend = microtime_float();
  $gen = $timeend - $timestart;
  $mili_gen = number_format(($gen * 1000), 0);
  echo "<br />Page rendered in  $mili_gen ms.<br />";
}

?>
<script language="javascript" type="text/javascript">
document.getElementById('search_wait').style.display='none';
</script>

<?php footer(); ?>
