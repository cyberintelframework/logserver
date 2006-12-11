<?php include("menu.php"); set_title("Log Detail"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.02                  #
# 07-12-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.02 Changes due to new table layout binaries, uniq_binaries, scanners, etc
# 1.04.01 Code layout
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Added intval() to session variables + access handling change
# 1.02.03 Added some more input checks and removed includes
# 1.02.02 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$err = 0;

### Variables check
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
} else {
  echo "Wrong or missing attack ID in the querystring.<br />\n";
  echo "<a href='logindex.php'>Logging Overview</a>\n";
  $err = 1;
}

### Admin check
if ($err != 1) {
  if ($s_access_search == 9) {
    $sql_details = "SELECT attackid, text, type FROM details WHERE attackid = " .$id;
  } else {
    $sql_details = "SELECT details.attackid, details.text, details.type FROM details, sensors WHERE details.attackid = " .$id. " AND details.sensorid = sensors.id AND sensors.organisation = '" .$s_org. "'";
  }
  $result_details = pg_query($pgconn, $sql_details);

  $debuginfo[] = $sql_details;

  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' width='100'>AttackID</td>\n";
      echo "<td class='dataheader' width='300'>Logging</td>\n";
    echo "</tr>\n";

  while ($row = pg_fetch_assoc($result_details)) {
    $attackid = $row['attackid'];
    $logging = $row['text'];
    $type = $row['type'];

    $sql_check = "SELECT COUNT(id) as total FROM uniq_binaries WHERE name = '$logging'";
    $result_check = pg_query($pgconn, $sql_check);
    $row = pg_fetch_assoc($result_check);
    $count = $row['total'];

    $debuginfo[] = $sql_check;

    echo "<tr>\n";
      echo "<td class='datatd'>$attackid</td>\n";
      if ($count != 0) {
        echo "<td class='datatd'><a href='binaryhist.php?binname=$logging'>$logging<a/></td>\n";
      } else {
        echo "<td class='datatd'>$logging</td>\n";
      }
    echo "</tr>\n";
  }

  echo "</table>\n";
}
pg_close($pgconn);
debug();
?>
<?php footer(); ?>
