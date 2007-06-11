<?php include("menu.php"); set_title("Argos Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 04-06-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 1.04.01 Initial release
####################################


$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access_sensor = intval($s_access{0});

$allowed_get = array(
                "int_m",
);
$check = extractvars($_GET, $allowed_get);
debug_input();


if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

if ($s_access_sensor > 1) {

  add_to_sql("argos.id", "select");
  add_to_sql("sensors.keyname", "select");
  add_to_sql("sensors.vlanid", "select");
  add_to_sql("sensors.tapip", "select");
  add_to_sql("argos.imageid", "select");
  add_to_sql("argos.templateid", "select");
  add_to_sql("argos", "table");
  add_to_sql("argos_images", "table");
  add_to_sql("argos_templates", "table");
  add_to_sql("sensors", "table");
  add_to_sql("sensors.id = argos.sensorid", "where");
  add_to_sql("argos.imageid = argos_images.id", "where");
  add_to_sql("argos.templateid = argos_templates.id", "where");
  add_to_sql("argos.id", "group");
  add_to_sql("sensors.id", "group");
  add_to_sql("sensors.keyname", "group");
  add_to_sql("sensors.vlanid", "group");
  add_to_sql("argos.templateid", "group");
  add_to_sql("sensors.tapip", "group");
  add_to_sql("argos.imageid", "group");
  add_to_sql("sensors.id", "order");
  if ($s_admin != 1) {
    add_to_sql("sensors.organisation = $s_org", "where");
  }
  prepare_sql();
  $sql_argos = "SELECT $sql_select ";
  $sql_argos .= "FROM $sql_from ";
  $sql_argos .= " $sql_where ";
  $sql_argos .= " GROUP BY $sql_group ";
  $sql_argos .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_argos;
  $result_argos = pg_query($pgconn, $sql_argos);

  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader' width='50'>Sensor</td>\n";
      echo "<td class='dataheader' width='50'>Device IP</td>\n";
      echo "<td class='dataheader' width='150'>Imagename</td>\n";
      echo "<td class='dataheader' width='75'>Template</td>\n";
      echo "<td class='dataheader' width='100'>Timespan</td>\n";
      echo "<td></td>\n";
      echo "<td></td>\n";
    echo "</tr>\n";

    while ($row = pg_fetch_assoc($result_argos)) {
      $id = $row['id'];
      $keyname = $row['keyname'];
      $vlanid = $row['vlanid'];
      $tapip = $row['tapip'];
      $templateid = $row['templateid'];
      $imageid = $row['imageid'];
      if ($vlanid != 0) {	
      	$sensor = "$keyname-$vlanid";
      } else {
      	$sensor = "$keyname";
      }
      echo "<tr class='datatr'>\n";
        echo "<form name='argosadmin_update' action='argosupdate.php' method='post'>\n";
          echo "<td>$sensor</td>\n";
          echo "<td>$tapip</td>\n";
          echo "<td>";
            echo "<select name='int_imageid' style='background-color:white;'>\n";
              $sql_image = "SELECT id, name FROM argos_images ORDER BY id";
              $debuginfo[] = $sql_image;
              $query_image = pg_query($sql_image);
              while ($rowimage = pg_fetch_assoc($query_image)) {
                echo printOption($rowimage["id"], $rowimage["name"], $imageid); 
              }
            echo "</select>\n";
          echo "</td>\n";
          echo "<td>";
            echo "<select name='int_templateid' style='background-color:white;'>\n";
              $sql_template = "SELECT id, name FROM argos_templates ORDER BY id";
              $debuginfo[] = $sql_template;
              $query_template = pg_query($sql_template);
              while ($rowtemplate = pg_fetch_assoc($query_template)) {
                echo printOption($rowtemplate["id"], $rowtemplate["name"], $templateid);
              }
            echo "</select>\n";
          echo "</td>\n";
          echo "<td>\n";
            echo "<select name='strip_html_escape_timespan' style='background-color:white;'>\n";
              $sql_timespan = "SELECT timespan FROM argos WHERE id = $id";
              $debuginfo[] = $sql_timespan;
              $query_timespan = pg_query($sql_timespan);
              $rowtimespan = pg_fetch_assoc($query_timespan);
              $timespan = $rowtimespan["timespan"];
              echo printOption('D', 'Last 24 hour' , $timespan); 
              echo printOption('W', 'Last week' , $timespan); 
              echo printOption('M', 'Last month' , $timespan); 
              echo printOption('Y', 'Last year' , $timespan); 
              echo printOption('N', 'No timespan' , $timespan); 
            echo "</select>\n";
          echo "</td>\n";
          echo "<input type='hidden' name='int_argosid' value='$id' />\n";
          echo "<td><input type='submit' class='button' style='width: 100%;' value='Update' /></td>\n";
        echo "</form>\n";
        echo "<form name='argosadmin_del' action='argosdel.php' method='post'>\n";
          echo "<input type='hidden' name='int_argosid' value='$id' />\n";
          echo "<td><input type='submit' class='button' style='width: 100%;' value='Delete' /></td>\n";
        echo "</form>\n";
      echo "</tr>\n";
    }

    echo "<form name='argosadmin_add' action='argosadd.php' method='post'>\n";
    echo "<tr>\n";
      echo "<td class='datatd' colspan=2>\n";
        if ($s_admin == 1) {
          $where = " sensors.status != 3 ";
        } else {
          $where = " sensors.status != 3 AND sensors.organisation = '$s_org'";
        }
        echo "<select name=\"int_sensorid\" style=\"background-color:white;\">\n";
          $sql = "SELECT sensors.id, sensors.keyname, sensors.vlanid, organisations.organisation, sensors.tapip FROM sensors, organisations ";
          $sql .= "WHERE organisations.id = sensors.organisation AND $where ORDER BY sensors.keyname";
          $debuginfo[] = $sql;
          $query = pg_query($sql);
          while ($sensor_data = pg_fetch_assoc($query)) {
            $sid = $sensor_data['id'];
            $label = $sensor_data["keyname"];  
            $vlanid = $sensor_data["vlanid"];
            $org = $sensor_data["organisation"];
            $tapip = $sensor_data["tapip"];
            if ($vlanid != 0 ) {
              $label .=  "-" .$vlanid. " (" .$tapip. ")";
            } else {
              $label .=  " (" .$tapip. ")";
            }
            if ($s_admin == 1) {
              $label .= " (" .$org. ")";
            }
            echo printOption($sid, $label, $sensorid);
          }
        echo "</select>\n";
      echo "  </td>\n";
      echo "<td class='datatd'>";
        echo "<select name='int_imageid' style='background-color:white;'>\n";
          $sql_image = "SELECT id, name FROM argos_images ORDER BY id";
          $debuginfo[] = $sql_image;
          $query_image = pg_query($sql_image);
          while ($rowimage = pg_fetch_assoc($query_image)) {
            echo printOption($rowimage["id"], $rowimage["name"]); 
          }
        echo "</select>\n";
      echo "</td>\n";
      echo "<td class='datatd'>";
        echo "<select name='int_templateid' style='background-color:white;'>\n";
          $sql_template = "SELECT id, name FROM argos_templates ORDER BY id";
          $debuginfo[] = $sql_template;
          $query_template = pg_query($sql_template);
          while ($rowtemplate = pg_fetch_assoc($query_template)) {
            echo printOption($rowtemplate["id"], $rowtemplate["name"]);
          }
        echo "</select>\n";
      echo "</td>\n";
      echo "<td class='datatd'>\n";
        echo "<select name='strip_html_escape_timespan' style='background-color:white;'>\n";
          echo printOption('D', 'Last 24 hour' , ""); 
          echo printOption('W', 'Last week' , ""); 
          echo printOption('M', 'Last month' , ""); 
          echo printOption('Y', 'Last year' , ""); 
          echo printOption('N', 'No timespan' , ""); 
        echo "</select>\n";
      echo "</td>\n";
      echo "<td class='datatd' colspan=2><input type='submit' class='button' style='width: 100%;' value='Add' /></td>\n";
    echo "</tr>\n";
    echo "</form>\n";
  echo "</table>\n";

  if ($s_admin == 1) {
    echo "<br />\n";
    echo "<h4>Image</h4>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='dataheader'>Name</td>\n";
        echo "<td class='dataheader'>Server IP</td>\n";
        echo "<td class='dataheader'>Imagename on Server</td>\n";
        echo "<td class='dataheader'>OS Name</td>\n";
        echo "<td class='dataheader'>OS Language</td>\n";
        echo "<td class='dataheader'>Mac address</a></td>\n";
        echo "<td  ></td>\n";
        echo "<td  ></td>\n";
      echo "</tr>\n";

      $sql_image = "SELECT * FROM argos_images ORDER BY id";
      $debuginfo[] = $sql_image;
      $query_image = pg_query($sql_image);
      while ($rowimage = pg_fetch_assoc($query_image)) {
        $imageid = $rowimage["id"];
        $name = $rowimage["name"];
        $serverip = $rowimage["serverip"];
        $macaddr = $rowimage["macaddr"];
        $imagename = $rowimage["imagename"];
        $osname = $rowimage["osname"];
        $oslang = $rowimage["oslang"];
        echo "<tr class='datatr'>\n";
          echo "<form name='argosadmin_updateimage' action='argosupdateimage.php' method='post'>\n";
            echo "<td class='datatd'><input type='text' name='strip_html_name' size='25' value='$name' /></td>";
            echo "<td class='datatd'><input type='text' name='ip_serverip' size='15' value='$serverip' /></td>";
            echo "<td class='datatd'><input type='text' name='strip_html_imagename' size='15' value='$imagename' /></td>";
            echo "<td class='datatd'>\n";
              echo "<select name='strip_html_osname' style='background-color:white;'>\n";
                echo printOption('win2k', 'win2k' , $osname); 
                echo printOption('winxp', 'winxp' , $osname); 
                echo printOption('linux', 'linux' , $osname); 
              echo "</select>\n";
            echo "</td>\n";
            echo "<td class='datatd'>\n";
              echo "<select name='strip_html_oslang' style='background-color:white;'>\n";
                echo printOption('nl', 'Dutch' , $oslang);
                echo printOption('en', 'English' , $oslang);
              echo "</select>\n";
            echo "</td>\n";
            echo "<td class='datatd'><input type='text' name='mac_macaddr' size='12' value='$macaddr' /></td>";
            echo "<input type='hidden' name='int_imageid' value='$imageid'>\n";
            echo "<td class='datatd'><input type='submit' class='button' style='width: 100%;' value='Update' /></td>\n";
          echo "</form>\n";
          echo "<form name='argosadmin_delimage' action='argosdelimage.php' method='post'>\n";
            echo "<input type='hidden' name='int_imageid' value='$imageid'>\n";
            echo "<td class='datatd'><input type='submit' class='button' style='width: 100%;' value='Delete' /></td>\n";
          echo "</form>\n";
        echo "</tr>\n";
      }
      echo "<form name='argosadmin_addimage' action='argosaddimage.php' method='post'>\n";
        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'><input type='text' name='strip_html_name' size='25' /></td>";
          echo "<td class='datatd'><input type='text' name='ip_serverip' size='15' /></td>";
          echo "<td class='datatd'><input type='text' name='strip_html_imagename' size='15' /></td>";
          echo "<td class='datatd'>\n";
            echo "<select name='strip_html_osname' style='background-color:white;'>\n";
              echo printOption('win2k', 'win2k', ""); 
              echo printOption('winxp', 'winxp', ""); 
              echo printOption('linux', 'linux', ""); 
            echo "</select>\n";
          echo "</td>\n";
          echo "<td class='datatd'>\n";
            echo "<select name='strip_html_oslang' style='background-color:white;'>\n";
              echo printOption('nl', 'Dutch' , "");
              echo printOption('en', 'English' , "");
 	    echo "</select>\n";
          echo "</td>\n";
          echo "<td class='datatd'><input type='text' name='mac_macaddr' size='12' /></td>";
          echo "<td class='datatd' colspan=2><input type='submit' class='button' style='width: 100%;' value='Add' /></td>\n";
        echo "</tr>\n";
      echo "</form>\n";
    echo "</table>\n";
  }
}
debug_sql();
?>
<?php footer(); ?>
