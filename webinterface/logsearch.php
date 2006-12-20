<?php
####################################
# SURFnet IDS                      #
# Version 1.04.08                  #
# 15-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
# Modified by Peter Arts           #
####################################

#########################################################################
# Changelog:
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
$valid_reptype = array("multi", "single", "chart_sensor", "chart_attack", "idmef", "pdf");
if (in_array($_GET['reptype'], $valid_reptype)) $rapport = pg_escape_string($_GET['reptype']);
else $rapport = "multi";

$ar_non_headers = array("idmef", "pdf");
if (in_array($rapport, $ar_non_headers)) {
	session_start();
	if (intval(@strlen($_SESSION["s_user"])) == 0) {
		// User not logged in
		header("Location: /login.php");
		exit;
	}
        include '../include/config.inc.php';
        include '../include/connect.inc.php';
        include '../include/functions.inc.php';

    if ($rapport == "idmef") {
		header("Content-type: text/xml");
	  
	  	header("Cache-control: private");
	  	$fn = "SURFnet_IDMEF_" . date("d-m-Y_H:i:s") . "_" . ucfirst($_SESSION['s_user']) . ".xml";
	  	header("Content-disposition: attachment; filename=$fn");
    }
} else {
  include("menu.php");
  set_title("Search");
}

$allowed_get = array(
                "reptype",
		"net_searchnet",
		"ip_searchip",
		"int_org",
		"sensorid",
		"sourceip",
		"sradio",
		"int_sport",
		"int_smask",
		"destip",
		"dradio",
		"int_dport",
		"int_dmask",
		"tsselect",
		"strip_html_escape_tsstart",
		"strip_html_escape_tsend",
		"int_sev",
		"strip_html_escape_bin",
		"int_attack",
		"int_virus",
		"strip_html_escape_virustxt",
		"strip_html_escape_filename",
		"int_from",
		"int_to",
		"int_charttype",
		"chartof",
		"order",
		"orderm",
		"int_page",
		"int_c"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (($rapport != "chart_sensor") && ($rapport != "chart_attack") && (!in_array($rapport, $ar_non_headers))) {
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
$search = $clean['search'];
$where = array();

### Set organisation
if ($s_access_search == 9) {
	if (isset($clean['org'])) $q_org = $clean['org'];
}

### Checking for admin.
if ($s_access_search < 9) $where[] = "sensors.organisation = '" . intval($s_org) . "'";
elseif ($q_org > 0) $where[] = "sensors.organisation = '" . intval($q_org) . "'";

### Setting values from searchform
if (@is_array($tainted["sensorid"])) {
	$sensorid = -1;
	$ar_sensorid = array();
	foreach ($tainted["sensorid"] as $sid) {
		$ar_sensorid[] = intval($sid);
	}
} else $sensorid = intval($tainted['sensorid']);

####################
# Source IP address
####################
$source_ip = $tainted['sourceip'];
$full_source_ip = "";
if (!empty($source_ip)) {
  foreach ($source_ip as $key => $val) {
	$val = intval(trim($val));
	if ($key > 0) $full_source_ip .= ".";
	$full_source_ip .= $val;
  }
} else {
  $full_source_ip = "0.0.0.0";
}
if ($full_source_ip == "0.0.0.0") $full_source_ip = -1;
elseif (ip2long($full_source_ip) === -1) $full_source_ip = -2;

$sradio_pattern = '/^(A|N)$/';
if (preg_match($sradio_pattern, $tainted['sradio'])) {
  if ($tainted['sradio'] == "A") {
	$source_port = $clean['sport'];
	$source_mask = -1;
  } else {
	$source_port = -1;
	$source_mask = $clean['smask'];
  }
}

####################
# Destination IP address
####################
$destination_ip = $tainted['destip'];
$full_destination_ip = "";
if (!empty($destination_ip)) {
  foreach ($destination_ip as $key=>$val) {
	$val = intval(trim($val));
	if ($key > 0) $full_destination_ip .= ".";
	$full_destination_ip .= $val;
  }
} else {
  $full_destination_ip = "0.0.0.0";
}
if ($full_destination_ip == "0.0.0.0") $full_destination_ip = -1;
elseif (ip2long($full_destination_ip) === -1) $full_destination_ip = -2;

$dradio_pattern = '/^(A|N)$/';
if (preg_match($dradio_pattern, $tainted['dradio'])) {
  if ($tainted["dradio"] == "A") {
	$destination_port = $clean['dport'];
	$destination_mask = -1;
  } else {
	$destination_port = -1;
	$destination_mask = $clean['dmask'];
  }
}

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
	$ts_start = trim(pg_escape_string(strip_tags($tainted["tsstart"])));
	$ts_end = trim(pg_escape_string(strip_tags($tainted["tsend"])));
}

####################
# Severity
####################
if (isset($clean['sev'])) {
	$f_sev = $clean['sev'];
        $sev_pattern = '/^(0|1|16|32)$/';
	if (!preg_match($sev_pattern, $f_sev)) unset($f_sev);
}

####################
# Binary name
####################
$bin_pattern = '/^[a-zA-Z0-9%]{1,33}$/';
if (preg_match($bin_pattern, $tainted['bin'])) {
  $f_bin = $tainted['bin'];
} else {
  $f_bin = "";
}

####################
# Attack type
####################
$f_attack = $clean['attack'];

####################
# Virus type
####################
$f_virus = $clean['virus'];
$f_virus_txt = $clean['virustxt'];

####################
# Filename
####################
$f_filename = $clean['filename'];

####################
# Report type
####################
$f_reptype = $rapport;

$db_table = array("sensors", "attacks");
$where[] = "attacks.sensorid = sensors.id";

####################
# Sensor ID's
####################
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

####################
# Source IP address
####################
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
} elseif (isset($clean['searchnet'])) {
	// Input from other page
	$input = $clean['searchnet'];
	add_db_table("attacks");
	$where[] = "attacks.source <<= '$input'";
} elseif (isset($clean['searchip'])) {
	// Input from other page
	$input = $clean['searchip'];
	add_db_table("attacks");
	$where[] = "attacks.source = '$input'";
}

