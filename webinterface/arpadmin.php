<?php include("menu.php"); set_title("ARP Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.05                  #
# 16-07-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 1.04.05 Fixed a bug with flagstring
# 1.04.04 Split the different selector forms
# 1.04.03 Fixed a bug with the sensor selector
# 1.04.02 Added manufacturer stuff
# 1.04.01 Initial release
####################################

$s_org = intval($_SESSION['s_org']);
$s_access_sensor = intval($s_access{0});
$s_hash = md5($_SESSION['s_hash']);
$url = $_SERVER['REQUEST_URI'];

$allowed_get = array(
	"int_m",
	"int_filter",
	"int_org",
	"sort",
	"int_arp",
	"int_sensor"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $sort = $tainted['sort'];
  $url = str_replace("&sort=" . $sort, "", $url);
  $pattern = '/^(ida|idd|maca|macd|ipa|ipd|keynamea|keynamed|last_seena|last_seend|manufacturera|manufacturerd)$/';
  if (!preg_match($pattern, $sort)) {
    $sort = "ida";
  }

  $sorttype = substr($sort, 0, (strlen($sort) - 1));
  $dir = substr($sort, -1, 1);
  if ($dir == "a") {
    $neworder = "d";
    $dir = "ASC";
  } else {
    $neworder = "a";
    $dir = "DESC";
  }
} else {
  $neworder = "d";
  $sorttype = "id";
  $dir = "ASC";
}

# URL check
$count = substr_count($url, "?");
if ($count == 0) {
  $op = "?";
} else {
  $op = "&";
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

if (isset($clean['filter'])) {
  $filter = $clean['filter'];
} else {
  $filter = 0;
}

if (isset($clean['arp'])) {
  if (isset($clean['sensor'])) {
    $tempsensor = $clean['sensor'];
  } else {
    $tempsensor = 0;
  }
  if ($tempsensor != 0 && $filter == $tempsensor) {
    $arp = $clean['arp'];
    if ($s_access_sensor == 9) {
      if (isset($clean['org'])) {
        $q_org = $clean['org'];
      } else {
        $q_org = $s_org;
      }
    } else {
      $q_org = $s_org;
    }
    $sql_setarp = "UPDATE sensors SET arp = $arp WHERE organisation = $q_org AND id = $tempsensor";
    $debuginfo[] = $sql_setarp;
    $result_setarp = pg_query($pgconn, $sql_setarp);
    if ($arp == 1) {
      $m = 4;
    } else {
      $m = 5;
    }
    $m = geterror($m);
    echo $m;
  }
}

$url = ltrim($url, "/");

if ($s_access_sensor > 1) {
  echo "<div style='float: left;'>\n";
  echo "<form name='orgselector' action='$url' method='get'>\n";
    if ($s_access_sensor == 9) {
      if (isset($clean['org'])) {
        $q_org = $clean['org'];
      } else {
        $q_org = $s_org;
      }
      $sql_orgs = "SELECT id, organisation FROM organisations WHERE NOT organisation = 'ADMIN' ORDER BY organisation";
      $debuginfo[] = $sql_orgs;
      $result_orgs = pg_query($pgconn, $sql_orgs);
      echo "<select name='int_org' onChange='javascript: this.form.submit();'>\n";
        echo printOption(0, "All", $q_org) . "\n";
        while ($row = pg_fetch_assoc($result_orgs)) {
          $org_id = $row['id'];
          $organisation = $row['organisation'];
          echo printOption($org_id, $organisation, $q_org) . "\n";
        }
      echo "</select>&nbsp;\n";
    } else {
      $q_org = $s_org;
    }
  echo "</form>\n";
  echo "</div>\n";
  echo "<div>\n";
  echo "<form name='sensorsel' action='$url' method='get'>\n";
    echo "<input type='hidden' name='int_org' value='$q_org' />\n";
    if ($filter != 0) {
      echo "The ARP module for ";
    }
    $sql_sensors = "SELECT id, keyname, vlanid, arp FROM sensors WHERE organisation = $q_org AND NOT status = 3 ORDER BY keyname";
    $debuginfo[] = $sql_sensors;
    $result_sensors = pg_query($pgconn, $sql_sensors);
    echo "<select name='int_filter' onChange='javascript: this.form.submit();'>\n";
      echo printOption(0, "All", $filter) . "\n";
      while ($row = pg_fetch_assoc($result_sensors)) {
        $id = $row['id'];
        $keyname = $row['keyname'];
        $vlanid = $row['vlanid'];
        $arp = $row['arp'];
        $arp_array[$id] = $arp;
        if ($vlanid != 0) {
          $keyname = "$keyname-$vlanid";
        }
        echo printOption($id, $keyname, $filter) . "\n";
      }
    echo "</select>&nbsp;\n";
    if ($filter != 0) {
        echo "<input type='hidden' name='int_sensor' value='$filter' />\n";
        $arp = $arp_array[$filter];
        echo " is ";
        echo "<select name='int_arp' onChange='javascript: this.form.submit();'>\n";
          echo printOption(0, "Disabled", $arp) . "\n";
          echo printOption(1, "Enabled", $arp) . "\n";
        echo "</select>\n";
        echo " !<br />\n";
    }
  echo "</form>\n";
  echo "</div>\n";
}

if ($s_access_sensor > 1) {
  echo "<h4>Monitored MAC addresses " .printhelp("arpmonitor"). "</h4>\n";
  echo "<form name='arp_static' action='arp_static_add.php?int_org=$q_org' method='post'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='100' class='dataheader'><a href='$url${op}sort=mac$neworder'>MAC address</a></td>\n";
      echo "<td width='100' class='dataheader'><a href='$url${op}sort=ip$neworder'>IP address</a></td>\n";
      echo "<td width='200' class='dataheader'>Type</td>\n";
      echo "<td width='100' class='dataheader'><a href='$url${op}sort=keyname$neworder'>Sensor</a></td>\n";
      echo "<td width='100' class='dataheader'>Action</td>\n";
    echo "</tr>\n";

    $sql_arp_static = "SELECT arp_static.id, arp_static.mac, arp_static.ip, sensors.keyname, sensors.vlanid ";
    $sql_arp_static .= " FROM arp_static, sensors";
    $sql_arp_static .= " WHERE sensors.organisation = $q_org AND sensors.id = arp_static.sensorid ";
    if ($filter != 0) {
      $sql_arp_static .= " AND arp_static.sensorid = $filter ";
    }
    if ($sorttype != "last_seen") {
      $sql_arp_static .= " ORDER BY $sorttype $dir ";
    }
    $debuginfo[] = $sql_arp_static;
    $result_arp_static = pg_query($pgconn, $sql_arp_static);

    while ($row_static = pg_fetch_assoc($result_arp_static)) {
      $id = $row_static['id'];
      $mac = $row_static['mac'];
      $ip = $row_static['ip'];
      $keyname = $row_static['keyname'];
      $vlanid = $row_static['vlanid'];
      if ($vlanid != 0) {
        $keyname = "$keyname-$vlanid";
      }
      $static_arp["$ip"] = $mac;
      $typestring = "";

      $sql_getht = "SELECT type FROM sniff_hosttypes WHERE staticid = '$id'";
      $debuginfo[] = $sql_getht;
      $result_getht = pg_query($pgconn, $sql_getht);
      while ($row_ht = pg_fetch_assoc($result_getht)) {
        $type = $row_ht['type'];
        $typestring .= "<img src='images/hosttypes/$type.gif' onmouseover='return overlib(\"$v_host_types[$type]\");' onmouseout='return nd();' />&nbsp;";
      }

      echo "<tr class='datatr'>\n";
        echo "<td>$mac</td>\n";
        echo "<td>$ip</td>\n";
        echo "<td>$typestring</td>\n";
        echo "<td>$keyname</td>\n";
        echo "<td>[<a href='arp_static_del.php?int_org=$q_org&md5_hash=$s_hash&int_id=$id&int_filter=$filter' onclick=\"javascript: return confirm('Are you sure you want to delete this entry?');\">delete</a>]</td>\n";
      echo "</tr>\n";
    }
    echo "<tr class='datatr'>\n";
      echo "<td><input type='text' name='mac_macaddr' value='' size='15' /></td>\n";
      echo "<td><input type='text' name='ip_ipaddr' value='' size='13' /></td>\n";
      echo "<td>";
#        echo "<select name='int_type'>\n";
#          echo printOption(0, "", -1) . "\n";
          foreach ($v_host_types as $key => $val) {
#            echo printOption($key, $val, -1) . "\n";
            echo printCheckBox("$val", "type[]", $key, -1) . "<br />\n";
          }
#        echo "</select>\n";
      echo "</td>\n";

      pg_result_seek($result_sensors, 0);
      echo "<td>";
        echo "<select name='int_sensor'>\n";
          echo printOption(0, "", $filter) . "\n";
          while ($row = pg_fetch_assoc($result_sensors)) {
            $id = $row['id'];
            $keyname = $row['keyname'];
            $vlanid = $row['vlanid'];
            if ($vlanid != 0) {
              $keyname = "$keyname-$vlanid";
            }
            echo printOption($id, $keyname, $filter) . "\n";
          }
        echo "</select>\n";
      echo "</td>\n";
      echo "<td align='right'><input type='submit' class='button' name='submit' value='Add' size='15' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
  echo "</form>\n";

  echo "<h4>Detected protocols</h4>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader' width='150'>Parent Protocol</td>\n";
      echo "<td class='dataheader' width='150'>Type Number</td>\n";
      echo "<td class='dataheader' width='300'>Type</td>\n";
    echo "</tr>\n";

    $sql_protos = "SELECT head, number, protocol FROM sniff_protos WHERE sensorid = '$filter' ORDER BY head, number";
    $debuginfo[] = $sql_protos;
    $result_protos = pg_query($pgconn, $sql_protos);

    while ($row_protos = pg_fetch_assoc($result_protos)) {
      $head = $row_protos['head'];
      $number = $row_protos['number'];
      $proto = $row_protos['protocol'];

      echo "<tr>\n";
        echo "<td class='datatd'>" .$v_proto_types[$head]. "</td>\n";
        echo "<td class='datatd'>$number</td>\n";
        echo "<td class='datatd'>$proto</td>\n";
      echo "</tr>\n";
    }
  echo "</table>\n";

  echo "<h4>Current ARP cache " .printhelp("arpcache"). "</h4>\n";
  echo "<a href='arp_cache_clr.php?int_org=$q_org&md5_hash=$s_hash&int_filter=$filter' onclick=\"javascript: return confirm('Are you sure you want to clear the ARP cache?');\">Clear ARP cache</a><br />\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td width='150' class='dataheader'><a href='$url${op}sort=mac$neworder'>MAC address</a></td>\n";
      echo "<td width='100' class='dataheader'><a href='$url${op}sort=ip$neworder'>IP address</a></td>\n";
      echo "<td width='100' class='dataheader'>Type</td>\n";
      echo "<td width='150' class='dataheader'><a href='$url${op}sort=manufacturer$neworder'>NIC Manufacturer</a></td>\n";
      echo "<td width='100' class='dataheader'><a href='$url${op}sort=keyname$neworder'>Sensor</a></td>\n";
      echo "<td width='150' class='dataheader'><a href='$url${op}sort=last_seen$neworder'>Last changed</a></td>\n";
      echo "<td width='50' class='dataheader'>Status</td>\n";
      echo "<td class='dataheader'></td>\n";
    echo "</tr>\n";

    $sql_arp_cache = "SELECT arp_cache.id, arp_cache.mac, ip, arp_cache.flags, sensors.keyname, ";
    $sql_arp_cache .= " sensors.vlanid, sensors.id as sid, arp_cache.last_seen, manufacturer ";
    $sql_arp_cache .= " FROM arp_cache, sensors WHERE arp_cache.sensorid = sensors.id AND sensors.organisation = $q_org ";
    if ($filter != 0) {
      $sql_arp_cache .= " AND sensors.id = $filter ";
    }
    $sql_arp_cache .= " ORDER BY $sorttype $dir ";
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

      echo "<form action='arp_static_add.php?int_org=$q_org&int_filter=$filter' method='post' name='cache_" .$id. "'>\n";
      echo "<tr>\n";
        if ($poisoned == 0) {
          echo "<td>$mac<input type='hidden' name='mac_macaddr' value='$mac' /></td>\n";
        } else {
          echo "<td><font class='warning'>$mac</font><input type='hidden' name='mac_macaddr' value='$mac' /></td>\n";
        }
        echo "<td>$ip<input type='hidden' name='ip_ipaddr' value='$ip' /></td>\n";
        if ("$flags" != "") {
          $flags_ar = split(",", $flags);
          $flagstring = "";
          foreach ($flags_ar as $key => $val) {
            $flagstring .= "<img src='images/hosttypes/$val.gif' onmouseover='return overlib(\"$v_host_types[$val]\");' onmouseout='return nd();' />&nbsp;";
          }
          echo "<td>$flagstring</td>\n";
        } else {
          echo "<td></td>\n";
        }
        echo "<td>$man</td>\n";
        echo "<td>$sensor<input type='hidden' name='int_sensor' value='$sensorid' /></td>\n";
        echo "<td>$lastseen</td>\n";
        if ($poisoned == 0) {
          echo "<td><font class='ok'>OK</font></td>\n";
          echo "<td align='center'><input type='submit' class='button' name='submit' value='Add' /></td>\n";
        } else {
          echo "<td><font class='warning'>Poisoned</font></td>\n";
          echo "<td align='center'><input type='submit' class='button' name='submit' value='Add' disabled /></td>\n";
        }
      echo "</tr>\n";
      echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
      echo "<input type='hidden' name='int_filter' value='$filter' />\n";
      echo "</form>\n";
    }
  echo "</table>\n";
}

pg_close($pgconn);
debug_sql();
footer();
?>
