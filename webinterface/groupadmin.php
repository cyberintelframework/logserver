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

echo "<div class='centerbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>Groups</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form id='groupadmin' name='groupadmin'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='100'>Name</th>\n";
            echo "<th width='50'>Type</th>\n";
            echo "<th width='80'>Detail</th>\n";
            echo "<th width='80'>Owner</th>\n";
            echo "<th width='130'>Status</th>\n";
            echo "<th width='50'>Members</th>\n";
            echo "<th width='70'>Modify</th>\n";
            if ($s_access_user == 9) {
              echo "<th width='150'>Actions</th>\n";
            }
          echo "</tr>\n";
          $sql = "SELECT groups.id, name, type, detail, approved, owner, organisation FROM groups, organisations WHERE groups.owner = organisations.id";
          $debuginfo[] = $sql;
          $result = pg_query($pgconn, $sql);

          while ($row = pg_fetch_assoc($result)) {
            $gid = $row['id'];
            $name = $row['name'];
            $type = $row['type'];
            $detail = $row['detail'];
            $status = $row['approved'];
            $owner = $row['owner'];
            $org = $row['organisation'];

            $sql_count = "SELECT id FROM groupmembers WHERE groupid = '$gid'";
            $debuginfo[] = $sql_count;
            $result_count = pg_query($pgconn, $sql_count);
            $membercount = pg_num_rows($result_count);

            if ($type == 1 || $s_access_user == 9 || $owner == $s_org) {
              if ($status == 0) { $message = "notice"; }
              elseif ($status == 1) { $message = "ok"; }
              elseif ($status == 2) { $message = "warning"; }
              echo "<tr id='$gid'>\n";
                echo "<td>$name</td>\n";
                echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
                echo "<td>" .$v_group_detail_ar[$detail]. "</td>\n";
                echo "<td>$org</td>\n";
                echo "<td><div id='status$id' class='$message'>" .$v_group_status_ar[$status]. "</div></td>\n";
                echo "<td>$membercount</td>\n";
                echo "<td>";
                  echo "[<a href='groupedit.php?int_gid=$gid'>edit</a>]\n";
                  echo "[<a onclick=\"javascript: submitform('', 'groupdel.php?int_gid=$gid', 'd', '$gid', '" .$l['ga_confirmdel']. "');\">delete</a>]";
                echo "</td>\n";
                if ($s_access_user == 9) {
                  echo "<td>";
                    echo "[<a onclick=\"javascript: submitform('', 'groupstatus.php?int_gid=$gid&md5_hash=$s_hash&int_app=1', 'u', 'status$gid', '');\">approve</a>]";
                    echo "[<a onclick=\"javascript: submitform('', 'groupstatus.php?int_gid=$gid&md5_hash=$s_hash&int_app=0', 'u', 'status$gid', '');\">disapprove</a>]";
                    echo "[<a onclick=\"javascript: submitform('', 'groupstatus.php?int_gid=$gid&md5_hash=$s_hash&int_app=2', 'u', 'status$gid', '');\">deny</a>]";
                  echo "</td>\n";
                }
              echo "</tr>\n";
            }
          }
          echo "<tr id='inputrow'>\n";
            echo "<td><input type='text' name='strip_html_escape_name'></td>\n";
            echo "<td>";
              echo "<select name='int_type'>\n";
                foreach ($v_group_type_ar as $key=>$val) {
                  echo printOption($key, $val, -1);
                }
              echo "</select>\n";
            echo "</td>\n";
            echo "<td>";
              echo "<select name='int_detail'>\n";
                foreach ($v_group_detail_ar as $key=>$val) {
                  echo printOption($key, $val, -1);
                }
              echo "</select>\n";
            echo "</td>\n";
            if ($s_access_user == 9) {
              $cs = 5;
            } else {
              $cs = 4;
            }
#            echo "<td colspan='$cs'><input type='submit' class='button aright' value='" .$l['g_insert']. "' /></td>\n";
            echo "<td colspan='$cs'><input type='button' class='button aright' value='" .$l['g_insert']. "' onclick=\"submitform('groupadmin', 'groupadd.php', 'a', 'inputrow');\" /></td>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "</form>\n";
      echo "</div>\n";
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n";
  echo "</div>\n";
echo "</div>\n";

debug_sql();
?>
<?php footer(); ?>
