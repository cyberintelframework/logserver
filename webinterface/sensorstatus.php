<?php $tab="4.1"; $pagetitle="Sensor Status"; include("menu.php"); contentHeader(1,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 007                    #
# 29-05-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 007 Added default page configuration
# 006 Added md5_hash to updateaction link
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
  # Making sure no duplicate vlanids get selected
  add_to_sql("DISTINCT vlanid", "select");
  # Adding 1 of the keyname fields
  add_to_sql("sensors.keyname as keyname", "select");
  # Adding the sensor ID field
  add_to_sql("sensors.id as sid", "select");
  # Adding all necessary sensor_details fields
  add_to_sql("remoteip, localip, sensortype, action, sensormac, lastupdate", "select");
  # Adding all necessary sensors fields
  add_to_sql("tap, tapip, mac, laststart, laststop, uptime, label, permanent, status, networkconfig", "select");
  if ($selview == "1") {
    add_to_sql("status = 0", "where");
  } elseif ($selview == "2") {
    add_to_sql("status > 0 AND NOT status = 3", "where");
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
$sql_sensors .= " LEFT JOIN sensor_details ON sensors.keyname = sensor_details.keyname ";
$sql_sensors .= " $sql_where ";
$sql_sensors .= " ORDER BY $sql_order ";

$debuginfo[] = $sql_sensors;
$result_sensors = pg_query($pgconn, $sql_sensors);

$sql_conf = "SELECT config FROM pageconf WHERE pageid = 1 AND userid = '$s_userid'";
$debuginfo[] = $sql_conf;
$result_conf = pg_query($pgconn, $sql_conf);
$row_conf = pg_fetch_assoc($result_conf);
$pageconf = $row_conf['config'];
if ($pageconf == "") {
  $pageconf = "1,3,4,6,9,10,11";
} else {
  $pageconf = split(",", $pageconf);
}

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
              echo "<th>" .$l['g_action']. " " .printhelp(1, $sid). "</th>\n";
            }
            echo "<th>" .$l['ss_quicknav']. "</th>\n";
          echo "</tr>\n";

          while ($row = pg_fetch_assoc($result_sensors)) {
            $now = time();
            $sid = $row['sid'];
            $keyname = $row['keyname'];
            $label = $row['label'];
            $vlanid = $row['vlanid'];
            $sensor = sensorname($keyname, $vlanid);
            $sensortype = $row['sensortype'];
            $networkconfig = $row['networkconfig'];

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
            $permanent = $row['permanent'];

            $cstatus = sensorstatus($status, $lastupdate, $uptime, $permanent);

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
                echo "<td>" .$v_sensornetconf_ar["$sensortype"]. "</td>\n";
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
                if (empty($tapip)) {
                  echo "<td></td>\n";
                } else {
                  echo "<td>$tapip</td>\n";
                }
              }
              if ($s_access_sensor == 9) {
                echo "<td><a href='getcontact.php?int_sid=$sid&int_orgid=$orgid' class='jTip' name='$org' id='contact${sid}'>$org</a></td>\n";
              }
              if ($s_access_sensor > 0) {
                echo "<td>\n";
                  echo "<form name='rebootform$sid' method='post' action='updateaction.php?int_selview=$selview&md5_hash=$s_hash'>\n";
                    echo "<input type='hidden' name='int_vlanid' value='$vlanid' />\n";
                    echo "<input type='hidden' name='int_sid' value='$sid' />\n";
                    echo "<select name='action' onchange='javascript: this.form.submit();'>\n";
                      echo printOption("NONE", $l['ss_none'], $action);
                      echo printOption("REBOOT", $l['ss_reboot'], $action);
                      echo printOption("SSHOFF", $l['ss_sshoff'], $action);
                      echo printOption("SSHON", $l['ss_sshon'], $action);
                      echo printOption("STOP", $l['ss_stop'], $action);
                      echo printOption("START", $l['ss_start'], $action);
                      echo printOption("IGNORE", $l['ss_ignore'], $action);
                      echo printOption("UNIGNORE", $l['ss_unignore'], $action);
                      if ($s_access_sensor > 2) {
                        if ($selview == 9) {
                          echo printOption("ACTIVATE", $l['ss_activate'], $action);
                        } else {
                          echo printOption("DEACTIVATE", $l['ss_deactivate'], $action);
                        }
                      }
                      if ($arp == 1 && $s_access_sensor > 1) {
                        echo "" . printOption("DISABLEARP", $l['ss_disable_arp'], $action) . "\n";
                      } else {
                        echo "" . printOption("ENABLEARP", $l['ss_enable_arp'], $action) . "\n";
                      }
                    echo "</select>\n";
                  echo "</form>\n";
                echo "</td>\n";
                echo "<td>\n";
                  echo "<select name='linker' id='linker' onchange='javascript: sensorlink(this.value, \"$sid\");'>\n";
                    echo printOption("0", $l['ss_selectlink'], -1);
                    echo printOption("arpcache", $l['ss_arpcache'], -1);
                    echo printOption("arpstatic", $l['ss_arpconf'], -1);
                    echo printOption("detproto", $l['ss_detprotos'], -1);
                    echo printOption("sdetails", $l['ss_sdetails'], -1);
                  echo "</select>\n";
#                  echo "<input type='submit' name='submit' value='" .$l['g_update']. "' class='button' />";
                echo "</td>\n";
              }
            echo "</tr>\n";
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

