<?php $tab="2.5"; $pagetitle="Server Info - Detail"; include("menu.php"); contentHeader(); ?>
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

$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_imgid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking access
if ($s_admin != 1) {
  $err = 1;
  $m = 101;
}

# Checking $_GET'ed variables
if (isset($clean['imgid'])) {
  $iid = $clean['imgid'];
} else {
  $err = 1;
  $m = 112;
}

if ($err == 0) {
  $sql_server = "SELECT * FROM serverstats ";
  $sql_server .= " WHERE label = (SELECT label FROM serverstats WHERE id = $iid) ";
  $sql_server .= " AND type = (SELECT type FROM serverstats WHERE id = $iid) AND server = (SELECT server FROM serverstats WHERE id = $iid) ORDER BY id";
  $result_server = pg_query($pgconn, $sql_server);
  $debuginfo[] = $sql_server;
  $checklabel = 0;

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        while ($row = pg_fetch_assoc($result_server)) {
          $imgid = $row['id'];
          $label = $row['label'];
          $type = $row['type'];
          $interval = $row['interval'];

          if ($checklabel == 0) {
            if ($type == "memory" || $type == "cpu") {
              echo "<div class='blockHeader'>$type</div>\n";
            } else {
              echo "<div class='blockHeader'>$type - $label</div>\n";
            }
            echo "<div class='blockContent'>\n";
            $checklabel = 1;
          }

          echo "<table>\n";
            echo "<tr>\n";
              echo "<td>\n";
                if ($interval == "day") {
                  echo $l['sv_dg']. "<br />\n";
                  echo "<img alt='$label " .$l['sv_daily']. "' src='showserver.php?int_imgid=$imgid' /><br />\n";
                } elseif ($interval == "week") {
                  echo $l['sv_wg']. "<br />\n";
                  echo "<img alt='$label " .$l['sv_weekly']. "' src='showserver.php?int_imgid=$imgid' /><br />\n";
                } elseif ($interval == "month") {
                  echo $l['sv_mg']. "<br />\n";
                  echo "<img alt='label " .$l['sv_monthly']. "' src='showserver.php?int_imgid=$imgid' /><br />\n";
                } elseif ($interval == "year") {
                  echo $l['sv_yg']. "<br />\n";
                  echo "<img alt='$label " .$l['sv_yearly']. "' src='showserver.php?int_imgid=$imgid' /><br />\n";
                  $labelcheck = 0;
                }
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        }
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
} else {
  geterror($m);
}

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
