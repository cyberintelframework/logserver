<?php include("menu.php"); set_title("ARP Admin");  ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.04                  #
# 09-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.04 Changed access handling + intval() for session variables
# 1.02.03 Added some more input checks and removed unecessary includes
# 1.02.02 Enhanced debugging
# 1.02.01 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  # arpadd.php
  if ($m == 10) { $m = '<p>Successfully added a new static ARP entry!</p>'; }
  elseif ($m == 90) { $m = '<p>Not enough rights to access this page!</p>'; }
  elseif ($m == 91) { $m = '<p>The MAC address field was empty!</p>'; }
  elseif ($m == 92) { $m = '<p>The IP address field was empty!</p>'; }
  elseif ($m == 93) { $m = '<p>The MAC address was not a valid MAC address!</p>'; }

  # arpsave.php
  elseif ($m == 11) { $m = '<p>Successfully changed this static ARP entry!</p>'; }
  elseif ($m == 80) { $m = '<p>Not enough rights to access this page!</p>'; }
  elseif ($m == 81) { $m = '<p>The MAC address field was empty!</p>'; }
  elseif ($m == 82) { $m = '<p>The IP address field was empty!</p>'; }
  elseif ($m == 83) { $m = '<p>The MAC address was not a valid MAC address!</p>'; }
  elseif ($m == 84) { $m = '<p>You are not the owner of this record!</p>'; }
  elseif ($m == 85) { $m = '<p>The type of action was not set!</p>'; }
  elseif ($m == 86) { $m = '<p>No record found with this id!</p>'; }
  elseif ($m == 87) { $m = '<p>Invalid IP address!</p>'; }

  # arpdel.php
  elseif ($m == 12) { $m = '<p>Successfully deleted the static ARP entry!</p>'; }
  elseif ($m == 70) { $m = '<p>Not enough rights to access this page!</p>'; }
  elseif ($m == 71) { $m = '<p>The ARP ID was not given!</p>'; }
  elseif ($m == 72) { $m = '<p>You are not the owner of that ARP entry!</p>'; }
  elseif ($m == 73) { $m = '<p>There was no ARP entry with this ID!</p>'; }

  # arpsettings.php
  elseif ($m == 13) { $m = '<p>Successfully saved the ARP settings!</p>'; }
  elseif ($m == 61) { $m = '<p>The ARP ID was not given!</p>'; }

  # else
  else { $m = '<p>Unknown error. Try again and hope for the best...!</p>'; }

  echo "<font color='red'>" .$m. "</font>";
}

if ($s_access_sensor > 1) {
  if ($s_access_sensor == 9) {
    $sql_arp = "SELECT arp_static.*, sensors.keyname FROM arp_static, sensors WHERE sensors.id = arp_static.sensorid";
    $sql_sensor = "SELECT id, keyname, arp, arp_threshold_perc FROM sensors ORDER BY keyname ASC";
  }
  else {
    $sql_arp = "SELECT arp_static.*, sensors.keyname FROM arp_static, sensors WHERE sensors.id = arp_static.sensorid AND sensors.organisation = $s_org";
    $sql_sensor = "SELECT id, keyname, arp, arp_threshold_perc FROM sensors WHERE sensors.organisation = $s_org ORDER BY keyname ASC";
  }

  echo "Enable/Disable ARP monitoring here. The threshold is a percentage of the average ARP traffic measured on the sensor.<br /><br />\n";
  echo "<form name='arpsettings' action='arpsettings.php' method='post'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'>Sensor</td>\n";
      echo "<td width='150' class='dataheader'>ARP Monitoring</td>\n";
      echo "<td width='100' class='dataheader'>Threshold %</td>\n";
    echo "</tr>\n";

    $result_sensor = pg_query($pgconn, $sql_sensor);
    while ($row = pg_fetch_assoc($result_sensor)) {
      $id = $row['id'];
      $keyname = $row['keyname'];
      $arp = $row['arp'];
      $threshold = $row['arp_threshold_perc'];
      echo "<tr>\n";
        echo "<td class='datatd'>$keyname</td>\n";
        echo "<td class='datatd'>";
          echo "<select name='arp_$id' style='width: 100%;'>\n";
            echo printOption(0, "Disabled", $arp);
            echo printOption(1, "Enabled", $arp);
          echo "</select>\n";
        echo "</td>\n";
        echo "<td class='datatd'><input type='text' name='threshold_$id' value='$threshold' size='10'>&nbsp;%</td>\n";
      echo "</tr>\n";
    }
    echo "<tr>\n";
      echo "<td class='datatd' colspan='3' align='right'><input type='submit' value='Save' class='button' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";

  echo "This is a listing of MAC - IP pairs.<br />\n";
  echo "These MAC addresses will be monitored and checked against the supplied IP's to enable detection of ARP anomalies (ie ARP poisoning).<br /><br />\n";
  echo "<form name='arpmonitor' action='arpsave.php?a=i' method='post'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'>MAC address</td>\n";
      echo "<td width='150' class='dataheader'>IP address</td>\n";
      echo "<td width='100' class='dataheader'>Sensor</td>\n";
      echo "<td width='50' class='dataheader'>Modify</td>\n";
      echo "<td width='50' class='dataheader'>Delete</td>\n";
    echo "</tr>\n";

    # Debug info
    if ($debug == 1) {
      echo "<pre>";
      echo "$sql_arp";
      echo "</pre>\n";
    }

    $result_arp = pg_query($pgconn, $sql_arp);
    while ($row = pg_fetch_assoc($result_arp)) {
      $id = $row['id'];
      $mac = $row['mac'];
      $ip = $row['ip'];
      $sensor = $row['keyname'];
      echo "<tr>\n";
        echo "<td class='datatd'>$mac</td>\n";
        echo "<td class='datatd'>$ip</td>\n";
        echo "<td class='datatd'>$sensor</td>\n";
        echo "<td class='datatd'><a href='arpedit.php?arpid=$id'>Modify</a></td>\n";
        echo "<td class='datatd'><a href='arpdel.php?arpid=$id' onclick=\"javascript: return confirm('Are you sure you want to delete this ARP entry?');\">Delete</a></td>\n";
      echo "</tr>\n";
    }
    echo "<tr class='datatr'>\n";
      echo "<td class='datatd'><input type='text' name='f_mac' size='20' /></td>\n";
      echo "<td class='datatd'><input type='text' name='f_ip' size='20' /></td>\n";
      echo "<td class='datatd'>\n";
        echo "<select name='f_sensor' style='width: 100%;'>\n";
          $result_sensor = pg_query($sql_sensor);
          while ($sensor_data = pg_fetch_assoc($result_sensor)) {
            echo printOption($sensor_data["id"], $sensor_data["keyname"], $sensorid);
          }
        echo "</select>\n";
      echo "</td>\n";
      echo "<td class='datatd' colspan='2'><input class='button' type='submit' value='Insert' style='width: 100%;' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";
}
pg_close($pgconn);

footer();
?>

