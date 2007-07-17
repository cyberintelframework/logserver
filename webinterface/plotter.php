<?php include("menu.php"); set_title("History graphs");

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 15-03-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

#############################################
# Changelog:
# 1.04.04 Added virus graphs
# 1.04.03 updated destination port graphs & added timepstamp + organisation option in menu
# 1.04.02 Added destination port graphs
# 1.04.01 Initial release
#############################################
?>

<style type="text/css">@import url('./calendar/css/calendar.css');</style>
<script type="text/javascript" src="./calendar/js/calendar.js"></script>
<script type="text/javascript" src="./calendar/js/calendar-en.js"></script>
<script type="text/javascript" src="./calendar/js/calendar-setup.js"></script>
<script>

var mytabs = new Array();
mytabs[0] = "attacks";
mytabs[1] = "severity";
mytabs[2] = "ports";
mytabs[3] = "os";
mytabs[4] = "virus";

function shlinks(id) {
  var status = document.getElementById(id).style.display;

  for (i=0;i<mytabs.length;i++) {
    var tab = mytabs[i];
    var but = 'button_' + tab;
    if (tab == id) {
      document.getElementById(but).className='tabsel';
      document.getElementById(id).style.display='block';
    } else {
      document.getElementById(but).className='tab';
      document.getElementById(tab).style.display='none';
    }
  }
  document.getElementById(id).blur();
}

</script>

<?
# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access_search = intval($s_access{1});

