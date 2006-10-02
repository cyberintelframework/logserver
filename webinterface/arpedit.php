<?php include("menu.php"); set_title("ARP Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.08                  #
# 09-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.08 changed access handling + intval() for session variables
# 1.02.07 Added some more input checks + removed includes
# 1.02.06 Enhanced debugging
# 1.02.05 Fixed a userid bug
# 1.02.04 Automatic table creation if user doesn't have mailreporting record
# 1.02.03 Extended mailreporting
# 1.02.02 Initial release
#############################################

# getting session variables
$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});
$err = 0;

# checking access
if ($s_access_sensor < 2) {
  $err = 1;
  $m = 90;
}

if (!isset($_GET['arpid'])) {
  $err = 1;
  $m = 91;
}
else {
  $arpid = intval($_GET['arpid']);
  if ($s_access_sensor != 9) {
    # if the user doesn't have sensor admin rights, check if his organisation owns the record
    $sql_arp = "SELECT * FROM arp_static, sensors WHERE arp_static.id = $arpid AND sensors.id = arp_static.sensor AND sensors.organisation = $s_org";
    $result_arp = pg_query($pgconn, $sql_arp);
    $numrows_arp = pg_num_rows($result_arp);
    if ($numrows_arp == 0) {
      $err = 1;
      $m = 92;
    }
  }
  # check if the record with arpid exists
  $sql_arp = "SELECT * FROM arp_static WHERE id = $arpid";
  $result_arp = pg_query($pgconn, $sql_arp);
  $numrows_arp = pg_num_rows($result_arp);
  if ($numrows_arp == 0) {
    $err = 1;
    $m = 93;
  }
}

# display error message if needed
if (isset($_GET['m'])) {
  $m = intval($_GET['m']);

  # arpsave.php
  if ($m == 11) { $m = '<p>Successfully changed this static ARP entry!</p>'; }
  elseif ($m == 81) { $m = '<p>The MAC address field was empty!</p>'; }
  elseif ($m == 82) { $m = '<p>The IP address field was empty!</p>'; }
  elseif ($m == 83) { $m = '<p>The MAC address was not a valid MAC address!</p>'; }

  # else
  else { $m = '<p>Unknown error. Try again and hope for the best...!</p>'; }

} else {
  # arpedit.php
  if ($m == 91) { $m = '<p>The ARP ID was not given!</p>'; }
  elseif ($m == 92) { $m = '<p>You are not the owner of this ARP entry!</p>'; }
  elseif ($m == 93) { $m = '<p>This ARP entry does not exist!</p>'; }

  echo "<font color='red'>" .$m. "</font>";
}

if ($err == 0) {
  $arpid = intval($_GET['arpid']);
  # check access and set query accordingly
  if ($s_access_sensor == 9) {
    $sql_arp = "SELECT * FROM arp_static WHERE id = $arpid";
    $sql_sensor = "SELECT id, keyname FROM sensors";
  } else {
    $sql_arp = "SELECT arp_static.* FROM arp_static, sensors WHERE arp_static.id = $arpid AND sensors.id = arp_static.sensor AND sensors.organisation = $s_org";
    $sql_sensor = "SELECT id, keyname FROM sensors WHERE organisation = $s_org";
  }
  $result_arp = pg_query($pgconn, $sql_arp);
  $row = pg_fetch_assoc($result_arp);

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "$sql_arp";
    echo "</pre>\n";
  }

  # generate form
  $id = $row['id'];
  $mac = $row['mac'];
  $ip = $row['ip'];
  $sensor = $row['sensorid'];

  echo "<form name='arpmonitor' action='arpsave.php?a=u' method='post'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'>MAC address</td>\n";
      echo "<td width='150' class='dataheader'>IP address</td>\n";
      echo "<td width='100' class='dataheader'>Sensor</td>\n";
    echo "</tr>\n";

    echo "<tr class='datatr'>\n";
      echo "<td class='datatd'><input type='hidden' name='f_id' value='$id' /><input type='text' name='f_mac' value='$mac' size='20' /></td>\n";
      echo "<td class='datatd'><input type='text' name='f_ip' value='$ip' size='20' /></td>\n";
      echo "<td class='datatd'>\n";
        echo "<select name='f_sensor' style='width: 100%;'>\n";
          $query_sensor = pg_query($sql_sensor);
          while ($sensor_data = pg_fetch_assoc($query_sensor)) {
            echo printOption($sensor_data["id"], $sensor_data["keyname"], $sensor);
          }
        echo "</select>\n";
      echo "</td>\n";
      echo "<td class='datatd' colspan='2'><input class='button' type='submit' value='Save' style='width: 100%;' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";  
}

footer();
?>
