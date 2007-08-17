<?php

#########################################
#             SURFnet IDS               #
#             Version 1.0               #
#              15-11-2005               #
#    Jan van Lith & Kees Trippelvitz    #
#########################################

session_start();
header("Cache-control: private");

$_SESSION['s_org'] = NULL;
$_SESSION['s_access'] = NULL;
$_SESSION['s_admin'] = NULL;
$_SESSION['s_user'] = NULL;
$_SESSION['s_userid'] = NULL;

header("location: login.php");
?>
