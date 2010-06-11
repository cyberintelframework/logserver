<?php $tab="2.5"; $pagetitle="Server Info"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Hiroshi Suzuki of NTT-CERT       #
# Modified by Kees Trippelvitz     #
####################################

#############################################
# Changelog:
# 001 Added language support
#############################################

# Checking access
if ($s_admin != 1) {
  pg_close($pgconn);
  header("Location: index.php");
  exit;
}

# Retrieving posted variables from $_GET
$allowed_get = array(
	"strip_html_escape_server"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['server'])) {
  $server = $clean['server'];
}
else {
  $server = "tunnelserver";
}

# Showing available servers
$sql_server = "SELECT DISTINCT(server) FROM serverstats";
$debuginfo[] = $sql_server;
$result_server = pg_query($pgconn, $sql_server);

echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>";
        echo "<div class='blockHeaderLeft'>" .$l['ss_info']. "</div>\n";
        echo "<div class='blockHeaderRight'>\n";
          echo "<form name='selectserver' method='get' action='serverstats.php'>\n";
            echo "<select name='strip_html_escape_server' onChange='javascript: this.form.submit();' class='smallselect'>\n";
              while ($row = pg_fetch_assoc($result_server)) {
                $fserver = $row['server'];
                echo printOption("$fserver", "$fserver", "$server") . "\n";
              }
            echo "</select>\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockHeaderRight>
      echo "</div>\n"; #</blockHeader>
      echo "<div class='blockContent'>\n";
        if ($server == '') {
          $server = $fserver;
        }

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
                echo $l['ss_daily']. "<br />\n";
                echo "<a href='serverstatsview.php?int_imgid=$imgid'><img alt='$label " .$l['ss_day']. "' src='showserver.php?int_imgid=$imgid' border='1' /></a>\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        } 
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</left>

debug_sql();
pg_close($pgconn);
?>
<?php footer(); ?>
