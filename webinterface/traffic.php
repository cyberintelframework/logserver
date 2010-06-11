<?php $tab="2.4"; $pagetitle="Traffic"; include("menu.php"); contentHeader(0,0); ?> 
<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 08-07-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Fixed bug #143
# 001 Added language support
#############################################

# Retrieving posted variables from $_GET
$allowed_get = array(
	"int_selview"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['selview'])) {
  $selview = $clean['selview'];
} elseif (isset($c_selview)) {
  $selview = intval($c_selview);
}
if ($selview == 9) {
  add_to_sql("deactivated_sensors.*", "select");
  add_to_sql("deactivated_sensors", "table");
} else {
  $sensors_table = "sensors";
  add_to_sql("sensors.*", "select");
  add_to_sql("sensors", "table");
  if ($selview == "1") {
    add_to_sql("status = 0", "where");
  } elseif ($selview == "2") {
    add_to_sql("status = 1", "where");
  }
}

if ($s_admin == 1) {
  if ($q_org != 0) {
    add_to_sql("organisation = $q_org", "where");
  }
} else {
  add_to_sql("organisation = $q_org", "where");
}

echo "<div class='left'>\n";
  echo "<div class='block'>\n";
    echo "<div class='dataBlock'>\n";
    echo "<div class='blockHeader'>";
      echo "<div class='blockHeaderLeft'>" .$l['tr_traffic']. "</div>\n";
      echo "<div class='blockHeaderRight'>\n";
          echo "<form name='viewform' action='$url' method='GET'>\n";
            echo "<select name='int_selview' class='smallselect' onChange='javascript: this.form.submit();'>\n";
              foreach ($v_selview_ar as $key => $val) {
                echo printOption($key, $val, $selview) . "\n";
              }
            echo "</select>\n";
          echo "</form>\n";
      echo "</div>\n";
    echo "</div>\n";
    echo "<div class='blockContent'>\n";
      echo "<table valign='left'>";
      echo "</table>";

      add_to_sql("organisation", "select");
      add_to_sql("keyname", "select");
      add_to_sql("vlanid", "select");
      add_to_sql("keyname", "order");

      prepare_sql();

      $sql_getactive = "SELECT $sql_select FROM $sql_from $sql_where ORDER BY $sql_order";
      $debuginfo[] = $sql_getactive;
      $result_getactive = pg_query($pgconn, $sql_getactive);

      if ($s_admin == 1 && $selview != 9) {
        # User is admin, show the graphs for all sensors
        $sql_allsensors = "SELECT id FROM rrd WHERE type = 'day' AND label = 'allsensors'";
        $result_allsensors = pg_query($pgconn, $sql_allsensors);
        $row_allsensors = pg_fetch_assoc($result_allsensors);
        $allid = $row_allsensors['id'];

        if ($allid != "") {
          echo "<table>\n";
            echo "<tr>\n";
              echo "<td><a href='trafficview.php?int_imgid=$allid'><img src='showtraffic.php?int_imgid=$allid' alt='" .$l['tr_allsensors']. "' border='1' /></a></td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        }
      }

      while ($rowactive = pg_fetch_assoc($result_getactive)) {
        $db_orgid = $rowactive['organisation'];
        $db_orgkeyname = $rowactive['keyname'];
        $db_orgvlanid = $rowactive['vlanid'];

        if ($db_orgvlanid != 0) {
          $label = "$db_orgkeyname-$db_orgvlanid";
        } else {
          $label = "$db_orgkeyname";
        }

        if ($q_org == 0) {
          $sql_sensors = "SELECT id, label, orgid FROM rrd WHERE type = 'day' AND label = '$label'";
        } else {
          $sql_sensors = "SELECT id, label, orgid FROM rrd WHERE orgid = $q_org AND type = 'day' AND label = '$label'";
        }
        $debuginfo[] = $sql_sensors;
        $result_sensors = pg_query($pgconn, $sql_sensors);
        $numrows_result_sensors = pg_numrows($result_sensors);

        echo "<table>\n";
          while ($row = pg_fetch_assoc($result_sensors)) {
            $imgid = $row['id'];
            $orgid = $row['orgid'];
            $label = $row['label'];

            echo "<tr>\n";
              echo "<td><a href='trafficview.php?int_imgid=$imgid'><img src='showtraffic.php?int_imgid=$imgid' alt='$sensor' border='1' /></a></td>\n";
            echo "</tr>\n";
          } 
        echo "</table>\n";
      }
    echo "</div>\n";
    echo "<div class='blockFooter'></div>\n";
  echo "</div>\n";
  echo "</div>\n";
echo "</div>\n";

debug_sql();
pg_close($pgconn);
?>
<?php footer(); ?>
