<?php

####################################
# SURFnet IDS                      #
# Version 2.00.02                  #
# 08-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.02 Fixed a bug with empty data points
# 2.00.01 version 2.00
# 1.04.11 Fixed attack-dialogue graph
# 1.04.10 Added IP exclusions stuff
# 1.04.09 Shows empty graph when no data
# 1.04.08 Added data colors array, background color
# 1.04.07 Added virus graphs
# 1.04.06 Fixed bug when not giving any port exclusions
# 1.04.05 Added extra dport and timestamp functionality 
# 1.04.04 Fixed bugs with organisation  
# 1.04.03 Location of phplot.php is a config value now
# 1.04.02 Fixed typo
# 1.04.01 Initial release
#############################################

#header("Content-type: image/png");
#header("Cache-control: no-cache");
#header("Pragma: no-cache");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$q_org = $_SESSION['q_org'];
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$drawerr = 0;
$limit = "";

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_interval",
		"int_type",
		"sensorid",
		"severity",
		"attack",
		"os",
		"strip_html_escape_ports",
		"int_width",
		"int_heigth",
		"int_org",
		"int_scanner"
);
$check = extractvars($_GET, $allowed_get);
#$c_debug_input = 1;
#debug_input();

# Checking if a timespan was correctly given
#if ((!isset($clean['tsstart']) || empty($clean['tsstart'])) && !isset($clean['tsselect'])) {
#  $drawerr = 1;
#} elseif ((!isset($clean['tsend']) || empty($clean['tsend'])) && !isset($clean['tsselect'])) {
#  $drawerr = 1;
#}

########################
#  Organisation 
########################
if ($q_org != 0) {
  $query_org = " sensors.organisation = $q_org ";
}

########################
# Interval
########################
if (!isset($clean['interval']) || empty($clean['interval'])) {
  $interval = 86400;
} else {
  $interval = $clean['interval'];
}

########################
# width & heigth 
########################
if (!isset($clean['width']) || empty($clean['width'])) {
  $width = "955";
} else {
  $width = $clean['width'];
}
if (!isset($clean['heigth']) || empty($clean['heigth'])) {
  $heigth = "575";
} else {
  $heigth = $clean['heigth'];
}

########################
# Type
########################
if (!isset($clean['type']) || empty($clean['type'])) {
  $type = $v_plottertypes[1];
} else {
  $type = $clean['type'];
  $type = $v_plottertypes[$type];
}

add_to_sql("sensors", "table");
add_to_sql("DISTINCT attacks.severity", "select");
add_to_sql("attacks", "table");
add_to_sql("attacks.sensorid = sensors.id", "where");
add_to_sql("$query_org", "where");

########################
# Severity
########################
if ($tainted['severity']) {
  if (@is_array($tainted['severity'])) {
    if (intval($tainted['severity'][0]) == 99) {
      $title .= "All attacks";
    } else {
      $title .= $v_severity_ar[$sev];
      $tempwhere .= "attacks.severity IN (";
      $check = 0;
      foreach ($tainted['severity'] as $sev) {
        $check++;
        $sev = intval($sev);
        if ($check != count($tainted['severity'])) {
          $tempwhere .= $sev .", ";
          $title .= $v_severity_ar[$sev] . ", ";
        } else {
          $tempwhere .= $sev;
          $title .= $v_severity_ar[$sev];
        }
      }
      $tempwhere .= ") ";
      add_to_sql("$tempwhere", "where");
    }
  }
} else {
  $title .= "All attacks";
}
add_to_sql("attacks.severity", "group");
$tempwhere = "";

########################
# Attack Types
########################
if ($tainted['attack']) {
  if (@is_array($tainted['attack'])) {
    if (intval($tainted['attack'][0]) != 99) {
      add_to_sql("details", "table");
      add_to_sql("attacks", "table");
      add_to_sql("attacks.id = details.attackid", "where");

      $check = 0;
      add_to_sql("details.text", "group");
      add_to_sql("details.text", "select");
      $title .= " (";
      $tempwhere .= "details.text IN (";
      foreach ($tainted['attack'] as $attack) {
        $check++;
        $attack = intval($attack);

        $sql_getattack = "SELECT name FROM stats_dialogue WHERE id = $attack";
        $debuginfo[] = $sql_getattack;
        $result_getattack = pg_query($sql_getattack);
        $row_attack = pg_fetch_assoc($result_getattack);
        $dianame = $row_attack['name'];
        $attackname = str_replace("Dialogue", "", $dianame);

        if ($check != count($tainted['attack'])) {
          $tempwhere .= "'" .$dianame. "', ";
          $title .= $attackname .", ";
        } else {
          $tempwhere .= "'" .$dianame. "'";
          $title .= $attackname;
        }
      }
      $tempwhere .= ") ";
      add_to_sql($tempwhere, "where");
      add_to_sql("details.text", "group");
      $title .= ") ";
      add_to_sql("details.text", "select");
      add_to_sql("details.type = 1", "where");
    } else {
      add_to_sql("details", "table");
      add_to_sql("attacks", "table");
      add_to_sql("attacks.id = details.attackid", "where");
      add_to_sql("details.type = 1", "where");

      add_to_sql("details.text", "group");
      add_to_sql("details.text", "select");
    }
  }
}
$tempwhere = "";

