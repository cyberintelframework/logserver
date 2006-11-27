<?php include("menu.php"); set_title("Log Detail"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.03.01                  #
# 10-10-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.03.01 Released as part of the 1.03 package
# 1.02.04 Added intval() to session variables + access handling change
# 1.02.03 Added some more input checks and removed includes
# 1.02.02 Initial release
#############################################

$s_org = intval($_SESSION['s_org']);
$s_access = $_SESSION['s_access'];
$s_access_search = intval($s_access{1});
$err = 0;

### Variables check
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);
} else {
  $m = 42;
  pg_close($pgconn);
  header("location: logindex.php?m=$m");
  exit;
}

### Admin check
if ($err != 1) {
  if ($s_access_search == 9) {
    $sql_details = "SELECT attackid, text, type FROM details WHERE attackid = " .$id;
  } else {
    $sql_details = "SELECT details.attackid, details.text, details.type FROM details, sensors WHERE details.attackid = " .$id. " AND details.sensorid = sensors.id AND sensors.organisation = '" .$s_org. "'";
  }
  $result_details = pg_query($pgconn, $sql_details);

  # Debug info
  if ($debug == 1) {
    echo "<pre>";
    echo "SQL_DETAILS: $sql_details";
    echo "</pre>";
  }

  echo "<table class='datatable'>\n";
    echo "<tr>\n";
      echo "<td class='dataheader' width='100'>AttackID</td>\n";
      echo "<td class='dataheader' width='300'>Logging</td>\n";
      echo "<td class='dataheader' width='200'>ClamAV result</td>\n";
      if ($bdc == 1) {
        echo "<td class='dataheader' width='200'>BitDefender result</td>\n";
      }
      if ($antivir == 1) {
        echo "<td class='dataheader' width='200'>Antivir result</td>\n";
      }
    echo "</tr>\n";

  while ($row = pg_fetch_assoc($result_details)) {
    $attackid = $row['attackid'];
    $logging = $row['text'];
    $type = $row['type'];

    $sql_getbin = "SELECT * FROM binaries WHERE bin = '$logging' AND id IN (SELECT MAX(id) FROM binaries WHERE bin = '$logging' GROUP BY scanner)";
    $result_getbin = pg_query($pgconn, $sql_getbin);
    $numrows_getbin = pg_num_rows($result_getbin);
    if ($numrows_getbin == 0) {
      $clamav_result = "Not scanned";
      $bdc_result = "Not scanned";
      $antivir_result = "Not scanned";
    } else {
      while ($row_getbin = pg_fetch_assoc($result_getbin)) {
        $scanner = $row_getbin['scanner'];
        if ($scanner == "ClamAV") {
          $clamav_result = $row_getbin['info'];
        }
        if ($bdc == 1 && $scanner == "BitDefender") {
          $bdc_result = $row_getbin['info'];
        }
        if ($antivir == 1) {
          $antivir_result = $row_getbin['info'];
        }
      }
    }

    echo "<tr>\n";
      echo "<td class='datatd'>$attackid</td>\n";
      echo "<td class='datatd'>$logging</td>\n";
      if ($type == 8) {
        echo "<td class='datatd'>&nbsp;$clamav_result</td>\n";
        if ($bdc == 1) {
          echo "<td class='datatd'>&nbsp;$bdc_result</td>\n";
        }
        if ($antivir == 1) {
          echo "<td class='datatd'>&nbsp;$antivir_result</td>\n";
        }
      } else {
        echo "<td class='datatd'>&nbsp;</td>\n";
        echo "<td class='datatd'>&nbsp;</td>\n";
        echo "<td class='datatd'>&nbsp;</td>\n";
      }
    echo "</tr>\n";
  }
  echo "</table>\n";
}
?>
<?php footer(); ?>
