<?php

####################################
# SURFnet IDS 2.10.00              #
# Changeset 003                    #
# 18-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Added ARP exclusion stuff
# 002 Fixed a bug with the severity
# 001 Initial release
#############################################

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
# Type
########################
if (!isset($clean['type']) || empty($clean['type'])) {
  $type = 1;
} else {
  $type = $clean['type'];
}

add_to_sql("sensors", "table");
add_to_sql("DISTINCT attacks.severity", "select");
add_to_sql("attacks", "table");
add_to_sql("attacks.sensorid = sensors.id", "where");
add_to_sql("$query_org", "where");

########################
# Severity
########################
if (isset($tainted['severity'])) {
  $sev = $tainted['severity'];
  $sev_ar = explode(",", $sev);

  if (!in_array(1, $sev_ar)) {
    $set_totalmal = 0;
  }

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
if ($tainted['attack']) {
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
        $title .= $attackname ." - ";
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
    $title .= "(";
    $tempwhere .= "split_part(system.name, ' ', 1) IN (";
    $check = 0;
    foreach ($os_ar as $os) {
      $check++;
      $os = pg_escape_string(strip_tags(htmlentities($os)));

      if ($check != count($os_ar)) {
        $tempwhere .= "'" .$os. "', ";
        $title .= $os ." - ";
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
  $sensors = $tainted['sensorid'];
  $sensors_ar = explode(",", $sensors);

  if ($sensors_ar[0] == 0) {
    of_title("for all sensors");
    $allsensors = 1;
  } else {
    add_to_sql("sensors.id", "group");
    add_to_sql("sensors.keyname", "group");
    add_to_sql("sensors.vlanid", "group");
    add_to_sql("sensors.keyname", "select");
    add_to_sql("sensors.vlanid", "select");
    add_to_sql("sensors.id as sensorid", "select");
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
        $title .= "$keytitle - ";
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
    $ports = str_replace(",", " ", $ports);
    of_title("with attack port ($ports)");
  }
}

##############
# Open Flash stuff
##############

# Initialising the used arrays and variables
###############################################
$data = array();
$point = array();
$check_ar = array();
$foundkeys_ar = array();
$of_legend_ar = array();
$of_link_colors_ar = array();
$of_links_ar = array();
$of_temp_links_ar = array();
$top_ar = array();
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
    $datestring = date("d-m", $date) . " " . date("H", $date) . ":00";
  } elseif ($interval == 86400) {
    $datestring = date("d-m", $date);
  } elseif ($interval == 604800) {
    $datestring = "Week " .date("W", $date);
  }

  # Setting up the labels for the x axis
  ###############################################
  $x_labels[] = $datestring;

  # Retrieving point data if there are any rows
  ###############################################
  if ($numrows != 0) {
    $r = 0;
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
            echo "SEVTOTAL: $sevtotal<br />\n";
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
        $text = $row['text'];
      }
      if (isset($row['keyname'])) {
        $sid = $row['sensorid'];
        $vlanid = $row['vlanid'];
        $keyname = $row['keyname'];
        $keytitle = sensorname($keyname, $vlanid);
        of_legend($keytitle);
        of_links("sensorid=$sid");
      }
   
      if (isset($row['text'])) {
        $sql_geta = "SELECT id FROM stats_dialogue WHERE name = '$text'";
        $result_a = pg_query($pgconn, $sql_geta);
        $row_a = pg_fetch_assoc($result_a);
        $att = $row_a['id'];

        $attack = str_replace("Dialogue", "", $row['text']);
        of_legend($attack);
        of_links("int_attack=$att");
      }
      if (isset($row['dport'])) {
        $port = $row['dport'];
        of_legend("port $port");
        of_links("int_dport=$port");
      }
      if (isset($row['os'])) {
        $os = $row['os'];
        of_legend($os);
      }
      if (isset($row['name'])) {
        $vir = $row['name'];
        if ($limit != "") {
          if (in_array($vir, $top_ar)) {
            of_legend($vir);
            of_links("strip_html_escape_virustxt=$vir");
          }
        } else {
          of_legend($vir);
          of_links("strip_html_escape_virustxt=$vir");
        }
      } else {
        if ($sev == 1) {
          if (isset($row['atype'])) {
            $atype = $row['atype'];
            of_legend($v_severity_ar[$sev] . " - " .$v_severity_atype_ar[$atype]);
            of_links("int_sev=$sev&int_sevtype=$atype");
          } else {
            of_legend($v_severity_ar[$sev]);
            of_links("int_sev=$sev");
          }
        } else {
          of_legend($v_severity_ar[$sev]);
          of_links("int_sev=$sev");
        }
      }

      # Setting up the links arrays
      ###############################################
      of_set_links();

      # Setting up the legend and adding the data
      ###############################################
      $legend = of_set_legend();
      if (!in_array($legend, $check_ar)) {
        $check_ar[] = $legend;
      }
      if ($limit != "") {
        if (in_array($legend, $top_ar)) {
          $data[$legend][$i] = $count;
        }
      } else {
        $data[$legend][$i] = $count;
      }
      $of_temp_links_ar = array();
      $r++;
    }

    if ($set_totalmal == 1) {
      # If needed, adding cumulative malicous attacks
      ###############################################
      if (isset($tainted['sevtype'])) {
        if ($limit == "") {
          $legend = "All malicious attacks";
          $datasev[$legend][$i] = $sevtotal;
          $sevtotal = 0;
        }
      }
    }
  }
}

