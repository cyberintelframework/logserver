<?php $tab="4.1"; $pagetitle="Sensor Status"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFids 2.00.03                  #
# Changeset 003                    #
# 26-05-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 003 Changed sensorstatus handling, modified legend, revision info
# 002 Removed check on SSH, action always available now
# 001 version 2.00
#############################################

# Retrieving posted variables from $_GET
$allowed_get = array(
                "sort",
                "int_selview",
		"int_m",
		"strip_html_key"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Setting up sorting stuff
if (isset($tainted['sort'])) {
  $pattern = '/^(keynamea|keynamed|labela|labeld|tapipa|tapipd|netconfa|netconfd|statusa|statusd|uptimea|uptimed|orga|orgd)$/';
  $sort = $tainted['sort'];
  $sql_sort = sorter($sort, $pattern);
  if ($sql_sort != "") {
    add_to_sql("$sql_sort", "order");
  }
} else {
  add_to_sql("keyname ASC", "order");
  add_to_sql("vlanid", "order");
  $sort = "keynamea";
}

if (isset($clean['selview'])) {
  $selview = $clean['selview'];
} elseif (isset($c_selview)) {
  $selview = intval($c_selview);
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

$or = "((netconf = 'vlans' OR netconf = 'static') AND tapip IS NULL AND NOT status = 3)";
add_to_sql("sensors.*", "select");
if ($selview == "1") {
  add_to_sql("(status = 0 OR $or)", "where");
} elseif ($selview == "2") {
  add_to_sql("(status = 1 OR $or)", "where");
}

if ($s_access_sensor < 9) {
  add_to_sql("organisation = '$s_org'", "where");
} elseif ($s_access_sensor == 9) {
  add_to_sql("organisations", "table");
  add_to_sql("organisations.organisation as org", "select");
  add_to_sql("sensors.organisation = organisations.id", "where");
}
if ($q_org != 0) {
  add_to_sql("sensors.organisation = '$q_org'", "where");
}

add_to_sql("sensors", "table");
prepare_sql();

$sql_sensors = "SELECT $sql_select ";
$sql_sensors .= " FROM $sql_from ";
$sql_sensors .= " $sql_where ";
$sql_sensors .= " ORDER BY $sql_order ";

$debuginfo[] = $sql_sensors;
$result_sensors = pg_query($pgconn, $sql_sensors);

$sql_rev = "SELECT value, timestamp FROM serverinfo WHERE name = 'updaterev'";
$debuginfo[] = $sql_rev;
$result_rev = pg_query($pgconn, $sql_rev);
$row_rev = pg_fetch_assoc($result_rev);
$server_rev = $row_rev['value'];
$server_rev_ts = $row_rev['timestamp'];

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>";
        echo "<div class='blockHeaderLeft'>Sensors</div>\n";
        echo "<div class='blockHeaderRight'>\n";
          echo "<form name='viewform' action='$url' method='GET'>\n";
            echo "<select name='int_selview' class='smallselect' onChange='javascript: this.form.submit();'>\n";
              foreach ($v_selview_ar as $key => $val) {
                echo printOption($key, $val, $selview) . "\n";
              }
            echo "</select>\n";
          echo "</form>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<table class='datatable' width='100%'>\n";
          echo "<tr>\n";
            echo "<th>" .printsort("Sensor", "keyname"). "</th>\n";
            echo "<th>" .printsort("Label", "label"). "</th>\n";
            echo "<th>" .printsort("Config method", "netconf"). "</th>\n";
            echo "<th>" .printsort("Device IP", "tapip"). "</th>\n";
            echo "<th>" .printsort("Uptime", "uptime"). "</th>\n";
            echo "<th>Status</th>\n";
            if ($s_access_sensor == 9) {
              echo "<th>" .printsort("Organisation", "org"). "</th>\n";
            }
            if ($s_access_sensor > 0) {
              echo "<th>Action</th>\n";
            }
            echo "<th></th>\n";
          echo "</tr>\n";

          while ($row = pg_fetch_assoc($result_sensors)) {
            $now = time();
            $sid = $row['id'];
            $keyname = $row['keyname'];
            $label = $row['label'];
            $tap = $row['tap'];
            $tapip = censorip($row['tapip']);
            $start = $row['laststart'];
            $action = $row['action'];
            $ssh = $row['ssh'];
            $status = $row['status'];
            $sensor_rev = $row['rev'];
            if ($start != "") {
              $uptime = date("U") - $start;
            } else {
              $uptime = 0;
            }
            $uptime_text = sec_to_string($uptime);
            $netconf = $row['netconf'];
            $vlanid = $row['vlanid'];
            $arp = $row['arp'];
            $sensor = sensorname($keyname, $vlanid);
            $lastupdate = $row['lastupdate'];
            if ($s_access_sensor == 9) {
              $org = $row['org'];
            }

            $cstatus = sensorstatus();

            echo "<form name='rebootform' method='post' action='updateaction.php?int_selview=$selview'>\n";
              echo "<tr>\n";
                echo "<td><a href='sensordetails.php?int_sid=$sid'>$sensor</a></td>\n";
                echo "<td><a href='sensordetails.php?int_sid=$sid'>$label</a></td>\n";
                echo "<td>";
                  echo $v_sensornetconf_ar["$netconf"];
                echo "</td>\n";
                # Tap IP address
                if ($netconf == "dhcp" || $netconf == "" || $netconf == "vland") {
                  if (empty($tapip)) {
                    echo "<td></td>\n";
                  } else {
                    echo "<td>$tapip</td>\n";
                  }
                } elseif ($netconf == "vlans") {
                  echo "<td>";
                    if ($s_access_sensor == 0) {
                      echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' disabled />\n";
                    } else {
                      echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' />\n";
                    }
                  echo "</td>\n";
                } else {
                  echo "<td>static ";
                    if ($s_access_sensor == 0) {
                      echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' disabled />\n";
                    } else {
                      echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' />\n";
                    }
                  echo "</td>\n";
                }
#                echo "<td>$uptime_text<input type='hidden' name='js_uptime' value='$uptime' /></td>\n";
                echo "<td>$uptime_text</td>\n";
                echo "<td>";
                  echo "<div class='sensorstatus'>";
                    echo "<div class='" .$v_sensorstatus_ar[$cstatus]["class"]. "'>";
                      echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[$cstatus]["text"]. "</div>";
                    echo "</div>";
                  echo "</div>";
                echo "</td>\n";

                if ($s_access_sensor == 9) {
                  echo "<td>$org</td>\n";
                }
                if ($s_access_sensor > 0) {
                  echo "<td>\n";
                    echo "<input type='hidden' name='int_vlanid' value='$vlanid' />\n";
                    echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                    echo "<select name='action'>\n";
                      echo "" . printOption("NONE", "None", $action) . "\n";
                      echo "" . printOption("REBOOT", "Reboot", $action) . "\n";
                      echo "" . printOption("SSHOFF", "SSH off", $action) . "\n";
                      echo "" . printOption("SSHON", "SSH on", $action) . "\n";
                      echo "" . printOption("STOP", "Stop", $action) . "\n";
                      echo "" . printOption("START", "Start", $action) . "\n";
                      echo "" . printOption("DISABLE", "Disable", $action) . "\n";
                      echo "" . printOption("ENABLE", "Enable", $action) . "\n";
                      echo "" . printOption("IGNORE", "Ignore", $action) . "\n";
                      echo "" . printOption("UNIGNORE", "Unignore", $action) . "\n";
                      if ($arp == 1) {
                        echo "" . printOption("DISABLEARP", "Disable ARP", $action) . "\n";
                      } else {
                        echo "" . printOption("ENABLEARP", "Enable ARP", $action) . "\n";
                      }
                    echo "</select>\n";
                    echo "<td colspan='12' align='right'>\n";
                      echo "<input type='submit' name='submit' value='Update' class='button' />";
                    echo "</td>\n";
                  echo "</td>\n";
                }
              echo "</tr>\n";
            echo "</form>\n";
          }
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</center>

echo "<div class='centerbig'>\n";
  echo "<div class='legend'>\n";
    echo "<div class='legendHeader'>Legend</div>\n";
    echo "<div class='legendContent'>\n";
      echo "<div class='legendContentLeft'>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[0]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[0]["text"]. "</div>\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[1]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[1]["text"]. "</div>\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[2]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[2]["text"]. "</div>\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[3]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[3]["text"]. "</div>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='legendContentRight'>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[4]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[4]["text"]. "</div>\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[5]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[5]["text"]. "</div>\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[6]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[6]["text"]. "</div>\n";
        echo "</div>\n";
        echo "<div class='legendItem'>\n";
          echo "<div class='sensorstatus'><div class='" .$v_sensorstatus_ar[7]["class"]. "'></div></div>\n";
          echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[7]["text"]. "</div>\n";
        echo "</div>\n";
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='legendFooter'></div>\n";
  echo "</div>\n";
echo "</div>\n";

debug_sql();
?>

<?php footer(); ?>

