<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 18-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added ARP exclusion stuff
# 001 Fixed bug #55
#############################################

#header("Content-type: image/png");
#header("Cache-control: no-cache");
#header("Pragma: no-cache");

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
###############################################
session_start();

# Retrieving some session variables
###############################################
$s_org = intval($_SESSION['s_org']);
$q_org = $_SESSION['q_org'];
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});

# Retrieving posted variables from $_GET
###############################################
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
		"int_scanner",
		"int_virus",
		"sevtype",
		"int_totalmal1",
		"int_totalmal2",
		"int_totalmal3"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

# Initialising some variables
###############################################
$of_title_ar = array();
$limit = "";

########################
#  Total Malicious attacks
########################
$set_totalmal = 0;
if (isset($clean['totalmal1'])) {
  $set_totalmal = 1;
}
if (isset($clean['totalmal2'])) {
  $set_totalmal = 1;
}
if (isset($clean['totalmal3'])) {
  $set_totalmal = 1;
}

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
  $sev = $tainted['severity'];
  $sev_ar = explode(",", $sev);

  $atype = $tainted['sevtype'];
  $atype_ar = explode(",", $atype);

  if ($sev_ar[0] == -1) {
    if ($clean['virus']) {
      of_title("Downloaded malware");
    } else {
      of_title("All attacks");
    }
  } else {
    $tempwhere .= "(attacks.severity IN (";
    $check = 0;
    foreach ($sev_ar as $sev) {
      $check++;
      $sev = intval($sev);
      if ($sev == 1) {
        if (isset($tainted['sevtype'])) {
          if ($tainted['sevtype'] == -1) {
            $tempwhere .= $sev .", ";
          }
        }
      } else {
        $tempwhere .= $sev .", ";
      }
    }
    $tempwhere = trim($tempwhere, " ");
    $tempwhere = trim($tempwhere, ",");
    $tempwhere .= ")";
    if ($tempwhere == "(attacks.severity IN ()") {
      $tempwhere = "(";
    }
    if (isset($tainted['sevtype'])) {
      if ($tainted['sevtype'] != -1) {
        if ($tempwhere != "(") {
          $tempwhere .= " OR";
        }
        $tempwhere .= " (atype IN (";
        $check = 0;
        foreach ($atype_ar as $atype) {
          $check++;
          $atype = intval($atype);
          if ($check != count($atype_ar)) {
            $tempwhere .= $atype .", ";
          } else {
            $tempwhere .= $atype;
          }
        }
        $tempwhere .= " ) AND severity = 1))";
      } else {
        $tempwhere .= ")";
      }
    } else {
      $tempwhere .= ")";
    }
    of_title("Attacks");
    add_to_sql("$tempwhere", "where");
  }
} else {
  if (isset($clean['virus'])) {
    of_title("Downloaded malware");
  } else {
    of_title("All attacks");
  }
}
add_to_sql("atype", "select");
add_to_sql("atype", "group");
add_to_sql("attacks.severity", "group");
$tempwhere = "";

