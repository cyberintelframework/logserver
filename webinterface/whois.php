<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.02.02                  #
# 11-08-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.02.02 Added exit after failed preg_match
# 1.02.01 Initial release
#############################################

if (isset($_GET['ip']) && $_GET['ip'] != '') {
  $ip = $_GET['ip'];
  if (!preg_match($ipregexp, $ip)) {
    echo "<p><font color='red'>Invalid IP address</font></p>";
    $err = 1;
    exit;
  }

  if (isset($_GET['s'])) {
    $serv = stripinput($_GET['s']);
    $pattern = '/^(arin|lacnic|apnic|ripe|afrinic)$/';
    if (preg_match($pattern, $serv) != 1) {
      $server = "whois.ripe.net";
    } else {
      $server = "whois." .$serv. ".net";
    }
  }
  else {
    $server = "whois.ripe.net";
  }
  echo "Other servers: <a href='whois.php?s=ripe&ip=$ip'>RIPE</a>&nbsp;|&nbsp;<a href='whois.php?s=arin&ip=$ip'>ARIN</a>&nbsp;|&nbsp;";
  echo "<a href='whois.php?s=lacnic&ip=$ip'>LACNIC</a>&nbsp;|&nbsp;<a href='whois.php?s=afrinic&ip=$ip'>AFRINIC</a>&nbsp;|&nbsp;";
  echo "<a href='whois.php?s=apnic&ip=$ip'>APNIC</a>\n";
  echo "<br><h4>WHOIS Query at $server for $ip</h4>\n";
  echo "<blockquote>\n";
  echo "<pre>\n";
  flush();

  echo "Connecting to $server:43...<br>\n";
  $fp=@fsockopen($server,43,&$errno,&$errstr,15);
  if(!$fp || $err == 1)
  {
    echo "Connection to $server:43 could not be made.<br>\n";
    return false;
  } else {
    echo "Connected to $server:43, sending request...<br>\n";
    fputs($fp,"$ip\r\n");
    while(!feof($fp)) {
      echo fgets($fp,256);
    }
    fclose($fp);
    echo "Connection closed.<br>\n";
  }
  echo "</pre>\n";
}
else {
  echo "No IP given to query.<br />\n";
  echo "<a href='logindex.php'>Logging Overview</a>\n";
}
?> 
<?php footer(); ?>
