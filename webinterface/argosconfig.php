<?php $tab="4.4"; $pagetitle="Argos"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.00.04                  #
# 24-09-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 2.00.04 Added hash check
# 2.00.03 Added access check
# 2.00.02 Removed obsolete session variable declarations
# 2.00.01 Initial release
####################################

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
                "int_m",
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($s_access_sensor > 1) {
  # Building SQL query
  add_to_sql("argos.id", "select");
  add_to_sql("sensors.keyname", "select");
  add_to_sql("sensors.vlanid", "select");
  add_to_sql("sensors.tapip", "select");
  add_to_sql("sensors.label", "select");
  add_to_sql("argos.imageid", "select");
  add_to_sql("argos.templateid", "select");
  add_to_sql("argos.sensorid", "select");
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
  add_to_sql("sensors.label", "group");
  add_to_sql("argos.templateid", "group");
  add_to_sql("sensors.tapip", "group");
  add_to_sql("argos.imageid", "group");
  add_to_sql("argos.sensorid", "group");
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

  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Sensor redirects</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='15%'>Sensor " .printhelp("sensor"). "</th>\n";
              echo "<th width='15%'>Device IP " .printhelp("deviceip"). "</th>\n";
              echo "<th width='30%'>Imagename " .printhelp("imagename"). "</th>\n";
              echo "<th width='20%'>Template " .printhelp("template"). "</th>\n";
              echo "<th width='10%'>Timespan " .printhelp("timespan"). "</th>\n";
              echo "<th width='5%'></th>\n";
              echo "<th width='5%'></th>\n";
            echo "</tr>\n";
            while ($row = pg_fetch_assoc($result_argos)) {
              $id = $row['id'];
              $keyname = $row['keyname'];
              $vlanid = $row['vlanid'];
              $label = $row['label'];
              $tapip = $row['tapip'];
              $templateid = $row['templateid'];
              $imageid = $row['imageid'];
              $sensorid = $row['sensorid'];
              $sensor = sensorname($keyname, $vlanid);
              if ($label != "" ) $sensor = $label;
              echo "<tr>\n";
                echo "<form name='argosadmin_update' action='argosupdate.php' method='post'>\n";
                  echo "<td>$sensor</td>\n";
                  echo "<td>$tapip</td>\n";
                  echo "<td>";
                    echo "<select name='int_imageid'>\n";
                      $sql_image = "SELECT id, name, organisationid FROM argos_images ORDER BY id";
                      $debuginfo[] = $sql_image;
                      $query_image = pg_query($sql_image);
                      while ($rowimage = pg_fetch_assoc($query_image)) {
                        $orgid = $rowimage["organisationid"]; 
                        if ($orgid == $s_org || $orgid == 0) {
                          echo printOption($rowimage["id"], $rowimage["name"], $imageid); 
                        }
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                  echo "<td>";
                    echo "<select name='int_templateid'>\n";
                      $sql_template = "SELECT id, name FROM argos_templates ORDER BY id";
                      $debuginfo[] = $sql_template;
                      $query_template = pg_query($sql_template);
                      while ($rowtemplate = pg_fetch_assoc($query_template)) {
                        echo printOption($rowtemplate["id"], $rowtemplate["name"], $templateid);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                  echo "<td>\n";
                    echo "<select name='strip_html_escape_timespan'>\n";
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
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                  echo "<td><input type='submit' class='button' value='Update' /></td>\n";
                echo "</form>\n";
                echo "<form name='argosadmin_del' action='argosdel.php' method='post'>\n";
                  echo "<input type='hidden' name='int_argosid' value='$id' />\n";
                  echo "<input type='hidden' name='int_sensorid' value='$sensorid' />\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                  echo "<td><input type='submit' class='button' value=\"Delete\" onclick=\"return confirm('This will also delete your range redirections.\\nAre you sure?');\" /></td>\n";
                echo "</form>\n";
              echo "</tr>\n";
            }
            echo "<form name='argosadmin_add' action='argosadd.php' method='post'>\n";
              echo "<tr>\n";
                echo "<td class='bottom' colspan=2>\n";
                  if ($s_admin == 1) {
                    $where = " sensors.status != 3 ";
                  } else {
                    $where = " sensors.status != 3 AND sensors.organisation = '$q_org'";
                  }
                  echo "<select name='int_sensorid'>\n";
                    $sql = "SELECT sensors.id, sensors.keyname, sensors.vlanid, organisations.organisation, sensors.tapip, sensors.label FROM sensors, organisations ";
                    $sql .= "WHERE organisations.id = sensors.organisation AND $where ORDER BY sensors.keyname";
                    $debuginfo[] = $sql;
                    $query = pg_query($sql);
        	    while ($sensor_data = pg_fetch_assoc($query)) {
                	$sid = $sensor_data['id'];
	                $keyname = $sensor_data["keyname"];
        	        $vlanid = $sensor_data["vlanid"];
               		$label = $sensor_data["label"];
             	  	$org = $sensor_data["organisation"];
           	        $tapip = $sensor_data["tapip"];
              	 	if ($label != "") { 
                 	 $name = $label;
             		} else {  
                 	  if ($vlanid != 0 ) {
                	    $name = sensorname($keyname, $vlanid);
			  } else {
			    $name = $keyname;
			  }
			}
			if ($q_org == 0) {
               		    $name .= " (" .$org. ") (" .$tapip. ")";
                 	} else {
               		    $name .= " (" .$tapip. ")";
                        }
	  	        echo printOption($sid, $name, $sensorid);
        	 }


                  echo "</select>\n";
                echo "</td>\n";
                echo "<td class='bottom'>";
                  echo "<select name='int_imageid'>\n";
                    $sql_image = "SELECT id, name, organisationid FROM argos_images ORDER BY id";
                    $debuginfo[] = $sql_image;
                    $query_image = pg_query($sql_image);
                    while ($rowimage = pg_fetch_assoc($query_image)) {
                      $orgid = $rowimage["organisationid"]; 
                      if ($orgid == $s_org || $orgid == 0) {
                        echo printOption($rowimage["id"], $rowimage["name"], $imageid); 
                      }
                    }
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td class='bottom'>";
                  echo "<select name='int_templateid'>\n";
                    $sql_template = "SELECT id, name FROM argos_templates ORDER BY id";
                    $debuginfo[] = $sql_template;
                    $query_template = pg_query($sql_template);
                    while ($rowtemplate = pg_fetch_assoc($query_template)) {
                      echo printOption($rowtemplate["id"], $rowtemplate["name"], $templateid);
                    }
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td class='bottom'>\n";
                  echo "<select name='strip_html_escape_timespan'>\n";
                    echo printOption('D', 'Last 24 hour' , ""); 
                    echo printOption('W', 'Last week' , ""); 
                    echo printOption('M', 'Last month' , ""); 
                    echo printOption('Y', 'Last year' , ""); 
                    echo printOption('N', 'No timespan' , ""); 
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td class='bottom' colspan=2><input type='submit' class='button' value='Add' /></td>\n";
              echo "</tr>\n";
              echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "</form>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Redirect to ranges</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='60%'>Sensor</th>\n";
              echo "<th width='30%'>Range or IP</th>\n";
              echo "<th width='5%'></th>\n";
              echo "<th width='5%'></th>\n";
            echo "</tr>\n";
            if ($s_admin != 1) {
              $where = "AND sensors.organisation = $s_org";
            } else { 
              $where = "";
            }
            $sql_range = "SELECT argos_ranges.id, argos_ranges.sensorid, sensors.keyname, sensors.vlanid, sensors.label, argos_ranges.range FROM sensors, argos_ranges WHERE sensors.id = argos_ranges.sensorid $where";
            $debuginfo[] = $sql_range;
            $query_range = pg_query($sql_range);
            while ($rowrange = pg_fetch_assoc($query_range)) {
              $rangeid = $rowrange["id"];
              $keyname = $rowrange["keyname"];
              $vlanid = $rowrange["vlanid"];
              $sensorid = $rowrange["sensorid"];
              $range = $rowrange["range"];
              $label = $rowrange["label"];
              $sensor = sensorname($keyname, $vlanid);
              if ($label != "") $sensor = $label;
              echo "<tr>\n";
                echo "<form name='argosadmin_updaterange' action='argosupdaterange.php' method='post'>\n";
                  echo "<td>$sensor</td>";
                  echo "<td><input type='text' name='inet_range' size='18' value='$range' /></td>";
                  echo "<td><input type='submit' class='button' value='Update' /></td>\n";
                  echo "<input type='hidden' name='int_rangeid' value='$rangeid'>\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                echo "</form>\n";
                echo "<form name='argosadmin_delrange' action='argosdelrange.php' method='post'>\n";
                  echo "<input type='hidden' name='int_rangeid' value='$rangeid'>\n";
                  echo "<td><input type='submit' class='button' value='Delete' /></td>\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                echo "</form>\n";
              echo "</tr>\n";
            }
            echo "<tr>\n";
              echo "<form name='argosadmin_addrange' action='argosaddrange.php' method='post'>\n";
                echo "<td class='bottom'>\n";
                  if ($s_admin == 1) {
		        $where = " sensors.status != 3 ";
		   } else {
		        $where = " sensors.status != 3 AND sensors.organisation = '$q_org'";
	           }
	 
	           $sql = "SELECT argos.sensorid, sensors.keyname, sensors.vlanid, organisations.organisation, sensors.tapip, sensors.label FROM sensors, organisations, argos ";
	           $sql .= "WHERE organisations.id = sensors.organisation AND sensors.id = argos.sensorid AND $where ORDER BY sensors.keyname";
	           $debuginfo[] = $sql;
	           $query = pg_query($sql);
		  
		  echo "<select name='int_sensorid'>\n";
        	    while ($sensor_data = pg_fetch_assoc($query)) {
                	$sid = $sensor_data['sensorid'];
	                $keyname = $sensor_data["keyname"];
        	        $vlanid = $sensor_data["vlanid"];
               		$label = $sensor_data["label"];
             	  	$org = $sensor_data["organisation"];
           	        $tapip = $sensor_data["tapip"];
              	 	if ($label != "") { 
                 	 $name = $label;
             		} else {  
                 	  if ($vlanid != 0 ) {
                	    $name = sensorname($keyname, $vlanid);
			  } else {
			    $name = $keyname;
			  }
			}
			if ($q_org == 0) {
               		    $name .= " (" .$org. ") (" .$tapip. ")";
                 	} else {
               		    $name .= " (" .$tapip. ")";
                        }
	  	        echo printOption($sid, $name, $sensorid);
        	 }
                  echo "</select>\n";
                echo "</td>\n";
                echo "<td class='bottom'><input type='text' name='inet_range' size='18' /></td>";
                echo "<td class='bottom' colspan='2'><input type='submit' class='button' value='Add' /></td>\n";
                echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
              echo "</form>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
}
debug_sql();
?>
<?php footer(); ?>
