<?php include("menu.php"); set_title("IP exclusions"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.01                  #
# 08-05-2007                       #
# Kees Trippelvitz & Jan van Lith  #
####################################

####################################
# Changelog:
# 1.04.01 Initial release
####################################

# Retrieving some session variables
$s_org = intval($_SESSION['s_org']);
$s_admin = intval($_SESSION['s_admin']);
$s_access = $_SESSION['s_access'];
$s_access_user = intval($s_access{2});
$s_hash = md5($_SESSION['s_hash']);
$err = 0;

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_m",
		"sort",
		"int_orgid"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Checking access
if ($s_access_user < 2) {
  $err = 1;
  $clean['m'] = 91;
}

# Showing info/error messages if any
if (isset($clean['m'])) {
  $m = $clean['m'];
  $m = geterror($m);
  echo $m;
}

# Setting up organisation
if ($s_admin == 1) {
  if (isset($clean['orgid'])) {
    $org = $clean['orgid'];
  } else {
    $org = $s_org;
  }
  echo "<form name='selectorg' method='get' action='orgipadmin.php'>\n";
  $sql_orgs = "SELECT id, organisation FROM organisations ORDER BY organisation";
  $debuginfo[] = $sql_orgs;
  $result_orgs = pg_query($pgconn, $sql_orgs);
  echo "<select name='int_orgid' onChange='javascript: this.form.submit();'>\n";
    echo printOption(0, "", $org);
    while ($row = pg_fetch_assoc($result_orgs)) {
      $org_id = $row['id'];
      $organisation = $row['organisation'];
      echo printOption($org_id, $organisation, $org) . "\n";
    }
  echo "</select>&nbsp;<br /><br />\n";
  echo "</form>\n";
} else {
  $org = $s_org;
}

if ($err == 0) {
  # Setting up sorting stuff
  if (isset($tainted['sort'])) {
    $sort = $tainted['sort'];
    $pattern = '/^(ia|id)$/';
    if (!preg_match($pattern, $sort)) {
      $sort = "ia";
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
    if ($type == "i") {
      $sqlsort = "exclusion $direction";
    }
    add_to_sql($sqlsort, "order");
  } else {
    $neworder = "d";
    add_to_sql("exclusion ASC", "order");
  }

  add_to_sql("id", "select");
  add_to_sql("exclusion", "select");
  add_to_sql("org_excl", "table");
  add_to_sql("orgid = $org", "where");
  prepare_sql();
  $sql_orgs = "SELECT $sql_select ";
  $sql_orgs .= "FROM $sql_from ";
  $sql_orgs .= " $sql_where ";
  $sql_orgs .= " ORDER BY $sql_order ";
  $debuginfo[] = $sql_orgs;
  $result_orgs = pg_query($pgconn, $sql_orgs);

  echo "<form name='orgadmin' action='orgipadd.php?int_orgid=$org' method='post'>\n";
  echo "<table class='datatable'>\n";
    echo "<tr class='datatr'>\n";
      echo "<td class='dataheader' width='100'><a href='orgipadmin.php?sort=i$neworder&int_orgid=$org'>Exclusion</a></td>\n";
      echo "<td class='dataheader' width='100'>Actions</td>\n";
    echo "</tr>\n";

    while ($row = pg_fetch_assoc($result_orgs)) {
      $id = $row['id'];
      $excl = $row['exclusion'];
    
      echo "<tr class='datatr'>\n";
        echo "<td class='datatd'>$excl</td>\n";
        echo "<td class='datatd'><a href='orgipdel.php?int_id=$id&int_orgid=$org' alt='Delete the IP' class='linkbutton'><font size=1>[Delete]</font></a></td>\n";
      echo "</tr>\n";
    }

    echo "<tr>\n";
      echo "<td class='datatd'><input type='hidden' name='int_orgid' value='$org' /><input type='text' name='ip_exclusion' size='40' /></td>\n";
      echo "<td class='datatd'><input type='submit' class='button' style='width: 100%;' value='Insert' /></td>\n";
    echo "</tr>\n";
  echo "</table>\n";
  echo "<input type='hidden' name='md5_hash' value='$s_hash' />\n";
  echo "</form>\n";
}
debug_sql();
?>
<?php footer(); ?>
