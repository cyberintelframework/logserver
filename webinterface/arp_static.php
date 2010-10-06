<?php $tab="4.2"; $pagetitle="Ethernet Modules"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.04                     #
# Changeset 005                    #
# 11-06-2010                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

####################################
# Changelog:
# 005 Fixed bug #223
# 004 Fixed sorting order
# 003 Added ORDER BY to host types
# 002 Fixed bug #68
# 001 Added language support
####################################

$allowed_get = array(
	"int_m",
	"int_sid",
	"bool_arp",
	"bool_ipv6",
	"bool_dhcp",
	"bool_protos",
	"int_sid",
	"sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();
$err = 0;

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
  if (isset($clean['dhcp'])) {
    $dhcp = $clean['dhcp'];
    $sql = "UPDATE sensors SET dhcp = '$dhcp' WHERE id = '$sid'";
    $result = pg_query($pgconn, $sql);
  }
  if (isset($clean['ipv6'])) {
    $ipv6 = $clean['ipv6'];
    $sql = "UPDATE sensors SET ipv6 = '$ipv6' WHERE id = '$sid'";
    $result = pg_query($pgconn, $sql);
  }
  if (isset($clean['protos'])) {
    $protos = $clean['protos'];
    $sql = "UPDATE sensors SET protos = '$protos' WHERE id = '$sid'";
    $result = pg_query($pgconn, $sql);
  }

  $sql = "SELECT keyname, vlanid, arp, dhcp, ipv6, protos FROM sensors WHERE id = '$sid'";
  $result = pg_query($pgconn, $sql);
  $row = pg_fetch_assoc($result);
  $keyname = $row['keyname'];
  $cur_keyname = $keyname;
  $vlanid = $row['vlanid'];
  $db_arp = $row['arp'];
  $db_dhcp = $row['dhcp'];
  $db_ipv6 = $row['ipv6'];
  $db_protos = $row['protos'];
  $selected = sensorname($keyname, $vlanid);

  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>" .$l['as_actions_for']. " $selected</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table width='100%'>\n";
            echo "<form name='arpform' method='get' action='arp_static.php'>\n";
              echo "<input type='hidden' name='int_sid' value='$sid' />\n";
              echo "<tr width='100%'>";
                echo "<td width='50%'>" .$l['as_arp_status']. "</td>";
                echo "<td width='50%' class='aright'>";
                  echo "<select name='bool_arp'>\n";
                    echo printOption("f", $l['as_disabled'], $db_arp);
                    echo printOption("t", $l['as_enabled'], $db_arp);
                  echo "</select>";
                echo "</td>";
              echo "</tr>\n";
              echo "<tr>";
                echo "<td>" .$l['as_dhcp_status']. "</td>";
                echo "<td class='aright'>";
                  echo "<select name='bool_dhcp'>\n";
                    echo printOption("f", $l['as_disabled'], $db_dhcp);
                    echo printOption("t", $l['as_enabled'], $db_dhcp);
                  echo "</select>";
                echo "</td>";
              echo "</tr>\n";
              echo "<tr>";
                echo "<td>" .$l['as_ipv6_status']. "</td>";
                echo "<td class='aright'>";
                  echo "<select name='bool_ipv6'>\n";
                    echo printOption("f", $l['as_disabled'], $db_ipv6);
                    echo printOption("t", $l['as_enabled'], $db_ipv6);
                  echo "</select>";
                echo "</td>";
              echo "</tr>\n";
              echo "<tr>";
                echo "<td>" .$l['as_protos_status']. "</td>";
                echo "<td class='aright'>";
                  echo "<select name='bool_protos'>\n";
                    echo printOption("f", $l['as_disabled'], $db_protos);
                    echo printOption("t", $l['as_enabled'], $db_protos);
                  echo "</select>";
                echo "</td>";
              echo "</tr>\n";
              echo "<tr>";
                echo "<td class=aright colspan=2><input class=sbutton type=submit name=submit value=" .$l['g_submit']. " /></td>";
              echo "</tr>\n";
            echo "</form>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</actionBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>
} else {
  $err = 1;
  echo "<div class='all'>\n";
    echo "<div class='left'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>" .$l['me_arp']. "</div>\n";
          echo "<div class='blockContent'>\n";
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
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</left>
  echo "</div>\n"; #</all>
}