####################
# Destination IP address
####################
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

####################
# Start timestamp
####################
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
} elseif (isset($clean['from'])) {
	add_db_table("attacks");
	$ts_start = $clean['from'];
	$where[] = "attacks.timestamp >= '$ts_start'";
}

####################
# End timestamp
####################
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
} elseif (isset($clean['to'])) {
	add_db_table("attacks");
	$ts_end = $clean['to'];
	$where[] = "attacks.timestamp <= '$ts_end'";
}

####################
# Severity
####################
if (isset($f_sev)) {
	add_db_table("attacks");
	$where[] = "attacks.severity = '$f_sev'";
}

####################
# Type of attack
####################
if ($f_attack > 0) {
	add_db_table("details");
	add_db_table("stats_dialogue");
	$where[] = "details.type = 1";
	$where[] = "details.text = stats_dialogue.name";
	$where[] = "stats_dialogue.id = '$f_attack'";
}

####################
# Type of virus
####################
if ($f_virus > 0) {
	// From selectbox
	add_db_table("binaries");
	add_db_table("details"); // for binaries, don't remove!
	add_db_table("stats_virus");
        $where[] = "binaries.info = $f_virus";
} elseif (!empty($f_virus_txt)) {
	// From inputbox
	add_db_table("binaries");
	add_db_table("details"); // for binaries, don't remove!
        add_db_table("stats_virus");
        $where[] = "binaries.info = stats_virus.id";
	$where[] = "stats_virus.name LIKE '%$f_virus_txt%'";
}

####################
# Filename
####################
if (!empty($f_filename)) {
	add_db_table("details");
	$where[] = "details.type = 4";
	$where[] = "details.text LIKE '%$f_filename%'";
}