# Setting up 0 values for missing points
###############################################
foreach ($data as $key => $val) {
  for ($tsc = 1; $tsc <= $tssteps; $tsc++) {
    if (!array_key_exists($tsc, $data[$key])) {
      $data[$key][$tsc] = 0;
    }
    ksort($data[$key]);
  }
}

if ($set_totalmal == 1) {
  if (isset($datasev)) {
    # Setting up 0 values for missing points (for total malicious attacks)
    ###############################################
    for ($tsc = 1; $tsc <= $tssteps; $tsc++) {
      if (!array_key_exists($tsc, $datasev[$legend])) {
        $datasev["All malicious attacks"][$tsc] = 0;
      }
    }
    ksort($datasev[$legend]);
  }
}

# Determining the step size of the x axis
###############################################
$x_steps = round($tssteps / 20);
if ($x_steps == 0) {
  $x_steps = 1;
}

# Including the php open flash library
###############################################
include_once('../include/php-ofc-library/open-flash-chart.php');

# Initialising the new graph
###############################################
$g = new graph();

# Generating the title
###############################################
$title = of_set_title();

# Setting up some general layout stuff
###############################################
$g->title($title, '{font-size: 24px; color: #000000;}');
$g->bg_colour = '#FFFFFF';
$g->set_inner_background('#f0f0f0', '#808080', 90);
$g->set_x_label_style(14, '#000000', 2, $x_steps);
$g->set_y_label_style(14, '#000000');
$g->x_axis_colour( '#8499A4', '#E4F5FC' );
$g->y_axis_colour( '#8499A4', '#E4F5FC' );

# Determining the step size of the y axis
###############################################
if ($y_max != 0) {
  $rp = -(strlen($y_max) - 1);
  $y_max = roundup($y_max, $rp);
  $rp = strlen($y_max) - 1;
  if ($rp == 0) {
    $rp = 1;
  }
  $deler = substr($y_max, 0, $rp);
  $y_steps = round($y_max / $deler);
  if ($y_max < 20) {
    $y_steps = $y_max;
  }
} else {
  $y_max = 10;
  $y_steps = 10;
}
$g->y_label_steps($y_steps);

# Setting the largest point for the y axis
###############################################
$g->set_y_max($y_max);

# Setting up the labels for the x axis
###############################################
$g->set_x_labels($x_labels);

# Setting up the links for the pie chart
###############################################
if ($type == 6) {
  foreach ($of_links_ar as $key => $val) {
    $tmp = urlencode("logsearch.php?$val");
    $of_links_ar[$key] = $tmp;
  }
}

# Adding the data to the graph
###############################################
foreach ($data as $key => $val) {
  $num = mt_rand(0, 0xaaaaaa);
  $color = sprintf('%x', $num);

  if ($type != 6) {
    $g->set_data($val);
  }
  if ($type == 1) {
    $g->bar_filled(50, $color, $color, $key, 10);
    $g->set_tool_tip('#x_label#<br>#key#: #val#');
  } elseif ($type == 2) {
    $g->line_dot(3, 5, $color, $key, 10);
  } elseif ($type == 4) {
    $g->area_hollow( 2, 3, 25, $color);
  } elseif ($type == 6) {
    $totalpoint = 0;
    foreach ($val as $pointkey => $point) {
      $totalpoint += $point;
    }
    $piekeys[] = "$key: $totalpoint";
    $piepoints[] = $totalpoint;
    $g->set_tool_tip('#x_label#<br> Click for details');
  }
}

if ($type != 6) {
  # Adding the cumulative malicious attack data to the graph
  ###############################################
  if (is_array($datasev)) {
    foreach ($datasev as $key => $val) {
      $num = mt_rand(0, 0xaaaaaa);
      $color = sprintf('%x', $num);
      $g->set_data($val);
      $g->line_dot(3, 5, $color, $key, 10);
    }
  }
}

# Pie charts need totals as data, adding them here
###############################################
if ($type == 6) {
  $g->pie(60,'#505050','#000000');
  if (count($of_links_ar) != 0) {
    $g->pie_values($piepoints, $piekeys, $of_links_ar);
  } else {
    $empty = array();
    $g->pie_values($piepoints, $piekeys, $empty);
  }
  $g->pie_slice_colours($of_link_colors_ar);
}

#debug_sql();

# Render the graph
###############################################
echo $g->render();

?>