if ($_GET) {
  $qs = $_SERVER['QUERY_STRING'];
  echo "<img src='showplot.php?$qs'><br />\n";
} else {
  if ($s_admin == 1) {
    $where = "";
  } else {
    $where = "AND organisations.id = $s_org";
  }
  $sql_getsensors = "SELECT sensors.id, sensors.keyname, sensors.vlanid, organisations.organisation FROM sensors, organisations ";
  $sql_getsensors .= "WHERE organisations.id = sensors.organisation $where ORDER BY sensors.keyname";
  $debuginfo[] = $sql_getsensors;
  $result_getsensors = pg_query($sql_getsensors);

  echo "<div class='tabselect' align='left' style='float: left;'>\n";
    echo "<input class='tabsel' id='button_severity' type='button' name='button_severity' value='Severity' onclick='javascript: shlinks(\"severity\", mytabs);' />\n";
    echo "<input class='tab' id='button_attacks' type='button' name='button_attacks' value='Attack' onclick='javascript: shlinks(\"attacks\");' />\n";
    echo "<input class='tab' id='button_ports' type='button' name='button_ports' value='Port' onclick='javascript: shlinks(\"ports\");' />\n";
    echo "<input class='tab' id='button_os' type='button' name='button_os' value='OS' onclick='javascript: shlinks(\"os\");' />\n";
    echo "<input class='tab' id='button_virus' type='button' name='button_virus' value='Virus' onclick='javascript: shlinks(\"virus\");' />\n";
  echo "</div>\n";
  echo "<br /><br />\n";

  ################
  # SEVERITY
  ################
  echo "<div class='tabcontent' id='severity' style='z-index: 9; display: block;'>\n";
  echo "<form method='get' action='$_SELF' name='plotform' id='plotform'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>Select:</td>\n";
        echo "<td class='datatd' width='300'>";
	echo "<select name='strip_html_escape_tsselect' style='background-color:white;'>
	 <option value=''></option>
	 <option value='D'>Last 24 hour</option>
	 <option value='T'>Today</option>
	 <option value='W'>Last week</option>
	 <option value='M'>Last month</option>
	 <option value='Y'>Last year</option>
	</select>";
        echo "</td>\n";
      echo "</tr>\n";
      if ($s_access_search == 9) {
        if (!isset($clean['org'])) {
          $err = 1;
        }
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' width='200'>Organisation:</td>\n";
          echo "<td class='datatd' width='300'>";

            $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
            $debuginfo[] = $sql_orgs;
            $result_orgs = pg_query($pgconn, $sql_orgs);
            echo "<select name='int_org'>\n";
              echo printOption(0, "All", $q_org) . "\n";
              while ($row = pg_fetch_assoc($result_orgs)) {
                $org_id = $row['id'];
                $organisation = $row['organisation'];
                echo printOption($org_id, $organisation, $q_org) . "\n";
              }
            echo "</select>&nbsp;\n";
          echo "</td>\n";
        echo "</tr>\n";
      }
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>From:</td>\n";
        echo "<td class='datatd' width='300'>";
          echo "<input type='text' name='strip_html_escape_tsstart' id='ts_start_sev' value='' />\n";
          echo "<input type='button' value='...' name='ts_start_sev_trigger' id='ts_start_sev_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>To:</td>\n";
        echo "<td class='datatd'>";
          echo "<input type='text' name='strip_html_escape_tsend' id='ts_end_sev' value='' />\n";
          echo "<input type='button' value='...' name='ts_end_sev_trigger' id='ts_end_sev_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td class='datatd'>Sensors:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='sensorid[]' style='background-color:white;' size='4' multiple='true'>\n";
            echo printOption("", "All sensors", "");
            while ($sensor_data = pg_fetch_assoc($result_getsensors)) {
              $sid = $sensor_data['id'];
              $label = $sensor_data["keyname"];
              $vlanid = $sensor_data["vlanid"];
              $org = $sensor_data["organisation"];
              if ($vlanid != 0 ) {
                $label .=  "-" .$vlanid;
              }
              echo printOption($sid, $label, $sensorid);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SEVERITY
      echo "<tr>\n";
        echo "<td class='datatd'>Severity:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='severity[]' style='background-color:white;' size='5' multiple='true'>\n";
            echo printOption(99, "All attacks", 99);
            foreach ($v_severity_ar as $key => $val) {
              echo printOption($key, $val, 99);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Interval</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(0, "", 0);
            echo printOption(3600, "Hour", 0);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Plot type</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_type'>\n";
            echo printOption(0, "", 0);
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 0);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # ATTACKS
  ################
  echo "<div class='tabcontent' id='attacks' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' action='$_SELF' name='plotform' id='plotform'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>Select:</td>\n";
        echo "<td class='datatd' width='300'>";
	echo "<select name='strip_html_escape_tsselect' style='background-color:white;'>
	 <option value=''></option>
	 <option value='D'>Last 24 hour</option>
	 <option value='T'>Today</option>
	 <option value='W'>Last week</option>
	 <option value='M'>Last month</option>
	 <option value='Y'>Last year</option>
	</select>";
        echo "</td>\n";
      echo "</tr>\n";
      if ($s_access_search == 9) {
        if (!isset($clean['org'])) {
          $err = 1;
        }
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' width='200'>Organisation:</td>\n";
          echo "<td class='datatd' width='300'>";

            $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
            $debuginfo[] = $sql_orgs;
            $result_orgs = pg_query($pgconn, $sql_orgs);
            echo "<select name='int_org'>\n";
              echo printOption(0, "All", $q_org) . "\n";
              while ($row = pg_fetch_assoc($result_orgs)) {
                $org_id = $row['id'];
                $organisation = $row['organisation'];
                echo printOption($org_id, $organisation, $q_org) . "\n";
              }
            echo "</select>&nbsp;\n";
          echo "</td>\n";
        echo "</tr>\n";
      }
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>From:</td>\n";
        echo "<td class='datatd' width='300'>";
          echo "<input type='text' name='strip_html_escape_tsstart' id='ts_start_att' value='' />\n";
          echo "<input type='button' value='...' name='ts_start_att_trigger' id='ts_start_att_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>To:</td>\n";
        echo "<td class='datatd'>";
          echo "<input type='text' name='strip_html_escape_tsend' id='ts_end_att' value='' />\n";
          echo "<input type='button' value='...' name='ts_end_att_trigger' id='ts_end_att_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td class='datatd'>Sensors:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='sensorid[]' style='background-color:white;' size='4' multiple='true'>\n";
            echo printOption(0, "All sensors", 0);
            pg_result_seek($result_getsensors, 0);
            while ($sensor_data = pg_fetch_assoc($result_getsensors)) {
              $sid = $sensor_data['id'];
              $label = $sensor_data["keyname"];
              $vlanid = $sensor_data["vlanid"];
              $org = $sensor_data["organisation"];
              if ($vlanid != 0 ) {
                $label .=  "-" .$vlanid;
              }
              echo printOption($sid, $label, $sensorid);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      # ATTACKS
      $sql_getattacks = "SELECT * FROM stats_dialogue";
      $debuginfo[] = $sql_getattacks;
      $result_getattacks = pg_query($sql_getattacks);

      echo "<tr>\n";
        echo "<td class='datatd'>Attack:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='attack[]' style='background-color:white;' size='5' multiple='true'>\n";
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
        echo "<td class='datatd'>Interval</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(0, "", 0);
            echo printOption(3600, "Hour", 0);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Plot type</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_type'>\n";
            echo printOption(0, "", 0);
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 0);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # PORTS
  ################
  echo "<div class='tabcontent' id='ports' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' action='$_SELF' name='plotform' id='plotform'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>Select:</td>\n";
        echo "<td class='datatd' width='300'>";
	echo "<select name='strip_html_escape_tsselect' style='background-color:white;'>
	 <option value=''></option>
	 <option value='D'>Last 24 hour</option>
	 <option value='T'>Today</option>
	 <option value='W'>Last week</option>
	 <option value='M'>Last month</option>
	 <option value='Y'>Last year</option>
	</select>";
        echo "</td>\n";
      echo "</tr>\n";
      if ($s_access_search == 9) {
        if (!isset($clean['org'])) {
          $err = 1;
        }
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' width='200'>Organisation:</td>\n";
          echo "<td class='datatd' width='300'>";

            $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
            $debuginfo[] = $sql_orgs;
            $result_orgs = pg_query($pgconn, $sql_orgs);
            echo "<select name='int_org'>\n";
              echo printOption(0, "All", $q_org) . "\n";
              while ($row = pg_fetch_assoc($result_orgs)) {
                $org_id = $row['id'];
                $organisation = $row['organisation'];
                echo printOption($org_id, $organisation, $q_org) . "\n";
              }
            echo "</select>&nbsp;\n";
          echo "</td>\n";
        echo "</tr>\n";
      }
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>From:</td>\n";
        echo "<td class='datatd' width='300'>";
          echo "<input type='text' name='strip_html_escape_tsstart' id='ts_start_por' value='' />\n";
          echo "<input type='button' value='...' name='ts_start_por_trigger' id='ts_start_por_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>To:</td>\n";
        echo "<td class='datatd'>";
          echo "<input type='text' name='strip_html_escape_tsend' id='ts_end_por' value='' />\n";
          echo "<input type='button' value='...' name='ts_end_por_trigger' id='ts_end_por_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td class='datatd'>Sensors:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='sensorid[]' style='background-color:white;' size='4' multiple='true'>\n";
            echo printOption(0, "All sensors", 0);
            pg_result_seek($result_getsensors, 0);
            while ($sensor_data = pg_fetch_assoc($result_getsensors)) {
              $sid = $sensor_data['id'];
              $label = $sensor_data['keyname'];
              $vlanid = $sensor_data['vlanid'];
              $org = $sensor_data["organisation"];
              if ($vlanid != 0 ) {
                $label .=  "-" .$vlanid;
              }
              echo printOption($sid, $label, $sensorid);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Destination ports/ranges:<br />example:<br />80,100-1000,!445,!137-145 or all</td>\n";
        echo "<td class='datatd'>\n";
          echo "<input type='text' name='strip_html_escape_ports' size='20' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SEVERITY
      echo "<tr>\n";
        echo "<td class='datatd'>Severity:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='severity[]' style='background-color:white;' size='3' multiple='true'>\n";
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
        echo "<td class='datatd'>Interval</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(0, "", 0);
            echo printOption(3600, "Hour", 0);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Plot type</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_type'>\n";
            echo printOption(0, "", 0);
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 0);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # OS
  ################
  echo "<div class='tabcontent' id='os' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' action='$_SELF' name='plotform' id='plotform'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>Select:</td>\n";
        echo "<td class='datatd' width='300'>";
	echo "<select name='strip_html_escape_tsselect' style='background-color:white;'>
          <option value=''></option>
          <option value='D'>Last 24 hour</option>
          <option value='T'>Today</option>
          <option value='W'>Last week</option>
          <option value='M'>Last month</option>
          <option value='Y'>Last year</option>
	</select>";
        echo "</td>\n";
      echo "</tr>\n";
      if ($s_access_search == 9) {
        if (!isset($clean['org'])) {
          $err = 1;
        }
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' width='200'>Organisation:</td>\n";
          echo "<td class='datatd' width='300'>";
  	
            $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
            $debuginfo[] = $sql_orgs;
            $result_orgs = pg_query($pgconn, $sql_orgs);
            echo "<select name='int_org'>\n";
              echo printOption(0, "All", $q_org) . "\n";
              while ($row = pg_fetch_assoc($result_orgs)) {
                $org_id = $row['id'];
                $organisation = $row['organisation'];
                echo printOption($org_id, $organisation, $q_org) . "\n";
              }
            echo "</select>&nbsp;\n";
          echo "</td>\n";
        echo "</tr>\n";
      }
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>From:</td>\n";
        echo "<td class='datatd' width='300'>";
          echo "<input type='text' name='strip_html_escape_tsstart' id='ts_start_os' value='' />\n";
          echo "<input type='button' value='...' name='ts_start_os_trigger' id='ts_start_os_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>To:</td>\n";
        echo "<td class='datatd'>";
          echo "<input type='text' name='strip_html_escape_tsend' id='ts_end_os' value='' />\n";
          echo "<input type='button' value='...' name='ts_end_os_trigger' id='ts_end_os_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td class='datatd'>Sensors:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='sensorid[]' style='background-color:white;' size='4' multiple='true'>\n";
            echo printOption(0, "All sensors", 0);
            pg_result_seek($result_getsensors, 0);
            while ($sensor_data = pg_fetch_assoc($result_getsensors)) {
              $sid = $sensor_data['id'];
              $label = $sensor_data['keyname'];
              $vlanid = $sensor_data['vlanid'];
              $org = $sensor_data["organisation"];
              if ($vlanid != 0 ) {
                $label .=  "-" .$vlanid;
              }
              echo printOption($sid, $label, $sensorid);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";

      # OS
      $sql_os = "SELECT DISTINCT split_part(name, ' ', 1) as os FROM system ORDER BY os ASC";
      $debuginfo[] = $sql_os;
      $result_os = pg_query($pgconn, $sql_os);

      echo "<tr>\n";
        echo "<td class='datatd'>OS type:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='os[]' style='background-color:white;' size='4' multiple='true'>\n";
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
        echo "<td class='datatd'>Severity:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='severity[]' style='background-color:white;' size='3' multiple='true'>\n";
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
        echo "<td class='datatd'>Interval</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(0, "", 0);
            echo printOption(3600, "Hour", 0);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Plot type</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_type'>\n";
            echo printOption(0, "", 0);
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 0);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  ################
  # VIRUS
  ################
  echo "<div class='tabcontent' id='virus' style='z-index: 9; display: none;'>\n";
  echo "<form method='get' action='$_SELF' name='plotform' id='plotform'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>Select:</td>\n";
        echo "<td class='datatd' width='300'>";
	echo "<select name='strip_html_escape_tsselect' style='background-color:white;'>
          <option value=''></option>
          <option value='D'>Last 24 hour</option>
          <option value='T'>Today</option>
          <option value='W'>Last week</option>
          <option value='M'>Last month</option>
          <option value='Y'>Last year</option>
	</select>";
        echo "</td>\n";
      echo "</tr>\n";
      if ($s_access_search == 9) {
        if (!isset($clean['org'])) {
          $err = 1;
        }
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd' width='200'>Organisation:</td>\n";
          echo "<td class='datatd' width='300'>";
  	
            $sql_orgs = "SELECT * FROM organisations WHERE NOT organisation = 'ADMIN'";
            $debuginfo[] = $sql_orgs;
            $result_orgs = pg_query($pgconn, $sql_orgs);
            echo "<select name='int_org'>\n";
              echo printOption(0, "All", $q_org) . "\n";
              while ($row = pg_fetch_assoc($result_orgs)) {
                $org_id = $row['id'];
                $organisation = $row['organisation'];
                echo printOption($org_id, $organisation, $q_org) . "\n";
              }
            echo "</select>&nbsp;\n";
          echo "</td>\n";
        echo "</tr>\n";
      }
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='200'>From:</td>\n";
        echo "<td class='datatd' width='300'>";
          echo "<input type='text' name='strip_html_escape_tsstart' id='ts_start_vir' value='' />\n";
          echo "<input type='button' value='...' name='ts_start_vir_trigger' id='ts_start_vir_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>To:</td>\n";
        echo "<td class='datatd'>";
          echo "<input type='text' name='strip_html_escape_tsend' id='ts_end_vir' value='' />\n";
          echo "<input type='button' value='...' name='ts_end_vir_trigger' id='ts_end_vir_trigger' />\n";
        echo "</td>\n";
      echo "</tr>\n";
      # SENSORS
      echo "<tr>\n";
        echo "<td class='datatd'>Sensors:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='sensorid[]' style='background-color:white;' size='4' multiple='true'>\n";
            echo printOption(0, "All sensors", 0);
            pg_result_seek($result_getsensors, 0);
            while ($sensor_data = pg_fetch_assoc($result_getsensors)) {
              $sid = $sensor_data['id'];
              $label = $sensor_data['keyname'];
              $vlanid = $sensor_data['vlanid'];
              $org = $sensor_data["organisation"];
              if ($vlanid != 0 ) {
                $label .=  "-" .$vlanid;
              }
              echo printOption($sid, $label, $sensorid);
            }
           echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";

      # VIRUS
      echo "<tr>\n";
        echo "<td class='datatd'>Virus info:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='virus[]' style='background-color:white;' size='2' multiple='true'>\n";
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
        echo "<td class='datatd'>Scanner:</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_scanner' style='background-color:white;' size='5'>\n";
            while ($scanner_data = pg_fetch_assoc($result_scanner)) {
              $sid = $scanner_data['id'];
              $scanner = $scanner_data['name'];
              echo printOption($sid, $scanner, 1);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";

      # SEVERITY
#      echo "<tr>\n";
#        echo "<td class='datatd'>Severity:</td>\n";
#        echo "<td class='datatd'>\n";
#          echo "<select name='severity[]' style='background-color:white;' size='3' multiple='true'>\n";
#            echo printOption(99, "All attacks", 99);
#            foreach ($v_severity_ar as $key => $val) {
#              if ($key == 0 || $key == 1) {
#                echo printOption($key, $val, 99);
#              }
#            }
#           echo "</select>\n";
#        echo "</td>\n";
#      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Interval</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_interval'>\n";
            echo printOption(0, "", 0);
            echo printOption(3600, "Hour", 0);
            echo printOption(86400, "Day", 0);
            echo printOption(604800, "Week", 0);
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Plot type</td>\n";
        echo "<td class='datatd'>\n";
          echo "<select name='int_type'>\n";
            echo printOption(0, "", 0);
            foreach ($v_plottertypes as $key => $val) {
              echo printOption($key, $val, 0);
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td colspan='2' align='right'><input type='submit' name='submit' value='Show' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  echo "</form>\n";
  echo "</div>\n";

  debug_sql();
?>
<script>



function catcalc(cal) {
    var date = cal.date;
    var time = date.getTime()
    // use the _other_ field
    var field = document.getElementById("ts_end");
    time += Date.WEEK; // add one week
    var date2 = new Date(time);
    field.value = date2.print("%d-%m-%Y %H:%M");
}

Calendar.setup(
  {
      inputField  : "ts_start_por",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_start_por_trigger",
      showsTime   : true,
      singleClick : false,
      onUpdate    : catcalc
  }
);
Calendar.setup(
  {
      inputField  : "ts_end_por",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_end_por_trigger",
      showsTime   : true,
      singleClick : false
  }
);


Calendar.setup(
  {
      inputField  : "ts_start_att",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_start_att_trigger",
      showsTime   : true,
      singleClick : false,
      onUpdate    : catcalc
  }
);
Calendar.setup(
  {
      inputField  : "ts_end_att",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_end_att_trigger",
      showsTime   : true,
      singleClick : false
  }
);


Calendar.setup(
  {
      inputField  : "ts_start_sev",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_start_sev_trigger",
      showsTime   : true,
      singleClick : false,
      onUpdate    : catcalc
  }
);
Calendar.setup(
  {
      inputField  : "ts_end_sev",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_end_sev_trigger",
      showsTime   : true,
      singleClick : false
  }
);


Calendar.setup(
  {
      inputField  : "ts_start_os",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_start_os_trigger",
      showsTime   : true,
      singleClick : false,
      onUpdate    : catcalc
  }
);
Calendar.setup(
  {
      inputField  : "ts_end_os",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_end_os_trigger",
      showsTime   : true,
      singleClick : false
  }
);


Calendar.setup(
  {
      inputField  : "ts_start_vir",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_start_vir_trigger",
      showsTime   : true,
      singleClick : false,
      onUpdate    : catcalc
  }
);
Calendar.setup(
  {
      inputField  : "ts_end_vir",
      ifFormat    : "%d-%m-%Y %H:%M",
      button      : "ts_end_vir_trigger",
      showsTime   : true,
      singleClick : false
  }
);
</script>
<?php
}
?>
<?php footer(); ?>