####################
# Filename
####################
if (!empty($f_bin)) {
	add_db_table("details");
        add_db_table("uniq_binaries");
	$where[] = "details.type = 8";
        $where[] = "details.text = uniq_binaries.name";
	$where[] = "uniq_binaries.name LIKE '$f_bin'";
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

if ($rapport == "pdf") {
    prepare_sql();

    $select = "SELECT * ";
    ### Prepare final SQL query
    $sql =  $select;
    $sql .= " FROM $sql_from ";
    $sql .= " $sql_where ";
    $sql .= $group_by;
    
    $result = pg_query($sql);

    flush();
    include ('../include/class.ezpdf.php');

    $pdf =& new Cezpdf();
    $pdf->addJpegFromFile("images/logo.jpg", 20, 750, 200, 70);
    $pdf->selectFont('../include/fonts/Helvetica.afm');
    //$pdf->ezText(' ',20);
    //$pdf->ezText(' ',20);
    //$pdf->ezText(' ',20);
    $space = '                                         ';
    $pdf->ezText($space . 'SURFnet IDS PDF results',20);
    $space = '                                                                                  ';
    $pdf->ezText($space . 'Generated at ' . date("d-m-Y H:i:s") . ' by SURFnetIDS webinterface', 10);
    $pdf->ezText('    ', 20);
    $pdf->ezText('    ', 20);
    $data = array();
    while ($row = pg_fetch_assoc($result)) {
      flush();
      $id = intval($row['id']);
      $keyname = $row['keyname'];
      $timestamp = $row['timestamp'];
      $source = $row['source'];
      $sport = intval($row['sport']);
      $dest = $row['dest'];
      $dport = intval($row['dport']);
      $sensorid = intval($row['sensorid']);
      if ($sensorid > 0) {
      	$query = pg_query("SELECT keyname FROM sensors WHERE id = '" . $sensorid . "'");
      	$sensorname = pg_result($query, 0);
      }
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
      //ID 	Timestamp 	Severity 	Source 	Destination 	Sensor 	Additional Info
      $ar = array();
      $ar["ID"] = $id;
      $ar["Timestamp"] = date("d-m-Y H:i:s", $timestamp);
      $ar["Severity"] = $sev_text;
      $ar["Source"] = $source . ":" . $sport;
      $ar["Destination"] = $dest . ":" . $dport;
      $ar["Sensor"] = $sensorname;
      if ($sev == 1 && $attack != "") $ar["Additional_Info"] = $attack;
      elseif ($sev == 16 && $malware != "") $ar["Additional_Info"] = $malware;
      elseif ($sev == 32 && $clamav != "") $ar["Additional_Info"] = $clamav;
      else $ar["Additional_Info"] = "";
      $data[] = $ar;
    }
    $pdf->ezTable($data, '', '', array( 'fontSize' => 8));
    $pdf->ezText('__________________________________________________________', 15);
    $pdf->ezText($space . 'http://ids.surfnet.nl', 10);
    $fn = "SURFnet_PDF_" . date("d-m-Y_H:i:s") . "_" . ucfirst($_SESSION['s_user']) . ".pdf";
    $ar = array('Content-Disposition'=>$fn);
    $pdf->ezStream($ar);
    exit;
}

if ($rapport == "chart_sensor") {
	$type = $clean['charttype'];
	if ($type > 2) $type = 0;
	$chartorg = $s_org;
	
        $chartof_pattern = '/^(attack|severity|virus)$/';
	$f_chart_of = $tainted['chartof'];
        if (!preg_match($chartof_pattern, $f_chart_of)) {
          exit("Invalid data supplied");
        } else {
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
        }
	
	if ($tainted['sensorid'][0] == 0) $label .= " for ALL sensors";
	else {
		// lookup keyname:
		$query = pg_query("SELECT keyname FROM sensors WHERE id = '" . $tainted['sensorid'][0] . "'");
		$label .= " for " . ucfirst(pg_result($query, 0));
	}
	
	prepare_sql();
	
	### Prepare final SQL query
	$sql =  $select;
	$sql .= " FROM $sql_from ";
	$sql .= " $sql_where ";
	$sql .= $group_by;
        $_SESSION['chartsql'] = $sql;
	
        $title = "Searchresults: $label";
        echo "<h4>$title</h4>\n";
        echo "<img alt='Chart' src='logsearchchart.php?type=$type&amp;int_org=$chartorg' />\n";
        // Personal search templates
	echo "<div id=\"personal_searchtemplate\"><a href=\"#\" onclick=\"submitSearchTemplateFromResults('" . $_SERVER['QUERY_STRING'] . "');\"><img src='/images/searchtemplate_add.png' alt='Add this search query to my personal search templates' title='Add this search query to my personal search templates' border='0'></a><br></div>\n";
	footer();
	exit;
}
if ($rapport == "chart_attack") {
	$type = $clean['charttype'];
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
        echo "<img alt='Chart' src='logsearchchart.php?type=$type&amp;int_org=$chartorg' />\n";
        // Personal search templates
		echo "<div id=\"personal_searchtemplate\" <a href=\"#\" onclick=\"submitSearchTemplateFromResults('" . $_SERVER['QUERY_STRING'] . "');\"><img src='/images/searchtemplate_add.png' alt='Add this search query to my personal search templates' title='Add this search query to my personal search templates' border='0'></a><br></div>\n";

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
if (isset($tainted['order'])) {
	$sql_order_by = $order_by_tbl[$tainted['order']];
}
if (empty($sql_order_by) || !isset($sql_order_by)) $sql_order_by = $order_by_tbl["id"];
// Order method (ascending or descending, default ASC)
if (isset($tainted['orderm'])) {
	if ($tainted['orderm'] == "DESC") $asc_desc = "DESC";
	else $asc_desc = "ASC";
} else $asc_desc = "ASC";
if ($asc_desc == "ASC") $order_m_url[$tainted['order']] = "&orderm=DESC";

if (!isset($_SESSION["search_num_rows"]) || (intval($_SESSION["search_num_rows"]) == 0) || ($clean['page'] == 0)) {
	### Prepare count SQL query
	$sql_count =  " SELECT COUNT(attacks.id) AS total ";
	$sql_count .= " FROM $sql_from ";
	$sql_count .= " $sql_where ";
        $debuginfo[] = $sql_count;

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

// XML IDMEF logging button
$idmef_url = $_SERVER['REQUEST_URI'];
if (intval(strpos($idmef_url, "reptype")) == 0) $idmef_url .= "&reptype=idmef";
else $idmef_url = str_replace("reptype=" . $tainted["reptype"], "&reptype=idmef", $idmef_url);
echo "<div id=\"xml_idmef\" <a href=\"$idmef_url\" title=\"Download these results as IDMEF format XML file\"><img src=\"./images/xml.png\" border=\"0\" width=\"48\" height=\"52\"></a><br>IDMEF</div>\n";

// PDF button
$pdf_url = $_SERVER['REQUEST_URI'];
if (intval(strpos($pdf_url, "reptype")) == 0) $pdf_url.= "&reptype=pdf";
else $pdf_url = str_replace("reptype=" . $tainted["reptype"], "&reptype=pdf", $pdf_url);
echo "<div id=\"pdf_btn\" <a href=\"$pdf_url\" title=\"Download these results as PDF file\"><img src=\"./images/pdf.gif\" border=\"0\" width=\"48\" height=\"52\"></a><br>&nbsp;&nbsp; PDF</div>\n";

// Personal search templates
echo "<div id=\"personal_searchtemplate\" <a href=\"#\" onclick=\"submitSearchTemplateFromResults('" . $_SERVER['QUERY_STRING'] . "');\"><img src='/images/searchtemplate_add.png' alt='Add this search query to my personal search templates' title='Add this search query to my personal search templates' border='0'></a><br>Search-<br>template</div>\n";

flush();


### Prepare final SQL query
$sql =  " SELECT DISTINCT attacks.id AS attacks_id, *";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
$sql .= " ORDER BY $sql_order_by $asc_desc ";
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
  $vlanid = $row['vlanid'];
  $sensorname = $row['keyname'];
  if ($vlanid != 0) $sensorname = "$sensorname-$vlanid";
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
    echo "<td class='datatd'>$severity</td>\n";
    if ($numrows_finger != 0) {
      $img = "$surfidsdir/webinterface/images/$os.gif";
      if (file_exists($img)) {
        echo "<td class='datatd'><img src='images/$os.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;<a href='whois.php?ip_ip=$source'>$source:$sport</a></td>\n";
      } else {
        echo "<td class='datatd'><img src='images/Blank.gif' onmouseover='return overlib(\"$fingerprint\");' onmouseout='return nd();' />&nbsp;<a href='whois.php?ip_ip=$source'>$source:$sport</a></td>\n";
      }
    } else {
      echo "<td class='datatd'><img src='images/Blank.gif' alt='No info' title='No info' />&nbsp;<a href='whois.php?ip_ip=$source'>$source:$sport</a></td>\n";
    }
    if ($hide_dest_ip == 1 && $s_admin == 0) {
      $range_check = matchCIDR($dest, $ranges_ar);
      if ($range_check == 1) {
        echo "<td class='datatd'>$dest:$dport</td>\n";
      } else {
        echo "<td class='datatd'>&lt;hidden&gt;</td>\n";
      }
    } else {
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

        $sql_bin = "SELECT binaries.bin, binaries.info FROM binaries, uniq_binaries, scanners WHERE uniq_binaries.name = '$bin' ";
        $sql_bin .= " AND binaries.bin = uniq_binaries.id AND binaries.scanner = scanners.id AND scanners.name = 'ClamAV' ";
        $sql_bin .= " ORDER BY timestamp LIMIT 1";
        $result_bin = pg_query($pgconn, $sql_bin);
        $numrows_bin = pg_num_rows($result_bin);
        $row_bin = pg_fetch_assoc($result_bin);

        echo "<td class='datatd'>";
        if ($numrows_bin != 0) {
          $info = $row_bin['bin'];
          echo "<a href='binaryhist.php?int_binid=$bin'>$info</a>";
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
