<?php include("menu.php"); set_title("Sensor Status"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.10                  #
# 08-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.10 Removed intval() for $s_access
# 1.02.09 intval() for $s_org, $s_admin and $s_access
# 1.02.08 Added some more input checks
# 1.02.07 Change the way SSH remote control is handled
# 1.02.06 Initial release
#############################################

include 'include/config.inc.php';
include 'include/connect.inc.php';
include 'include/functions.inc.php';
include 'include/variables.inc.php';

$orderby = "ORDER BY keyname ASC";
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = $s_access{0};

# Link tables tap and mac with the associated sensor
if ($s_access_sensor < 9) {
  $where = "WHERE organisation = " . $s_org;
  $and = "AND";
}
else {
  $where = "";
  $and = "WHERE";
}

if (isset($_GET['sort'])) {
  $sorterr = 0;
  $sort = pg_escape_string(stripinput($_GET['sort']));
  if ($sort == "tap") {
    $orderby = "ORDER BY tap ASC";
  }
  elseif ($sort == "lastupdate") {
    $orderby = "ORDER BY lastupdate ASC";
  }
  elseif ($sort == "laststart") {
    $orderby = "ORDER BY laststart ASC";
  }
  elseif ($sort == "sensor") {
    $orderby = "ORDER BY keyname ASC";
  }
  else {
    $sorterr = 1;
  }
}

if (isset($_GET['selview'])) {
  $selview = intval($_GET['selview']);
}

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  if (isset($_GET['key'])) {
    $key = stripinput($_GET['key']);
  }

  if ($m == 1) { $m = "<p>Successfully updated status info!</p>"; }
  elseif ($m == 90) { $m = "<p>This is a read-only account. Remote administration of the sensor is not possible!</p>"; }
  elseif ($m == 91) { $m = "<p>IP address for $key is already in use. Changes not saved!</p>"; }
  elseif ($m == 92) { $m = "<p>Incorrect IP address for $key. Changes not saved!</p>"; }

#  echo "<font color='red'>" .$m. "</font>";
}
echo "<table width='100%'>\n";
  echo "<tr>\n";
    if (isset($_GET['m'])) {
      echo "<td valign='top'><font color='red'>$m</font></td>\n";
    } else {
      echo "<td></td>\n";
    }
    echo "<td>\n";
      echo "<form name='viewform' action='sensorstatus.php?sort=$sort' method='GET'>\n";
        echo "<table width='100%' id='sensortable'>\n";
          echo "<tr>\n";
            echo "<td align='right'>\n";
              echo "<select name='selview' onChange='javascript: this.form.submit();'>\n";
                echo "" . printOption(0, "View all sensors", $selview) . "<br />\n";
                echo "" . printOption(1, "View offline sensors", $selview) . "<br />\n";
                echo "" . printOption(2, "View online sensors", $selview) . "<br />\n";
                echo "" . printOption(3, "View outdated sensors", $selview) . "<br />\n";
              echo "</select>\n";
            echo "</td>\n";
          echo "</tr>\n";
        echo "</table>\n";
      echo "</form>\n";
    echo "</td>\n";
  echo "</tr>\n";
echo "</table>\n";


