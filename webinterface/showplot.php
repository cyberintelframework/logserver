<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 08-01-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.01 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

#header("Content-type: image/png");
#header("Cache-control: no-cache");
#header("Pragma: no-cache");
#header("Content-disposition: attachment; filename=plot.jpg");

function getepoch($stamp) {
  list($date, $time) = explode(" ", $stamp);
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
      $epoch = mktime($hour, $min, 0, $mon, $day, $year);
      return $epoch;
    }
  }
}

$allowed_get = array(
                "int_interval",
                "strip_html_escape_tsstart",
                "strip_html_escape_tsend",
		"int_type",
		"sensorid",
		"severity",
		"attack"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

$allattacks = 0;
$allsensors = 0;
$allsev = 0;
$groupbysensorid = "";
$groupbyattacks = "";
$selectattacks = "";
$selectsensorid = "";
$where = "";

########################
# Severity
########################
if (@is_array($tainted['severity'])) {
  $count = count($tainted['severity']);
  if ($count == 1) {
    $sev = intval($tainted['severity'][0]);
    if ($sev == 99) {
      $title .= "All attacks";
      $allsev = 1;
    } else {
      $title .= $severity_ar[$sev];
      $where .= " AND attacks.severity = $sev";
    }
  } else {
    $where .= " AND attacks.severity IN (";
    $check = 0;
    foreach ($tainted['severity'] as $sev) {
      $check++;
      $sev = intval($sev);
      if ($check != count($tainted['severity'])) {
        $where .= $sev .", ";
        $title .= $severity_ar[$sev] . ", ";
      } else {
        $where .= $sev;
        $title .= $severity_ar[$sev];
      }
    }
    $where .= ") ";
  }
} else {
  $sev = intval($tainted['severity']);
  if ($sev == 99) {
    $title .= "All attacks";
    $allsev = 1;
  } else {
    $title .= $severity_ar[$sev];
    $where .= " AND attacks.severity = $sev";
  }
}

########################
# Attack Types
########################
if ($allsev == 1) {
  if (@is_array($tainted['attack'])) {
    $count = count($tainted['attack']);
    if ($count == 1) {
      $attack = intval($tainted['attack'][0]);
      if ($attack != 99) {
        $groupbyattacks = "details.text, ";
        $selectattacks = "details.text, ";

        $sql_getattack = "SELECT name FROM stats_dialogue WHERE id = $attack";
        $debuginfo[] = $sql_getattack;
        $result_getattack = pg_query($sql_getattack);
        $row_attack = pg_fetch_assoc($result_getattack);
        $dianame = $row_attack['name'];

        $attackname = str_replace("Dialogue", "", $dianame);
        $title .= " ($attackname)";
        $where .= " AND details.text = '$dianame'";
        $where .= " AND attacks.severity = 1 ";
      } else {
        $allattacks = 1;
      }
    } else {
      $where .= " AND details.text IN (";
      $check = 0;
      $groupbyattacks = "details.text, ";
      $selectattacks = "details.text, ";
      $title .= " (";
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
          $where .= "'" .$dianame. "', ";
          $title .= $attackname .", ";
        } else {
          $where .= "'" .$dianame. "'";
          $title .= $attackname;
        }
      }
      $where .= ") ";
      $title .= ") ";
    }
  } else {
    $attack = intval($tainted['attack']);
    if ($attack != 99) {
      $groupbyattacks = "details.text, ";
      $selectattacks = "details.text, ";

      $sql_getattack = "SELECT name FROM stats_dialogue WHERE id = $attack";
      $debuginfo[] = $sql_getattack;
      $result_getattack = pg_query($sql_getattack);
      $row_attack = pg_fetch_assoc($result_getattack);
      $dianame = $row_attack['name'];
      $attackname = str_replace("Dialogue", "", $dianame);

      $title .= " ($attackname)";
      $where .= " AND details.text = $dianame";
      $where .= " AND attacks.severity = 1 ";
    } else {
      $allattacks = 1;
    }
  }
}

########################
# Interval & Timestamps
########################
$interval = $clean['interval'];
if ($interval == 3600) {
  $title .= " per hour";
} elseif ($interval == 86400) {
  $title .= " per day";
} elseif ($interval == 604800) {
  $title .= " per week";
}
$tsstart = $clean['tsstart'];
$tsend = $clean['tsend'];
#$textstart = date("d-m-y H:m", $tsstart);
#$textend = date("d-m-y H:m", $tsend);
$textstart = $clean['tsstart'];
$textend = $clean['tsend'];
$tsstart = getepoch($tsstart);
$tsend = getepoch($tsend);
$tsperiod = $tsend - $tsstart;
#echo "INTERVAL: $interval<br />\n";
#echo "TSPERIOD: $tsperiod\n";
$tssteps = intval($tsperiod / $interval);

########################
# Type
########################
$type = $clean['type'];
$type = $v_plottertypes[$type];

