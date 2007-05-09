<?php include("menu.php"); set_title("Sensor Status"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.12                  #
# 16-04-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.12 Added ignore/unignore actions
# 1.04.11 Added new status code
# 1.04.10 Fixed sort bug
# 1.04.09 Changed printhelp stuff
# 1.04.08 Added help message for static IP addresses
# 1.04.07 Added config status, removed organisation query and added help links
# 1.04.06 Added censorip stuff
# 1.04.05 Fixed bug where 2 error messages where shown
# 1.04.04 Changed data input handling
# 1.04.03 Changed debug stuff
# 1.04.02 Added VLAN support 
# 1.04.01 Released as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.09 Added some more input checks and removed includes
# 1.02.08 Enhanced debugging
# 1.02.07 Change the way SSH remote control is handled
# 1.02.06 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

$allowed_get = array(
                "sort",
                "int_selview",
		"int_m",
		"strip_html_key"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($tainted['sort'])) {
  $sort = $tainted['sort'];
  $pattern = '/^(ka|kd|ta|td|oa|od)$/';
  if (!preg_match($pattern, $sort)) {
    $sort = "ka";
  }

  $type = $sort{0};
  $direction = $sort{1};
  if ($direction == "a") {
    $neworder = "d";
    $direction = "ASC";
  } else {
    $neworder = "a";
    $direction = "DESC";
  }
  if ($type == "k") {
    add_to_sql("ORDER BY keyname $direction", "order");
  } elseif ($type == "l") {
    add_to_sql("ORDER BY tap $direction", "order");
  } elseif ($type == "o") {
    add_to_sql("organisations.organisation $direction", "order");
  }
} else {
  $neworder = "d";
  add_to_sql("keyname ASC", "order");
}

if (isset($clean['selview'])) {
  $selview = $clean['selview'];
} elseif (isset($c_selview)) {
  $selview = intval($c_selview);
}

if (isset($clean['m'])) {
  $m = $clean['m'];

  if (isset($clean['key'])) {
    $pattern = '/^sensor[0-9]*$/';
    if (!preg_match($pattern, $key)) {
      $m = 103;
    } else {
      $key = $clean['key'];
    }
  }

  if ($m == 101) { $m = "<p>IP address for $key is already in use. Changes not saved!</p>"; }
  elseif ($m == 102) { $m = "<p>Incorrect IP address for $key. Changes not saved!</p>"; }
  elseif ($m == 103) { $m = "<p>Invalid sensor name returned!</p>"; }
  else { $m = geterror($m); }
}

echo "<table width='100%'>\n";
  echo "<tr>\n";
    if ($m) {
      echo "<td>$m</td>\n";
    } else {
      echo "<td></td>\n";
    }
    echo "<td>\n";
      echo "<form name='viewform' action='sensorstatus.php?sort=$sort' method='GET'>\n";
        echo "<table width='100%' id='sensortable'>\n";
          echo "<tr>\n";
            echo "<td align='right'>\n";
              echo "<select name='int_selview' onChange='javascript: this.form.submit();'>\n";
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

$or = "((netconf = 'vlans' OR netconf = 'static') AND tapip IS NULL AND NOT status = 3)";
add_to_sql("*", "select");
if ($selview == "1") {
  add_to_sql("(status = 0 OR $or)", "where");
} elseif ($selview == "2") {
  add_to_sql("(status = 1 OR $or)", "where");
} elseif ($selview == "3") {
  $now = time();
  $upd = $now - 3600;
  add_to_sql("((NOT status = 0", "where");
  add_to_sql("lastupdate < $upd) OR $or)", "where");
}

if ($s_access_sensor < 9) {
  add_to_sql("organisation = '$s_org'", "where");
} elseif ($s_access_sensor == 9) {
  add_to_sql("organisations", "table");
  add_to_sql("organisations.organisation", "select");
  add_to_sql("sensors.organisation = organisations.id", "where");
}

add_to_sql("sensors", "table");
prepare_sql();

$sql_sensors = "SELECT $sql_select ";
$sql_sensors .= " FROM $sql_from ";
$sql_sensors .= " $sql_where ";
$sql_sensors .= " ORDER BY $sql_order ";

$debuginfo[] = $sql_sensors;
$result_sensors = pg_query($pgconn, $sql_sensors);

echo "<table class='datatable' width='100%'>\n";
  echo "<tr class='datatr' align='center'>\n";
    echo "<td class='dataheader'><a href='sensorstatus.php?sort=k$neworder&int_selview=$selview'>Sensor</a></td>\n";
    echo "<td class='dataheader'>Remote Address</td>\n";
    echo "<td class='dataheader'>Local Address</td>\n";
    echo "<td class='dataheader'><a href='sensorstatus.php?sort=t$neworder&int_selview=$selview'>Tap Device</a></td>\n";
    echo "<td class='dataheader'>Tap Device MAC</td>\n";
    echo "<td class='dataheader'>Tap IP Address</td>\n";
    echo "<td class='dataheader'>Timestamps</td>\n";
    echo "<td class='dataheader'>Status</td>\n";
    if ($s_access_sensor == 9) {
      echo "<td class='dataheader'><a href='sensorstatus.php?sort=o$neworder'>Organisation</a></td>\n";
    }
    if ($s_access_sensor > 0) {
      echo "<td class='dataheader'>Action</td>\n";
    }
  echo "</tr>\n";
  if ($c_showhelp == 1) {
    echo "<tr align='center'>\n";
      echo "<td class='dataheader'>" .printhelp("sensor"). "</td>\n";
      echo "<td class='dataheader'>" .printhelp("remote"). "</td>\n";
      echo "<td class='dataheader'>" .printhelp("local"). "</td>\n";
      echo "<td class='dataheader'>" .printhelp("tap"). "</a></td>\n";
      echo "<td class='dataheader'>" .printhelp("tapmac"). "</td>\n";
      echo "<td class='dataheader'>" .printhelp("tapip"). "</td>\n";
      echo "<td class='dataheader'>" .printhelp("timestamps"). "</td>\n";
      echo "<td class='dataheader'>" .printhelp("status"). "</td>\n";
      if ($s_access_sensor == 9) {
        echo "<td class='dataheader'></td>\n";
      }
      if ($s_access_sensor > 0) {
        echo "<td class='dataheader'>" .printhelp("action"). "</td>\n";
      }
    echo "</tr>\n";
  }

  while ($row = pg_fetch_assoc($result_sensors)) {
    $now = time();
    $sid = $row['id'];
    $sensor = $row['keyname'];
    $remote = $row['remoteip'];
    $local = $row['localip'];
    $tap = $row['tap'];
    $tapip = censorip($row['tapip']);
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
    $vlanid = $row['vlanid'];
    if ($vlanid != 0) {
      $sensor = "$sensor-$vlanid";
    }
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
    }

    echo "<form name='rebootform' method='post' action='updateaction.php?int_selview=$selview'>\n";
      echo "<tr>\n";
        echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$sensor</td>\n";
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
          } else {
            echo "<td class='datatd' valign='top' style='padding-top: 10px;'><center>$tapip</center></td>\n";
          }
        } elseif ($netconf == "vland") {
          if (empty($tapip)) {
            echo "<td class='datatd' valign='top' style='padding-top: 0px;' align='center'>VLAN DHCP<br />\n";
            echo "&nbsp;\n";
          } else {
            echo "<td class='datatd' valign='top' style='padding-top: 0px;' align='center'>VLAN DHCP<br />\n";
            echo "$tapip\n";
          }
        } elseif ($netconf == "vlans") {
          echo "<td class='datatd' valign='top' style='padding-top: 0px;' align='center'>VLAN static ";
          echo printhelp("static");
          echo "<br />\n";
            if ($s_access_sensor == 0) {
              echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' disabled />\n";
	    } else {
              echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' />\n";
            }
          echo "</td>\n";
        } else {
          echo "<td class='datatd' valign='top' style='padding-top: 0px;' align='center'>static ";
          echo printhelp("static");
          echo "<br />\n";
            if ($s_access_sensor == 0) {
              echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' disabled />\n";
            } else {
              echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' />\n";
            }
          echo "</td>\n";
        }
        if ($status == 1) {
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

        echo "<td class='datatd'>\n";
          echo "<table width='100%' >\n";
            echo "<tr class='datatr'>\n";
              echo "<td class='datatd' width='40'>Uptime</td>\n";
              echo "<td>${days}d ${hours}h ${minutes}m ${seconds}s</td>\n";
              echo "<td align='right'><img id='time_${sensor}-${vlanid}_img' src='${address}images/plus.gif' style='cursor:pointer;' title='Click to view/hide extra info.' onclick=\"changeId('time_$sensor-$vlanid');\" \></td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "<table id='time_$sensor-$vlanid' style='display:none;'>\n";
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

        if ($status == 3) {
          echo "<td class='datatd'>&nbsp;</td>\n";
        } elseif (($netconf == "vlans" || $netconf == "static") && (empty($tapip) || $tapip == "")) {
          echo "<td class='datatd' bgcolor='blue'>&nbsp;</td>\n";
        } elseif ($status == 0) {
          echo "<td class='datatd' bgcolor='red'>&nbsp;</td>\n";
        } elseif ($diffupdate <= 3600 && $status == 1 && !empty($tap)) {
          echo "<td class='datatd' bgcolor='green'>&nbsp;</td>\n";
        } elseif ($diffupdate > 3600 && $status == 1) {
          echo "<td class='datatd' bgcolor='orange'>&nbsp;</td>\n";
        } elseif ($status == 1 && empty($tap)) {
          echo "<td class='datatd' bgcolor='yellow'>&nbsp;</td>\n";
        } elseif ($status == 2) {
          echo "<td class='datatd' bgcolor='black'>&nbsp;</td>\n";
        } else {
          echo "<td class='datatd' bgcolor='red'>&nbsp;</td>\n";
        }
        if ($s_access_sensor == 9) {
          echo "<td class='datatd' valign='top' style='padding-top: 10px;'>$org</td>\n";
        }
        if ($s_access_sensor > 0) {
          echo "<td class='datatd' valign='top' style='padding-top: 10px;'>\n";
            echo "<input type='hidden' name='int_vlanid' value='$vlanid' />\n";
            echo "<input type='hidden' name='int_sid' value='$sid' />\n";
            echo "<select name='action' style='width:100%;'>\n";
              echo "" . printOption("NONE", "None", $action) . "\n";
              echo "" . printOption("REBOOT", "Reboot", $action) . "\n";
              if ($ssh == 1) {
                echo "" . printOption("SSHOFF", "SSH off", $action) . "\n";
              } else {
                echo "" . printOption("SSHON", "SSH on", $action) . "\n";
              }
              echo "" . printOption("STOP", "Stop", $action) . "\n";
              echo "" . printOption("START", "Start", $action) . "\n";
              echo "" . printOption("DISABLE", "Disable", $action) . "\n";
              echo "" . printOption("ENABLE", "Enable", $action) . "\n";
              echo "" . printOption("IGNORE", "Ignore", $action) . "\n";
              echo "" . printOption("UNIGNORE", "Unignore", $action) . "\n";
            echo "</select>\n";
            echo "<td colspan='12' class='datatd' align='right'>\n";
              echo "<input type='submit' name='submit' value='Update' class='button' />";
            echo "</td>\n";
          echo "</td>\n";
        }
      echo "</tr>\n";
    echo "</form>\n";
  }