if ($sorterr == 0) {
  if ($selview == "0") {
    $sql_sensors = "SELECT * FROM sensors $where $orderby";
  }
  elseif ($selview == "1") {
    $sql_sensors = "SELECT * FROM sensors $where $and status = 0 $orderby";
  }
  elseif ($selview == "2") {
    $sql_sensors = "SELECT * FROM sensors $where $and status = 1 $orderby";
  }
  elseif ($selview == "3") {
    $now = time();
    $upd = $now - 3600;
    $sql_sensors = "SELECT * FROM sensors $where $and lastupdate < $upd AND NOT status = 0 $orderby";
  }
  else {
    $sql_sensors = "SELECT * FROM sensors $where $orderby";
  }
  $result_sensors = pg_query($pgconn, $sql_sensors);

  if ($s_access_sensor > 0) {
    echo "<form name='rebootform' method='post' action='updateaction.php?selview=$selview'>\n";
  }
  echo "<table class='datatable' width='100%'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader'><a href='sensorstatus.php?sort=sensor'>Sensor</a></td>\n";
      echo "<td class='dataheader'>Remote Address</td>\n";
      echo "<td class='dataheader'>Local Address</td>\n";
      echo "<td class='dataheader'><a href='sensorstatus.php?sort=tap'>Tap Device</a></td>\n";
      echo "<td class='dataheader'>Tap Device MAC</td>\n";
      echo "<td class='dataheader'>Tap IP Address</td>\n";
      echo "<td class='dataheader'>Timestamps</td>\n";
      echo "<td class='dataheader'>Status</td>\n";
      if ($s_access_sensor == 9) {
        echo "<td class='dataheader'>Organisation</td>\n";
      }
      if ($s_access_sensor > 0) {
        echo "<td class='dataheader'>Action</td>\n";
      }
    echo "</tr>\n";

  while ($row = pg_fetch_assoc($result_sensors)) {
    $now = time();
    $sensor = $row['keyname'];
    $remote = $row['remoteip'];
    $local = $row['localip'];
    $tap = $row['tap'];
    $tapip = $row['tapip'];
    $update = $row['lastupdate'];
    $start = $row['laststart'];
    $laststop = $row['laststop'];
    $mac = $row['mac'];
    $action = $row['action'];
    $ssh = $row['ssh'];
    $status = $row['status'];
    $uptime = $row['uptime'];
    $server = $row['server'];
    $netconf = $row['netconf'];
    $laststart = "";
    $lastupdate = "";
    $diffstart = 0;
    $diffupdate = 0;
    if ($update != "") {
      $lastupdate = date("d-m-Y H:i:s", $update);
      $diffupdate = $now - $update;
    }
    if (!empty($start)) {
      $laststart = date("d-m-Y H:i:s", $start);
      $diffstart = $now - $start;
    }
    if (!empty($laststop)) {
      $laststop = date("d-m-Y H:i:s", $laststop);
    }
    if ($s_access_sensor == 9) {
      $org = $row['organisation'];
      $sql_getorg = "SELECT organisation FROM organisations WHERE id = " .$org;
      $result_getorg = pg_query($pgconn, $sql_getorg);
      $org = pg_result($result_getorg, 0);
    }

    echo "<tr>\n";
      echo "<td class='datatd' valign='top' style='padding-top: 10px;'><a href='trafficview.php?view=$sensor'>$sensor</a></td>\n";
      echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$remote</td>\n";
      echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$local</td>\n";
      # Tap device
      if ($tap == "") {
        echo "<td class='datatd' valign='top' style='padding-top: 10px;'>&nbsp;</td>\n";
      } else {
        echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$tap</td>\n";
      }
      # Mac address
      echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$mac</td>\n";
      # Tap IP address
      if ($netconf == "dhcp" || $netconf == "") {
        if (empty($tapip)) {
           echo "<td class='datatd' valign='top' style='padding-top: 10px;'>&nbsp;</td>\n";
        }
        else {
          echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$tapip</td>\n";
        }
      }
      else {
        echo "<td class='datatd' valign='top' style='padding-top: 0px;' align='center'>static<br />\n";
          if ($s_access_sensor == 0) {
            echo "<input type='text' name='tapip_$sensor' value='$tapip' size='14' class='sensorinput' disabled />\n";
          } else {
            echo "<input type='text' name='tapip_$sensor' value='$tapip' size='14' class='sensorinput' />\n";
          }
        echo "</td>\n";
      }
      if ( $status == 1) {
        $uptime = $diffstart + $uptime;
      }
      $onehour = 60 * 60;
      $oneday = $onehour * 24;

      $days = floor($uptime / $oneday);
      $uptime = $uptime % $oneday;
      $hours = floor($uptime / $onehour);
      $uptime = $uptime % $onehour;
      $minutes = floor($uptime / 60);
      $seconds = $uptime % 60;
#      if ($status != 1) {
#        echo "<td class='datatd'>&nbsp;</td>\n";
#      }
#      else {
        echo "<td class='datatd'>\n";
          echo "<table width='100%' >\n";
            echo "<tr class='datatr'>\n";
              echo "<td class='datatd' width='40'>Uptime</td>\n";
              echo "<td>${days}d ${hours}h ${minutes}m ${seconds}s</td>\n";
              echo "<td align='right'><img id='time_${sensor}_img' src='${address}images/plus.gif' style='cursor:pointer;' title='Click to view/hide extra info.' onclick=\"changeId('time_$sensor');\" \></td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "<table id='time_$sensor' style='display:none;'>\n";
            echo "<tr class='datatr'>\n";
              echo "<td class='datatd' width='40'>Start</td><td width='100%'>$laststart</td>\n";
            echo "</tr>\n";
            echo "<tr class='datatr'>\n";
              echo "<td class='datatd'>Stop</td><td>$laststop</td>\n";
            echo "</tr>\n";
            echo "<tr class='datatr'>\n";
              echo "<td class='datatd'>Update</td><td>$lastupdate</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</td>\n";
#      }
    
      if ($status == 0) {
        echo "<td class='datatd' bgcolor='red'>&nbsp;</td>\n";
      }
      elseif ($diffupdate <= 3600 && $status == 1 && !empty($tap)) {
        echo "<td class='datatd' bgcolor='green'>&nbsp;</td>\n";
      }
      elseif ($diffupdate > 3600 && $status == 1) {
        echo "<td class='datatd' bgcolor='orange'>&nbsp;</td>\n";
      }
      elseif ($status == 1 && empty($tap)) {
        echo "<td class='datatd' bgcolor='yellow'>&nbsp;</td>\n";
      }
      elseif ($status == 2) {
        echo "<td class='datatd' bgcolor='black'>&nbsp;</td>\n";
      }
      else {
        echo "<td class='datatd' bgcolor='red'>&nbsp;</td>\n";
      }
      if ($s_access_sensor == 9) {
        echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$org</td>\n";
      }
      if ($s_access_sensor > 0) {
        echo "<td class='datatd' valign='top' style='padding-top: 10px;'>\n";
###################################
          echo "<select name='f_${sensor}' style='width:100%;'>\n";
            echo "" . printOption("NONE", "None", $action) . "\n";
            echo "" . printOption("REBOOT", "Reboot", $action) . "\n";
            if ($ssh == 1) {
              echo "" . printOption("SSHOFF", "SSH off", $action) . "\n";
            }
            else {
              echo "" . printOption("SSHON", "SSH on", $action) . "\n";
            }
            if ($status == 1) {
              echo "" . printOption("CLIENT", "Stop", $action) . "\n";
              echo "" . printOption("RESTART", "Restart", $action) . "\n";
            }
            elseif ($status == 0) {
              echo "" . printOption("CLIENT", "Start", $action) . "\n";
              echo "" . printOption("BLOCK", "Disable", $action) . "\n";
            }
            elseif ($status == 2) {
              echo "" . printOption("BLOCK", "Enable", $action) . "\n";
            }
          echo "</select>\n";
#################################
        echo "</td>\n";
      }
    echo "</tr>\n";
  }

  if ($s_access_sensor > 0) {
    echo "<tr>\n";
      echo "<td colspan='12' class='datatd' align='right'><input type='submit' name='submit' value='Update' class='button' /></td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
  if ($s_access_sensor > 0) {
    echo "</form>\n";
  }
  echo "<br />\n";
  echo "<table>\n";
    echo "<tr>\n";
      echo "<td width='2' bgcolor='red'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
      echo "<td>Sensor not active</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td width='2' bgcolor='orange'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
      echo "<td>Sensor not up to date</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td width='2' bgcolor='yellow'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
      echo "<td>Sensor starting up</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td width='2' bgcolor='green'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
      echo "<td>Sensor active</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
      echo "<td width='2' bgcolor='black'>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
      echo "<td>Sensor disabled by admin</td>\n";
    echo "</tr>\n";
  echo "</table>\n";
}
else {
  echo "Error in sort querystring.<br />\n";
  echo "<a href='sensorstatus.php'>Back</a>\n";
}
pg_close($pgconn);
?>

<?php footer(); ?>