########################
# OS Types
########################
if ($tainted['os']) {
  if (@is_array($tainted['os'])) {
    if ($tainted['os'][0] == "all") {
      add_to_sql("system", "table");
      add_to_sql("attacks.source = system.ip_addr", "where");
      add_to_sql("os", "group");
      add_to_sql("split_part(system.name, ' ', 1) as os", "select");
    } else {
      add_to_sql("system", "table");
      add_to_sql("attacks.source = system.ip_addr", "where");
      add_to_sql("os", "group");
      add_to_sql("split_part(system.name, ' ', 1) as os", "select");
      $title .= " (";
      $tempwhere .= "split_part(system.name, ' ', 1) IN (";
      $check = 0;
      foreach ($tainted['os'] as $os) {
        $check++;
        $os = pg_escape_string(strip_tags(htmlentities($os)));

        if ($check != count($tainted['os'])) {
          $tempwhere .= "'" .$os. "', ";
          $title .= $os .", ";
        } else {
          $tempwhere .= "'" .$os. "'";
          $title .= $os;
        }
      }
      $tempwhere .= ") ";
      $title .= ") ";
      add_to_sql($tempwhere, "where");
    }
  }
}
$tempwhere = "";

########################
# Virus Types
########################
if ($tainted['virus']) {
  if (@is_array($tainted['virus'])) {
    add_to_sql("binaries.bin", "select");
    add_to_sql("binaries", "table");
    add_to_sql("uniq_binaries", "table");
    add_to_sql("details", "table");
    add_to_sql("attacks.id = details.attackid", "where");
    add_to_sql("details.type = 8", "where");
    add_to_sql("details.text = uniq_binaries.name", "where");
    add_to_sql("uniq_binaries.id = binaries.bin", "where");
    add_to_sql("binaries.bin", "group");
    if ($clean['scanner']) {
      $scanner = $clean['scanner'];
      add_to_sql("binaries.scanner = $scanner", "where");
      $sql_getscanner = "SELECT name FROM scanners WHERE id = $scanner";
      $debuginfo[] = $sql_getscanner;
      $result_getscanner = pg_query($sql_getscanner);
      $row_scanner = pg_fetch_assoc($result_getscanner);
      $scannername = " (" .$row_scanner['name'] . ")";
    }
    if ($tainted['virus'][0] != "all") {
      $limit = 10;
      $title .= " (Top $limit virusses$scannername)";
      $sqllimit = " LIMIT $limit";
    } else {
      $sqllimit = "";
      $title .= "$scannername";
    }
  }
}
$tempwhere = "";

########################
# Interval & Timestamps
########################
if ($interval == 3600) {
  $title .= " per hour";
} elseif ($interval == 86400) {
  $title .= " per day";
} elseif ($interval == 604800) {
  $title .= " per week";
}

$from = $_SESSION['s_from'];
$to = $_SESSION['s_to'];
$textstart = date("d-m-Y H:i:s", $from);
$textend = date("d-m-Y H:i:s", $to);
$tsstart = $from;
$tsend = $to;
$tsperiod = $tsend - $tsstart;
$tssteps = intval($tsperiod / $interval);

########################
# Sensor ID
########################
if ($tainted['sensorid']) {
  if (@is_array($tainted['sensorid'])) {
    if ($tainted['sensorid'][0] == 0) {
      $title .= " for all sensors";
      $allsensors = 1;
    } else {
      add_to_sql("sensors.keyname", "group");
      add_to_sql("sensors.vlanid", "group");
      add_to_sql("sensors.keyname", "select");
      add_to_sql("sensors.vlanid", "select");
      $title .= " for sensors: ";
      $tempwhere .= "sensors.id IN (";
      $check = 0;
      foreach ($tainted['sensorid'] as $sid) {
        $check++;
        $sid = intval($sid);
        $sql_gs = "SELECT keyname, vlanid FROM sensors WHERE id = $sid";
        $result_gs = pg_query($pgconn, $sql_gs);
        $row_gs = pg_fetch_assoc($result_gs);
        $keyname = $row_gs['keyname'];
        $vlanid = $row_gs['vlanid'];
        $keytitle = sensorname($keyname, $vlanid);
        if ($check != count($tainted['sensorid'])) {
          $tempwhere .= $sid .", ";
          $title .= "$keytitle, ";
        } else {
          $tempwhere .= $sid;
          $title .= $keytitle;
        }
      }
      $tempwhere .= ") ";
      add_to_sql("$tempwhere", "where");
    }
  }
}
$tempwhere = "";

