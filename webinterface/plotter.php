<?php $tab="2.8"; $pagetitle="Graphs"; include("menu.php"); contentHeader();

####################################
# SURFnet IDS                      #
# Version 2.10.02                  #
# 14-12-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

#############################################
# Changelog:
# 2.10.02 Added default settings support, interval suggest
# 2.10.01 Added language support
# 2.00.04 Fixed bug with the popups
# 2.00.03 Fixed some layout issues
# 2.00.02 Graphs now shown in popups
# 2.00.01 2.00 version
# 1.04.04 Added virus graphs
# 1.04.03 updated destination port graphs & added timepstamp + organisation option in menu
# 1.04.02 Added destination port graphs
# 1.04.01 Initial release
#############################################

if (isset($_GET['int_type']) && !empty($_GET['int_type'])) {
  $qs = $_SERVER['QUERY_STRING'];
  $qs = strip_tags($qs);
  include_once '../include/php-ofc-library/open_flash_chart_object.php';
  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['pl_graph']. "</div>\n";
        echo "<div class='blockContent'>\n";
          open_flash_chart_object(960, 600, 'showopenflash.php?'.$qs, true, "include/");
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>
  footer();
  exit;
} else {

  # Retrieving cookie variables from $_COOKIE[SURFids]
  $allowed_cookie = array(
                "int_dplotter",
                "int_dplottype"
  );
  $check = extractvars($_COOKIE[SURFids], $allowed_cookie);
  debug_input();

  if (isset($clean['dplotter'])) {
    $d_plotter = $clean['dplotter'];
  } else {
    $d_plotter = 0;
  }
  if (isset($clean['dplottype'])) {
    $d_plottype = $clean['dplottype'];
  } else {
    $d_plottype = 1;
  }

  # Calculate the best interval time
  ########################
  $time = $to - $from;
  if ($time < 72001) {
    $int_suggest = 3600;
  } elseif ($time < 1728001) {
    $int_suggest = 86400;
  } else {
    $int_suggest = 604800;
  }

  if ($s_admin == 1) {
    $where = "";
  } else {
    $where = "AND organisations.id = $q_org";
  }
  $sql_getsensors = "SELECT sensors.id, sensors.keyname, sensors.vlanid, sensors.label, organisations.organisation FROM sensors, organisations ";
  $sql_getsensors .= "WHERE organisations.id = sensors.organisation $where ORDER BY sensors.keyname";
  $result_getsensors = pg_query($sql_getsensors);
  $debuginfo[] = $sql_getsensors;

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<div class='tabselect'>\n";
            echo "<input class='tabsel' id='button_severity' type='button' name='button_severity' value='" .$l['pl_sev']. "' onclick='javascript: shlinks(\"severity\", myplots);' />\n";
            echo "<input class='tab' id='button_attacks' type='button' name='button_attacks' value='" .$l['pl_attack']. "' onclick='javascript: shlinks(\"attacks\");' />\n";
            echo "<input class='tab' id='button_ports' type='button' name='button_ports' value='" .$l['pl_port']. "' onclick='javascript: shlinks(\"ports\");' />\n";
            echo "<input class='tab' id='button_os' type='button' name='button_os' value='" .$l['pl_os']. "' onclick='javascript: shlinks(\"os\");' />\n";
            echo "<input class='tab' id='button_virus' type='button' name='button_virus' value='" .$l['pl_virus']. "' onclick='javascript: shlinks(\"virus\");' />\n";
            echo "<select id='int_method' name='int_method'>\n";
              foreach ($v_plotters_ar as $key => $plotter) {
                echo printOption($key, $plotter, $d_plotter);
              }
            echo "</select>\n";
          echo "</div>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</actionBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['pl_graphs']. "</div>\n";
        echo "<div class='blockContent'>\n";
  ################
  # SEVERITY
  ################
  echo "<div class='tabcontent' id='severity' style='z-index: 9; display: block;'>\n";
#  echo "<form method='get' name='sevform' id='sevform' onsubmit='return buildqs();'>\n";
  echo "<form method='get' name='sevform' id='sevform'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>" .$l['pl_sensors']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid' size='5' class='smallselect' multiple='true'>\n";
            echo printOption("", "All sensors", $sid);
            while ($row = pg_fetch_assoc($result_getsensors)) {
              $id = $row['id'];
              $keyname = $row['keyname'];
              $vlanid = $row['vlanid'];
              $label = $row['label'];
              $sensor = sensorname($keyname, $vlanid);
              if ($label != "") $sensor = $label; 
              $org = $row['organisation'];
              if ($q_org == 0 ) {
                echo printOption($id, "$sensor - $org", $sid);
              } else {
                echo printOption($id, $sensor, $sid);
              }
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SEVERITY
      echo "<tr>\n";
        echo "<td>" .$l['pl_sev']. "</td>\n";
        echo "<td>\n";
          echo "<select name='severity' size='5' multiple='true' onclick='sh_plotsevtype(1);' id='plotsev_1'>\n";
            echo printOption(-1, $l['pl_allattacks'], -1);
            foreach ($v_severity_ar as $key => $val) {
              echo printOption($key, $val, -1);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='plotsevtype_1' style='display:none;'>\n";
        echo "<td>" .$l['pl_sevtype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sevtype' size='5' multiple='true'>\n";
            echo printOption(-1, $l['pl_allattacks'], -1);
            foreach ($v_severity_atype_ar as $key => $val) {
              echo printOption($key, $val, -1);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='plotsevtype_1' style='display:none;'>\n";
        echo "<td>" .$l['pl_options']. "</td>\n";
        echo "<td>" .printCheckBox($l['pl_totalmal'], "int_totalmal1", 1, 1). "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_int']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, $l['pl_hour'], $int_suggest);
            echo printOption(86400, $l['pl_day'], $int_suggest);
            echo printOption(604800, $l['pl_week'], $int_suggest);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_plottype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, $d_plottype);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' name='submit' value='" .$l['pl_show']. "' class='button' onclick='buildqs();' /></td>\n";
#        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # ATTACKS
  ################
  echo "<div class='tabcontent' id='attacks' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='attackform' id='attackform' onsubmit='return buildqs();'>\n";
#  echo "<form method='get' name='attackform' id='attackform'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>" .$l['pl_sensors']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid' size='5' class='smallselect' multiple='true'>\n";
            echo printOption(0, "All sensors", $sid);
            pg_result_seek($result_getsensors, 0);
            while ($sensord = pg_fetch_assoc($result_getsensors)) {
              $id = $sensord['id'];
              $keyname = $sensord['keyname'];
              $vlanid = $sensord['vlanid'];
              $label = $sensord['label'];
              $sensor = sensorname($keyname, $vlanid);
              if ($label != "") $sensor = $label; 
              $org = $sensord['organisation'];
              if ($q_org == 0) {
                echo printOption($id, "$sensor - $org", $sid);
              } else {
                echo printOption($id, $sensor, $sid);
              }
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      # ATTACKS
      $sql_getattacks = "SELECT * FROM stats_dialogue";
      $debuginfo[] = $sql_getattacks;
      $result_getattacks = pg_query($sql_getattacks);

      echo "<tr>\n";
        echo "<td>" .$l['pl_attack']. "</td>\n";
        echo "<td>\n";
          echo "<select name='attack' size='5' multiple='true'>\n";
            echo printOption(-1, $l['pl_allattacks'], -1);
            while ($attack_data = pg_fetch_assoc($result_getattacks)) {
              $id = $attack_data['id'];
              $name = $attack_data['name'];
              $name = str_replace("Dialogue", "", $name);
              echo printOption($id, $name, -1); 
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_int']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, $l['pl_hour'], $int_suggest);
            echo printOption(86400, $l['pl_day'], $int_suggest);
            echo printOption(604800, $l['pl_week'], $int_suggest);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_plottype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, $d_plotttype);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' onclick='buildqs();' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
#        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # PORTS
  ################
  echo "<div class='tabcontent' id='ports' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='portform' id='portform' onsubmit='return buildqs();'>\n";
#  echo "<form method='get' name='portform' id='portform'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>" .$l['pl_sensors']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid' size='5' class='smallselect' multiple='true'>\n";
            echo printOption(0, "All sensors", $sid);
            pg_result_seek($result_getsensors, 0);
            while ($sensord = pg_fetch_assoc($result_getsensors)) {
              $id = $sensord['id'];
              $keyname = $sensord['keyname'];
              $vlanid = $sensord['vlanid'];
              $label = $sensord['label'];
              $sensor = sensorname($keyname, $vlanid);
              if ($label != "") $sensor = $label; 
              $org = $sensord['organisation'];
              if ($q_org == 0) {
                echo printOption($id, "$sensor - $org", $sid);
              } else {
                echo printOption($id, $sensor, $sid);
              }
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_dports']. "<br />" .$l['pl_example']. ":<br />80,100-1000,!445,!137-145 or " .$l['pl_all']. "</td>\n";
        echo "<td>\n";
          echo "<input type='text' name='strip_html_escape_ports' size='20' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SEVERITY
      echo "<tr>\n";
        echo "<td>" .$l['pl_sev']. "</td>\n";
        echo "<td>\n";
          echo "<select name='severity' size='3' multiple='true' id='plotsev_2' onclick='sh_plotsevtype(2);'>\n";
            echo printOption(-1, $l['pl_allattacks'], -1);
            foreach ($v_severity_ar as $key => $val) {
              if ($key == 0 || $key == 1) {
                echo printOption($key, $val, -1);
              }
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='plotsevtype_2' style='display:none;'>\n";
        echo "<td>" .$l['pl_sevtype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sevtype' size='5' multiple='true'>\n";
            echo printOption(-1, $l['pl_allattacks'], -1);
            foreach ($v_severity_atype_ar as $key => $val) {
              echo printOption($key, $val, -1);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='plotsevtype_2' style='display:none;'>\n";
        echo "<td>" .$l['pl_options']. "</td>\n";
        echo "<td>" .printCheckBox($l['pl_totalmal'], "int_totalmal2", 1, 1). "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Interval</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, $l['pl_hour'], $int_suggest);
            echo printOption(86400, $l['pl_day'], $int_suggest);
            echo printOption(604800, $l['pl_week'], $int_suggest);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_plottype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, $d_plottype);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' onclick='buildqs();' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
#        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # OS
  ################
  echo "<div class='tabcontent' id='os' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='osorm' id='osform' onsubmit='return buildqs();'>\n";
#  echo "<form method='get' name='osorm' id='osform'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>" .$l['pl_sensors']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid' size='5' class='smallselect' multiple='true'>\n";
            echo printOption(0, "All sensors", $sid);
            pg_result_seek($result_getsensors, 0);
            while ($sensord = pg_fetch_assoc($result_getsensors)) {
              $id = $sensord['id'];
              $keyname = $sensord['keyname'];
              $vlanid = $sensord['vlanid'];
              $label = $sensord['label'];
              $sensor = sensorname($keyname, $vlanid);
              if ($label != "") $sensor = $label; 
              $org = $sensord['organisation'];
              if ($q_org == 0) {
                echo printOption($id, "$sensor - $org", $sid);
              } else {
                echo printOption($id, $sensor, $sid);
              }
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";

      # OS
      $sql_os = "SELECT os FROM ostypes ORDER BY os ASC";
      $debuginfo[] = $sql_os;
      $result_os = pg_query($pgconn, $sql_os);

      echo "<tr>\n";
        echo "<td>" .$l['pl_ostype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='os' size='4' multiple='true'>\n";
            echo printOption("all", "All OS types", "all");
            while ($os_data = pg_fetch_assoc($result_os)) {
              $os = $os_data['os'];
              echo printOption($os, $os, "none");
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SEVERITY
      echo "<tr>\n";
        echo "<td>" .$l['pl_sev']. "</td>\n";
        echo "<td>\n";
          echo "<select name='severity' size='3' multiple='true' id='plotsev_3' onclick='sh_plotsevtype(3);'>\n";
            echo printOption(-1, $l['pl_allattacks'], -1);
            foreach ($v_severity_ar as $key => $val) {
              if ($key == 0 || $key == 1) {
                echo printOption($key, $val, -1);
              }
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='plotsevtype_3' style='display:none;'>\n";
        echo "<td>" .$l['pl_sevtype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sevtype' size='5' multiple='true'>\n";
            echo printOption(-1, $l['pl_allattacks'], -1);
            foreach ($v_severity_atype_ar as $key => $val) {
              echo printOption($key, $val, -1);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr class='plotsevtype_3' style='display:none;'>\n";
        echo "<td>" .$l['pl_options']. "</td>\n";
        echo "<td>" .printCheckBox($l['pl_totalmal'], "int_totalmal3", 1, 1). "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_int']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, $l['pl_hour'], $int_suggest);
            echo printOption(86400, $l['pl_day'], $int_suggest);
            echo printOption(604800, $l['pl_week'], $int_suggest);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_plottype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, $d_plottype);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' onclick='buildqs();' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
#        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # VIRUS
  ################
  echo "<div class='tabcontent' id='virus' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='virusform' id='virusform' onsubmit='return buildqs();'>\n";
#  echo "<form method='get' name='virusform' id='virusform'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>" .$l['pl_sensors']. "</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid' size='5' class='smallselect' multiple='true'>\n";
            echo printOption(0, "All sensors", $sid);
            pg_result_seek($result_getsensors, 0);
            while ($sensord = pg_fetch_assoc($result_getsensors)) {
              $id = $sensord['id'];
              $keyname = $sensord['keyname'];
              $vlanid = $sensord['vlanid'];
              $label = $sensord['label'];
              $sensor = sensorname($keyname, $vlanid);
              if ($label != "") $sensor = $label; 
              $org = $sensord['organisation'];
              if ($q_org == 0) {
                echo printOption($id, "$sensor - $org", $sid);
              } else {
                echo printOption($id, $sensor, $sid);
              }
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";

      # VIRUS
      echo "<tr>\n";
        echo "<td>" .$l['pl_virusinfo']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_virus'>\n";
            echo printOption(0, $l['pl_allvirii'], 0);
            echo printOption(1, $l['pl_top10virii'], 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";

      $sql_scanner = "SELECT id, name FROM scanners ORDER BY id ASC";
      $debuginfo[] = $sql_scanner;
      $result_scanner = pg_query($pgconn, $sql_scanner);

      # SCANNERS
      echo "<tr>\n";
        echo "<td>" .$l['pl_scanner']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_scanner' size='5'>\n";
            while ($scanner_data = pg_fetch_assoc($result_scanner)) {
              $sid = $scanner_data['id'];
              $scanner = $scanner_data['name'];
              echo printOption($sid, $scanner, 1);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_int']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, $l['pl_hour'], $int_suggest);
            echo printOption(86400, $l['pl_day'], $int_suggest);
            echo printOption(604800, $l['pl_week'], $int_suggest);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>" .$l['pl_plottype']. "</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, $d_plottype);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' name='submit' value='" .$l['pl_show']. "' class='button' onclick='buildqs();' /></td>\n";
#        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='" .$l['pl_show']. "' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>

  echo "<input type='hidden' value='1' id='switch' />\n";
} 

debug_sql();

?>
<?php footer(); ?>
