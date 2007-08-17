<?php include("menu.php"); set_title("Organisation Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.06                  #
# 30-03-2007                       #
# Kees Trippelvitz                 #
####################################

####################################
# Changelog:
# 1.04.06 Changed printhelp stuff
# 1.04.05 Added hash check stuff
# 1.04.04 Changed data input handling
# 1.04.03 Changed debug stuff
# 1.04.02 Added identifier type
# 1.04.01 Code layout & error message handling
# 1.03.01 Released as part of the 1.03 package
# 1.02.06 Added intval() to session variables + pg_close
# 1.02.05 Added some more input checks and removed includes
# 1.02.04 Enhanced debugging
# 1.02.03 Added modifications for org_id table.
# 1.02.02 Added identifier column to table.
# 1.02.01 Initial release
####################################

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_hash = md5($_SESSION['s_hash']);
# $s_access is obsolete again because $s_admin needs to be 1 to gain access
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});

if ( $s_admin != 1 ) {
  $err = 1;
}

$allowed_get = array(
                "int_orgid",
                "int_m"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (!isset($clean['orgid'])) {
  $err = 1;
} else {
  $orgid = $clean['orgid'];
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
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

  echo "<form action='orgsave.php?savetype=ident' method='POST'>\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd' width='100'>ID</td>\n";
        echo "<td class='datatd' width='300'>$orgid<input type='hidden' name='int_orgid' value='$orgid' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd'>Organisation</td>\n";
        echo "<td class='datatd'><input type='text' name='strip_html_escape_orgname' value='$orgname' style='width: 99%;' /></td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' valign='top'>Ranges&nbsp;&nbsp;" .printhelp("ranges"). "</td>\n";
        echo "<td class='datatd'><textarea name='strip_html_escape_ranges' cols='40' rows='10'>$ranges</textarea></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
    echo "<br />\n";

    $sql_orgids = "SELECT * FROM org_id WHERE orgid = " .$orgid;
    $result_orgids = pg_query($pgconn, $sql_orgids);
    $debuginfo[] = $sql_orgids;
    
    echo "<a href='orgsave.php?savetype=md5&int_orgid=$orgid&md5_hash=$s_hash'>Generate Random Identifier String</a>&nbsp;&nbsp;";
    echo printhelp("ris") ."<br />\n";
    echo "<table class='datatable'>\n";
      echo "<tr class='datatr'>\n";
        echo "<td class='dataheader' width='100'>ID</td>\n";
        echo "<td class='dataheader' width='250'>Identifier</td>\n";
        echo "<td class='dataheader' width='150'>Type</td>\n";
        echo "<td class='dataheader' width='50'>Action</td>\n";
      echo "</tr>\n";

      while ($row = pg_fetch_assoc($result_orgids)) {
        $id = $row['id'];
        $type = $row['type'];
        $identifier = $row['identifier'];

        echo "<tr class='datatr'>\n";
          echo "<td class='datatd'>$id</td>\n";
          echo "<td class='datatd'>$identifier</td>\n";
          echo "<td class='datatd'>$v_org_ident_type_ar[$type]</td>\n";
          echo "<td class='datatd'><a href='orgdel.php?int_orgid=$orgid&int_ident=$id&md5_hash=$s_hash' onclick=\"javascript: return confirm('Are you sure you want to delete this identifier?');\">Delete</a></td>\n";
        echo "</tr>\n";
      }

      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>#</td>\n";
        echo "<td class='datatd' colspan='1'><input type='text' name='strip_html_escape_orgident' style='width: 99%;' /></td>\n";
        echo "<td class='datatd' colspan='2'>\n";
          echo "<select name='int_identtype' style='width: 99%;'>";
            echo printOption(0, "Select a type...", 0);
            foreach ($v_org_ident_type_ar as $key => $val) {
              if ($key != 1) {
                echo printOption($key, $val, 0);
              }
            }
          echo "</select>\n";
        echo "</td>\n";
      echo "</tr>\n";
      echo "<tr>\n";
        echo "<td class='datatd' colspan='4' align='right'>";
          echo "<input type='submit' name='submit' value='Save' class='button' /></td>\n";
      echo "</tr>\n";
    echo "</table>\n";
    echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
  echo "</form>\n";
}
pg_close($pgconn);
debug_sql();
?>
<?php footer(); ?>