########################
# Attack Types
########################
if (isset($tainted['attack'])) {
  $attack = $tainted['attack'];
  $attacks_ar = explode(",", $attack);

  if ($attacks_ar[0] != -1) {
    add_to_sql("details", "table");
    add_to_sql("attacks", "table");
    add_to_sql("attacks.id = details.attackid", "where");

    $check = 0;
    add_to_sql("details.text", "group");
    add_to_sql("details.text", "select");
    $title .= " (";
    $tempwhere .= "details.text IN (";
    foreach ($attacks_ar as $attack) {
      $check++;
      $attack = intval($attack);

      $sql_getattack = "SELECT name FROM stats_dialogue WHERE id = $attack";
      $debuginfo[] = $sql_getattack;
      $result_getattack = pg_query($sql_getattack);
      $row_attack = pg_fetch_assoc($result_getattack);
      $dianame = $row_attack['name'];
      $attackname = str_replace("Dialogue", "", $dianame);

      if ($check != count($attacks_ar)) {
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
    of_title($title);
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
$tempwhere = "";

########################
# OS Types
########################
if ($tainted['os']) {
  $os = $tainted['os'];
  $os_ar = explode(",", $os);

  if ($os_ar[0] == "all") {
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
    foreach ($os_ar as $os) {
      $check++;
      $os = pg_escape_string(strip_tags(htmlentities($os)));

      if ($check != count($os_ar)) {
        $tempwhere .= "'" .$os. "', ";
        $title .= $os .", ";
      } else {
        $tempwhere .= "'" .$os. "'";
        $title .= $os;
      }
    }
    $tempwhere .= ") ";
    $title .= ") ";
    of_title($title);
    add_to_sql($tempwhere, "where");
  }
}
$tempwhere = "";

########################
# Virus Types
########################
if (isset($clean['virus'])) {
  $virus = $clean['virus'];

  add_to_sql("stats_virus.name", "select");
  add_to_sql("binaries", "table");
  add_to_sql("stats_virus", "table");
  add_to_sql("uniq_binaries", "table");
  add_to_sql("details", "table");
  add_to_sql("attacks.id = details.attackid", "where");
  add_to_sql("details.type = 8", "where");
  add_to_sql("details.text = uniq_binaries.name", "where");
  add_to_sql("uniq_binaries.id = binaries.bin", "where");
  add_to_sql("stats_virus.id = binaries.info", "where");
  add_to_sql("stats_virus.name", "group");

  if ($clean['scanner']) {
    $scanner = $clean['scanner'];
    add_to_sql("binaries.scanner = $scanner", "where");
    $sql_getscanner = "SELECT name FROM scanners WHERE id = $scanner";
    $debuginfo[] = $sql_getscanner;
    $result_getscanner = pg_query($sql_getscanner);
    $row_scanner = pg_fetch_assoc($result_getscanner);
    $scannername = " (" .$row_scanner['name'] . ")";
  }
  if ($virus == 1) {
    $limit = 10;
    of_title("(Top $limit virusses$scannername)");
    $sqllimit = " LIMIT $limit";
  } else {
    $sqllimit = "";
    of_title("$scannername");
  }
}
$tempwhere = "";

########################
# Interval & Timestamps
########################
if ($interval == 3600) {
  of_title("per hour");
} elseif ($interval == 86400) {
  of_title("per day");
} elseif ($interval == 604800) {
  of_title("per week");
}

$from = $_SESSION['s_from'];
$to = $_SESSION['s_to'];
$textstart = date($c_date_format, $from);
$textend = date($c_date_format, $to);
$tsstart = $from;
$tsend = $to;
$tsperiod = $tsend - $tsstart;
$tssteps = intval($tsperiod / $interval);

########################
# Sensor ID
########################
if ($tainted['sensorid']) {
  $sensors = $tainted['sensorid'];
  $sensors_ar = explode(",", $sensors);

  if ($sensors_ar[0] == 0) {
    of_title("for all sensors");
    $allsensors = 1;
  } else {
    add_to_sql("sensors.keyname", "group");
    add_to_sql("sensors.vlanid", "group");
    add_to_sql("sensors.keyname", "select");
    add_to_sql("sensors.vlanid", "select");
    $title .= " for sensors: ";
    $tempwhere .= "sensors.id IN (";
    $check = 0;
    foreach ($sensors_ar as $sid) {
      $check++;
      $sid = intval($sid);
      $sql_gs = "SELECT keyname, vlanid FROM sensors WHERE id = $sid";
      $result_gs = pg_query($pgconn, $sql_gs);
      $row_gs = pg_fetch_assoc($result_gs);
      $keyname = $row_gs['keyname'];
      $vlanid = $row_gs['vlanid'];
      $keytitle = sensorname($keyname, $vlanid);
      if ($check != count($sensors_ar)) {
        $tempwhere .= $sid .", ";
        $title .= "$keytitle, ";
      } else {
        $tempwhere .= $sid;
        $title .= $keytitle;
      }
    }
    of_title($title);
    $tempwhere .= ") ";
    add_to_sql("$tempwhere", "where");
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
    # MAC Exclusion stuff
    add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

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
            $notsqlports .= "attacks.dport NOT BETWEEN ($portrange_ar[0]) AND ($portrange_ar[1]) ";
          } else { 
            $portrange_ar = explode("-", $port);
            $sqlports .= "attacks.dport BETWEEN ($portrange_ar[0]) AND ($portrange_ar[1]) ";
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
    if ($portlist != "") {
      $portlist = trim($portlist, ",");
      $sqlports .= " OR attacks.dport IN ($portlist)";
      $sqlports = trim($sqlports, " ");
      $sqlports = trim($sqlports, "OR");
    }
    if ($sqlports != "") {
      add_to_sql("($sqlports)", "where");
    }
    if ($notportlist != "") {
      $notportlist = trim($notportlist, ",");
      $notsqlports .= " AND attacks.dport NOT IN ($notportlist)";
      $notsqlports = trim($notsqlports);
      $notsqlports = trim($notsqlports, "AND");
    }
    if ($notsqlports != "") {
      add_to_sql("($notsqlports)", "where");
    }
    add_to_sql("attacks.dport", "select");
    add_to_sql("attacks.dport", "group");
    of_title("with attack port ($ports)");
  }
}
of_title("\n From $textstart to $textend");
$title = of_set_title();

##############
# PHPlot stuff
##############

require_once "$c_phplot";  // here we include the PHPlot code 
$plot =& new PHPlot($width,$heigth);    // here we define the variable

$plot->SetTitle($title);
$plot->SetXTitle('Time');
$plot->SetYTitle('Attacks');
$plot->SetPlotType($type);

# Initialising the used arrays and variables
###############################################
$data = array();
$check_ar = array();
$point = array();
$foundkeys_ar = array();
$i = 0;
$y_max = 0;
$sevtotal = 0;

# Preparing the final SQL stuff
###############################################
add_to_sql("severity", "order");
add_to_sql("COUNT(attacks.severity) as total", "select");

# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");
# MAC Exclusion stuff
add_to_sql("(attacks.src_mac IS NULL OR NOT attacks.src_mac IN (SELECT mac FROM arp_excl))", "where");

prepare_sql();

if ($limit != "") {
  # Setting up the top $limit query
  ###############################################
  $sql = "SELECT $sql_select ";
  $sql .= "FROM $sql_from";
  $sql .= "$sql_where";
  if ($sql_where != "") {
    $sql .= " AND ";
  } else {
    $sql .= " WHERE ";
  }
  $sql .= " attacks.timestamp >= $from ";
  $sql .= " AND attacks.timestamp <= $to ";
  $sql .= " GROUP BY $sql_group ";
  $sql .= " ORDER BY total DESC";
  $sql .= $sqllimit;
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($result)) {
    $top_ar[] = $row['name'];
  }
}

# Looping through the steps
###############################################
while ($i != $tssteps) {
  # Setting the step variables
  # a = from step
  # i = to step
  # Example: where timestamp from $a till $i
  ###############################################
  $a = $i;
  $i++;

  # Resetting the point array while keeping the keys
  ###############################################
  foreach ($point as $key => $value) {
    if ("$key" != "0") {
      if (!in_array($key, $foundkeys_ar)) {
        $foundkeys_ar[] = $key;
      }
    }
    $point[$key] = 0;
  }

  # Building the SQL query
  ###############################################
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
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $numrows = pg_num_rows($result);

  # Creating the date string for the x axis
  ###############################################
  $date = $tsstart + ($interval * $a);
  if ($interval == 3600) {
    $datestring = date($c_date_format_noyear, $date) . "\n " . date("H", $date) . ":00";
  } elseif ($interval == 86400) {
    $datestring = date($c_date_format_noyear, $date);
  } elseif ($interval == 604800) {
    $datestring = "Week\n" .date("W", $date);
  }
  $point[0] = $datestring;

  # Retrieving point data if there are any rows
  ###############################################
  if ($numrows != 0) {
    while($row = pg_fetch_assoc($result)) {
      # Resetting legend array
      ###############################################
      $of_legend_ar = array();

      # Fetching row data
      ###############################################
      $count = $row['total'];
      $sev = $row['severity'];
      $atype = $row['atype'];

      if ($set_totalmal == 1) {
        # Keeping track of the cumulative malicious attacks
        ###############################################
        if (isset($tainted['severity'])) {
          if ($sev == 1) {
            $sevtotal += $count;
          }
        }
      }

      # Determining the maximum value of the y axis
      ###############################################
      if ($y_max < $count) {
        $y_max = $count;
      } elseif ($y_max < $sevtotal) {
        $y_max = $sevtotal;
      }

      # Handling the row data
      ###############################################
      if (isset($row['text'])) {
        $type = $row['text'];
      }

      if (isset($row['keyname'])) {
        $vlanid = $row['vlanid'];
        $keyname = $row['keyname'];
        $keytitle = sensorname($keyname, $vlanid);
        of_legend("$keytitle");
      }
     
      if (isset($row['text'])) {
        $attack = str_replace("Dialogue", "", $row['text']);
        of_legend($attack);
      }
      if (isset($row['dport'])) {
        $port = $row['dport'];
        of_legend("$port");
      }
      if (isset($row['os'])) {
        $os = $row['os'];
        of_legend("$os");
      }
      if (isset($row['name'])) {
        $vir = $row['name'];
        if ($limit != "") {
          if (in_array($vir, $top_ar)) {
            of_legend($vir);
          }
        } else {
          of_legend($vir);
        }
      } else {
        if ($v_severity_ar[$sev] == "Possible malicious attack") { $v_severity_ar[$sev] = "PosA"; }
        if ($v_severity_ar[$sev] == "Malicious attack") { $v_severity_ar[$sev] = "MalA"; }
        if ($v_severity_ar[$sev] == "Malware offered") { $v_severity_ar[$sev] = "MalO"; }
        if ($v_severity_ar[$sev] == "Malware downloaded") { $v_severity_ar[$sev] = "MalD"; }

        if ($sev == 1) {
          if (isset($row['atype'])) {
            $atype = $row['atype'];
            of_legend($v_severity_ar[$sev] . " - " .$v_severity_atype_ar[$atype]);
          } else {
            of_legend($v_severity_ar[$sev]);
          }
        } else {
          of_legend($v_severity_ar[$sev]);
        }
      }

      # Setting up the legend and adding it (if needed)
      ###############################################
      $legend = of_set_legend();
      if ($legend != "") {
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
      }
      if (in_array($legend, $check_ar)) {
        $point[$legend] = $count;
      }
    }

    if ($set_totalmal == 1) {
      # If needed, adding cumulative malicous attacks
      ###############################################
      if (isset($tainted['sevtype'])) {
        if ($limit == "") {
          $legend = "All MalA";
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
          $point[$legend] = $sevtotal;
          $sevtotal = 0;
        }
      }
    }

    $data[] = $point;
  } else  {
    $data[] = $point;
  }
}

# Setting up 0 values for missing points
###############################################
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

#debug_sql();

if (empty($data)) {
  # Handling empty data sets
  ###############################################
  $point = array();
  $point[] = " ";
  $point[] = " ";
  $data = array();
  $data[] = $point;
  $ytick = 1;
} else {
  # Calculating the step size of the y-axis
  ###############################################
  $pertick = intval($y_max / 20);
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

# Setting up some graphing options
###############################################
$plot->SetDataColors($v_phplot_data_colors);
$plot->SetBackgroundColor("#f0f0f0");
$plot->SetVertTickIncrement("$ytick");
$plot->SetDataValues($data);
$plot->SetLegendPixels(0, 0);
$plot->SetDefaultDashedStyle('4-3');

# Printing the graph
###############################################
$plot->DrawGraph();

?>
