<?php $tab="5.2"; $pagetitle="Users - Edit"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 04-04-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 002 Added UTC support
# 001 Added language support
#############################################

if ($s_access_user == 0) {
  geterror(101);
  footer();
  exit;
}

$err = 0;
# Retrieving posted variables from $_GET
$allowed_get = array(
		"int_userid",
		"int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_user < 2) {
  $userid = $s_userid;
} else {
  $userid = $clean['userid'];
}

if ($s_access_user == 9) {
  $sql_user = "SELECT * FROM login WHERE id = $userid";
} else {
  $sql_user = "SELECT * FROM login WHERE id = $userid AND organisation = $q_org";
}
$debuginfo[] = $sql_user;
$result_user = pg_query($pgconn, $sql_user);
$numrows_user = pg_num_rows($result_user);

# Checking if the user exists
if ($numrows_user == 0) {
  $err = 1;
  $clean['m'] = 139;
} else {
  $access_user = pg_result($result_user, "access");
  $access_user = intval($access_user{2});
}

# Checking access
if ($s_access_user < $access_user) {
  $err = 1;
  $clean['m'] = 101;
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($err == 0) {
  $row = pg_fetch_assoc($result_user);

  $username = $row['username'];
  $org = $row['organisation'];
  $email = $row['email'];
  $maillog = $row['maillog'];
  $access = $row['access'];
  $gpg = $row['gpg'];
  $access_sensor = $access{0};
  $access_search = $access{1};
  $access_user = $access{2};
  $d_plotter = $row['d_plotter'];
  $d_plottype = $row['d_plottype'];
  $d_utc = $row['d_utc'];

  echo "<script type='text/javascript' src='${address}include/md5.js'></script>\n";
?>
<script type='text/javascript'>    
  $(function() {
    $('#password').pstrength({
      colors: ["#f00","#ffa500", "#c7c93a","#0d0","#080"],
    });
  });
</script>
<?php

echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['g_edit']. " $username</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='usermodify' action='usersave.php' method='post' onsubmit='return encrypt_pass();'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<td>" .$l['lo_username']. "</td>\n";
                if ($s_access_user > 0) {
                  echo "<td><input type='text' name='strip_html_escape_username' size='30' value='$username' /></td>\n";
                } else {
                  echo "<td>$username<input type='hidden' /></td>\n";
                }
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['lo_pass']. "</td>\n";
                echo "<td><input type='password' size='30' value='' id='password' /><input type='hidden' name='md5_pass' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['ma_confirmp']. "</td>\n";
                echo "<td><input type='password' size='30' value='' /><input type='hidden' name='md5_confirm' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['g_domain']. "</td>\n";
                echo "<td>\n";
                  if ($s_access_user == 9) {
                    echo "<select name='int_org'>\n";
                      echo "<option value='0'></option>\n";
                      $sql_org = "SELECT id, organisation FROM organisations";
                      $debuginfo[] = $sql_org;
                      $result_org = pg_query($pgconn, $sql_org);
                      while ($row_org = pg_fetch_assoc($result_org)) {
                        $d_org_id = $row_org['id'];
                        $d_org_name = $row_org['organisation'];
                        echo printOption($d_org_id, $d_org_name, $org);
                      }
                    echo "</select>\n";
                  } else {
                    $sql_org = "SELECT organisation FROM organisations WHERE id = $org";
                    $debuginfo[] = $sql_org;
                    $result_org = pg_query($pgconn, $sql_org);
                    $db_org_name = pg_result($result_org, 0);
                    echo "<input type='hidden' name='int_org' value='$org' />\n";
                    echo "$db_org_name";
                  }
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['ma_email']. "</td>\n";
                echo "<td>";
                  echo "<input type='text' name='strip_html_escape_email' value='" . $email . "' size='30'><br />";
                echo "</td>\n";
              echo "</td>\n";
              echo "<tr>\n";
                echo "<td>" .$l['ma_signing']. "</td>\n";
                echo "<td>\n";
                  echo printRadio($l['ma_enable_gpg'], "int_gpg", 1, $gpg) . "<br />\n";
                  echo printRadio($l['ma_disable_gpg'], "int_gpg", 0, $gpg) . "<br />\n";
                echo "</td>\n";
              echo "</tr>\n";

              if ($s_access_user > 1) {
                #### Access: Sensor ####
                echo "<tr>\n";
                  echo "<td valign='top'>" .$l['ma_asensor']. "</td>\n";
                  echo "<td>\n";
                    echo printRadio("0 - $v_access_ar_sensor[0]", "int_asensor", 0, $access_sensor) . "<br />\n";
                    echo printRadio("1 - $v_access_ar_sensor[1]", "int_asensor", 1, $access_sensor) . "<br />\n";
                    if ($c_enable_arp == 1 && $c_enable_argos == 1) {
                      echo printRadio("2 - $v_access_ar_sensor[2]", "int_asensor", 2, $access_sensor) . "<br />\n";
                    } elseif ($c_enable_arp == 1) {
                      echo printRadio("2 - " .$l['ma_arpac'], "int_asensor", 2, $access_sensor) . "<br />\n";
                    } elseif ($c_enable_argos == 1) {
                      echo printRadio("2 - " .$l['ma_argosac'], "int_asensor", 2, $access_sensor) . "<br />\n";
                    }
                    if ($s_access_user == 9) {
                      echo printRadio("9 - $v_access_ar_sensor[9]", "int_asensor", 9, $access_sensor) . "<br />\n";
                    }
                  echo "</td>\n";
                echo "</tr>\n";
                #### Access: Search ####
                echo "<tr>\n";
                  echo "<td valign='top'>" .$l['ma_asearch']. "</td>\n";
                  echo "<td>\n";
                    echo printRadio("1 - $v_access_ar_search[1]", "int_asearch", 1, $access_search) . "<br />\n";
                    if ($s_access_user == 9) {
                      echo printRadio("9 - $v_access_ar_search[9]", "int_asearch", 9, $access_search) . "<br />\n";
                    }
                  echo "</td>\n";
                echo "</tr>\n";
                #### Access: User ####
                echo "<tr>\n";
                  echo "<td valign='top'>" .$l['ma_auseradmin']. "</td>\n";
                  echo "<td>\n";
                    echo printRadio("0 - $v_access_ar_user[0]", "int_auser", 0, $access_user) . "<br />\n";
                    echo printRadio("1 - $v_access_ar_user[1]", "int_auser", 1, $access_user) . "<br />\n";
                    if ($s_access_user > 1) {
                      echo printRadio("2 - $v_access_ar_user[2]", "int_auser", 2, $access_user) . "<br />\n";
                    }
                    if ($s_access_user == 9) {
                      echo printRadio("9 - $v_access_ar_user[9]", "int_auser", 9, $access_user) . "<br />\n";
                    }
                  echo "</td>\n";
                echo "</tr>\n";
              } else {
                echo "<tr>\n";
                  echo "<td>" .$l['ma_asensor']. "</td><td>$v_access_ar_sensor[$access_sensor]</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td>" .$l['ma_asearch']. "</td><td>$v_access_ar_search[$access_search]</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                  echo "<td>" .$l['ma_auseradmin']. "</td><td>$v_access_ar_user[$access_user]</td>\n";
                echo "</tr>\n";
              }
              echo "<tr>\n";
                echo "<td><input type='hidden' name='int_userid' value='$userid' /></td>\n";
                echo "<td align='right'>\n";
                  echo "<input type='submit' name='submit' value='" .$l['g_update']. "' class='button' />\n";
                echo "</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
   echo "</div>\n"; #</leftmed>

  echo "<div class='rightmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['ma_pref']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='preferences' action='updatepref.php' method='get'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<td width='150'>" .$l['ma_def_graph']. "</td>\n";
                echo "<td>\n";
                  echo "<select name='int_plotter'>\n";
                    foreach ($v_plotters_ar as $key => $plotter) {
                      echo printOption($key, $plotter, $d_plotter);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td width='150'>" .$l['ma_def_graph_type']. "</td>\n";
                echo "<td>\n";
                  echo "<select name='int_plottype'>\n";
                    foreach ($v_plottertypes as $key => $plottype) {
                      echo printOption($key, $plottype, $d_plottype);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td width='150'>" .$l['ma_def_utc']. "</td>\n";
                echo "<td>\n";
                  echo "<select name='int_utc'>\n";
                    foreach ($v_timestamp_format_ar as $key => $val) {
                      echo printOption($key, $val, $d_utc);
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td colspan='2'><input class='button aright' type='submit' name='submit' value='" .$l['g_update']. "' /></td>\n";
                echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                echo "<input type='hidden' name='int_userid' value='$s_userid' />\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</rightmed>

  echo "<div class='rightmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
#        echo "<div class='blockHeader'>" .$l['ma_modules']. "</div>\n";
        echo "<div class='blockHeader'>\n";
          echo "<div class='blockHeaderLeft'>" .$l['ma_modules']. " " .printhelp(19). "</div>\n";
          echo "<div class='blockHeaderRight'>";
            echo "<select name='page_select' id='page_select' class='smallselect' onchange='switch_page_conf();'>";
              foreach ($v_page_select_ar as $key => $val) {
                echo printOption($key, $val, 0);
              }
            echo "</select>\n";
          echo "</div>\n";
        echo "</div>\n";

        echo "<div class='blockContent'>\n";
          $sql_mods = "SELECT indexmod_id FROM indexmods_selected WHERE login_id = $userid";
          $debuginfo[] = $sql_mods;
          $result_mods = pg_query($pgconn, $sql_mods);
          while ($row_mods = pg_fetch_assoc($result_mods)) {
            $mod_id = $row_mods['indexmod_id'];
            $mods[$mod_id] = $mod_id;
          }

          # INDEXMODS
          echo "<form name='indexmodsform' action='updateindexmods.php' method='post'>\n";
            echo "<input type='hidden' name='int_pageid' value='0' />\n";
            echo "<table class='datatable' id='page_indexmods' name='page_indexmods'>\n";
              echo "<tr>\n";
                echo "<td>\n";
                  echo printCheckBox($l['g_attacks'], "mods[]", 1, $mods[1]) . "<br />\n";
                  echo printCheckBox($l['g_exploits'], "mods[]", 2, $mods[2]) . "<br />\n";
                  echo printCheckBox($l['me_search'], "mods[]", 3, $mods[3]) . "<br />\n";
                  echo printCheckBox($l['mo_top10']. " ".$l['in_attackers']."", "mods[]", 4, $mods[4]) . "<br />\n";
                  echo printCheckBox($l['mo_top10']." ".$l['ra_proto_org']."", "mods[]", 5, $mods[5]) . "<br />\n";
                  echo printCheckBox($l['mod_virusscanners'], "mods[]", 6, $mods[6]) . "<br />\n";
                  echo printCheckBox($l['lc_cross'], "mods[]", 7, $mods[7]) . "<br />\n";
                  echo printCheckBox($l['me_maloff'], "mods[]", 8, $mods[8]) . "<br />\n";
                  echo printCheckBox($l['me_sensorstatus'], "mods[]", 9, $mods[9]) . "<br />\n";
                  echo printCheckBox($l['in_ports'], "mods[]", 10, $mods[10]) . "<br />\n";
                  echo printCheckBox($l['mo_top10']." ".$l['pl_sensors']."", "mods[]", 11, $mods[11]) . "<br />\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>\n";
                  echo "<input type='submit' name='submit' value='" .$l['g_update']. "' class='button' />\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                  echo "<input type='hidden' name='int_userid' value='$userid' />\n";
                echo "</td>\n";
            echo "</table>\n";
          echo "</form>\n";

          $sql_pageconf = "SELECT config FROM pageconf WHERE userid = '$userid' AND pageid = '1'";
          $debuginfo[] = $sql_pageconf;
          $result_pageconf = pg_query($pgconn, $sql_pageconf);
          $row = pg_fetch_assoc($result_pageconf);
          $pageconfig = $row['config'];
          $config_ar = split(",", $pageconfig);
          foreach ($config_ar as $key => $val) {
            $sensorstatus[$val] = $val;
          }

          # SENSOR STATUS (PAGE ID: 1)
          echo "<form name='sensorstatusform' action='updatepageconf.php' method='post''>\n";
            echo "<input type='hidden' name='int_pageid' value='1' />\n";
            echo "<table class='datatable' id='page_sensorstatus' name='page_sensorstatus' style='display:none;'>\n";
              echo "<tr>\n";
                echo "<td>\n";
                  echo printCheckBox($l['sd_sensorname'], "mods[]", 1, $sensorstatus[1]) . "<br />\n";
                  echo printCheckBox($l['sd_label'], "mods[]", 2, $sensorstatus[2]) . "<br />\n";
                  echo printCheckBox($l['ss_config'], "mods[]", 3, $sensorstatus[3]) . "<br />\n";
                  echo printCheckBox($l['sd_status'], "mods[]", 4, $sensorstatus[4]) . "<br />\n";
                  echo printCheckBox($l['sd_uptime'], "mods[]", 5, $sensorstatus[5]) . "<br />\n";
                  echo "<br /><b>" .$l['sd_sensorside']. "</b><br />\n";
                  echo printCheckBox($l['sd_rip'], "mods[]", 6, $sensorstatus[6]) . "<br />\n";
                  echo printCheckBox($l['sd_lip'], "mods[]", 7, $sensorstatus[7]) . "<br />\n";
                  echo printCheckBox($l['sd_sensormac'], "mods[]", 8, $sensorstatus[8]) . "<br />\n";
                  echo "<br /><b>" .$l['sd_serverside']. "</b><br />\n";
                  echo printCheckBox($l['sd_device'], "mods[]", 9, $sensorstatus[9]) . "<br />\n";
                  echo printCheckBox($l['sd_devmac'], "mods[]", 10, $sensorstatus[10]) . "<br />\n";
                  echo printCheckBox($l['sd_devip'], "mods[]", 11, $sensorstatus[11]) . "<br />\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>\n";
                  echo "<input type='submit' name='submit' value='" .$l['g_update']. "' class='button' />\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                  echo "<input type='hidden' name='int_userid' value='$userid' />\n";
                echo "</td>\n";
            echo "</table>\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</rightmed>
echo "</div>\n"; #</all>
echo "<script type='text/javascript' src='${address}include/jquery.pstrength-min.1.2.js'></script>\n";
}

pg_close($pgconn);
debug_sql();
footer();
?>
