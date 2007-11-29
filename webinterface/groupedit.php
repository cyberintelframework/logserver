<?php $tab="5.4"; $pagetitle="Groups"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 06-11-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 2.10.01 Initial release
####################################

$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_m",
		"int_gid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if (isset($clean['gid'])) {
  $gid = $clean['gid'];
} else {
  $m = 117;
  $err = 1;
}

if ($s_access_user < 2) {
  $m = 101;
  $err = 1;
}

if ($err == 0) {
  $sql = "SELECT name, type, owner, organisations.organisation ";
  $sql .= " FROM groups, organisations WHERE groups.owner = organisations.id AND groups.id = '$gid'";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);

  $row = pg_fetch_assoc($result);
  $name = $row['name'];
  $type = $row['type'];
  $owner = $row['owner'];
  $org = $row['organisation'];

  if ($type == 0 && $owner != $s_org && $s_access_user < 9) {
    $err = 1;
    $m = 101;
  }
}

if (isset($m)) {
  geterror($m);
}

if ($err == 0) {
  echo "<div class='all'>\n";
  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['ge_edit']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form id='groupedit' name='groupedit'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='100'>" .$l['ga_name']. "</th>\n";
              echo "<th width='50'>" .$l['ga_type']. "</th>\n";
              echo "<th width='50'>" .$l['ga_owner']. "</th>\n";
              echo "<th width='100'>" .$l['g_actions']. "</th>\n";
            echo "</tr>\n";

            echo "<tr id='grouprow'>\n";
              if ($owner == $q_org || $s_access_user == 9) {
                echo "<td><input type='text' name='strip_html_escape_name' value='$name' size='40' /></td>\n";
                echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
                echo "<td>$org</td>\n";
                echo "<td><input type='button' onclick=\"submitform('groupedit', 'groupsave.php', 'u', 'grouprow');\" class='button' value='" .$l['g_update']. "' /></td>\n";
              } else {
                echo "<td>$name</td>\n";
                echo "<td>$v_group_type_ar[$type]</td>\n";
                echo "<td>$org</td>\n";
                echo "<td></td>\n";
              }
            echo "</tr>\n";
          echo "</table>\n";
          echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "<input type='hidden' name='int_gid' value='$gid' />\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
  echo "</div>\n"; #</all>

  echo "<div class='all'>\n";
  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['ge_members']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='groupmembers' id='groupmembers'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='75%'>" .$l['g_sensor']. "</th>\n";
              echo "<th width='25%'>" .$l['g_actions']. "</th>\n";
            echo "</tr>\n";

            $sql = "SELECT keyname, label, vlanid, sensorid, organisations.id as orgid, organisations.organisation ";
            $sql .= " FROM sensors, groupmembers, organisations ";
            $sql .= " WHERE groupid = '$gid' AND sensors.id = sensorid AND sensors.organisation = organisations.id";
            $result = pg_query($pgconn, $sql);
            while ($row = pg_fetch_assoc($result)) {
              $sid = $row['sensorid'];
              $keyname = $row['keyname'];
              $vlanid = $row['vlanid'];
              $label = $row['label'];
              $sensor = sensorname($keyname, $vlanid, $label);
              $org = $row['orgid'];
              $orgname = $row['organisation'];

              echo "<tr id='sensor$sid'>\n";
                echo "<td>$sensor - $orgname</td>\n";
                echo "<td>";
                  if ($org == $s_org || $s_access_user == 9 || $owner == $s_org) {
                    echo "[<a onclick=\"submitform('', 'groupmdel.php?int_gid=$gid&int_sid=$sid&md5_hash=$s_hash', 'u', 'sensor$sid');\">" .$l['g_remove_l']. "</a>]\n";
                  }
                echo "</td>\n";
              echo "</tr>\n";
            }
            echo "<tr id='inputrow'>\n";
              echo "<td>\n";
                echo "<select name='int_sid'>\n";
                  $sql = "SELECT sensors.id as sid, keyname, vlanid, label, organisations.organisation ";
                  $sql .= " FROM sensors, organisations WHERE sensors.organisation = organisations.id";
                  if ($s_access_sensor != 9) {
                    $sql .= " AND sensors.organisation = '$q_org'";
                  }
                  $debuginfo[] = $sql;
                  $result = pg_query($pgconn, $sql);
                  while ($row = pg_fetch_assoc($result)) {
                    $sid = $row['sid'];
                    $keyname = $row['keyname'];
                    $vlanid = $row['vlanid'];
                    $label = $row['label'];
                    $sensor = sensorname($keyname, $vlanid, $label);
                    $org = $row['organisation'];
                    if ($s_access_sensor == 9) {
                      echo "<option value='$sid'>$sensor - $org</option>\n";
                    } else {
                      echo "<option value='$sid'>$sensor</option>\n";
                    }
                  }
                echo "</select>\n";
              echo "</td>\n";
              echo "<td>";
                echo "<input class='button aright' type='button' onclick=\"submitform('groupmembers', 'groupmadd.php', 'a', 'inputrow', '');\" value='" .$l['g_add']. "' />";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</table>\n";
          echo "<input type='hidden' name='int_gid' value='$gid' />\n";
          echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>
  echo "</div>\n"; #</all>
}

debug_sql();
?>
<?php footer(); ?>
