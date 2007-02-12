<?php include("menu.php"); set_title("Organisation Admin"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.04                  #
# 01-02-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
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

### Access level: s_admin == 1

$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$err = 0;

$allowed_get = array(
                "int_m",
		"sort"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if ($s_admin != 1) {
  $err = 1;
  $m = 91;
}

if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

if ($err == 0) {
  if (isset($tainted['sort'])) {
    $sort = $tainted['sort'];
    $pattern = '/^(oa|od|ia|id)$/';
    if (!preg_match($pattern, $sort)) {
      $sort = "oa";
    }

    $type = $sort{0};
    $direction = $sort{1};
    if ($direction == "a") {
      $neworder = "d";
      $direction = "ASC";
    } else {
      $neworder = "a";
      $direction = "DESC";
    }
    if ($type == "o") {
      $sqlsort = "organisations.organisation $direction";
    } elseif ($type == "i") {
      $sqlsort = "total $direction";
    }
    add_to_sql($sqlsort, "order");
  } else {
    $neworder = "d";
    add_to_sql("organisations.organisation", "order");
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

  echo "<form name='orgadmin' action='orgsave.php?savetype=org' method='post'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader' width='50'>ID</td>\n";
      echo "<td class='dataheader' width='200'><a href='orgadmin.php?sort=o$neworder' title='Sort on organisation'>Organisation</a></td>\n";
      echo "<td class='dataheader' width='100'><a href='orgadmin.php?sort=i$neworder' title='Sort on identifiers'># of identifiers</a></td>\n";
      echo "<td class='dataheader' width='100'>Actions</td>\n";
    echo "</tr>\n";

    while ($row = pg_fetch_assoc($result_orgs)) {
      $id = $row['id'];
      $org = $row['organisation'];
      $count = $row['total'];
    
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>$id</td>\n";
        echo "<td class='datatd'>$org</td>\n";
        echo "<td class='datatd'>$count</td>\n";
        echo "<td class='datatd'><a href='orgedit.php?int_orgid=$id' alt='Edit the organisation' class='linkbutton'><font size=1>[Edit]</font></a></td>\n";
      echo "</tr>\n";
    }

    echo "<tr>\n";
      echo "<td class='datatd'>#</td>\n";
      echo "<td class='datatd' colspan='2'><input type='text' name='strip_html_escape_orgname' size='40' /></td>\n";
      echo "<td class='datatd'><input type='submit' class='button' style='width: 100%;' value='Insert' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "</form>\n";
}
debug_sql();
?>
<?php footer(); ?>
