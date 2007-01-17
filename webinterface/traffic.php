<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 11-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
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
                "int_onoff",
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_search == 9) {
  $q_org = 0;
} else {
  $q_org = intval($s_org);
}

echo "QORG: $q_org\n";
echo "s_access_search: $s_access_search\n";
echo "s_admin: $s_admin\n";

$sql_getorg = "SELECT organisation FROM organisations WHERE id = $q_org";
$result_getorg = pg_query($pgconn, $sql_getorg);

$debuginfo[] = $sql_getorg;

echo "<form name='selectorg' method='get' action='traffic.php'>\n";
    echo "<select name='int_onoff' onChange='javascript: this.form.submit();'>\n";
      echo printOption(0, "Online", $q_org) . "\n";
      echo printOption(1, "Offline", $q_org) . "\n";
      echo printOption(2, "All", $q_org) . "\n";
    echo "</select>&nbsp;\n";
echo "</form>\n";


$sql_getorg = "SELECT organisation FROM organisations WHERE id = $s_org";
$debuginfo[] = $sql_getorg;
$result_getorg = pg_query($pgconn, $sql_getorg);
$db_org_name = pg_result($result_getorg, 0);

if ($s_admin == 1) {
  echo "<h3>Traffic analysis for: All</h3>\n";
  $sql_getactive = "SELECT organisation, keyname, vlanid  FROM sensors WHERE status = 1";
} else {
  echo "<h3>Traffic analysis for: $db_org_name</h3>\n";
  $sql_getactive = "SELECT organisation, keyname, vlanid  FROM sensors WHERE status = 1 AND organisation = $s_org";
}


$debuginfo[] = $sql_getactive;
$result_getactive = pg_query($pgconn, $sql_getactive);
while ($rowactive = pg_fetch_assoc($result_getactive)) {

$db_orgid = $rowactive['organisation'];
$db_orgkeyname = $rowactive['keyname'];
$db_orgvlanid = $rowactive['vlanid'];

if ($db_orgvlanid != 0) {
$label = "$db_orgkeyname-$db_orgvlanid";
}
else {
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

if ($numrows_result_sensors == 0) {
  $m = geterror(92);
  echo $m;
}  
else {

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

}
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
