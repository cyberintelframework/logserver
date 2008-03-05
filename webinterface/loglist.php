<?php $tab="2.2"; $pagetitle="Cross Domain"; include("menu.php"); contentHeader(); ?>
<?php

####################################
# SURFnet IDS 2.10.00              #
# Changeset 001                    #
# 03-03-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 001 Added language support
#############################################

# Including language file
include "../lang/${c_language}.php";

# Retrieving posted variables from $_GET
$allowed_get = array(
		"inet_source",
		"int_sev"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['source'])) {
  $source = $clean['source'];
} else {
  $err = 1;
  $m = 140;
  geterror($m);
}

if (isset($clean['sev'])) {
  $sev = $clean['sev'];
} else {
  $err = 1;
  $m = 131;
}

### Checking for errors.
if ($err != 1) {
  $tsquery = "timestamp >= $from AND timestamp <= $to";
  ### retrieving organisation ranges.

  add_to_sql("attacks", "table");
  add_to_sql("$tsquery", "where");
  add_to_sql("attacks.source <<= '$source'", "where");
  add_to_sql("attacks.severity = '$sev'", "where");
  add_to_sql("DISTINCT attacks.source", "select");
  add_to_sql("COUNT(attacks.source) as total", "select");
  add_to_sql("attacks.source", "group");

  # IP Exclusion stuff
  add_to_sql("NOT attacks.source IN (SELECT exclusion FROM org_excl WHERE orgid = $q_org)", "where");

  prepare_sql();

  $sql_uniq = "SELECT $sql_select ";
  $sql_uniq .= " FROM $sql_from ";
  $sql_uniq .= " $sql_where ";
  if ($sql_group) {
    $sql_uniq .= " GROUP BY $sql_group ";
  }
  if ($sql_order) {
    $sql_uniq .= " $sql_order ";
  }
  $result_uniq = pg_query($pgconn, $sql_uniq);
  $debuginfo[] = $sql_uniq;

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['lc_uniqsource']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<table class='datatable'>\n";
            echo "<tr>\n";
              echo "<th>" .$l['ls_sourceip']. " " .$l['ls_address']. "</th>\n";
              if ($sev == 1) {
                echo "<th width='200'>" .$l['g_mal']. "</th>\n";
              } else {
                echo "<th width='200'>" .$l['g_pos']. "</th>\n";
              }
            echo "</tr>\n";
            while ($row = pg_fetch_assoc($result_uniq)) {
              $source = $row['source'];
              $count = $row['total'];
              echo "<tr>\n";
                echo "<td>$source</td>\n";
                echo "<td>" .downlink("logsearch.php?inet_source=$source&amp;int_sev=$sev", nf($count)). "</td>\n";
              echo "</tr>\n";
            }
          echo "</table>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
}

pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
