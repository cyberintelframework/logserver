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
  $sql = "SELECT name, type, detail, approved, organisation ";
  $sql .= " FROM groups, organisations WHERE groups.owner = organisations.id AND groups.id = '$gid'";
  $debuginfo[] = $sql;
  $result = pg_query($pgconn, $sql);

  $row = pg_fetch_assoc($result);
  $name = $row['name'];
  $type = $row['type'];
  $detail = $row['detail'];
  $owner = $row['organisation'];
  $status = $row['approved'];

  if ($type == 0 && $owner != $s_org && $s_access_user < 9) {
    $err = 1;
    $m = 101;
  }
}

if (isset($m)) {
  geterror($m);
}

if ($err == 0) {
  echo "<div class='leftbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Edit Group</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form id='groupedit' name='groupedit'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='100'>Name</th>\n";
              echo "<th width='50'>Type</th>\n";
              echo "<th width='80'>Detail</th>\n";
              echo "<th width='50'>Owner</th>\n";
              echo "<th width='150'>Status</th>\n";
              echo "<th width='100'>Actions</th>\n";
            echo "</tr>\n";

            if ($status == 0) { $message = "notice"; }
            elseif ($status == 1) { $message = "ok"; }
            elseif ($status == 2) { $message = "warning"; }

            echo "<tr id='grouprow'>\n";
              if ($owner == $q_org || $s_access_user == 9) {
                echo "<td><input type='text' name='strip_html_escape_name' value='$name' /></td>\n";
                if ($status == 0 || ($type == 1 && $status != 0) || $s_access_user == 9) {
                  echo "<td>";
                    echo "<select name='int_type'>\n";
                      foreach ($v_group_type_ar as $key=>$val) {
                        echo printOption($key, $val, $type);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                  echo "<td>";
                    echo "<select name='int_detail'>\n";
                      foreach ($v_group_detail_ar as $key=>$val) {
                        echo printOption($key, $val, $detail);
                      }
                    echo "</select>\n";
                  echo "</td>\n";
                } else {
                  echo "<td>" .$v_group_type_ar[$type]. "</td>\n";
                  echo "<td>" .$v_group_detail_ar[$detail]. "</td>\n";
                }
                echo "<td>$owner</td>\n";
                echo "<td><div class='$message'>" .$v_group_status_ar[$status]. "</div></td>\n";
                echo "<td><input type='button' onclick=\"submitform('groupedit', 'groupsave.php', 'u', 'grouprow');\" class='button' value='" .$l['g_update']. "' /></td>\n";
              } else {
                echo "<td>$name</td>\n";
                echo "<td>$v_group_type_ar[$type]</td>\n";
                echo "<td>$v_group_detail_ar[$detail]</td>\n";
                echo "<td>$owner</td>\n";
                echo "<td><div class='$message'>" .$v_group_status_ar[$status]. "</div></td>\n";
                echo "<td></td>\n";
              }
            echo "</tr>\n";
          echo "</table>\n";
          echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "<input type='hidden' name='int_id' value='$gid' />\n";
          echo "</form>\n";
        echo "</div>\n";
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n";
    echo "</div>\n";
  echo "</div>\n";

  $groupstatus = $status;

  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>Group members</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='groupmembers' id='groupmembers'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th width='50%'>Sensor</th>\n";
              echo "<th width='25%'>Status</th>\n";
              echo "<th width='25%'>Actions</th>\n";
            echo "</tr>\n";

            $sql = "SELECT keyname, label, vlanid, groupmembers.status, sensorid FROM sensors, groupmembers ";
            $sql .= " WHERE groupid = '$gid' AND sensors.id = sensorid";
            $result = pg_query($pgconn, $sql);
            while ($row = pg_fetch_assoc($result)) {
              $sid = $row['sensorid'];
              $keyname = $row['keyname'];
              $vlanid = $row['vlanid'];
              $label = $row['label'];
              $sensor = sensorname($keyname, $vlanid, $label);
              $status = $row['status'];

              if ($status == 0) { $cl = "notice"; }
              else { $cl = "ok"; }

              echo "<tr id='sensor$sid'>\n";
                echo "<td>$sensor</td>\n";
                echo "<td><div class='$cl'>" .$v_groupmember_status_ar[$status]. "</div></td>\n";
                echo "<td>";
                  if ($status == 0) {
                    echo "[<a onclick=\"submitform('', 'groupmapp.php?int_gid=$gid&int_sid=$sid&int_app=1&md5_hash=$s_hash', 'u', 'sensor$sid');\">" .$l['g_approve']. "</a>]\n";
                  }
                  echo "[<a onclick=\"submitform('', 'groupmdel.php?int_gid=$gid&int_sid=$sid&md5_hash=$s_hash', 'u', 'sensor$sid');\">" .$l['g_remove']. "</a>]\n";
                echo "</td>\n";
              echo "</tr>\n";
            }
            if ($groupstatus == 1) {
              echo "<tr id='inputrow'>\n";
                echo "<td colspan='2'>\n";
                  echo "<select name='int_sid'>\n";
                    $sql = "SELECT sensors.id as sid, keyname, vlanid, label, organisations.organisation ";
                    $sql .= " FROM sensors, organisations WHERE sensors.organisation = organisations.id";
                    if ($s_access_sensor != 9) {
                      $sql .= " AND organisation = '$q_org'";
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
            }
          echo "</table>\n";
          echo "<input type='hidden' name='int_gid' value='$gid' />\n";
          echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>
}

debug_sql();
?>
<?php footer(); ?>