########################
# Ports
########################
if (isset($clean['ports'])) {
  $ports = $clean['ports'];
  if ($ports == "all") {
    add_to_sql("attacks.dport", "select");
    add_to_sql("attacks.dport", "group");
    $notsqlports .= "attacks.dport NOT IN (0)"; 
    add_to_sql("($notsqlports)", "where");

    # IP Exclusion stuff
    add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");

    prepare_sql();
  } else {
    $ports = trim($ports, ",");
    $pattern = '/^(\!?[0-9]+-?,?)+$/';
    if (preg_match($pattern, $ports)) {
      $ports_ar = explode(",", $ports);
      foreach ($ports_ar as $port) {
        $count = substr_count($port, '-');
        if ($count == 1) {
          if ($port{0} == "!") {
            $port = substr($port, 1); 
            $portrange_ar = explode("-", $port);
            $notsqlports .= "attacks.dport NOT BETWEEN ($portrange_ar[0]) AND ($portrange_ar[1]) AND ";
          } else { 
            $portrange_ar = explode("-", $port);
            $sqlports .= "attacks.dport BETWEEN ($portrange_ar[0]) AND ($portrange_ar[1]) OR ";
          }
        } else { 
          if ($port{0} == "!") {
            $port = substr($port, 1); 
            $notportlist .= "0,". $port .",";
          } else {
            $portlist .= $port .","; 
          }
        }
      }
    }  
    if ($portlist) { 
      $portlist = trim($portlist, ",");
      $sqlports .= "attacks.dport IN ($portlist)"; 
      $sqlports = trim($sqlports);
      $sqlports = trim($sqlports, "OR");
      add_to_sql("($sqlports)", "where");
    }
    if ($notportlist) { 
      $notportlist = trim($notportlist, ",");
      $notsqlports .= "attacks.dport NOT IN ($notportlist)"; 
      $notsqlports = trim($notsqlports);
      $notsqlports = trim($notsqlports, "AND");
      add_to_sql("($notsqlports)", "where");
    }
    add_to_sql("attacks.dport", "select");
    add_to_sql("attacks.dport", "group");
    $title .= " with attack port ($ports)  ";
  }
}
$title .= "\n From $textstart to $textend";

##############
# PHPlot stuff
##############

require_once "$c_phplot";  // here we include the PHPlot code 
$plot =& new PHPlot($width,$heigth);    // here we define the variable

$plot->SetTitle($title);
$plot->SetXTitle('Time');
$plot->SetYTitle('Attacks');
$plot->SetPlotType($type);

$data = array();
$i = 0;

$check_ar = array();
$maxcount = 0;

add_to_sql("severity", "order");
add_to_sql("COUNT(attacks.severity) as total", "select");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");

prepare_sql();

#print "tssteps: $tssteps\n";
$stepcount = $tssteps / 30;
$stepcount_org = round($stepcount);
$stepcount = $stepcount_org;