########################
# Sensor ID
########################
if (@is_array($tainted["sensorid"])) {
  $count = count($tainted['sensorid']);
  if ($count == 1) {
    $sid = intval($tainted['sensorid'][0]);
    if ($sid == 0) {
      $groupbysensorid = "";
      $selectsensorid = "";
      $title .= " for all sensors";
      $allsensors = 1;
    } else {
      $groupbysensorid = "sensors.keyname, ";
      $selectsensorid = "sensors.keyname, ";
      $sql_gs = "SELECT keyname FROM sensors WHERE id = $sid";
      $result_gs = pg_query($pgconn, $sql_gs);
      $row_gs = pg_fetch_assoc($result_gs);
      $keyname = $row_gs['keyname'];
      $title .= " for $keyname";
      $where .= " AND sensors.id = $sid";
    }
  } else {
    $groupbysensorid = "sensors.keyname, ";
    $selectsensorid = "sensors.keyname, ";
    $title .= " for sensors: ";
    $where .= " AND sensors.id IN (";
    $check = 0;
    foreach ($tainted['sensorid'] as $sid) {
      $check++;
      $sid = intval($sid);
      $sql_gs = "SELECT keyname FROM sensors WHERE id = $sid";
      $result_gs = pg_query($pgconn, $sql_gs);
      $row_gs = pg_fetch_assoc($result_gs);
      $keyname = $row_gs['keyname'];
      if ($check != count($tainted['sensorid'])) {
        $where .= $sid .", ";
        $title .= "$keyname, ";
      } else {
        $where .= $sid;
        $title .= $keyname;
      }
    }
    $where .= ") ";
  }
} else {
  $sid = intval($tainted['sensorid']);
  if ($sid == 0) {
    $groupbysensorid = "";
    $selectsensorid = "";
    $title .= " for all sensors";
    $allsensors = 1;
  } else {
    $groupbysensorid = "sensors.keyname, ";
    $selectsensorid = "sensors.keyname, ";
    $sql_gs = "SELECT keyname FROM sensors WHERE id = $sid";
    $result_gs = pg_query($pgconn, $sql_gs);
    $row_gs = pg_fetch_assoc($result_gs);
    $keyname = $row_gs['keyname'];
    $title .= " for $keyname";
    $where .= " AND sensors.id = $sid";
  }
}

$title .= "\n From $textstart to $textend";

##############
# PHPlot stuff
##############
require_once '/usr/share/phplot/phplot.php';  // here we include the PHPlot code 
$plot =& new PHPlot(990,600);    // here we define the variable
$plot->SetTitle($title);
$plot->SetXTitle('Time');
$plot->SetYTitle('Attacks');
$plot->SetPlotType($type);

$data = array();
$i = 0;

$check_ar = array();
$maxcount = 0;

while ($i != $tssteps) {
  $a = $i;
  $i++;
  $point = array();
  $sql = "SELECT DISTINCT $selectattacks $selectsensorid attacks.severity, COUNT(attacks.severity) as total FROM attacks, sensors, details ";
  $sql .= "WHERE details.attackid = attacks.id AND timestamp >= $tsstart + ($interval * $a) AND timestamp <= $tsstart + ($interval * $i) ";
  $sql .= "AND sensors.id = attacks.sensorid $where ";
  $sql .= "GROUP BY $groupbysensorid $groupbyattacks attacks.severity ORDER BY severity";
#  printer($sql);
  $debuginfo[] = $sql;

  $result = pg_query($pgconn, $sql);
  $numrows = pg_num_rows($result);
  if ($numrows != 0) {
    $date = $tsstart + ($interval * $a);
    if ($interval == 3600) {
      $datestring = date("d-m-y", $date) . "\n " . date("H", $date) . ":00";
    } elseif ($interval == 86400) {
      $datestring = date("d-m-y", $date);
    } elseif ($interval == 604800) {
      $datestring = "Week\n" .date("W", $date);
    }
    $point[] = $datestring;
    while($row = pg_fetch_assoc($result)) {
      $count = $row['total'];
      $sev = $row['severity'];
      if ($allsev == 1 && $allattacks == 0) {
        $type = $row['text'];
      }
      $legend = "";
      if ($maxcount < $count) {
        $maxcount = $count;
      }
      if ($allsensors == 0) {
        $keyname = $row['keyname'];
        $legend .= $keyname;
      } else {
        $legend .= "All sensors";
      }
      if ($allattacks == 0 && $allsev == 1) {
        $attack = str_replace("Dialogue", "", $row['text']);
        $legend .= " - ". $attack;
      }
      $legend .= " - " .$severity_ar[$sev];
      if (!in_array($legend, $check_ar)) {
        $check_ar[] = $legend;
        $plot->SetLegend($legend);
      }
      $point[$legend] = $count;
    }
    $data[] = $point;
  }
}

#printer($data);

#debug_sql();

if (!empty($data)) {
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

#  printer($pertick);
#  printer($ytick);

  $plot->SetYTickIncrement($ytick);
  $plot->SetDataValues($data);
  #$plot->SetXTickLabelPos('none');
  #$plot->SetXTickPos('none');
  $plot->DrawGraph();
}
?>
