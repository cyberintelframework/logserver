<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 15-10-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Fixed bug with unescaped characters in download url
# 001 Added language support
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Including language file
include "../lang/${c_language}.php";

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
                "int_id",
				"int_atype",
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

if (isset($clean['atype'])) {
  $atype = $clean['atype'];
} else {
  $m = 167;
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

  echo "<div class='centerbig'>\n";
#    echo "<div class='block'>\n";
#      echo "<div class='dataBlock'>\n";
#        echo "<div class='blockHeader'>Details of attack ID: $id</div>\n";
#        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<td colspan='2' class='title'>" .$l['ld_aid_details']. ": $id</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<th width='20%'>" .$l['g_type']. "</th>\n";
              echo "<th width='80%'>" .$l['g_info']. "</th>\n";
            echo "</tr>\n";

            if ($atype == 7) {
				if ($s_access_search == 9) {
	                $sql_version = "SELECT version FROM ssh_version WHERE attackid = $id";
				} else {
	                $sql_version = "SELECT version FROM ssh_version, attacks, sensors WHERE attackid = $id";
					$sql_version .= " AND ssh_version.attackid = attacks.id AND attacks.sensorid = sensors.id AND ";
					$sql_version .= " sensors.organisation = '" .$q_org. "'";
				}
                $result_version = pg_query($pgconn, $sql_version);
                $num_version = pg_num_rows($result_version);
                $debuginfo[] = $sql_version;
                if ($num_version > 0) {
                  $row = pg_fetch_assoc($result_version);
                  echo "<tr>\n";
                    echo "<td>" .$l['ld_sshversion']. "</td>";
                    echo "<td>" .$row['version']. "</td>";
                  echo "</tr>\n";
                }

				if ($s_access_search == 9) {
	                $sql_logins = "SELECT sshuser, sshpass FROM ssh_logins WHERE attackid = $id";
				} else {
	                $sql_logins = "SELECT sshuser, sshpass FROM ssh_logins WHERE attackid = $id";
					$sql_logins .= " AND ssh_logins.attackid = attacks.id AND attacks.sensorid = sensors.id AND ";
					$sql_logins .= " sensors.organisation = '" .$q_org. "'";
				}
                $result_logins = pg_query($pgconn, $sql_logins);
                $num_logins = pg_num_rows($result_logins);
                $debuginfo[] = $sql_logins;
                $row = pg_fetch_assoc($result_logins);
                echo "<tr>\n";
                  echo "<td>" .$l['ld_sshlogin']. "</td>";
                  echo "<td>" .$row['sshuser']. " / " .$row['sshpass']. "</td>";
                echo "</tr>\n";

				if ($s_access_search == 9) {
	                $sql_command = "SELECT command FROM ssh_command WHERE attackid = $id";
				} else {
	                $sql_command = "SELECT command FROM ssh_command WHERE attackid = $id";
					$sql_command .= " AND ssh_command.attackid = attacks.id AND attacks.sensorid = sensors.id AND ";
					$sql_command .= " sensors.organisation = '" .$q_org. "'";
				}
                $result_command = pg_query($pgconn, $sql_command);
                $num_command = pg_num_rows($result_command);
                $debuginfo[] = $sql_command;
                while ($row = pg_fetch_assoc($result_command)) {
                  echo "<tr>\n";
                    echo "<td>" .$l['ld_sshcommand']. "</td>";
                    echo "<td>" .$row['command']. "</td>";
                  echo "</tr>\n";
                }
            } else {

            while ($row = pg_fetch_assoc($result_details)) {
              $attackid = $row['attackid'];
              $logging = pg_escape_string($row['text']);
              $type = $row['type'];
              $typetext = $v_attacktype_ar[$type];

              $sql_check = "SELECT COUNT(id) as total FROM uniq_binaries WHERE name = '$logging'";
              $result_check = pg_query($pgconn, $sql_check);
              $row = pg_fetch_assoc($result_check);
              $count = $row['total'];
              $debuginfo[] = $sql_check;

              if ($type == 83) {
                continue;
              }
              echo "<tr>\n";
                echo "<td>$typetext</td>\n";
                if ($count != 0) {
                  echo "<td><a href='binaryhist.php?md5_binname=$logging'>$logging<a/></td>\n";
                } else {
                  if ($type == 81) {
                    echo "<td>\n";
                    $logging = formatEmu($logging);
                    echo "$logging";
                    echo "</td>\n";
#				  } elseif ($type == 84) {
#					$sql = "SELECT service FROM dcerpc WHERE uuid = '$logging'";
#		            $r = pg_query($pgconn, $sql);
#        		    $rij = pg_fetch_assoc($r);
#					echo "<td>" .$rij['service']. "</td>";
#				  } elseif ($type == 85) {
#					$sql = "SELECT opname FROM dcerpc WHERE opnum = $logging";
#		            $r = pg_query($pgconn, $sql);
#        		    $rij = pg_fetch_assoc($r);
#					echo "<td>" .$rij['opname']. "</td>"
                  } else {
                    echo "<td>$logging</td>\n";
                  }
                }
              echo "</tr>\n";
            }

            } # atype if/else
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
    echo "<input type='button' onclick='popout();' class='button' value='" .$l['ld_popout']. "' />\n";
  echo "</div>\n";
echo "</div>\n";

debug_sql();
pg_close($pgconn);
?>
<?php cleanfooter(); ?>
