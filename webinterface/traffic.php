<?php include("menu.php");?> 
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.09                  #
# 20-06-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.09 Fixed a bug with displaying organisation and the all sensors image 
# 1.04.08 Fixed a bug when no traffic graphs are present and user is admin
# 1.04.07 Fixed display of allsensor when no admin
# 1.04.06 Added allsensors pictures again
# 1.04.05 Changed location debug info 
# 1.04.04 Added online/offline status selector
# 1.04.03 Changed debug stuff
# 1.04.02 Added vlan support 
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Storing images in the database
# 1.02.03 Removed includes
# 1.02.02 Changed the way sensor is passed along the querystring
# 1.02.01 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access_search = intval($_SESSION['s_access_search']);

$allowed_get = array(
	"int_onoff"
);
$check = extractvars($_GET, $allowed_get);

if ($s_access_search == 9) {
  $q_org = 0;
} else {
  $q_org = intval($s_org);
}

if (isset($clean['onoff'])) {
  $onoff = $clean['onoff'];
} else {
  $onoff = 1;
}

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);
$db_org_name = pg_result($result_getorg, 0);

$debuginfo[] = $sql_getorg;

if ($s_admin == 1) {
  echo "<h3>Traffic analysis for: All</h3>\n";
} else {
  echo "<h3>Traffic analysis for: $db_org_name</h3>\n";
  add_to_sql("organisation = $s_org", "where");
}

debug_input();

echo "<form name='selectonoff' method='get' action='traffic.php'>\n";
echo "Display: ";
  echo "<select name='int_onoff' onChange='javascript: this.form.submit();'>\n";
    echo printOption(1, "Online Sensors", $onoff) . "\n";
    echo printOption(0, "Offline Sensors", $onoff) . "\n";
    echo printOption(2, "All Sensors", $onoff) . "\n";
  echo "</select>&nbsp;\n";
echo "</form>\n";
echo "<br />\n";

add_to_sql("organisation", "select");
add_to_sql("keyname", "select");
add_to_sql("vlanid", "select");
add_to_sql("sensors", "table");
add_to_sql("keyname", "order");

if ($onoff != 2) {
  add_to_sql("status = $onoff", "where");
}
prepare_sql();

$sql_getactive = "SELECT $sql_select FROM $sql_from $sql_where ORDER BY $sql_order";
$debuginfo[] = $sql_getactive;
$result_getactive = pg_query($pgconn, $sql_getactive);

if ($s_admin == 1) {
  $sql_allsensors = "SELECT id FROM rrd WHERE type = 'day' AND label = 'allsensors'";
  $result_allsensors = pg_query($pgconn, $sql_allsensors);
  $row_allsensors = pg_fetch_assoc($result_allsensors);
  $allid = $row_allsensors['id'];

  if ($allid != "" && $onoff != 0) {
    echo "<table>\n";
      echo "<tr>\n";
        echo "<td><a href='trafficview.php?int_imgid=$allid'><img src='showtraffic.php?int_imgid=$allid' alt='All sensors' border='1' /></a></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
  }
}

while ($rowactive = pg_fetch_assoc($result_getactive)) {
  $db_orgid = $rowactive['organisation'];
  $db_orgkeyname = $rowactive['keyname'];
  $db_orgvlanid = $rowactive['vlanid'];

  if ($db_orgvlanid != 0) {
    $label = "$db_orgkeyname-$db_orgvlanid";
  } else {
    $label = "$db_orgkeyname";
  }

  if ($s_admin == 1) {
    $sql_sensors = "SELECT id, label, orgid FROM rrd WHERE type = 'day' AND label = '$label'";
  } else {
    $sql_sensors = "SELECT id, label, orgid FROM rrd WHERE orgid = $s_org AND type = 'day' AND label = '$label'";
  }
  $debuginfo[] = $sql_sensors;
  $result_sensors = pg_query($pgconn, $sql_sensors);
  $numrows_result_sensors = pg_numrows($result_sensors);

  echo "<table>\n";
  while ($row = pg_fetch_assoc($result_sensors)) {
    $imgid = $row['id'];
    $orgid = $row['orgid'];
    $label = $row['label'];

    echo "<tr>\n";
      echo "<td><a href='trafficview.php?int_imgid=$imgid'><img src='showtraffic.php?int_imgid=$imgid' alt='$sensor' border='1' /></a></td>\n";
    echo "</tr>\n";
  } 
  echo "</table>\n";
}

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
