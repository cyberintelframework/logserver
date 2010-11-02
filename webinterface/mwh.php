<?php

####################################
# SURFids 3.00                     #
# Changeset 005                    #
# 21-10-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 005 Fixed checkSID call
# 004 Added extra flush()
# 003 Fixed bug #79
# 002 Added JPNIC support and fixed KRNIC
# 001 Added language support
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';
include '../include/variables.inc.php';

# Including language file
include "../lang/${c_language}.php";

# Starting the session
session_start();

if (isset($_SESSION['s_admin'])) {
  # Validate the session_id() against the SID in the database
  $chk_sid = checkSID($c_chksession_ip, $c_chksession_ua);
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
  echo "<title>SURFids MWH</title>\n";
echo "</head>\n";

# Retrieving posted variables from $_GET
$allowed_get = array(
                "md5_bin"
);
$check = extractvars($_GET, $allowed_get);
debug_input();

$server = "hash.cymru.com";
#$server = "whois -h hash.cymru.com e1112134b6dcc8bed54e0e34d8ac272795e73d74";

if (isset($clean['bin'])) {
  $bin = $clean['bin'];
} else {
  $err = 1;
}

if ($err == 0) {
  echo "<div class='centerbig'>\n";
    echo "<div class='block'>\n";
      echo "<div class='dataBlock'>\n";
        echo "<div class='blockHeader'>" .$l['wh_mwhquery']. " $server " .$l['wh_for']. " $ip</div>\n";
        echo "<div class='blockContent'>\n";
          echo "<pre id='mwh'>\n";
            flush();
            echo "<b>". $l['wh_connect']. " $server:43...</b>\n";
            flush();
            $fp = @fsockopen($server,43,&$errno,&$errstr,15);
            if(!$fp || $err != 0) {
              echo "<b>". $l['wh_connto']. " $server:43 " .$l['wh_couldnot']. ".</b>\n\n";
              return false;
            } else {
              echo "<b>". $l['wh_connected']. " $server:43, " .$l['wh_sending']. "</b>\n\n";
              fputs($fp, "$bin /e\r\n");
              while(!feof($fp)) {
                $line = fgets($fp, 256);
                $line_ar = explode(" ", $line);
                echo "HASH:\t\t\t\t$line_ar[0]\n";
                echo "LAST KNOWN DATE:\t\t" .date($c_date_format, $line_ar[1]). "\n";
                echo "AV DETECTION RATE:\t\t" .trim($line_ar[2]). "%\n";
                flush();
              }
              fclose($fp);
              echo "\n<b>". $l['wh_connclosed']. "...</b>\n";
            }
          echo "</pre>\n";
        echo "</div>\n"; #</blockContent>
        echo "<div class='blockFooter'></div>\n";
      echo "</div>\n"; #</dataBlock>
    echo "</div>\n"; #</block>
  echo "</div>\n"; #</centerbig>
}

?> 