$point = array();
$foundkeys_ar = array();
while ($i != $tssteps) {
  $a = $i;
  $i++;
  foreach ($point as $key => $value) {
    if ("$key" != "0") {
      if (!in_array($key, $foundkeys_ar)) {
        $foundkeys_ar[] = $key;
      }
    }
    $point[$key] = 0;
  }
#  $sql = "SELECT DISTINCT $selectattacks $selectsensorid attacks.severity, COUNT(attacks.severity) as total FROM attacks, sensors, details ";
#  $sql .= "WHERE details.attackid = attacks.id AND timestamp >= $tsstart + ($interval * $a) AND timestamp <= $tsstart + ($interval * $i) ";
#  $sql .= "AND sensors.id = attacks.sensorid $where ";
#  $sql .= "GROUP BY $groupbysensorid $groupbyattacks attacks.severity ORDER BY severity";
  $sql = "SELECT $sql_select ";
  $sql .= "FROM $sql_from";
  $sql .= "$sql_where";
  if ($sql_where != "") {
    $sql .= " AND ";
  } else {
    $sql .= " WHERE ";
  }
  $sql .= " attacks.timestamp >= $tsstart + ($interval * $a) ";
  $sql .= " AND attacks.timestamp <= $tsstart + ($interval * $i) ";
  $sql .= " GROUP BY $sql_group ";
  $sql .= " ORDER BY $sql_order ";
  if (isset($tainted['virus'])) {
    $sql .= $sqllimit;
  }
  $debuginfo[] = $sql;

  $result = pg_query($pgconn, $sql);
  $numrows = pg_num_rows($result);
 # if ($numrows != 0) {
    $date = $tsstart + ($interval * $a);
    if ($interval == 3600) {
      $datestring = date("d-m", $date) . "\n " . date("H", $date) . ":00";
    } elseif ($interval == 86400) {
      $datestring = date("d-m", $date);
    } elseif ($interval == 604800) {
      $datestring = "Week\n" .date("W", $date);
    }
   # print "stepcount: $stepcount<br />\n";
    #print "i: $i<br />\n";
    if ($stepcount == $i) {
       $point[0] = $datestring;
       $stepcount = $stepcount + $stepcount_org;
  #     print "<br />check: $datestring<br />\n";
    }
    elseif ($i == 1 || $i == $tssteps) $point[0] = $datestring;
    else  $point[0] = ''; 

  if ($numrows != 0) {
    while($row = pg_fetch_assoc($result)) {
      $count = $row['total'];
      $sev = $row['severity'];
      if (isset($row['text'])) {
        $type = $row['text'];
      }
      $legend = "";
      if ($maxcount < $count) {
        $maxcount = $count;
      }
      if (isset($row['keyname'])) {
        $vlanid = $row['vlanid'];
        $keyname = $row['keyname'];
        $keytitle = sensorname($keyname, $vlanid);
        $legend .= "$keytitle -";
      }
     
      if (isset($row['text'])) {
        $attack = str_replace("Dialogue", "", $row['text']);
	if($legend == "") { $legend .= " ".$attack;}
	else { $legend .= " - ".$attack; }
      }
      if (isset($row['dport'])) {
        $port = $row['dport'];
        $legend .= " $port";
      }
      if (isset($row['os'])) {
        $os = $row['os'];
        $legend .= " $os";
      }
      if (isset($row['bin'])) {
        $bin = $row['bin'];

        $sql_bin = "SELECT name FROM stats_virus, binaries ";
        $sql_bin .= "WHERE binaries.info = stats_virus.id AND binaries.bin = $bin AND binaries.scanner = $scanner ";
        $sql_bin .= "ORDER BY binaries.timestamp ASC LIMIT 1";
        $result_bin = pg_query($pgconn, $sql_bin);
        $row_bin = pg_fetch_assoc($result_bin);
        $name = $row_bin['name'];

        $legend .= " $name";
      } else {
        if ($v_severity_ar[$sev] == "Possible malicious attack") { $v_severity_ar[$sev] = "PosA"; }
        if ($v_severity_ar[$sev] == "Malicious attack") { $v_severity_ar[$sev] = "MalA"; }
        if ($v_severity_ar[$sev] == "Malware offered") { $v_severity_ar[$sev] = "MalO"; }
        if ($v_severity_ar[$sev] == "Malware downloaded") { $v_severity_ar[$sev] = "MalD"; }
        if ($legend == "") {$legend .= " ".$v_severity_ar[$sev];}
        else {$legend .= " - ".$v_severity_ar[$sev];}
      }
      if (!in_array($legend, $check_ar)) {
        if ($limit != "") {
          if (count($check_ar) != $limit) {
            $check_ar[] = $legend;
            $plot->SetLegend($legend);
          }
        } else {
          $check_ar[] = $legend;
          $plot->SetLegend($legend);
        }
      }
      if (in_array($legend, $check_ar)) {
        $point[$legend] = $count;
      }
    }

    $data[] = $point;
  } else  {
    $data[] = $point;
  }
}

if (count($foundkeys_ar) != 0) {
  foreach ($data as $key => $val) {
    foreach ($foundkeys_ar as $fkey => $fval) {
      if (!array_key_exists($fval, $val)) {
        $data[$key][$fval] = 0;
      }
    }
  }
} else {
  foreach ($data as $key => $val) {
    $data[$key][1] = 0;
  }
}

#printer($data);
#printer($sql);

#debug_sql();

#if (!empty($data)) {
if (empty($data)) {
  $point = array();
  $point[] = " ";
  $point[] = " ";
  $data = array();
  $data[] = $point;
  $ytick = 1;
} else {
#  printer($maxcount);
  $pertick = intval($maxcount / 20);
  if ($pertick > 50) {
    $ytick = $pertick - ($pertick % 50);
  } elseif ($pertick > 10) {
    $ytick = $pertick - ($pertick % 10);
  } elseif ($pertick > 5) {
    $ytick = 10;
  } elseif ($pertick != 0) {
    $ytick = 5;
  } else {
    $ytick = 1;
  }
}
 # printer($pertick);
 # printer($ytick);
  $plot->SetDataColors($v_phplot_data_colors);
  $plot->SetBackgroundColor("#f0f0f0");
  $plot->SetVertTickIncrement("$ytick");
  $plot->SetDataValues($data);
  $plot->SetLegendPixels(0, 0);
  $plot->SetDefaultDashedStyle('4-3');
  $plot->DrawGraph();
#} else {
#  readfile("images/nodata.gif");
#}
?>
