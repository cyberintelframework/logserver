<?php

####################################
# SURFids 2.00.03                  #
# Changeset 002                    #
# 27-05-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 002 Added session check
# 001 version 2.00
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Starting the session
session_start();

if (isset($_SESSION['s_admin'])) {
  # Validate the session_id() against the SID in the database
  $chk_sid = checkSID();
  if ($chk_sid == 1) {
    $absfile = $_SERVER['SCRIPT_NAME'];
    $file = basename($absfile);
    $address = getaddress();

    $url = basename($_SERVER['SCRIPT_NAME']);
    header("location: ${address}login.php");
    exit;
  }
} else {
  $absfile = $_SERVER['SCRIPT_NAME'];
  $file = basename($absfile);
  $address = getaddress();

  $url = basename($_SERVER['SCRIPT_NAME']);
  header("location: ${address}login.php");
  exit;
}

echo "<head>\n";
  echo "<link rel='stylesheet' href='${address}include/layout.css' />\n";
  echo "<link rel='stylesheet' href='${address}include/design.css' />\n";
  echo "<link rel='stylesheet' href='${address}include/idsstyle.css' />\n";
  echo "<title>SURFids Whois</title>\n";
echo "</head>\n";

# Retrieving posted variables from $_GET
$allowed_get = array(
                "ip_ip",
		"strip_html_s"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

# Setting the whois server
if (isset($clean['s'])) {
  $serv = $clean['s'];
  $pattern = '/^(arin|lacnic|apnic|ripe|afrinic|krnic)$/';
  if (preg_match($pattern, $serv) != 1) {
    $server = "whois.ripe.net";
  } else {
    $server = "whois." .$serv. ".net";
  }
} else {
  $serv = "ripe";
  $server = "whois.ripe.net";
}

if (isset($clean['ip'])) {
  $ip = $clean['ip'];
} else {
  $err = 1;
}

echo "<div class='leftsmall'>\n";
  echo "<div class='block'>\n";
    echo "<div class='actionBlock'>\n";
      echo "<div class='blockHeader'>Select server</div>\n";
      echo "<div class='blockContent'>\n";
        echo "<form name='whois' method='get'>\n";
          echo "<table class='actiontable'>\n";
            echo "<tr>\n";
              echo "<td>Whois query:</td>\n";
              echo "<td>\n";
                echo "<select name='strip_html_s'>\n";
                  foreach ($v_whois_servers as $key => $val) {
                    echo printOption($key, $val, $serv);
                  }
                echo "</select>\n";
              echo "</td>\n";
            echo "</tr>\n";
            echo "<tr>\n";
              echo "<td>Enter whois IP:</td>\n";
              echo "<td><input type='text' size='12' name='ip_ip' value='$ip' /><input type='submit' class='pbutton' value='Query' /></td>\n";
            echo "</tr>\n";
          echo "</table>\n";
        echo "</form>\n";
      echo "</div>\n"; #</blockContent>
      echo "<div class='blockFooter'></div>\n";
    echo "</div>\n"; #</actionBlock>
  echo "</div>\n"; #</block>
echo "</div>\n"; #</leftsmall>

if ($err == 0) {
  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>WHOIS Query at $server for $ip</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<pre id='whois'>\n";
            flush();
            echo "Connecting to $server:43...<br>\n";
            $fp = @fsockopen($server,43,&$errno,&$errstr,15);
            if(!$fp || $err != 0) {
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
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>
}

#echo "<div class='leftsmall'>\n";
#  echo "<div class='block'>\n";
#    echo "<input type='button' onclick='popout();' class='button' value='Close this popup' />\n";
#  echo "</div>\n";
#echo "</div>\n";

?> 
