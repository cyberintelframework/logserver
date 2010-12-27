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

$s_userid = $_SESSION['s_userid'];

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
        "int_attackid",
        "int_sshversionid"
);
$check = extractvars($_GET, $allowed_get);

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$search = $clean['search'];
reset_sql();

$q_org = $_SESSION['q_org'];
$to = $_SESSION['s_to'];
$from = $_SESSION['s_from'];

####################
# INCLUDE SQL BUILDER
####################
include_once 'search_sqlbuilder.php';

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
  $dest = censorip($dest, $d_censor);
  $destmac = $row['dst_mac'];
  $dport = intval($row['dport']);
  $sev = intval($row['severity']);
  $sevtype = intval($row['atype']);

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

  #########################
  # DETAILS
  #########################

  if ($sevtype == 7) {
    # SSH version
    $sql_version = "SELECT uniq_sshversion.version FROM ssh_version, uniq_sshversion WHERE attackid = $id";
    $result_version = pg_query($pgconn, $sql_version);
    $row_v = pg_fetch_assoc($result_version);
    $sshversion = $row_v['version'];

    echo "  <idmef:AdditionalData type=\"string\" meaning=\"ssh-version\">\n";
    echo "    <idmef:string>$sshversion</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";

    # SSH login
    $sql_login = "SELECT sshuser, sshpass, type FROM ssh_logins WHERE attackid = $id";
    $result_login = pg_query($pgconn, $sql_login);
    $row_l = pg_fetch_assoc($result_login);
    $sshuser = $row_l['sshuser'];
    $sshpass = $row_l['sshpass'];
    $logintype = $row_l['type'];

    echo "  <idmef:AdditionalData type=\"string\" meaning=\"ssh-user\">\n";
    echo "    <idmef:string>$sshuser</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";
    echo "  <idmef:AdditionalData type=\"string\" meaning=\"ssh-pass\">\n";
    echo "    <idmef:string>$sshpass</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";
    echo "  <idmef:AdditionalData type=\"string\" meaning=\"login-type\">\n";
    echo "    <idmef:string>$logintype</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";

    # SSH commands
    $sql_command = "SELECT command FROM ssh_command WHERE attackid = $id";
    $result_command = pg_query($pgconn, $sql_command);
    while ($row_c = pg_fetch_assoc($result_command)) {
      $sshcommand = $row_c['command'];

      echo "  <idmef:AdditionalData type=\"string\" meaning=\"ssh-command\">\n";
      echo "    <idmef:string>$sshcommand</idmef:string>\n";
      echo "  </idmef:AdditionalData>\n";
    }
  } else {
    $sql_details = "SELECT id, text, type FROM details WHERE attackid = " . $id;
    $result_details = pg_query($pgconn, $sql_details);
    $numrows_details = pg_num_rows($result_details);
 
    $sev_text = "$v_severity_ar[$sev]-$v_severity_atype_ar[$sevtype]";

    if ($numrows_details != 0) {
      if ($sev == 1) {
        $dia_ar = array('attackid' => $id, 'type' => 1);
        $dia_result_ar = pg_select($pgconn, 'details', $dia_ar);
        $text = $dia_result_ar[0]['text'];
        $attack = str_replace("Dialogue", "", $text);
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
  }

  if ($sev == 1 && $attack != "") {
    echo "  <idmef:AdditionalData type=\"string\" meaning=\"attack-type\">\n";
    echo "    <idmef:string>$attack</idmef:string>\n";
    echo "  </idmef:AdditionalData>\n";
  } elseif ($sev == 16 && $malware != "") {
    echo "  <idmef:AdditionalData type=\"string\" meaning=\"file-offered\">\n";
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
