<?php

####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 12-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.04 Fixed binname variable name
# 1.04.03 Changed data input handling
# 1.04.02 Changes due to new table layout binaries, uniq_binaries, scanners, etc
# 1.04.01 Code layout
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Added intval() to session variables + access handling change
# 1.02.03 Added some more input checks and removed includes
# 1.02.02 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  echo "<script src='include/surfnetids.js'>\n";
    echo "popout();";
  echo "</script>\n";
}

# Retrieving some session variables
$q_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = $s_access{1};

$err = 0;
# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_id"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking $_GET'ed variables
if (isset($clean['id'])) {
  $id = $clean['id'];
} else {
  $m = 117;
  geterror($m);
  $err = 1;
}

### Admin check
if ($err != 1) {
  if ($s_access_search == 9) {
    $sql_details = "SELECT attackid, text, type FROM details WHERE attackid = " .$id. " ORDER BY type ASC";
  } else {
    $sql_details = "SELECT details.attackid, details.text, details.type FROM details, sensors ";
    $sql_details .= " WHERE details.attackid = " .$id. " AND details.sensorid = sensors.id AND sensors.organisation = '" .$q_org. "' ORDER BY type ASC";
  }
  $result_details = pg_query($pgconn, $sql_details);
  $debuginfo[] = $sql_details;

  echo "<div class='leftmed'>\n";
#    echo "<div class='block'>\n";
#      echo "<div class='dataBlock'>\n";
#        echo "<div class='blockHeader'>Details of attack ID: $id</div>\n";
#        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<td colspan='2' class='title'>Details of attack ID: $id</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<th width='30%'>Type</th>\n";
              echo "<th width='70%'>Info</th>\n";
            echo "</tr>\n";

            while ($row = pg_fetch_assoc($result_details)) {
              $attackid = $row['attackid'];
              $logging = $row['text'];
              $type = $row['type'];
              $typetext = $v_attacktype_ar[$type];

              $sql_check = "SELECT COUNT(id) as total FROM uniq_binaries WHERE name = '$logging'";
              $result_check = pg_query($pgconn, $sql_check);
              $row = pg_fetch_assoc($result_check);
              $count = $row['total'];
              $debuginfo[] = $sql_check;

              echo "<tr>\n";
                echo "<td>$typetext</td>\n";
                if ($count != 0) {
                  echo "<td><a href='binaryhist.php?md5_binname=$logging'>$logging<a/></td>\n";
                } else {
                  echo "<td>$logging</td>\n";
                }
              echo "</tr>\n";
            }
          echo "</table>\n";
#        echo "</div>\n"; #</blockContent>
#        echo "<div class='blockFooter'></div>\n";
#      echo "</div>\n"; #</dataBlock>
#    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
}

echo "<div class='all'>\n";
echo "&nbsp;";
echo "</div>\n";

echo "<div class='leftsmall'>\n";
  echo "<div class='block'>\n";
    echo "<input type='button' onclick='popout();' class='button' value='Close this popup' />\n";
  echo "</div>\n";
echo "</div>\n";

pg_close($pgconn);
debug_sql();
?>
<?php cleanfooter(); ?>
