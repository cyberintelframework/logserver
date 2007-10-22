<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.02 Fixed bugs: display criterea, source address  
# 2.00.01 Initial release (split from logsearch.php)
#############################################

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

# Setting headers
#header("Content-type: text/pdf");
#header("Cache-control: private");
#$fn = "SURFnet_IDMEF_" . date("d-m-Y_H:i:s") . "_" . ucfirst($_SESSION['s_user']) . ".xml";
#header("Content-disposition: attachment; filename=$fn");

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
		"order",
		"orderm",
		"int_page",
		"int_binid",
                "int_sourcechoice",
                "int_destchoice"
);
$check = extractvars($_GET, $allowed_get);

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$search = $clean['search'];

$q_org = $_SESSION['q_org'];

reset_sql();

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
} else {
  $sensorid = intval($tainted['sensorid']);
}
$to = $_SESSION['s_to'];
$from = $_SESSION['s_from'];
$txt_to = date('d-m-Y, H:i', $to);
$txt_from = date('d-m-Y, H:i', $from);

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
  add_to_sql("details.text LIKE '%$f_filename%'", "where");
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

include ('../include/class.ezpdf.php');

$smaspace = '                  ';
$space    = '                                         ';
$bigspace = '                                                                                  ';

$pdf =& new Cezpdf();
$pdf->addPngFromFile("images/logo.png", 15, 755, 126, 80);
$pdf->selectFont('../include/fonts/Helvetica.afm');
$pdf->ezText($space . 'SURFnet IDS PDF results',20);
$pdf->ezText($bigspace . 'Generated at ' . date("d-m-Y H:i:s") . ' by SURFnetIDS webinterface', 10);
$pdf->ezText('    ', 30);

#####################
# SEARCH CRITERIA
#####################

$pdf->ezText("Period: $txt_from - $txt_to ", 10);
$pdf->ezText("Destination: ", 10);

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
                          $name = $label;
                        } else {  
                          $name = sensorname($keyname, $vlanid);
                        }
                       $out .= "$name, ";  
                      }
       }
  	$dst_txt = "Sensor(s): $out";
  }
}


if (isset($destination_ip)) {
  $dst_txt = "IP address: $destination_ip";
} elseif (isset($dest_mac)) {
  $dst_txt = "MAC address: $dest_mac";
}
if (isset($dport)) {
  $dst_txt .= ":$dport";
}
if ($dst_txt != "") {
  $pdf->ezText("$smaspace$dst_txt", 10);
}

#### SOURCE
$pdf->ezText("Source:", 10);
if ($f_sourcechoice == 3 && !isset($source_ip)) {
  $pdf->ezText("Own Ranges", 10);
}
if (isset($source_ip)) {
  $src_txt = "IP address: $source_ip";
} elseif (isset($source_mac)) {
  $src_txt = "MAC address: $source_mac";
}
if (isset($sport)) {
  $src_txt .= ":$sport";
}
if ($src_txt != "") {
  $pdf->ezText("$smaspace$src_txt", 10);
}

$sql_exclusion = "SELECT exclusion FROM org_excl WHERE orgid = $q_org";
$result_exclusion = pg_query($pgconn, $sql_exclusion);
$query = pg_query($sql_exclusion);
$debuginfo[] = $sql_exclusion;
$nr_exclusionrows = intval(@pg_result($query, 0));

if ($nr_exclusionrows > 1) {
  $pdf->ezText("$smaspace(IP Exclusion ON)", 10);
} else {
  $pdf->ezText("$smaspace(IP Exclusion OFF)", 10);
}

$pdf->ezText("Characteristics:", 10);

if (isset($f_sev)) {
  $pdf->ezText("${smaspace}Severity: $v_severity_ar[$f_sev]", 10);
}
if (isset($f_sevtype)) {
  $pdf->ezText("${smaspace}Severity Type: $v_severity_atype_ar[$f_sevtype]", 10);
}
if (isset($f_attack)) {
  $sql_g = "SELECT name FROM stats_dialogue WHERE id = '$f_attack'";
  $result_g = pg_query($pgconn, $sql_g);
  $row_g = pg_fetch_assoc($result_g);
  $expl = $row_g['name'];
  $expl = str_replace("Dialogue", "", $expl);
  if ($expl != "") {
    $pdf->ezText("${smaspace}Exploit: $expl", 10);
  }
}
if (isset($f_binname)) {
  $pdf->ezText("${smaspace}Binary Name: $f_binname", 10);
}
if (isset($f_virus_txt)) {
  $pdf->ezText("${smaspace}Virus: $f_virus_txt", 10);
}
if (isset($f_filename)) {
  $pdf->ezText("${smaspace}Filename: $f_filename", 10);
}

#####################
# PDF generation
#####################

$pdf->ezText('    ', 20);
$data = array();

while ($row = pg_fetch_assoc($result)) {
  $id = $row['id'];
  $keyname = $row['keyname'];
  $timestamp = $row['timestamp'];
  $source = $row['source'];
  $srcmac = $row['src_mac'];
  $sport = intval($row['sport']);
  $dest = $row['dest'];
  $destmac = $row['dst_mac'];
  $dport = intval($row['dport']);
  $sensorid = intval($row['sensorid']);
  if ($sensorid > 0) {
    $query = pg_query("SELECT keyname, vlanid, label FROM sensors WHERE id = '" . $sensorid . "'");
    $sensorname = pg_result($query, 0);
    $vlanid = pg_result($query, 1);
    $label = pg_result($query, 2);
    $sensor = sensorname($sensorname, $vlanid);
    if ($label != "") $sensor = $label;
  }
  $sev = intval($row['severity']);
  $sevtype = intval($row['atype']);
  $sev_text = "$v_severity_ar[$sev]-$v_severity_atype_ar[$sevtype]";

  $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
  $result_details = pg_query($pgconn, $sql_details);
  $numrows_details = pg_num_rows($result_details);
   

   
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
  //Timestamp 	Severity 	Source Port 	Destination Port 	Sensor 	Additional Info
  $ar = array();
#  $ar["ID"] = $id;
  $ar["Timestamp"] = date("d-m-Y H:i:s", $timestamp);
  $ar["Severity"] = $sev_text;
  if ($sevtype == 10) $ar["Source"] = $srcmac; 
  else $ar["Source"] = $source;
  if ($sport == 0) $ar["Src port"] = "";
  else $ar["Src port"] = $sport;
  $ar["Destination"] = $dest;
  if ($dport == 0) $ar["Dst port"] = "";
  else $ar["Dst port"] = $dport;
  $ar["Sensor"] = $sensor;
  if ($sev == 1 && $attack != "") {
    $ar["Additional_Info"] = $attack;
  } elseif ($sev == 16 && $malware != "") {
    $ar["Additional_Info"] = $malware;
  } else {
    $ar["Additional_Info"] = "";
  }
  $data[] = $ar;
}

$pdf->ezTable($data, '', '', array( 'fontSize' => 8));
$pdf->ezText('__________________________________________________________', 15);
$pdf->ezText($space . 'http://ids.surfnet.nl', 10);
$fn = "SURFnet_PDF_" . date("d-m-Y_H:i:s") . "_" . ucfirst($_SESSION['s_user']) . ".pdf";
$ar = array('Content-Disposition'=>$fn);
$pdf->ezStream($ar);
pg_close($pgconn);
exit;
?>
