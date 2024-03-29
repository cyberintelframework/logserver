<?php $tab="5.3"; $pagetitle="Domain Admin - Edit"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 18-11-2008                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 001 Added language support
####################################

# Checking access
if ($s_admin != 1) {
  $err = 1;
  $clean['m'] = 101;
}

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_orgid",
                "int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking $_GET'ed variables
if (!isset($clean['orgid'])) {
  $err = 1;
} else {
  $orgid = $clean['orgid'];
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($err != 1) {
  $sql_orgs = "SELECT * FROM organisations WHERE id = " .$orgid;
  $result_orgs = pg_query($pgconn, $sql_orgs);
  $row = pg_fetch_assoc($result_orgs);
  $debuginfo[] = $sql_orgs;

  $orgname = $row['organisation'];
  $ident = $row['identifier'];
  $ranges = $row['ranges'];
  $ranges = str_replace(";", "\n", $ranges);

  echo "<div class='leftmed'>\n";
    echo "<div class='block'>\n";
      echo "<div class='actionBlock'>\n";
        echo "<div class='blockHeader'>" .$l['g_actions']. "</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<a href='orgsave.php?savetype=md5&int_orgid=$orgid&md5_hash=$s_hash'>" .$l['oe_generate']. "</a> " .printhelp(7,0,1). " ";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</leftmed>

  echo "<div class='left'>\n";
    echo "<form action='orgsave.php?savetype=ident' method='POST'>\n";
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>" .$l['oe_editdomain']. " & " .$l['oe_idents']. "</div>\n";
          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<td width='100'>" .$l['g_id']. "</td>\n";
                echo "<td width='300'>$orgid<input type='hidden' name='int_orgid' value='$orgid' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td>" .$l['g_domain']. "</td>\n";
                echo "<td><input type='text' name='strip_html_escape_orgname' value='$orgname' /></td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td valign='top'>". $l['oe_ranges'] . " " . printhelp(21,21). "</td>\n";
                echo "<td><textarea name='strip_html_escape_ranges' cols='40' rows='10'>$ranges</textarea></td>\n";
              echo "</tr>\n";
            echo "</table>\n";
          echo "</div>\n"; #</blockContent>
  /*        echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
*/
      $sql_orgids = "SELECT * FROM org_id WHERE orgid = " .$orgid;
      $result_orgids = pg_query($pgconn, $sql_orgids);
      $debuginfo[] = $sql_orgids;
/*    
      echo "<div class='block'>\n";
        echo "<div class='dataBlock'>\n";
          echo "<div class='blockHeader'>Edit domain identifiers</div>\n";
*/          echo "<div class='blockContent'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th width='100'>" .$l['g_id']. "</th>\n";
                echo "<th width='250'>" .$l['oe_ident']. "</th>\n";
                echo "<th width='150'>" .$l['g_type']. "</th>\n";
                echo "<th width='50'>" .$l['g_action']. "</th>\n";
              echo "</tr>\n";

              while ($row = pg_fetch_assoc($result_orgids)) {
                $id = $row['id'];
                $type = $row['type'];
                $identifier = $row['identifier'];

                echo "<tr>\n";
                  echo "<td>$id</td>\n";
                  echo "<td>$identifier</td>\n";
                  echo "<td>$v_org_ident_type_ar[$type]</td>\n";
                  echo "<td>";
                    echo "<a href='orgdel.php?int_orgid=$orgid&int_ident=$id&md5_hash=$s_hash' ";
                    echo " onclick=\"javascript: return confirm('" .$l['oe_confirmdel']. "?');\">" .$l['g_delete']. "</a>";
                  echo "</td>\n";
                echo "</tr>\n";
              }
              echo "<tr>\n";
                echo "<td>#</td>\n";
                echo "<td colspan='1'><input type='text' name='strip_html_escape_orgident' /></td>\n";
                echo "<td colspan='2'>\n";
                  echo "<select name='int_identtype' style='width: 99%;'>";
                    echo printOption(-1, "Select a type...", -1);
                    foreach ($v_org_ident_type_ar as $key => $val) {
                      if ($key != 1 && $key != 0) {
                        if (($key == 4 && $c_surfnet_funcs == 1) || $key != 4) {
                          echo printOption($key, $val, -1);
                        }
                      }
                    }
                  echo "</select>\n";
                echo "</td>\n";
              echo "</tr>\n";
              echo "<tr>\n";
                echo "<td colspan='4' align='right'>";
                  echo "<input type='submit' name='submit' value='" .$l['g_add']. "' class='button' />";
                echo "</td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</div>\n"; #</blockContent>
          echo "<div class='blockFooter'></div>\n";
        echo "</div>\n"; #</dataBlock>
      echo "</div>\n"; #</block>
    echo "</form>\n";
  echo "</div>\n"; #</left>
}
debug_sql();
pg_close($pgconn);
?>
<?php footer(); ?>
