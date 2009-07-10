<?php $tab="5.4"; $pagetitle="Groups"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 03-03-2008                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 001 Initial release
####################################

$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_access_user < 2) {
  geterror(101);
  footer();
  exit;
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

echo "<div id='err'></div>\n";

echo "<div class='leftmed'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['ga_groups']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form id='groupadmin' name='groupadmin'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='100'>" .$l['ga_name']. "</th>\n";
            echo "<th width='80'>" .$l['ga_owner']. "</th>\n";
            echo "<th width='100'>" .$l['ge_members']. "</th>\n";
            echo "<th width='73'>" .$l['g_modify']. "</th>\n";
          echo "</tr>\n";
          $sql = "SELECT groups.id, name, owner, organisation FROM groups, organisations WHERE groups.owner = organisations.id";
          $debuginfo[] = $sql;
          $result = pg_query($pgconn, $sql);

          while ($row = pg_fetch_assoc($result)) {
            $gid = $row['id'];
            $name = $row['name'];
            $type = $row['type'];
            $owner = $row['owner'];
            $org = $row['organisation'];

            $sql_count = "SELECT COUNT(id) as total FROM groupmembers WHERE groupid = '$gid'";
            $debuginfo[] = $sql_count;
            $result_count = pg_query($pgconn, $sql_count);
            $rowmembers = pg_fetch_assoc($result_count);
            $members = $rowmembers['total'];

            if ($type == 1 || $s_access_user == 9 || $owner == $s_org) {
              echo "<tr id='group$gid'>\n";
                echo "<td>$name</td>\n";
                echo "<td>$org</td>\n";
                echo "<td>$members</td>\n";
                echo "<td>";
                  echo "[<a onclick=\"javascript: expand_edit('$gid', '$name', 'edit_block', 'edit_title', 'groupmget.php?int_gid=$gid&md5_hash=$s_hash', 'getgroupmembers');\">" .$l['g_edit_l']. "</a>]\n";
                  echo "[<a onclick=\"javascript: db_del_record('groupdel.php?int_gid=$gid&md5_hash=$s_hash', 'groupdel');\">" .$l['g_delete_l']. "</a>]";
                echo "</td>\n";
              echo "</tr>\n";
            }
          }
          echo "<tr id='inputrow'>\n";
            echo "<td><input type='text' name='strip_html_escape_name' class='toclear'></td>\n";
            echo "<td>";
            echo "</td>\n";
            echo "<td colspan='3'><input type='button' class='button aright' value='" .$l['g_insert']. "' onclick=\"db_add_record('groupadd.php', 'groupadd', 'groupadmin');\" /></td>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "</form>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftmed>

echo "<div class='rightmed' style='display: none;' id='edit_block'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['g_edit']. " <span id='edit_title'></span></div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form name='testform' id='testform'>\n";
          echo "<input type='hidden' name='int_gid' value='' class='edit_id' />\n";
          echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "<table class='datatable'>\n";
            echo "<tr>";
              echo "<th>" .$l['g_sensor']. "</td>";
              echo "<th width='50'>" .$l['g_delete']. "</td>";
            echo "</tr>\n";
            echo "<tr id='edit_row'>\n";
              echo "<td>&nbsp;</td>\n";
              echo "<td><input type='button' class='button' value='" .$l['g_delete']. "' onclick=\"db_del_selected_cb('groupmdel.php', 'groupmdel', 'testform', 'intcsv_members');\" /></td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</form\n";
        echo "<table class='datatable'>\n";
          echo "<form name='addsensors' id='addsensors'>\n";
            echo "<tr>\n";
              echo "<td>\n";
                echo "<input type='hidden' name='int_gid' value='' class='edit_id' />\n";
                echo "<input class='button' type='button' onclick=\"db_add_record('groupmadd.php', 'groupaddsensor', 'addsensors');\" value='" .$l['g_addsensor']. "' />&nbsp;&nbsp;";
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
                echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
              echo "</td>\n";
            echo "</tr>\n";
          echo "</form>\n";
          if ($s_access_sensor == 9) {
            echo "<form name='addorg' id='addorg'>\n";
              echo "<tr>\n";
                echo "<td>\n";
                  echo "<input type='hidden' name='int_gid' value='' class='edit_id' />\n";
                  echo "<input class='button' type='button' onclick=\"db_add_record('groupmadd.php', 'groupaddorg', 'addorg');\" value='" .$l['g_addorg']. "' />&nbsp;&nbsp;";
                  echo "<select name='int_org'>\n";
                    $sql = "SELECT id, organisation ";
                    $sql .= " FROM organisations WHERE NOT organisation = 'ADMIN'";
                    $debuginfo[] = $sql;
                    $result = pg_query($pgconn, $sql);
                    while ($row = pg_fetch_assoc($result)) {
                      $oid = $row['id'];
                      $db_org = $row['organisation'];
                      echo printoption($oid, $db_org, -1);
                    }
                  echo "</select>\n";
                  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
                echo "</td>\n";
              echo "</tr>\n";
            echo "</form>\n";
          }
        echo "</table>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</rightmed>

debug_sql();
?>
<?php footer(); ?>
