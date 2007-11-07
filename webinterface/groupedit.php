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
		"int_id"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if (isset($clean['id'])) {
  $id = $clean['id'];
} else {
  $m = 117;
  $err = 1;
}
if ($err == 0) {
echo "<div class='leftbig'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
      echo "<div class='blockHeader'>Edit Group</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form name='groupadmin' method='get' onsubmit=\"return popit('groupsave.php');\">\n";
        echo "<table class='datatable'>\n";
          echo "<tr>\n";
            echo "<th width='100'>Name</th>\n";
            echo "<th width='50'>Type</th>\n";
            echo "<th width='80'>Detail</th>\n";
            echo "<th width='50'>Owner</th>\n";
            echo "<th width='150'>Status</th>\n";
            echo "<th width='100'>Actions</th>\n";
          echo "</tr>\n";
          $sql = "SELECT name, type, detail, approved, organisation ";
          $sql .= " FROM groups, organisations WHERE groups.owner = organisations.id AND groups.id = '$id'";
          $debuginfo[] = $sql;
          $result = pg_query($pgconn, $sql);

          $row = pg_fetch_assoc($result);
          $name = $row['name'];
          $type = $row['type'];
          $detail = $row['detail'];
          $owner = $row['organisation'];
          $status = $row['approved'];

          if ($status == 0) { $message = "warning"; }
          elseif ($status == 1) { $message = "ok"; }
          elseif ($status == 2) { $message = "notice"; }

          echo "<tr>\n";
            echo "<td><input type='text' name='strip_html_escape_name' value='$name' /></td>\n";
            if ($status == 0 || ($type == 1 && $status != 0)) {
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
            echo "<td><input type='submit' class='button' value='" .$l['g_update']. "' /></td>\n";
          echo "</tr>\n";
        echo "</table>\n";
        echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
        echo "<input type='hidden' name='int_id' value='$id' />\n";
        echo "</form>\n";
      echo "</div>\n";
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n";
  echo "</div>\n";
echo "</div>\n";
}

debug_sql();
?>
<?php footer(); ?>
