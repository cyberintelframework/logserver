<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 11-12-2006                       #
# Hiroshi Suzuki of NTT-CERT       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.04 Changed debug stuff
# 1.04.03 Added pg_close when admin != 1
# 1.04.02 Changed the database storage to base64
# 1.04.01 Initial release by Mr. Hiroshi Suzuki
#############################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

if ($s_admin != 1) {
  pg_close($pgconn);
  header("Location: index.php");
  exit;
}
$sql_server = "SELECT * FROM serverstats WHERE interval = 'day' ORDER BY type";
$debuginfo[] = $sql_server;
$result_server = pg_query($pgconn, $sql_server);

$labelcheck = 0;

while ($row = pg_fetch_assoc($result_server)) {
  $imgid = $row['id'];
  $type = $row['type'];
  $label = $row['label'];
  $interval = $row['interval'];

  if ($type == "memory") {
    echo "<h3>$type</h3>\n";
  } else {
    echo "<h3>$type - $label</h3>\n";
  }
  echo "<table>\n";
    echo "<tr>\n";
      echo "<td>\n";
        echo "Daily Graph (5 minute averages)<br />\n";
        echo "<a href='serverstatsview.php?int_imgid=$imgid'><img alt='$label Daily' src='showserver.php?int_imgid=$imgid' border='1' /></a>\n";
      echo "</td>\n";
    echo "</tr>\n";
  echo "</table>\n";
} 
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