if ($err == 0) {
echo "<div class='all'>\n";
echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['as_arp_status']. "</div>\n";
      echo "<div class='blockContent'>\n";
        #############################
        # ARP
        #############################
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='30%'>" .printsort($l['g_mac'], "mac"). "</th>\n";
            echo "<th width='30%'>" .printsort($l['g_ip'], "ip"). "</th>\n";
            echo "<th width='23%'>" .$l['g_sensor']. "</th>\n";
            echo "<th width='17%'>" .$l['g_action']. "</th>\n";
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

            echo "<tr>\n";
              echo "<td>$mac</td>\n";
              echo "<td>$ip</td>\n";
              echo "<td>$sensor</td>\n";
              echo "<td>";
                echo "[<a href='arp_static_del.php?int_id=$id&md5_hash=$s_hash&int_sid=$sid&strip_html_type=arp' onclick=\"javascript: return confirm('" .$l['as_delconfirm']. "');\">delete</a>]&nbsp;&nbsp;";
              echo "</td>\n";
            echo "</tr>\n";
          }
          echo "<form name='arp_static' action='arp_static_add.php' method='post'>\n";
            echo "<tr>\n";
              echo "<td><input type='text' name='mac_mac' value='' size='17' /></td>\n";
              echo "<td><input type='text' name='ip_ip' value='' size='17' /></td>\n";

              pg_result_seek($result_sensors, 0);
              echo "<td>$selected</td>\n";
              echo "<td align='right'>";
                echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                echo "<input type='submit' class='button' name='submit' value='" .$l['g_add']. "' size='15' />&nbsp;";
                echo printhelp(23,23);
              echo "</td>\n";
            echo "</tr>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "<input type='hidden' name='strip_html_type' value='arp' />\n";
          echo "</form>\n";
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>

  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['as_dhcp_status']. "</div>\n";
      echo "<div class='blockContent'>\n";
        #############################
        # DHCP
        #############################
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='40%'>" .printsort($l['g_ip'], "ip"). "</th>\n";
            echo "<th width='40%'>" .$l['g_sensor']. "</th>\n";
            echo "<th width='20%'>" .$l['g_action']. "</th>\n";
          echo "</tr>\n";
          $sql_dhcp_static = "SELECT dhcp_static.id, dhcp_static.ip, sensors.keyname, sensors.vlanid ";
          $sql_dhcp_static .= " FROM dhcp_static, sensors";
          $sql_dhcp_static .= " WHERE sensors.id = dhcp_static.sensorid AND dhcp_static.sensorid = $sid ";
          if ($q_org != 0) {
            $sql_dhcp_static .= " AND sensors.organisation = $q_org ";
          }
          if ($sql_sort != "") {
            $sql_dhcp_static .= " ORDER BY $sql_sort ";
          }
          $debuginfo[] = $sql_dhcp_static;
          $result_dhcp_static = pg_query($pgconn, $sql_dhcp_static);

          while ($row_static = pg_fetch_assoc($result_dhcp_static)) {
            $id = $row_static['id'];
            $ip = $row_static['ip'];
            $keyname = $row_static['keyname'];
            $vlanid = $row_static['vlanid'];
            $sensor = sensorname($keyname, $vlanid);

            echo "<tr>\n";
              echo "<td>$ip</td>\n";
              echo "<td>$sensor</td>\n";
              echo "<td>";
                echo "[<a href='arp_static_del.php?int_id=$id&md5_hash=$s_hash&int_sid=$sid&strip_html_type=dhcp' onclick=\"javascript: return confirm('" .$l['as_delconfirm']. "');\">delete</a>]&nbsp;&nbsp;";
              echo "</td>\n";
            echo "</tr>\n";
          }
          echo "<form name='arp_dhcp' action='arp_static_add.php' method='post'>\n";
            echo "<tr>\n";
              echo "<td><input type='text' name='ip_ip' value='' size='31' /></td>\n";

              pg_result_seek($result_sensors, 0);
              echo "<td>$selected</td>\n";
              echo "<td align='right'>";
                echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                echo "<input type='submit' class='button' name='submit' value='" .$l['g_add']. "' size='15' />&nbsp;";
                echo printhelp(24,24);
              echo "</td>\n";
            echo "</tr>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "<input type='hidden' name='strip_html_type' value='dhcp' />\n";
          echo "</form>\n";
          echo "<form name='arp_dhcp2' action='arp_static_add.php' method='post'>\n";
            echo "<tr>\n";
              echo "<td><input type='text' name='ip_ip' value='' size='31' /></td>\n";

              echo "<td>${cur_keyname}-all</td>\n";
              echo "<td align='right'>";
                echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                echo "<input type='hidden' name='int_all' value='1' />\n";
                echo "<input type='submit' class='button' name='submit' value='" .$l['g_add']. "' size='15' />&nbsp;";
                echo printhelp(25,25);
              echo "</td>\n";
            echo "</tr>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "<input type='hidden' name='strip_html_type' value='dhcp' />\n";
          echo "</form>\n";
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>

  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['as_ipv6_status']. "</div>\n";
      echo "<div class='blockContent'>\n";
        #############################
        # IPv6
        #############################
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='40%'>" .printsort($l['g_ip6'], "ip"). "</th>\n";
            echo "<th width='40%'>" .$l['g_sensor']. "</th>\n";
            echo "<th width='20%'>" .$l['g_action']. "</th>\n";
          echo "</tr>\n";
          $sql_ipv6_static = "SELECT ipv6_static.id, ipv6_static.ip, sensors.keyname, sensors.vlanid ";
          $sql_ipv6_static .= " FROM ipv6_static, sensors";
          $sql_ipv6_static .= " WHERE sensors.id = ipv6_static.sensorid AND ipv6_static.sensorid = $sid ";
          if ($q_org != 0) {
            $sql_ipv6_static .= " AND sensors.organisation = $q_org ";
          }
          if ($sql_sort != "") {
            $sql_ipv6_static .= " ORDER BY $sql_sort ";
          }
          $debuginfo[] = $sql_arp_static;
          $result_ipv6_static = pg_query($pgconn, $sql_ipv6_static);

          while ($row_static = pg_fetch_assoc($result_ipv6_static)) {
            $id = $row_static['id'];
            $ip = $row_static['ip'];
            $keyname = $row_static['keyname'];
            $vlanid = $row_static['vlanid'];
            $sensor = sensorname($keyname, $vlanid);

            echo "<tr>\n";
              echo "<td>$ip</td>\n";
              echo "<td>$sensor</td>\n";
              echo "<td>";
                echo "[<a href='arp_static_del.php?int_id=$id&md5_hash=$s_hash&int_sid=$sid&strip_html_type=ipv6' onclick=\"javascript: return confirm('" .$l['as_delconfirm']. "');\">delete</a>]&nbsp;&nbsp;";
              echo "</td>\n";
            echo "</tr>\n";
          }
          echo "<form name='ipv6_static' action='arp_static_add.php' method='post'>\n";
            echo "<tr>\n";
              echo "<td><input type='text' name='ipv6_ip' value='' size='31' /></td>\n";

              pg_result_seek($result_sensors, 0);
              echo "<td>$selected</td>\n";
              echo "<td align='right'>";
                echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                echo "<input type='submit' class='button' name='submit' value='" .$l['g_add']. "' size='15' />&nbsp;";
                echo printhelp(26,26);
              echo "</td>\n";
            echo "</tr>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "<input type='hidden' name='strip_html_type' value='ipv6' />\n";
          echo "</form>\n";
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>
echo "</div>\n"; #</all>
}

debug_sql();
pg_close($pgconn);
footer();
?>
