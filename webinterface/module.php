<?php include("menu.php"); contentHeader($cHeader); ?>
<?php
####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 23-10-2009                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 001 Added language support
#############################################

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_mod"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($tainted['show'])) {
    $show = $tainted['show'];
    $pattern = '/^(top|all)$/';
    if (!preg_match($pattern, $show)) {
        $show = "top";
    } else {
        $show = $tainted['show'];
    }
} else {
    $show = "top";
}
if ($show == 'all') $showtext = $l['g_all'];
if ($show == 'top') $showtext = $l['mo_top10'];

$err = 0;
if (isset($clean['mod'])) {
    $mod = $clean['mod'];
} else {
    $err = 1;
    $m = 162;
}

# The hidden fields with the module parameters. Used for persistent browsing via the selector.
# The modules themselves will need to take care of their own parameters they want to pass on.
echo "<input type='hidden' class='pers' name='tab' value='$tab' />\n";
echo "<input type='hidden' class='pers' name='header' value='$cHeader' />\n";
echo "<input type='hidden' class='pers' name='title' value='$pagetitle' />\n";
echo "<input type='hidden' class='pers' name='int_mod' value='$mod' />\n";

echo "<div class='leftmed'>\n";
if ($err == 0) {
    $sql_getmod = "SELECT phppage FROM indexmods WHERE id = $mod";
    $debugsql[] = $sql_getmod;
    $result_getmod = pg_query($pgconn, $sql_getmod);
    $numrows_getmod = pg_num_rows($result_getmod);
    if ($numrows_getmod == 1) {
        $row = pg_fetch_assoc($result_getmod);
        $page = $row['phppage'];

        include_once('../include/indexmods/'.$page);
    } else {
        $err = 1;
        $m = 163;
    }
}

echo "</div>\n";

# Show error if any
if ($err == 1) {
    geterror($m);
}

# Debug info
debug_sql();

?>
<?php footer(); ?>
