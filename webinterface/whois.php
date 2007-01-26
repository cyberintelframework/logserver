<?php include("menu.php"); ?>
<?php

####################################
# SURFnet IDS                      #
# Version 1.04.03                  #
# 19-01-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 1.04.03 Added KRNIC and flush()
# 1.04.02 Changed data input handling
# 1.04.01 pg_close added
# 1.03.01 Released as part of the 1.03 package
# 1.02.02 Added exit after failed preg_match
# 1.02.01 Initial release
#############################################

$allowed_get = array(
                "ip_ip",
		"strip_html_s"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

if (isset($clean['ip'])) {
  $ip = $clean['ip'];

  if (isset($clean['s'])) {
    $serv = $clean['s'];
    $pattern = '/^(arin|lacnic|apnic|ripe|afrinic|krnic)$/';
    if (preg_match($pattern, $serv) != 1) {
      $server = "whois.ripe.net";
    } else {
      $server = "whois." .$serv. ".net";
    }
  } else {
    $server = "whois.ripe.net";
  }
  echo "Other servers: ";
  echo "<a href='whois.php?strip_html_s=ripe&ip_ip=$ip'>RIPE</a>";
  echo "&nbsp;|&nbsp;";
  echo "<a href='whois.php?strip_html_s=arin&ip_ip=$ip'>ARIN</a>";
  echo "&nbsp;|&nbsp;";
  echo "<a href='whois.php?strip_html_s=lacnic&ip_ip=$ip'>LACNIC</a>";
  echo "&nbsp;|&nbsp;";
  echo "<a href='whois.php?strip_html_s=afrinic&ip_ip=$ip'>AFRINIC</a>";
  echo "&nbsp;|&nbsp;";
  echo "<a href='whois.php?strip_html_s=apnic&ip_ip=$ip'>APNIC</a>\n";
  echo "&nbsp;|&nbsp;";
  echo "<a href='whois.php?strip_html_s=krnic&ip_ip=$ip'>KRNIC</a>\n";
  echo "<br><h4>WHOIS Query at $server for $ip</h4>\n";
  echo "<blockquote>\n";
  echo "<pre>\n";
  flush();

  echo "Connecting to $server:43...<br>\n";
  $fp = @fsockopen($server,43,&$errno,&$errstr,15);
  if(!$fp || $err == 1) {
    echo "Connection to $server:43 could not be made.<br>\n";
    return false;
  } else {
    echo "Connected to $server:43, sending request...<br>\n";
    fputs($fp, "$ip\r\n");
    while(!feof($fp)) {
      echo fgets($fp, 256);
      flush();
    }
    fclose($fp);
    echo "Connection closed.<br>\n";
  }
  echo "</pre>\n";
} else {
  echo "No IP given to query.<br />\n";
  echo "<a href='logindex.php'>Logging Overview</a>\n";
}
?> 
<?php footer(); ?>
