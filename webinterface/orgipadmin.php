<?php $tab="4.3"; $pagetitle="Exclusions"; include("menu.php"); contentHeader(1,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 002                    #
# 23-04-2008                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 002 Added MAC exclusion stuff
# 001 Added language support
####################################

# Checking access
if ($s_access_user < 2) {
  geterror(101);
  footer();
  exit;
}

$err = 0;
# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_m",
		        "sort",
        		"int_orgid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($err == 0) {
  add_to_sql("exclusion ASC", "order");
  add_to_sql("id", "select");
  add_to_sql("exclusion", "select");
  add_to_sql("org_excl", "table");
  add_to_sql("orgid = $q_org", "where");
  prepare_sql();
  $sql_ex = "SELECT $sql_select ";
  $sql_ex .= "FROM $sql_from ";
  $sql_ex .= " $sql_where ";
  $sql_ex .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_ex;
  $result_ex = pg_query($pgconn, $sql_ex);

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>";
          echo "<div class='blockHeaderLeft'>" .$l['oi_ip_excl']. " " .printhelp(11,11). "</div>\n";
          echo "<div class='blockHeaderRight'>\n";
#            echo "<form name='viewform' action='$url' method='GET'>\n";
#              if ($s_access_search == 9) {
#                $sql_orgs = "SELECT id, organisation FROM organisations WHERE NOT organisation = 'ADMIN' ORDER BY organisation";
#                $debuginfo[] = $sql_orgs;
#                $result_orgs = pg_query($pgconn, $sql_orgs);
#                $num = pg_num_rows($result_orgs);
#                echo "<select name='int_org' class='smallselect' onChange='javascript: this.form.submit();'>\n";
#                  while ($row = pg_fetch_assoc($result_orgs)) {
#                    $org_id = $row['id'];
#                    $organisation = $row['organisation'];
#                    echo printOption($org_id, $organisation, $q_org);
#                  }
#                echo "</select>\n";
#              }
#            echo "</form>\n";
          echo "</div>\n";
        echo "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<form name='orgadmin' action='orgipadd.php' method='post'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th width='80%'>" .$l['oi_ip_excl']. "</th>\n";
                echo "<th width='20%'>" .$l['g_actions']. "</th>\n";
              echo "</tr>\n";
              while ($row = pg_fetch_assoc($result_ex)) {
                $id = $row['id'];
                $excl = $row['exclusion'];
                echo "<tr>\n";
                  echo "<td>$excl</td>\n";
                  echo "<td><a href='orgipdel.php?int_id=$id&int_orgid=$q_org&int_type=1' onclick=\"javascript: return confirm('" .$l['oi_confirmdel']. "?');\">[" .$l['g_delete']. "]</a></td>\n";
                echo "</tr>\n";
              }
              echo "<tr>\n";
                echo "<td><input type='hidden' name='int_orgid' value='$q_org' /><input type='text' name='ip_exclusion' size='40' /></td>\n";
                echo "<td><input type='submit' class='button' style='width: 100%;' value='" .$l['g_add']. "' /></td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' name='int_type' value='1' />\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>

  if ($s_admin == 1) {
    reset_sql();
    add_to_sql("mac ASC", "order");
    add_to_sql("id", "select");
    add_to_sql("mac", "select");
    add_to_sql("arp_excl", "table");
    prepare_sql();
    $sql_ex = "SELECT $sql_select ";
    $sql_ex .= "FROM $sql_from ";
    $sql_ex .= " $sql_where ";
    $sql_ex .= " ORDER BY $sql_order ";
    $debuginfo[] = $sql_ex;
    $result_ex = pg_query($pgconn, $sql_ex);

    echo "<div class='left'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>";
            echo "<div class='blockHeaderLeft'>" .$l['oi_mac_excl']. "</div>\n";
            echo "<div class='blockHeaderRight'>\n";
#              echo "<form name='viewform' action='$url' method='GET'>\n";
#                if ($s_access_search == 9) {
#                  $sql_orgs = "SELECT id, organisation FROM organisations WHERE NOT organisation = 'ADMIN' ORDER BY organisation";
#                  $debuginfo[] = $sql_orgs;
#                  $result_orgs = pg_query($pgconn, $sql_orgs);
#                  $num = pg_num_rows($result_orgs);
#                  echo "<select name='int_org' class='smallselect' onChange='javascript: this.form.submit();'>\n";
#                    while ($row = pg_fetch_assoc($result_orgs)) {
#                      $org_id = $row['id'];
#                      $organisation = $row['organisation'];
#                      echo printOption($org_id, $organisation, $q_org);
#                    }
#                  echo "</select>\n";
#                }
#              echo "</form>\n";
            echo "</div>\n";
          echo "</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<form name='orgmacadmin' action='orgipadd.php' method='post'>\n";
              echo "<table class='datatable'>\n";
                echo "<tr>\n";
                  echo "<th width='80%'>" .$l['oi_mac_excl']. "</th>\n";
                  echo "<th width='20%'>" .$l['g_actions']. "</th>\n";
                echo "</tr>\n";
                while ($row = pg_fetch_assoc($result_ex)) {
                  $id = $row['id'];
                  $excl = $row['mac'];
                  echo "<tr>\n";
                    echo "<td>$excl</td>\n";
                    echo "<td><a href='orgipdel.php?int_id=$id&int_orgid=$q_org&int_type=2' onclick=\"javascript: return confirm('" .$l['oi_confirmdel']. "?');\">[" .$l['g_delete']. "]</a></td>\n";
                  echo "</tr>\n";
                }
                echo "<tr>\n";
                  echo "<td><input type='hidden' name='int_orgid' value='$q_org' /><input type='text' name='mac_exclusion' size='40' /></td>\n";
                  echo "<td><input type='submit' class='button' style='width: 100%;' value='" .$l['g_add']. "' /></td>\n";
                echo "</tr>\n";
              echo "</table>\n";
              echo "<input type='hidden' name='int_type' value='2' />\n";
              echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
            echo "</form>\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</div>\n"; #</left>
  }
}
debug_sql();
?>
<?php footer(); ?>
