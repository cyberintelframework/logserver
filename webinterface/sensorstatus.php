<?php $tab="4.1"; $pagetitle="Sensor Status"; include("menu.php"); contentHeader(1,0); ?>
<?php

####################################
# SURFnet IDS 2.10.00              #
# Changeset 005                    #
# 17-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 005 Changed sensor $status stuff
# 004 Added selector for org selection
# 003 Removed outdated status view
# 002 Fixed uptime when uptime = NULL
# 001 Added language support
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

if ($selview == 9) {
  add_to_sql("deactivated_sensors", "table");
  add_to_sql("deactivated_sensors.*", "select");
} else {
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
    add_to_sql("organisations.id as orgid", "select");
    add_to_sql("sensors.organisation = organisations.id", "where");
  }
  if ($q_org != 0) {
    add_to_sql("sensors.organisation = '$q_org'", "where");
  }
  add_to_sql("sensors", "table");
}

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

$sql_conf = "SELECT config FROM pageconf WHERE pageid = 1 AND userid = '$s_userid'";
$debuginfo[] = $sql_conf;
$result_conf = pg_query($pgconn, $sql_conf);
$row_conf = pg_fetch_assoc($result_conf);
$pageconf = $row_conf['config'];
$pageconf = split(",", $pageconf);

foreach ($pageconf as $key => $val) {
  $pconf[$val] = 1;
}

# Headers
# 01: Sensor name
# 02: Sensor label
# 03: Config
# 04: Status
# 05: Uptime
# 06: Remote IP
# 07: Local IP
# 08: Sensor MAC
# 09: Tap
# 10: Tap MAC
# 11: Tap IP

