<?php $tab="4.1"; $pagetitle="Sensor Status"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 26-10-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.10.01 Added language support
# 2.00.02 Removed check on SSH, action always available now
# 2.00.01 version 2.00
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
  add_to_sql("organisations.organisation as org", "select");
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
            echo "<th>" .printsort($l['g_sensor'], "keyname"). "</th>\n";
            echo "<th>" .printsort($l['ss_label'], "label"). "</th>\n";
            echo "<th>" .printsort($l['ss_config'], "netconf"). "</th>\n";
            echo "<th>" .printsort($l['sd_devip'], "tapip"). "</th>\n";
            echo "<th>" .printsort($l['sd_uptime'], "uptime"). "</th>\n";
            echo "<th>" .$l['g_status']. "</th>\n";
            if ($s_access_sensor == 9) {
              echo "<th>" .printsort($l['g_domain'], "org"). "</th>\n";
            }
            if ($s_access_sensor > 0) {
              echo "<th>" .$l['g_action']. "</th>\n";
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
            $uptime = date("U") - $start;
            $uptime_text = sec_to_string($uptime);
            $netconf = $row['netconf'];
            $vlanid = $row['vlanid'];
            $arp = $row['arp'];
            $sensor = sensorname($keyname, $vlanid);
            $lastupdate = $row['lastupdate'];
            $diffupdate = 0;
            if ($lastupdate != "") {
              $diffupdate = $now - $lastupdate;
              $lastupdate = date("d-m-Y H:i:s", $lastupdate);
            }
            if ($s_access_sensor == 9) {
              $org = $row['org'];
            }

            # Setting status correctly
            if (($netconf == "vlans" || $netconf == "static") && (empty($tapip) || $tapip == "")) {
              $status = 5;
            } elseif ($diffupdate <= 3600 && $status == 1 && !empty($tap)) {
              $status = 1;
            } elseif ($diffupdate > 3600 && $status == 1) {
              $status = 4;
            } elseif ($status == 1 && empty($tap)) {
              $status = 6;
            }

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
                  echo "<td>";
                    if ($s_access_sensor == 0) {
                      echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' disabled />\n";
                    } else {
                      echo "<input type='text' name='ip_tapip' value='$tapip' size='14' class='sensorinput' />\n";
                    }
                  echo "</td>\n";
                }
                echo "<td>$uptime_text<input type='hidden' name='js_uptime' value='$uptime' /></td>\n";
                echo "<td>";
                  echo "<div class='sensorstatus'>";
                    echo "<div class='" .$v_sensorstatus_ar[$status]["class"]. "'>";
                      echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[$status]["text"]. "</div>";
                    echo "</div>";
                  echo "</div>";
                echo "</td>\n";
/*
                if ($status == 3) {
                  echo "<td></td>\n";
                } elseif (($netconf == "vlans" || $netconf == "static") && (empty($tapip) || $tapip == "")) {
                  echo "<td bgcolor='blue'></td>\n";
                } elseif ($status == 0) {
                  echo "<td bgcolor='red'></td>\n";
                } elseif ($diffupdate <= 3600 && $status == 1 && !empty($tap)) {
                  echo "<td bgcolor='green'></td>\n";
                } elseif ($diffupdate > 3600 && $status == 1) {
                  echo "<td bgcolor='orange'></td>\n";
                } elseif ($status == 1 && empty($tap)) {
                  echo "<td bgcolor='yellow'></td>\n";
                } elseif ($status == 2) {
                  echo "<td bgcolor='black'></td>\n";
                } else {
                  echo "<td bgcolor='red'></td>\n";
                }
*/
                if ($s_access_sensor == 9) {
                  echo "<td>$org</td>\n";
                }
                if ($s_access_sensor > 0) {
                  echo "<td>\n";
                    echo "<input type='hidden' name='int_vlanid' value='$vlanid' />\n";
                    echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                    echo "<select name='action'>\n";
                      echo "" . printOption("NONE", $l['ss_none'], $action) . "\n";
                      echo "" . printOption("REBOOT", $l['ss_reboot'], $action) . "\n";
                      echo "" . printOption("SSHOFF", $l['ss_sshoff'], $action) . "\n";
                      echo "" . printOption("SSHON", $l['ss_sshon'], $action) . "\n";
                      echo "" . printOption("STOP", $l['ss_stop'], $action) . "\n";
                      echo "" . printOption("START", $l['ss_start'], $action) . "\n";
                      echo "" . printOption("DISABLE", $l['ss_disable'], $action) . "\n";
                      echo "" . printOption("ENABLE", $l['ss_enable'], $action) . "\n";
                      echo "" . printOption("IGNORE", $l['ss_ignore'], $action) . "\n";
                      echo "" . printOption("UNIGNORE", $l['ss_unignore'], $action) . "\n";
                      if ($arp == 1) {
                        echo "" . printOption("DISABLEARP", $l['ss_disable_arp'], $action) . "\n";
                      } else {
                        echo "" . printOption("ENABLEARP", $l['ss_enable_arp'], $action) . "\n";
                      }
                    echo "</select>\n";
                    echo "<td colspan='12' align='right'>\n";
                      echo "<input type='submit' name='submit' value='" .$l['g_update']. "' class='button' />";
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
    echo "<div class='legendHeader'>" .$l['ss_legend']. "</div>\n";
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
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='legendFooter'></div>\n";
  echo "</div>\n";
echo "</div>\n";

debug_sql();
?>

<?php footer(); ?>

