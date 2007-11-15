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
      echo "<div class='blockHeader'>" .$l['ga_groups']. "</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form id='groupadmin' name='groupadmin'>\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='100'>" .$l['ga_name']. "</th>\n";
            echo "<th width='20'>" .$l['ga_type']. "</th>\n";
            echo "<th width='50'>" .$l['ga_detail']. "</th>\n";
            echo "<th width='80'>" .$l['ga_owner']. "</th>\n";
            echo "<th width='130'>" .$l['ga_status']. "</th>\n";
            echo "<th width='100'>" .$l['ga_pending']. " / " .$l['ga_active']. "</th>\n";
            echo "<th width='73'>" .$l['g_modify']. "</th>\n";
            if ($s_access_user == 9) {
              echo "<th width='150'>" .$l['g_actions']. "</th>\n";
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

            $sql_count = "SELECT COUNT(id) as total, status FROM groupmembers WHERE groupid = '$gid' GROUP BY status";
            $debuginfo[] = $sql_count;
            $result_count = pg_query($pgconn, $sql_count);
            $m_pending = 0;
            $m_active = 0;
            while ($rowmembers = pg_fetch_assoc($result_count)) {
              $m_status = $rowmembers['status'];
              if ($m_status == 0) {
                $m_pending = $rowmembers['total'];
              } elseif ($status == 1) {
                $m_active = $rowmembers['total'];
              }
            }

            if ($type == 1 || $s_access_user == 9 || $owner == $s_org) {
              if ($status == 0) { $message = "notice"; }
              elseif ($status == 1) { $message = "ok"; }
              elseif ($status == 2) { $message = "warning"; }
              echo "<tr id='$gid'>\n";
                echo "<td>$name</td>\n";
                echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
                echo "<td>" .$v_group_detail_ar[$detail]. "</td>\n";
                echo "<td>$org</td>\n";
                echo "<td><div id='status$gid' class='$message'>" .$v_group_status_ar[$status]. "</div></td>\n";
                echo "<td><span class='notice'>$m_pending</span> / <span class='ok'>$m_active</span></td>\n";
                echo "<td>";
                  echo "[<a href='groupedit.php?int_gid=$gid'>". $l['g_edit_l']. "</a>]\n";
                  echo "[<a onclick=\"javascript: submitform('', 'groupdel.php?int_gid=$gid', 'd', '$gid', '" .$l['ga_confirmdel']. "');\">" .$l['g_delete_l']. "</a>]";
                echo "</td>\n";
                if ($s_access_user == 9) {
                  echo "<td>";
                    echo "[<a onclick=\"javascript: submitform('', 'groupstatus.php?int_gid=$gid&md5_hash=$s_hash&int_app=1', 'u', 'status$gid', '');\">" .$l['g_approve_l']. "</a>]";
                    echo "[<a onclick=\"javascript: submitform('', 'groupstatus.php?int_gid=$gid&md5_hash=$s_hash&int_app=0', 'u', 'status$gid', '');\">" .$l['g_disapprove_l']. "</a>]";
                    echo "[<a onclick=\"javascript: submitform('', 'groupstatus.php?int_gid=$gid&md5_hash=$s_hash&int_app=2', 'u', 'status$gid', '');\">" .$l['g_deny_l']. "</a>]";
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
