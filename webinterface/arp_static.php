<?php $tab="4.2"; $pagetitle="ARP"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 004                    #
# 19-11-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 004 Fixed sorting order
# 003 Added ORDER BY to host types
# 002 Fixed bug #68
# 001 Added language support
####################################

$allowed_get = array(
	"int_m",
	"int_sid",
	"int_arp",
	"int_sid",
	"sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_sensor < 2) {
  $m = 101;
  geterror($m);
  footer();
  pg_close($pgconn);
  exit;
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(maca|macd|ipa|ipd)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
} else {
  $sql_sort = " mac ASC ";
  $sort = "maca";
}

if (isset($clean['sid'])) {
  $sid = $clean['sid'];

  if (isset($clean['arp'])) {
    $arp = $clean['arp'];
    $sql = "UPDATE sensors SET arp = '$arp' WHERE id = '$sid'";
    $result = pg_query($pgconn, $sql);
  }

  $sql = "SELECT keyname, vlanid, arp FROM sensors WHERE id = '$sid'";
  $result = pg_query($pgconn, $sql);
  $row = pg_fetch_assoc($result);
  $keyname = $row['keyname'];
  $vlanid = $row['vlanid'];
  $db_arp = $row['arp'];
  $selected = sensorname($keyname, $vlanid);

  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>" .$l['as_actions_for']. " $selected</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='arpform' method='get' action='arp_static.php'>\n";
            echo "<input type='hidden' name='int_sid' value='$sid' />\n";
            echo $l['as_arp_status']. " ";
            echo "<select name='int_arp' onchange='this.form.submit();'>\n";
              echo printOption(0, $l['as_disabled'], $db_arp);
              echo printOption(1, $l['as_enabled'], $db_arp);
            echo "</select>\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</actionBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>
} else {
  $err = 1;
}

