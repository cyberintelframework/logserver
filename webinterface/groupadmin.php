<?php $tab="5.4"; $pagetitle="Groups"; include("menu.php"); contentHeader(0); ?>
<?php

####################################
# SURFnet IDS 2.10.00              #
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

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

echo "<div id='err'></div>\n";

echo "<div class='leftbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>" .$l['ga_groups']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form id='groupadmin' name='groupadmin'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='100'>" .$l['ga_name']. "</th>\n";
            echo "<th width='20'>" .$l['ga_type']. "</th>\n";
            echo "<th width='80'>" .$l['ga_owner']. "</th>\n";
            echo "<th width='100'>" .$l['ge_members']. "</th>\n";
            echo "<th width='73'>" .$l['g_modify']. "</th>\n";
          echo "</tr>\n";
          $sql = "SELECT groups.id, name, type, owner, organisation FROM groups, organisations WHERE groups.owner = organisations.id";
          if ($s_access_user < 9) {
            $sql .= " AND groups.type = 0 ";
          }
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
              echo "<tr id='$gid'>\n";
                echo "<td>$name</td>\n";
                echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
                echo "<td>$org</td>\n";
                echo "<td>$members</td>\n";
                echo "<td>";
                  echo "[<a href='groupedit.php?int_gid=$gid'>". $l['g_edit_l']. "</a>]\n";
                  echo "[<a onclick=\"javascript: submitform('', 'groupdel.php?int_gid=$gid', 'd', '$gid', '" .$l['ga_confirmdel']. "');\">" .$l['g_delete_l']. "</a>]";
                echo "</td>\n";
              echo "</tr>\n";
            }
          }
          echo "<tr id='inputrow'>\n";
            echo "<td><input type='text' name='strip_html_escape_name'></td>\n";
            echo "<td>";
#              if ($s_access_user == 9) {
#                echo "<select name='int_type'>\n";
#                  foreach ($v_group_type_ar as $key=>$val) {
#                    echo printOption($key, $val, 1);
#                  }
#                echo "</select>\n";
#              }
            echo "</td>\n";
            echo "<td colspan='4'><input type='button' class='button aright' value='" .$l['g_insert']. "' onclick=\"submitform('groupadmin', 'groupadd.php', 'a', 'inputrow');\" /></td>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "</form>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</dataBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftbig>

debug_sql();
?>
<?php footer(); ?>
