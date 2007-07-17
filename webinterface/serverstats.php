<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.05                  #
# 22-06-2007                       #
# Hiroshi Suzuki of NTT-CERT       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 1.04.04 Added support for multiple servers 
# 1.04.04 Changed debug stuff
# 1.04.03 Added pg_close when admin != 1
# 1.04.02 Changed the database storage to base64
# 1.04.01 Initial release by Mr. Hiroshi Suzuki
#############################################

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);

# Checking access
if ($s_admin != 1) {
  pg_close($pgconn);
  header("Location: index.php");
  exit;
}

echo "<h3>Serverinfo</h3>";

# Retrieving posted variables from $_GET
$allowed_get = array(
	"strip_html_escape_server"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['server'])) {
  $server = $clean['server'];
}

# Showing available servers
$sql_server = "SELECT DISTINCT(server) FROM serverstats";
$debuginfo[] = $sql_server;
$result_server = pg_query($pgconn, $sql_server);

echo "<form name='selectserver' method='get' action='serverstats.php'>\n";
  echo "Display: ";
  echo "<select name='strip_html_escape_server' onChange='javascript: this.form.submit();'>\n";
    echo printOption("", "", "") . "\n";
    while ($row = pg_fetch_assoc($result_server)) {
      $fserver = $row['server'];
      echo printOption("$fserver", "$fserver", "$server") . "\n";
    }
  echo "</select>&nbsp;\n";
echo "</form>\n";

if ($server == '') { $server = $fserver; }

# Showing the actual server stats
$sql_server = "SELECT * FROM serverstats WHERE interval = 'day' AND server = '$server' ORDER BY type";
$debuginfo[] = $sql_server;
$result_server = pg_query($pgconn, $sql_server);

while ($row = pg_fetch_assoc($result_server)) {
  $imgid = $row['id'];
  $type = $row['type'];
  $label = $row['label'];
  $interval = $row['interval'];

  if ($type == "memory" || $type == "cpu") {
    echo "<h3>$server $type</h3>\n";
  } else {
    echo "<h3>$server $type - $label</h3>\n";
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
