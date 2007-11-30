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
		"int_scanner",
		"int_method",
		"int_virus",
		"int_sevtype"
);
$check = extractvars($_GET, $allowed_get);
#$c_debug_input = 1;
#debug_input();

########################
#  Method
########################
if (isset($clean['method'])) {
  $method = $clean['method'];
} else {
  $method = 0;
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
if ($tainted['severity']) {
  $sev = $tainted['severity'];
  $sev_ar = explode(",", $sev);

  if ($sev_ar[0] == -1) {
    if ($clean['virus']) {
      $title .= "Downloaded malware";
    } else {
      $title .= "All attacks";
    }
  } else {
    $tempwhere .= "attacks.severity IN (";
    $check = 0;
    foreach ($sev_ar as $sev) {
      $check++;
      $sev = intval($sev);
      if ($check != count($sev_ar)) {
        $tempwhere .= $sev .", ";
        if ($sev == 1 && $clean['sevtype'] != -1) {
          add_to_sql("atype", "select");
          add_to_sql("atype", "group");
          $atype = $clean['sevtype'];
          add_to_sql("atype = $atype", "where");
          $title .= $v_severity_ar[1] . "s - " . $v_severity_atype_ar[$atype] . " - ";
        } else {
          $title .= $v_severity_ar[$sev] . " - ";
        }
      } else {
        $tempwhere .= $sev;
        if ($sev == 1 && $clean['sevtype'] != -1) {
          add_to_sql("atype", "select");
          add_to_sql("atype", "group");
          $atype = $clean['sevtype'];
          add_to_sql("atype = $atype", "where");
          $title .= $v_severity_ar[1] . "s - " . $v_severity_atype_ar[$atype];
        } else {
          $title .= $v_severity_ar[$sev] . " - ";
        }
      }
    }
    $tempwhere .= ") ";
    add_to_sql("$tempwhere", "where");
  }
} else {
  if (isset($clean['virus'])) {
    $title .= "Downloaded malware";
  } else {
    $title .= "All attacks";
  }
}
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

      if ($check != count($os_ar['os'])) {
        $tempwhere .= "'" .$os. "', ";
        $title .= $os ." - ";
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
    $title .= " (Top $limit virusses$scannername)";
    $sqllimit = " LIMIT $limit";
  } else {
    $sqllimit = "";
    $title .= "$scannername";
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
  $sensors = $tainted['sensorid'];
  $sensors_ar = explode(",", $sensors);

  if ($sensors_ar[0] == 0) {
    $title .= " for all sensors";
    $allsensors = 1;
  } else {
    add_to_sql("sensorid", "group");
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
    $ports = str_replace(",", " -", $ports);
    $title .= " with attack port ($ports)  ";
  }
}
#$title .= "\n From $textstart to $textend";

##############
# PHPlot stuff
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
$top_ar = array();
$i = 0;
$maxcount = 0;
$maxpoints = 0;

# Preparing the final SQL stuff
###############################################
add_to_sql("severity", "order");
add_to_sql("COUNT(attacks.severity) as total", "select");
# IP Exclusion stuff
add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $s_org)", "where");
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
  if (isset($tainted['virus'])) {
    $sql .= $sqllimit;
  }
#  printer($sql);
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  while ($row = pg_fetch_assoc($result)) {
    $top_ar[] = $row['name'];
  }
}

#printer($top_ar);

while ($i != $tssteps) {
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
#  if (isset($tainted['virus'])) {
#    $sql .= $sqllimit;
#  }
  printer($sql);
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);
  $numrows = pg_num_rows($result);

  $date = $tsstart + ($interval * $a);
  if ($interval == 3600) {
    $datestring = date("d-m", $date) . " " . date("H", $date) . ":00";
  } elseif ($interval == 86400) {
    $datestring = date("d-m", $date);
  } elseif ($interval == 604800) {
    $datestring = "Week\n" .date("W", $date);
  }

  # Setting up the labels for the x axis
  $xlabels[] = $datestring;

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
      if (isset($row['text'])) {
        $text = $row['text'];
      }
      if ($maxcount < $count) {
        $maxcount = $count;
      }
      if (isset($row['keyname'])) {
        $sid = $row['sensorid'];
        $vlanid = $row['vlanid'];
        $keyname = $row['keyname'];
        $keytitle = sensorname($keyname, $vlanid);
        of_legend($keytitle);
        of_links("sensorid=$sid", $r);
      }
   
      if (isset($row['text'])) {
        $sql_geta = "SELECT id FROM stats_dialogue WHERE name = '$text'";
        $result_a = pg_query($pgconn, $sql_geta);
        $row_a = pg_fetch_assoc($result_a);
        $att = $row_a['id'];

        $attack = str_replace("Dialogue", "", $row['text']);
        of_legend($attack);
        of_links("int_attack=$att", $r);
      }
      if (isset($row['dport'])) {
        $port = $row['dport'];
        of_legend("port $port");
        of_links("int_dport=$port", $r);
      }
      if (isset($row['os'])) {
        $os = $row['os'];
        of_legend($os);
      }
      if (isset($row['name'])) {
        $vir = $row['name'];
        of_legend($vir);
        if ($limit != "") {
          if (in_array($vir, $top_ar)) {
            of_links("strip_html_escape_virustxt=$vir", $r);
          }
        } else {
          of_links("strip_html_escape_virustxt=$vir", $r);
        }
      } else {
        if ($sev == 1 && isset($clean['sevtype'])) {
          $atype = $clean['sevtype'];
          of_legend($v_severity_ar[$sev] . " - " .$v_severity_atype_ar[$atype]);
          of_links("int_sev=$sev&int_sevtype=$atype", $r);
        } else {
          of_legend($v_severity_ar[$sev]);
          of_links("int_sev=$sev", $r);
        }
      }

      $legend = of_set_legend();
      if (!in_array($legend, $check_ar)) {
        $check_ar[] = $legend;
      }
      if ($limit != "") {
        if (in_array($legend, $top_ar)) {
          $data[$legend][] = $count;
        }
      } else {
        $data[$legend][] = $count;
      }
      $r++;
    }
  }
}
#printer($data);

