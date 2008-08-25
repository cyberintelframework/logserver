<?php $tab="5.3"; $pagetitle="Domains"; include("menu.php"); contentHeader(0,0); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 2.10.01                  #
# 25-10-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 2.10.01 Added language support
# 2.00.02 Changed unauthorized access error message
# 2.00.01 version 2.00
# 1.04.05 Added hash check stuff
# 1.04.04 Added sort option
# 1.04.03 Changed data input handling
# 1.04.02 Changed debug info
# 1.04.01 Rereleased as 1.04.01
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 Added intval() for session variables
# 1.02.05 Added some more input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Added modifications for org_id table
# 1.02.02 Added identifier column to table
# 1.02.01 Initial release
####################################

$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_m",
		"sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking access
if ($s_admin != 1) {
  geterror(101);
  footer();
  exit;
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  geterror($m);
}

if ($err == 0) {
  # Setting up sorting stuff
  if (isset($tainted['sort'])) {
    $pattern = '/^(organisationa|organisationd|ida|idd)$/';
    $sort = $tainted['sort'];
    $sql_sort = sorter($sort, $pattern);
    if ($sql_sort != "") {
      add_to_sql("$sql_sort", "order");
    }
  } else {
    add_to_sql("organisation ASC", "order");
    $sort = "organisationa";
  }

  add_to_sql("organisations.id", "select");
  add_to_sql("organisations.organisation", "select");
  add_to_sql("COUNT(org_id.id) as total", "select");
  add_to_sql("organisations", "table");
  add_to_sql("organisations.id", "group");
  add_to_sql("organisations.organisation", "group");
  if ($s_admin != 1) {
    add_to_sql("organisations.id = $s_org", "where");
  }
  prepare_sql();
  $sql_orgs = "SELECT $sql_select ";
  $sql_orgs .= "FROM $sql_from ";
  $sql_orgs .= " $sql_where ";
  $sql_orgs .= " LEFT JOIN org_id ";
  $sql_orgs .= " ON organisations.id = org_id.orgid ";
  $sql_orgs .= " GROUP BY $sql_group ";
  $sql_orgs .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_orgs;
  $result_orgs = pg_query($pgconn, $sql_orgs);

  echo "<div class='left'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['me_domains']. "</div>";
        echo "<div class='blockContent'>\n";
          echo "<form name='orgadmin' action='orgsave.php?savetype=org' method='post'>\n";
            echo "<table class='datatable'>\n";
              echo "<tr>\n";
                echo "<th width='50'>" .printsort($l['g_id'], "id"). "</th>\n";
                echo "<th width='200'>" .printsort($l['g_domain'], "organisation"). "</th>\n";
                echo "<th width='100'># " .$l['oa_identifiers']. "</th>\n";
                echo "<th width='100'>" .$l['g_actions']. "</th>\n";
              echo "</tr>\n";

              while ($row = pg_fetch_assoc($result_orgs)) {
                $id = $row['id'];
                $org = $row['organisation'];
                $count = $row['total'];
    
                echo "<tr>\n";
                  echo "<td>$id</td>\n";
                  echo "<td>$org</td>\n";
                  if ($org != "ADMIN" && $count == 0) {
                    echo "<td><span class='warning'>$count</span></td>\n";
                  } else {
                    echo "<td>$count</td>\n";
                  }
                  echo "<td><a href='orgedit.php?int_orgid=$id' alt='" .$l['oa_editdomain']. "' class='linkbutton'><font size=1>[" .$l['g_edit']. "]</font></a></td>\n";
                echo "</tr>\n";
              }

              echo "<tr>\n";
                echo "<td>#</td>\n";
                echo "<td colspan='2'><input type='text' name='strip_html_escape_orgname' size='40' /></td>\n";
                echo "<td><input type='submit' class='button' style='width: 100%;' value='" .$l['g_insert']. "' /></td>\n";
              echo "</tr>\n";
            echo "</table>\n";
            echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
          echo "</form>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</left>
}
debug_sql();
?>
<?php footer(); ?>
