<?php
####################################
# SURFids 3.00                     #
# Changeset 004                    #
# 03-03-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 004 Added support for SNORT info
# 003 Added ARP exclusion stuff
# 002 IE bug "could not download" fixed 
# 001 Added language support
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

# Including language file
include "../lang/${c_language}.php";

# Setting headers
header("Pragma: public");
header("Cache-Control: max-age=0");
header("Content-type: application/xml");
$fn = "SURFnet_IDMEF_" . date($c_date_format) . "_" . ucfirst($_SESSION['s_user']) . ".xml";
header("Content-disposition: attachment; filename=$fn");

# Retrieving posted variables from $_GET
$allowed_get = array(
        "reptype",
		"int_org",
		"sensorid",
		"mac_sourcemac",
		"inet_source",
		"inet_sourceip",
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
reset_sql();

$q_org = $_SESSION['q_org'];

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
$to = $_SESSION['s_to'];
$from = $_SESSION['s_from'];

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
if ($f_sourcechoice == 3 && $ownsource == "") {
  add_to_sql(gen_org_sql(1), "where");
} else {
  add_to_sql(gen_org_sql(), "where");
}

add_to_sql("sensors.keyname", "select");
add_to_sql("sensors.vlanid", "select");
add_to_sql("sensors.label", "select");
add_to_sql("attacks.*", "select");
add_to_sql("sensors", "table");
add_to_sql("attacks", "table");
add_to_sql("sensors.id = attacks.sensorid", "where");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");
# MAC Exclusion stuff
add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

prepare_sql();

### Prepare final SQL query
$sql = "SELECT $sql_select ";
$sql .= " FROM $sql_from ";
$sql .= " $sql_where ";
$sql .= " $sql_group ";
    
$result = pg_query($sql);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
#echo "<!DOCTYPE IDMEF-Message PUBLIC \"-//IETF//DTD RFC XXXX IDMEF v1.0//EN\" \"idmef-message.dtd\">\n";
echo "<idmef:IDMEF-Message version=\"1.0\" xmlns:idmef=\"http://iana.org/idmef\">\n";
flush();
while ($row = pg_fetch_assoc($result)) {
  flush();
  $id = intval($row['id']);
  $keyname = $row['keyname'];
  $vlanid = $row['vlanid'];
  $label = $row['label'];
  $keyname = sensorname($keyname, $vlanid);
  if ($label != "") $keyname = $label;
  $timestamp = $row['timestamp'];
  $source = $row['source'];
  $srcmac = $row['src_mac'];
  $sport = intval($row['sport']);
  $dest = $row['dest'];
  $destmac = $row['dst_mac'];
  $dport = intval($row['dport']);
  $sev = intval($row['severity']);
  $sevtype = intval($row['atype']);
  $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
  $result_details = pg_query($pgconn, $sql_details);
  $numrows_details = pg_num_rows($result_details);
 
  $sev_text = "$v_severity_ar[$sev]-$v_severity_atype_ar[$sevtype]";

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
    } elseif ( ( $sev == 1 || $sev == 0 ) && $sevtype == 2) {
      $dia_ar = array('attackid' => $id, 'type' => 40);
      $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
      $module = $dia_result_ar[0]['text'];
    }
  }
  echo "<idmef:Alert messageid=\"$id\">\n";
  echo "  <idmef:Analyzer analyzerid=\"$keyname\">\n";
  echo "  </idmef:Analyzer>\n";
  echo "  <idmef:CreateTime>$timestamp</idmef:CreateTime>\n";
  echo "  <idmef:Classification ident=\"$sev\" text=\"$sev_text\"></idmef:Classification>\n";
  echo "  <idmef:Source>\n";
  echo "    <idmef:Node>\n";
  if ($sevtype == 10) {
  echo "      <idmef:Address category=\"mac\">\n";
  echo "        <idmef:address>$srcmac</idmef:address>\n";
  echo "      </idmef:Address>\n";
  } else {
  echo "      <idmef:Address category=\"ipv4-addr\">\n";
  echo "        <idmef:address>$source</idmef:address>\n";
  echo "      </idmef:Address>\n";
  }
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
    echo "  <idmef:AdditionalData type=\"string\" meaning=\"" .$l['ls_at']. "\">\n";
    echo "    <idmef:string>$attack</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";
  } elseif ($sev == 16 && $malware != "") {
    echo "  <idmef:AdditionalData type=\"string\" meaning=\"" .$l['ls_fo']. "\">\n";
    echo "    <idmef:string>$malware</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";
  } elseif ( ( $sev == 1 || $sev == 0 ) && $sevtype == 2) {
    echo "  <idmef:AdditionalData type=\"string\" meaning=\"snort-rule\">\n";
    echo "    <idmef:string>$module</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";
  }
  echo "</idmef:Alert>\n";
}
echo "</idmef:IDMEF-Message>\n";
pg_close($pgconn);
exit;
?>
