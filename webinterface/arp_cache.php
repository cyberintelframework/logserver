<?php $tab="3.6"; $pagetitle="ARP Cache"; include("menu.php"); contentHeader(0); ?>
<?php

###################################
# SURFnet IDS                     #
# Version 2.00.02                 #
# 24-09-2007                      #
# Jan van Lith & Kees Trippelvitz #
###################################

#############################################
# Changelog:
# 2.00.03 Fixed a bug with clear and missing sensor ID's
# 2.00.02 Added access check
# 2.00.01 Initial release
#############################################

# Checking access
if ($s_access_sensor < 2) {
  $m = 101;
  geterror($m);
  footer();
  pg_close($pgconn);
  exit;
}
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_sid",
		"sort",
		"int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (!isset($clean['sid'])) {
  $err = 1;
} else {
  $sid = $clean['sid'];
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(maca|macd|ipa|ipd|keynamea|keynamed|manufacturera|manufacturerd|last_seena|last_seend|flagsa|flagsd)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
} else {
  $sql_sort = " mac ASC";
  $sort = "maca";
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($err == 0) {
  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>Actions</div>\n";
        echo "<div class='blockContent'>\n";
          $confirm = "Are you sure you want to clear the ARP cache?";
          echo "<a href='arp_cache_clr.php?int_org=$q_org&md5_hash=$s_hash&int_sid=$sid' onclick=\"javascript: return confirm('" .$confirm. "');\">";
          echo "Clear ARP cache</a>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</actionBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>
}

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>";
        echo "<div class='blockHeaderLeft'>ARP Cache</div>\n";
        echo "<div class='blockHeaderRight'>";
          echo "<form method='get'>\n";
            if ($q_org == 0 || $s_access_search == 9) {
              $sql_sensors = "SELECT sensors.id, keyname, vlanid, arp, status, label, organisations.organisation ";
              $sql_sensors .= " FROM sensors, organisations WHERE sensors.organisation = organisations.id ORDER BY tapip, keyname";
            } else {
              $sql_sensors = "SELECT id, keyname, vlanid, arp, status, label FROM sensors WHERE organisation = $q_org ORDER BY tapip, keyname";
            }
            $debuginfo[] = $sql_sensors;
            $result_sensors = pg_query($pgconn, $sql_sensors);

            echo "<select class='smallselect' name='int_sid' onChange='javascript: this.form.submit();'>\n";
              echo printOption("", "", $sid);
              while ($row = pg_fetch_assoc($result_sensors)) {
                $id = $row['id'];
                $keyname = $row['keyname'];
                $label = $row['label'];
		$vlanid = $row['vlanid'];
                $sensor = sensorname($keyname, $vlanid);
                if ($label != "") $sensor = $label;
                $status = $row['status'];
                $org = $row['organisation'];
                if ($org != "") {
                  echo printOption($id, "$sensor - $org", $sid, $status);
                } else {
                  echo printOption($id, $sensor, $sid, $status);
                }
              }
            echo "</select>\n";
          echo "</form>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";
        if ($err == 0) {
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='120'>" .printsort("MAC address", "mac"). "</th>\n";
              echo "<th width='100'>" .printsort("IP address", "ip"). "</th>\n";
              echo "<th width='50'>" .printsort("Type", "flags"). "</th>\n";
              echo "<th width='200'>" .printsort("NIC Manufacturer", "manufacturer"). "</th>\n";
              echo "<th width='100'>Sensor</th>\n";
              echo "<th width='140'>" .printsort("Last changed", "last_seen"). "</th>\n";
              echo "<th width='70'>Status</th>\n";
              echo "<th width='60'></th>\n";
            echo "</tr>\n";

            $sql_arp_static = "SELECT arp_static.id, arp_static.mac, arp_static.ip, sensors.keyname, sensors.vlanid ";
            $sql_arp_static .= " FROM arp_static, sensors";
            $sql_arp_static .= " WHERE sensors.id = arp_static.sensorid AND arp_static.sensorid = $sid ";
            if ($q_org != 0) {
              $sql_arp_static .= " AND sensors.organisation = $q_org ";
            }
            $debuginfo[] = $sql_arp_static;
            $result_arp_static = pg_query($pgconn, $sql_arp_static);

            while ($row_static = pg_fetch_assoc($result_arp_static)) {
              $mac = $row_static['mac'];
              $ip = $row_static['ip'];
              $static_arp["$ip"] = $mac;
            }

            $sql_arp_cache = "SELECT arp_cache.id, arp_cache.mac, ip, arp_cache.flags, sensors.keyname, ";
            $sql_arp_cache .= " sensors.vlanid, sensors.id as sid, arp_cache.last_seen, manufacturer ";
            $sql_arp_cache .= " FROM arp_cache, sensors WHERE arp_cache.sensorid = sensors.id ";
            if ($q_org != 0) {
              $sql_arp_cahce .= " AND sensors.organisation = $q_org ";
            }
            if ($sid != 0) {
              $sql_arp_cache .= " AND sensors.id = $sid ";
            }
            if ($sql_sort != "") {
              $sql_arp_cache .= " ORDER BY $sql_sort ";
            }
            $debuginfo[] = $sql_arp_cache;
            $result_arp_cache = pg_query($pgconn, $sql_arp_cache);

            while ($row_cache = pg_fetch_assoc($result_arp_cache)) {
              $id = $row_cache['id'];
              $mac = $row_cache['mac'];
              $ip = $row_cache['ip'];
              $flags = $row_cache['flags'];
              $sensor = $row_cache['keyname'];
              $vlanid = $row_cache['vlanid'];
              $sensorid = $row_cache['sid'];
              $man = $row_cache['manufacturer'];
              $lastseen = date("d-m-Y H:i:s", $row_cache['last_seen']);
              if ($vlanid != 0) {
                $sensor = "$sensor-$vlanid";
              }

              $poisoned = 0;
              if (!empty($static_arp["$ip"])) {
                if ($static_arp["$ip"] != $mac) {
                  $poisoned = 1;
                }
              }

              echo "<form action='arp_static_add.php' method='post' name='cache_" .$id. "'>\n";
                echo "<tr>\n";
                  if ($poisoned == 0) {
                    echo "<td>$mac<input type='hidden' name='mac_macaddr' value='$mac' /></td>\n";
                  } else {
                    echo "<td><font class='bwarning'>$mac</font><input type='hidden' name='mac_macaddr' value='$mac' /></td>\n";
                  }
                  echo "<td>$ip<input type='hidden' name='ip_ipaddr' value='$ip' /></td>\n";
                  if ("$flags" != "") {
                    $flags_ar = split(",", $flags);
                    $flagstring = "";
                    foreach ($flags_ar as $key => $val) {
                      $flagstring .= "<img src='images/hosttypes/$val.gif' onmouseover='return overlib(\"$v_host_types[$val]\");' onmouseout='return nd();' />&nbsp;";
                      echo "<input type='hidden' name='type[]' value='$val' />\n";
                    }
                    echo "<td>$flagstring</td>\n";
                  } else {
                    echo "<td></td>\n";
                  }
                  echo "<td>$man</td>\n";
                  echo "<td>$sensor<input type='hidden' name='int_sensor' value='$sensorid' /></td>\n";
                  echo "<td>$lastseen</td>\n";
                  if ($poisoned == 0) {
                    echo "<td><font class='bok'>OK</font></td>\n";
                    echo "<td><input type='submit' value='Add to static list' class='button' /></td>\n";
                  } else {
                    echo "<td><font class='bwarning'>Poisoned</font></td>\n";
                    echo "<td><input type='submit' value='Add to static list' class='button' disabled /></td>\n";
                  }
                echo "</tr>\n";
                echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                echo "<input type='hidden' name='int_sid' value='$sid' />\n";
              echo "</form>\n";
            }
          echo "</table>\n";
        } else {
          echo "<form method='get'>\n";
          echo "<table>";
            echo "<tr>";
              echo "<td><span class='warning'>Select a sensor</span></td>\n";
               echo "<td>\n";
                 $select_size = 8;
                 if ($q_org == 0) {
                   $sql_sensors = "SELECT sensors.id, keyname, vlanid, arp, status, label, organisations.organisation ";
                   $sql_sensors .= " FROM sensors, organisations WHERE sensors.organisation = organisations.id ORDER BY tapip, keyname";
                 } else {
                   $sql_sensors = "SELECT id, keyname, vlanid, arp, status, label FROM sensors WHERE organisation = $q_org ORDER BY tapip, keyname";
                 }
                 $debuginfo[] = $sql_sensors;
                 $result_sensors = pg_query($pgconn, $sql_sensors);
                 echo "<select name='int_sid' size='$select_size' class='smallselect' onChange='javascript: this.form.submit();'>\n";
                   while ($row = pg_fetch_assoc($result_sensors)) {
                     $id = $row['id'];
                     $keyname = $row['keyname'];
                     $label = $row['label'];
                     $vlanid = $row['vlanid'];
                     $sensor = sensorname($keyname, $vlanid);
                     if ($label != "") $sensor = $label;
                     $status = $row['status'];
                     $org = $row['organisation'];
                     if ($org != "") {
                       echo printOption($id, "$sensor - $org", $sid, $status);
                     } else {
                       echo printOption($id, $sensor, $sid, $status);
                     }
                   }
                 echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "</form>\n";
        }
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</centerbig>

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