if ($s_admin == 1) {
  echo "<div id='adminmenu' onclick='adminmenu();'>\n";
    echo "<div class='center'>\n";
    echo "<table class='actiontable'>\n";
      echo "<tr>\n";
        echo "<th><span id='sensort'></span></th>\n";
      echo "</tr>\n";
    if ($selview != 9) {
      echo "<tr><td><a id='activator' href=''>Deactivate</a></td></tr>\n";
    } else {
       echo "<tr><td><a id='activator' href=''>Activate</a></td></tr>\n";
    }
    echo "</table>\n";
    echo "</div>\n";
  echo "</div>\n";
}

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>";
        echo "<div class='blockHeaderLeft'>" .$l['g_sensors']. "</div>\n";
        echo "<div class='blockHeaderRight'>\n";
          echo "<form name='viewform' action='$url' method='GET'>\n";
            echo "<select name='int_selview' class='smallselect' onChange='javascript: this.form.submit();'>\n";
              foreach ($v_selview_ar as $key => $val) {
                if ($key != 9) {
                  echo printOption($key, $val, $selview) . "\n";
                } elseif ($key == 9 && $s_admin == 1) {
                  echo printOption($key, $val, $selview) . "\n";
                }
              }
            echo "</select>\n";
          echo "</form>\n";
        echo "</div>\n";
      echo "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<table class='datatable' width='100%'>\n";
          echo "<tr>\n";
            # Main info
            if (array_key_exists("1", $pconf)) {
              echo "<th>" .printsort($l['g_sensor'], "keyname"). "</th>\n";
            }
            if (array_key_exists("2", $pconf)) {
              echo "<th>" .printsort($l['ss_label'], "label"). "</th>\n";
            }
            if (array_key_exists("3", $pconf)) {
              echo "<th>" .printsort($l['ss_config'], "netconf"). "</th>\n";
            }
            if (array_key_exists("4", $pconf)) {
              echo "<th>" .$l['g_status']. "</th>\n";
            }
            if (array_key_exists("5", $pconf)) {
              echo "<th>" .printsort($l['sd_uptime'], "uptime"). "</th>\n";
            }

            # Sensor side
            if (array_key_exists("6", $pconf)) {
              echo "<th>" .printsort($l['sd_rip'], "remoteip"). "</th>\n";
            }
            if (array_key_exists("7", $pconf)) {
              echo "<th>" .printsort($l['sd_lip'], "localip"). "</th>\n";
            }
            if (array_key_exists("8", $pconf)) {
              echo "<th>" .printsort($l['sd_smac'], "sensormac"). "</th>\n";
            }

            # Server side
            if (array_key_exists("9", $pconf)) {
              echo "<th>" .printsort($l['sd_device'], "tap"). "</th>\n";
            }
            if (array_key_exists("10", $pconf)) {
              echo "<th>" .printsort($l['sd_devmac'], "mac"). "</th>\n";
            }
            if (array_key_exists("11", $pconf)) {
              echo "<th>" .printsort($l['sd_devip'], "tapip"). "</th>\n";
            }


            if ($s_access_sensor == 9) {
              echo "<th>" .printsort($l['g_domain'], "org"). "</th>\n";
            }
            if ($s_access_sensor > 0) {
              echo "<th>" .$l['g_action']. "</th>\n";
            }
            echo "<th></th>\n";
            if ($s_admin == 1) {
              echo "<th></th>\n";
            }
          echo "</tr>\n";

          while ($row = pg_fetch_assoc($result_sensors)) {
            $now = time();
            $sid = $row['id'];
            $keyname = $row['keyname'];
            $label = $row['label'];
            $vlanid = $row['vlanid'];
            $sensor = sensorname($keyname, $vlanid);
            $netconf = $row['netconf'];

            $tap = $row['tap'];
            $tapip = censorip($row['tapip']);
            $mac = $row['mac'];

            $sensormac = $row['sensormac'];
            $localip = $row['localip'];
            $remoteip = $row['remoteip'];

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
            $arp = $row['arp'];
            $lastupdate = $row['lastupdate'];
            if ($s_access_sensor == 9) {
              $org = $row['org'];
              $orgid = $row['orgid'];
            }

            $cstatus = sensorstatus($server_rev, $sensor_rev, $status, $server_rev_ts, $lastupdate, $netconf, $tap, $tapip, $uptime);

            echo "<form name='rebootform' method='post' action='updateaction.php?int_selview=$selview'>\n";
              echo "<tr>\n";
                ########################
                # GENERAL
                ########################
                if (array_key_exists("1", $pconf)) {
                  echo "<td><a href='sensordetails.php?int_sid=$sid' " .printover($label). ">$sensor</a></td>\n";
                }
                if (array_key_exists("2", $pconf)) {
                  echo "<td><a href='sensordetails.php?int_sid=$sid'>$label</a></td>\n";
                }
                if (array_key_exists("3", $pconf)) {
                  echo "<td>" .$v_sensornetconf_ar["$netconf"]. "</td>\n";
                }
                if (array_key_exists("4", $pconf)) {
                  echo "<td>";
                    echo "<div class='sensorstatus'>";
                      echo "<div class='" .$v_sensorstatus_ar[$cstatus]["class"]. "'>";
                        echo "<div class='sensorstatustext'>" .$v_sensorstatus_ar[$cstatus]["text"]. "</div>";
                      echo "</div>";
                    echo "</div>";
                  echo "</td>\n";
                }
                if (array_key_exists("5", $pconf)) {
                  echo "<td>$uptime_text</td>\n";
                }
                ########################
                # SENSOR SIDE
                ########################
                if (array_key_exists("6", $pconf)) {
                  echo "<td>$remoteip</td>\n";
                }
                if (array_key_exists("7", $pconf)) {
                  echo "<td>$localip</td>\n";
                }
                if (array_key_exists("8", $pconf)) {
                  echo "<td>$sensormac</td>\n";
                }

                ########################
                # SERVER SIDE
                ########################
                if (array_key_exists("9", $pconf)) {
                  echo "<td>$tap</td>\n";
                }
                if (array_key_exists("10", $pconf)) {
                  echo "<td>$mac</td>\n";
                }
                # Tap IP address
                if (array_key_exists("11", $pconf)) {
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
                }
                if ($s_access_sensor == 9) {
                  echo "<td><a onclick='arequest(\"xml_getcontact.php?int_orgid=$orgid&int_sid=$sid\", \"getcontact\");'>$org</a></td>\n";
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
                    echo "<td>\n";
                      echo "<input type='submit' name='submit' value='" .$l['g_update']. "' class='button' />";
                    echo "</td>\n";
                    if ($s_admin == 1) {
                      echo "<td><a onclick='adminmenu(this, \"$sid\", \"$sensor\");'>Admin</a></td>";
                    }
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