echo "</table>\n";

echo "<br />\n";

echo "<table>\n";
  echo "<tr>\n";
    echo "<td bgcolor='red' align='center'>" .printhelp("statusred"). "&nbsp;</td>\n";
    echo "<td>Sensor not active</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td bgcolor='orange' align='center'>" .printhelp("statusorange"). "&nbsp;</td>\n";
    echo "<td>Sensor not synchronized but active</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td bgcolor='yellow' align='center'>" .printhelp("statusyellow"). "&nbsp;</td>\n";
    echo "<td>Sensor starting up</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td bgcolor='green' align='center'>" .printhelp("statusgreen"). "&nbsp;</td>\n";
    echo "<td>Sensor active</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td bgcolor='blue' align='center'>" .printhelp("statusblue"). "&nbsp;</td>\n";
    echo "<td>Sensor needs configuration</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td bgcolor='black' align='center'><font color='white'>" .printhelp("statusblack"). "&nbsp;</font></td>\n";
    echo "<td>Sensor disabled by admin</td>\n";
  echo "</tr>\n";
  echo "<tr>\n";
    echo "<td style='border: 1px solid black;' align='center'>" .printhelp("statusnone"). "&nbsp;</td>\n";
    echo "<td>Sensor ignored by network config</td>\n";
  echo "</tr>\n";
echo "</table>\n";

debug_sql();
?>

<?php footer(); ?>