echo "<div class='all'>\n";
echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>\n";
        echo "<div class='blockHeaderLeft'>" .$l['as_arp_mod']. " " .printhelp(9,9). "</div>\n";
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
        echo "</div>\n"; #</blockHeaderRight>
      echo "</div>\n"; #</blockHeader>
      echo "<div class='blockContent'>\n";
        if ($s_access_sensor > 1 && $err == 0) {
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th width='100'>" .printsort($l['g_mac'], "mac"). "</th>\n";
                echo "<th width='100'>" .printsort($l['g_ip'], "ip"). "</th>\n";
                echo "<th width='200'>" .$l['g_type']. "</th>\n";
                echo "<th width='100'>" .$l['g_sensor']. "</th>\n";
                echo "<th width='350'>" .$l['g_action']. "</th>\n";
              echo "</tr>\n";
              $sql_arp_static = "SELECT arp_static.id, arp_static.mac, arp_static.ip, sensors.keyname, sensors.vlanid ";
              $sql_arp_static .= " FROM arp_static, sensors";
              $sql_arp_static .= " WHERE sensors.id = arp_static.sensorid AND arp_static.sensorid = $sid ";
              if ($q_org != 0) {
                $sql_arp_static .= " AND sensors.organisation = $q_org ";
              }
              if ($sql_sort != "") {
                $sql_arp_static .= " ORDER BY $sql_sort ";
              }
              $debuginfo[] = $sql_arp_static;
              $result_arp_static = pg_query($pgconn, $sql_arp_static);

              while ($row_static = pg_fetch_assoc($result_arp_static)) {
                $id = $row_static['id'];
                $mac = $row_static['mac'];
                $ip = $row_static['ip'];
                $keyname = $row_static['keyname'];
                $vlanid = $row_static['vlanid'];
                $sensor = sensorname($keyname, $vlanid);
                $static_arp["$ip"] = $mac;
                $typestring = "";

                $sql_getht = "SELECT type FROM sniff_hosttypes WHERE staticid = '$id' ORDER BY type";
                $debuginfo[] = $sql_getht;
                $result_getht = pg_query($pgconn, $sql_getht);
                $types = array();
                while ($row_ht = pg_fetch_assoc($result_getht)) {
                  $type = $row_ht['type'];
                  $types[$type] = 0;
                  $typestring .= "<img src='images/hosttypes/$type.gif' onmouseover='return overlib(\"$v_host_types[$type]\");' onmouseout='return nd();' />&nbsp;";
                }

                echo "<tr>\n";
                  echo "<td>$mac</td>\n";
                  echo "<td>$ip</td>\n";
                  echo "<td>$typestring</td>\n";
                  echo "<td>$sensor</td>\n";
                  echo "<td>";
                    echo "[<a href='arp_static_del.php?int_id=$id&md5_hash=$s_hash&int_sid=$sid' onclick=\"javascript: return confirm('" .$l['as_delconfirm']. "');\">delete</a>]&nbsp;&nbsp;";
                    if (array_key_exists(1, $types)) {
                      echo "[<a href='arp_modtype.php?int_id=$id&action=del&int_type=1&md5_hash=$s_hash&int_sid=$sid'>" .$l['as_del_router']. "</a>]&nbsp;&nbsp;";
                    } else {
                      echo "[<a href='arp_modtype.php?int_id=$id&action=add&int_type=1&md5_hash=$s_hash&int_sid=$sid'>" .$l['as_add_router']. "</a>]&nbsp;&nbsp;";
                    }
                    if (array_key_exists(2, $types)) {
                      echo "[<a href='arp_modtype.php?int_id=$id&action=del&int_type=2&md5_hash=$s_hash&int_sid=$sid'>" .$l['as_del_dhcp']. "</a>]";
                    } else {
                      echo "[<a href='arp_modtype.php?int_id=$id&action=add&int_type=2&md5_hash=$s_hash&int_sid=$sid'>" .$l['as_add_dhcp']. "</a>]";
                    }
                  echo "</td>\n";
                echo "</tr>\n";
              }
            echo "<form name='arp_static' action='arp_static_add.php' method='post'>\n";
              echo "<tr>\n";
                echo "<td><input type='text' name='mac_macaddr' value='' size='15' /></td>\n";
                echo "<td><input type='text' name='ip_ipaddr' value='' size='13' /></td>\n";

                echo "<td>";
                  foreach ($v_host_types as $key => $val) {
                    echo printCheckBox("$val", "type[]", $key, -1) . "<br />\n";
                  }
                echo "</td>\n";

                pg_result_seek($result_sensors, 0);
                echo "<td>$selected</td>\n";
                echo "<td align='right'>";
                  echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                  echo "<input type='submit' class='button' name='submit' value='" .$l['g_add']. "' size='15' />&nbsp;";
                  echo printhelp(10,10);
                echo "</td>\n";
              echo "</tr>\n";
              echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "</form>\n";
            echo "<form name='arp_static2' action='arp_static_add.php' method='post'>\n";
              echo "<tr>\n";
                echo "<td><input type='text' name='mac_macaddr' value='' size='15' /></td>\n";
                echo "<td><input type='text' name='ip_ipaddr' value='' size='13' /></td>\n";

                echo "<td>";
                    echo printCheckBox($v_host_types[2], "type[]", 2, 2) . "<br />\n";
                echo "</td>\n";

                echo "<td>${keyname}-all</td>\n";
                echo "<td align='right'>";
                  echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                  echo "<input type='hidden' name='int_all' value='1' />\n";
                  echo "<input type='submit' class='button' name='submit' value='" .$l['g_add']. "' size='15' />&nbsp;";
                  echo printhelp(8,8);
                echo "</td>\n";
              echo "</tr>\n";
              echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "</form>\n";
            echo "</table>\n";
        } else {
          echo "<form method='get'>\n";
          echo "<table>";
            echo "<tr>";
              echo "<td><span class='warning'>" .$l['g_select_sensor']. "</span></td>\n";
              echo "<td>\n";
                $select_size = 8;
                if ($q_org == 0) {
                  $sql_sensors = "SELECT sensors.id, keyname, vlanid, arp, status, label, organisations.organisation ";
                  $sql_sensors .= " FROM sensors, organisations ";
                  $sql_sensors .= " WHERE sensors.organisation = organisations.id AND NOT status = 3 ORDER BY status DESC, keyname";
                } else {
                  $sql_sensors = "SELECT id, keyname, vlanid, arp, status, label FROM sensors ";
                  $sql_sensors .= " WHERE organisation = $q_org AND NOT status = 3 ORDER BY status DESC, keyname";
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
echo "</div>\n"; #</left>
echo "</div>\n"; #</all>

debug_sql();
pg_close($pgconn);
footer();
?>