# Determining the step size of the x axis
###############################################
$rp = -(strlen($tssteps) - 1);
$maxpoints = roundup($tssteps, $rp);
$pointsteps = round($tssteps / 20 + 1);

#printer($data);

# Including the php open flash library
###############################################
include_once( 'include/php-ofc-library/open-flash-chart.php' );

# Initialising the new graph
###############################################
$g = new graph();

# Setting up some general layout stuff
###############################################
$g->title($title, '{font-size: 24px; color: #000000;}');
$g->bg_colour = '#FFFFFF';
$g->set_inner_background('#f0f0f0', '#808080', 90);
$g->set_x_label_style(14, '#000000', 2, $pointsteps);
$g->set_y_label_style(14, '#000000');
$g->x_axis_colour( '#8499A4', '#E4F5FC' );
$g->y_axis_colour( '#8499A4', '#E4F5FC' );

# Determining the step size of the y axis
###############################################
#printer($maxcount);
if ($maxcount > 2000) {
  $maxcount = roundup($maxcount, -3);
  $maxsteps = $maxcount / 100;
  $g->y_label_steps($maxsteps);
} elseif ($maxcount > 1000) {
  $maxcount = roundup($maxcount, -2);
  $maxsteps = $maxcount / 50;
  $g->y_label_steps($maxsteps);
} elseif ($maxcount > 500) {
  $maxcount = roundup($maxcount, -2);
  $maxsteps = $maxcount / 50;
  $g->y_label_steps($maxsteps);
} elseif ($maxcount > 100) {
  $maxcount = roundup($maxcount, -2);
  $maxsteps = $maxcount / 20;
  $g->y_label_steps($maxsteps);
} elseif ($maxcount > 50) {
  $maxcount = roundup($maxcount, -1);
  $maxsteps = $maxcount / 10;
  $g->y_label_steps($maxsteps);
} elseif ($maxcount > 20) {
  $maxcount = roundup($maxcount, -1);
  $maxsteps = $maxcount / 2;
  $g->y_label_steps($maxsteps);
} else {
  $maxcount = roundup($maxcount, -1);
}
#printer($maxcount);

# Setting the largest point for the y axis
###############################################
$g->set_y_max($maxcount);

# Setting up the labels for the x axis
###############################################
$g->set_x_labels($xlabels);

# Setting up the links for the pie chart
###############################################
if ($type == 6) {
  foreach ($of_links_ar as $key => $val) {
    $tmp = urlencode("logsearch.php?$val");
    $of_links_ar[$key] = $tmp;
  }
}

#printer($data);

# Adding the data to the graph
###############################################
$i = 0;
foreach ($data as $key => $val) {
  $num = mt_rand(0, 0xffffff);
  $color = sprintf('%x', $num);

  if ($type != 6) {
    $g->set_data($val);
  }
  if ($type == 1) {
    $g->bar_filled(50, $color, $color, $key, 10);
    $g->set_tool_tip('#key#: #val#');
  } elseif ($type == 2) {
    $g->line_dot(3, 5, $color, $key, 10);
  } elseif ($type == 4) {
    $g->area_hollow( 2, 3, 25, $color);
  } elseif ($type == 6) {
    $piekeys[] = $key;
    $totalpoint = 0;
    foreach ($val as $pointkey => $point) {
      $totalpoint += $point;
    }
    $piepoints[] = $totalpoint;
    $g->set_tool_tip('#x_label#: #val# <br> Click for details');
  }
  $i++;
}

# Pie charts need totals as data, adding them here
###############################################
if ($type == 6) {
  $empty = array();
  $g->pie(60,'#505050','#000000');
  if (count($of_links_ar) != 0) {
    $g->pie_values($piepoints, $piekeys, $of_links_ar);
  } else {
    $g->pie_values($piepoints, $piekeys, $empty);
  }
  $g->pie_slice_colours($of_link_colors_ar);
}

#debug_sql();

# Render the graph
###############################################
echo $g->render();

?>
