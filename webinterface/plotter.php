<?php $tab="2.8"; $pagetitle="Graphs"; include("menu.php"); contentHeader();

####################################
# SURFids 2.00.03                  #
# Changeset 006                    #
# 27-05-2008                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

#############################################
# Changelog:
# 006 Removed javascript
# 005 Fixed bug #66
# 004 Fixed bug with the popups
# 003 Fixed some layout issues
# 002 Graphs now shown in popups
# 001 2.00 version
#############################################

#if ($_GET['int_type']) {
#  $qs = $_SERVER['QUERY_STRING'];
#  echo "<div class='centerbig'>\n";
#    echo "<div class='block'>\n";
#      echo "<div class='dataBlock'>\n";
#        echo "<div class='blockHeader'>Graph</div>\n";
#        echo "<div class='blockContent'>\n";
#        echo "<img id='plot' src='showplot.php?$qs'>\n";
#	echo "</div>\n";
#        echo "<div class='blockFooter'></div>\n";
#      echo "</div>\n";
#    echo "</div>\n";
#  echo "</div>\n";
#} else {
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
        echo "<div class='blockHeader'>Actions</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<div class='tabselect'>\n";
            echo "<input class='tabsel' id='button_severity' type='button' name='button_severity' value='Severity' onclick='javascript: shlinks(\"severity\", myplots);' />\n";
            echo "<input class='tab' id='button_attacks' type='button' name='button_attacks' value='Attack' onclick='javascript: shlinks(\"attacks\");' />\n";
            echo "<input class='tab' id='button_ports' type='button' name='button_ports' value='Port' onclick='javascript: shlinks(\"ports\");' />\n";
            echo "<input class='tab' id='button_os' type='button' name='button_os' value='OS' onclick='javascript: shlinks(\"os\");' />\n";
            echo "<input class='tab' id='button_virus' type='button' name='button_virus' value='Virus' onclick='javascript: shlinks(\"virus\");' />\n";
          echo "</div>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</actionBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Graphs</div>\n";
        echo "<div class='blockContent'>\n";
  ################
  # SEVERITY
  ################
  echo "<div class='tabcontent' id='severity' style='z-index: 9; display: block;'>\n";
  echo "<form method='get' name='sevform' id='sevform' onsubmit='return buildqs();'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>Sensors:</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid[]' size='5' class='smallselect' multiple='true'>\n";
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
        echo "<td>Severity:</td>\n";
        echo "<td>\n";
          echo "<select name='severity[]' size='5' multiple='true'>\n";
            echo printOption(99, "All attacks", 99);
            foreach ($v_severity_ar as $key => $val) {
              echo printOption($key, $val, 99);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Interval</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, "Hour", 3600);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Plot type</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 1);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' name='submit' value='Show' class='button' onclick='buildqs();' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # ATTACKS
  ################
  echo "<div class='tabcontent' id='attacks' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='attackform' id='attackform' onsubmit='return buildqs();'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>Sensors:</td>\n";
        echo "<td>\n";


          echo "<select name='sensorid[]' size='5' class='smallselect' multiple='true'>\n";
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
        echo "<td>Attack:</td>\n";
        echo "<td>\n";
          echo "<select name='attack[]' size='5' multiple='true'>\n";
            echo printOption(99, "All attacks", 99);
            while ($attack_data = pg_fetch_assoc($result_getattacks)) {
              $id = $attack_data['id'];
              $name = $attack_data['name'];
              $name = str_replace("Dialogue", "", $name);
              echo printOption($id, $name, 99); 
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Interval</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, "Hour", 3600);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Plot type</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 1);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' onclick='buildqs();' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # PORTS
  ################
  echo "<div class='tabcontent' id='ports' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='portform' id='portform' onsubmit='return buildqs();'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>Sensors:</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid[]' size='5' class='smallselect' multiple='true'>\n";
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
        echo "<td>Destination ports/ranges:<br />example:<br />80,100-1000,!445,!137-145 or all</td>\n";
        echo "<td>\n";
          echo "<input type='text' name='strip_html_escape_ports' size='20' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SEVERITY
      echo "<tr>\n";
        echo "<td>Severity:</td>\n";
        echo "<td>\n";
          echo "<select name='severity[]' size='3' multiple='true'>\n";
            echo printOption(99, "All attacks", 99);
            foreach ($v_severity_ar as $key => $val) {
              if ($key == 0 || $key == 1) {
                echo printOption($key, $val, 99);
              }
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Interval</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, "Hour", 3600);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Plot type</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 1);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' onclick='buildqs();' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # OS
  ################
  echo "<div class='tabcontent' id='os' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='osorm' id='osform' onsubmit='return buildqs();'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>Sensors:</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid[]' size='5' class='smallselect' multiple='true'>\n";
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
        echo "<td>OS type:</td>\n";
        echo "<td>\n";
          echo "<select name='os[]' size='4' multiple='true'>\n";
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
        echo "<td>Severity:</td>\n";
        echo "<td>\n";
          echo "<select name='severity[]' size='3' multiple='true'>\n";
            echo printOption(99, "All attacks", 99);
            foreach ($v_severity_ar as $key => $val) {
              if ($key == 0 || $key == 1) {
                echo printOption($key, $val, 99);
              }
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Interval</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, "Hour", 3600);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Plot type</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 1);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' onclick='buildqs();' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # VIRUS
  ################
  echo "<div class='tabcontent' id='virus' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' name='virusform' id='virusform' onsubmit='buildqs();'>\n";
    echo "<table class='datatable'>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td width='185'>Sensors:</td>\n";
        echo "<td>\n";
          echo "<select name='sensorid[]' size='5' class='smallselect' multiple='true'>\n";
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
        echo "<td>Virus info:</td>\n";
        echo "<td>\n";
          echo "<select name='virus[]' size='2' multiple='true'>\n";
            echo printOption("all", "All virusses", "all");
            echo printOption("top10", "Top 10 virusses", "none");
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";

      $sql_scanner = "SELECT id, name FROM scanners ORDER BY id ASC";
      $debuginfo[] = $sql_scanner;
      $result_scanner = pg_query($pgconn, $sql_scanner);

      # SCANNERS
      echo "<tr>\n";
        echo "<td>Scanner:</td>\n";
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
        echo "<td>Interval</td>\n";
        echo "<td>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(3600, "Hour", 3600);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td>Plot type</td>\n";
        echo "<td>\n";
          echo "<select name='int_type'>\n";
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 1);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='button' name='submit' value='Show' class='button' onclick='buildqs();' /></td>\n";
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
  debug_sql();
#}
?>
<?php footer(); ?>
