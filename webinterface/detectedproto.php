<?php $tab="2.7"; $pagetitle="Detected Protocols"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 19-11-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Fixed sorting order
# 002 Made page more user friendly
# 001 Added language support
#############################################

include '../include/protos.inc.php';

# Checking access
if ($s_access_sensor < 2) {
  $m = 101;
  geterror($m);
  footer();
  pg_close($pgconn);
  exit;
}

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_sid",
		"int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();
$err = 0;

if (!isset($clean['sid'])) {
  $err = 1;
} else {
  $sid = $clean['sid'];
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($err == 0) {
  echo "<div class='leftsmall'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<a href='detectedproto_clr.php?int_org=$q_org&md5_hash=$s_hash&int_sid=$sid' onclick=\"javascript: return confirm('" .$l['dp_confirm_del']. "');\">";
          echo $l['dp_clear_det_prot']. "</a>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</actionBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftsmall>
}

echo "<div class='all'>\n";
echo "<div class='leftbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>";
        echo "<div class='blockHeaderLeft'>" .$l['dp_detected']. " " .printhelp(15). "</div>\n";
        echo "<div class='blockHeaderRight'>";
          echo "<form method='get'>\n";
            if ($q_org == 0) {
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
      if ($err == 0) {
        echo "<div class='blockSubHeader'>\n";
          echo "<div id='tabEthernet' class='selected' onClick='protoSwitch(\"Ethernet\");'>Ethernet</div>\n";
          echo "<div id='tabIPv4' onClick='protoSwitch(\"IPv4\");'>IPv4</div>\n";
          echo "<div id='tabICMP' onClick='protoSwitch(\"ICMP\");'>ICMP</div>\n";
          echo "<div id='tabIGMP' onClick='protoSwitch(\"IGMP\");'>IGMP</div>\n";
          echo "<div id='tabDHCP' onClick='protoSwitch(\"DHCP\");'>DHCP</div>\n";
          echo "<div id='tabOther' onClick='protoSwitch(\"Other\");'>Other</div>\n";
        echo "</div>\n";
      }
      echo "<div class='blockContent'>\n";
        if ($err == 0) {
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='100'>" .$l['dp_parent']. "</th>\n";
              echo "<th width='50'>" .$l['dp_type']. "</th>\n";
              echo "<th width='450'>" .$l['dp_desc']. "</th>\n";
            echo "</tr>\n";

            $sql_protos = "SELECT parent, number, subtype FROM sniff_protos WHERE sensorid = '$sid' ORDER BY parent, number";
            $debuginfo[] = $sql_protos;
            $result_protos = pg_query($pgconn, $sql_protos);

            while ($row_protos = pg_fetch_assoc($result_protos)) {
              $head = $row_protos['parent'];
              $number = $row_protos['number'];
              $subtype = $row_protos['subtype'];
              if ($head == 0) {
                # These are dirty fixes to avoid letting the v_protos_ethernet_ar array grow too big.
                if ($number >= 33452 && $number <= 34451) {
                  $proto = "Walker Richer & Quinn";
                } elseif ($number >= 0 && $number <= 1500) {
                  $proto = "IEEE802.3 Length Field";
                } else {
                  $proto = $v_protos_ethernet_ar[$number];
                }
              } elseif ($head == 1) {
                $proto = $v_protos_ipv4_ar[$number];
              } elseif ($head == 11) {
                $proto = $v_protos_icmp_ar[$number]["desc"];
                if ($subtype != -1) {
                  if (array_key_exists($subtype, $v_protos_icmp_ar[$number])) {
                    $subdesc = $v_protos_icmp_ar[$number][$subtype];
                  } else {
                    $subdesc = "";
                  }
                } else {
                  $subdesc = "";
                }
              } elseif ($head == 12) {
                $proto = $v_protos_igmp_ar[$number];
                $subdesc = "";
              } elseif ($head == 11768) {
                $proto = $v_protos_dhcp_ar[$number];
              } else {
                $proto = "Unknown";
              }
              if ($proto == "") {
                $proto = "Unknown";
              }

              if ($head != 0) {
                $visi = " style='display: none;'";
              }
              if (array_key_exists($head, $v_proto_types)) {
                $class = $v_proto_types[$head];
              } else {
                $class = "Other";
              }

              # Handle $number below zero (IEEE 802.3)
              if ($number < 0) {
                $number = "N/A";
              }

              echo "<tr class='protos " .$class. "' $visi >\n";
                echo "<td>" .$v_protos_main_ar[$head]. "</td>\n";
                echo "<td>$number</td>\n";
                echo "<td>";
                  echo "$proto";
                  if ($subdesc != "") {
                    echo " - $subdesc";
                  }
                echo "</td>\n";
              echo "</tr>\n";
            }
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
?>
<?php footer(); ?>
