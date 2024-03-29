<?php

####################################
# SURFids 3.00                     #
# Changeset 003                    #
# 21-10-2009                       #
# Kees Trippelvitz                 #
####################################

#############################################
# Changelog:
# 003 Added ranges help info, fixed domain strings
# 002 Fixed bug #181
# 001 Initial release
#############################################

ini_set("session.bug_compat_42", "off");
ini_set("session.bug_compat_warn", "off");

include '../include/config.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  #$address = getaddress();
  #pg_close($pgconn);
  #header("location: ${address}login.php");
  echo "You are not logged in!";
  exit;
}

# Retrieving some session variables
$s_hash = md5($_SESSION['s_hash']);
$s_access = $_SESSION['s_access'];
$s_access_sensor = intval($s_access{0});

# Retrieving posted variables from $_GET
$allowed_get = array(
                "int_id"
);
$check = extractvars($_GET, $allowed_get);
#debug_input();

if (!isset($clean['id'])) {
    echo "No help item available for the given ID!";
    exit;
} else {
    $id = $clean['id'];
}

if ($id == 1) {
    # sensorstatus.php - Action
    echo "<b>Ignore</b>: A sensor can be ignored when its currently not in use.<br />";
    echo "<b>Deactivate</b>: A sensor can be deactivated when the sensor is never expected to be used again. ";
    echo "Both the sensor and attached attack records are stored in an archive table.<br />";
    if ($s_access_sensor == 9) {
        echo "<b>Sensor Upgrade</b>: The sensor will upgrade itself to a newer version if available.<br />";
        echo "<b>Dep Upgrade</b>: The sensor will upgrade it's dependencies to newer versions if available.<br />";
        echo "<b>APT Upgrade</b>: The sensor will do an upgrade of all outdated packages if available.<br />";
        echo "<b>APT Count</b>: Queries the sensor for the amount of updates currently available.";
    }
} elseif ($id == 2) {
    # binaryhist.php - UPX
    echo "UPX is a well-known compression often used for malicious files. This shows the result of the UPX check.";
} elseif ($id == 3) {
    # binaryhist.php - File
    echo 'The output of the linux program "file".';
} elseif ($id == 4) {
    # Attack stages
    echo "<b>Possible Malicious Attack</b>: This is a possible attack. Example: Port scans, random network traffic or unidentified attacks.<br />";
    echo "<b>Malicious Attack</b>: At this point it&#39;s certain that the connection that was made to the sensor was a malicious connection.<br />";
    echo "&nbsp;&nbsp;&nbsp;<b>Nepenthes</b>: A low interaction honeypot detecting attacks using signatures.<br />";
    echo "&nbsp;&nbsp;&nbsp;<b>Dionaea</b>: A low interaction honeypot detecting attacks on the SMB protocol using emulation.<br />";
    echo "&nbsp;&nbsp;&nbsp;<b>Argos</b>: A high interaction honeypot detecting control flow diversions or buffer overflows.<br />";
    echo "&nbsp;&nbsp;&nbsp;<b>Kippo</b>: A low interaction honeypot detecting SSH brute force scans / logins.<br />";
    echo "&nbsp;&nbsp;&nbsp;<b>ARP Poisoning</b>: ARP poisoning is an ethernet layer man-in-the-middle attack.<br />";
    echo "&nbsp;&nbsp;&nbsp;<b>Rogue DHCP server</b>: A Rogue DHCP server is an unauthorized DHCP server.<br />";
    echo "<b>Malware Offered</b>: A piece of malware is offered to the honeypot. The honeypot will try to download it.<br />";
    echo "<b>Malware Downloaded</b>: The malware was succesfully downloaded to the honeypot.";
} elseif ($id == 5) {
    # arp_cache.php - ARP cache
    echo "The ARP cache is a table with the translation of MAC addresses into IP addresses. Note that the ARP cache table you see here is not the actual ";
    echo " table that is present on the system, but the internal table of our detection script.";
} elseif ($id == 6) {
    # logcheck.php - Cross Domain
    echo "Cross domain attacks are attacks that have been detected by sensors that do not belong to your own domain.";
} elseif ($id == 7) {
    # orgedit.php - Generate OID
    echo "An OID is a randomly generated string of 32 alphanumeric characters that is unique. You can supply this string ";
    echo " in the configuration file of the sensor. This will make the sensor send the string to the server when it will ";
    echo " request it's certificates. This ensures that sensors you create with this string in the configuration file will ";
    echo " always be categorized under this domain.<br /><br />";
    echo " <b>FAQ - L08</b> (Click the help link to go to the SURFids FAQ page)";
} elseif ($id == 8) {
    # arp_config.php - ARP
    echo "This module enables the detection of ARP poisoning (an ethernet layer man-in-the-middle attack).";
} elseif ($id == 9) {
    # arp_config.php - DHCP
    echo "This module enables the detection of unauthorized DHCP servers.";
} elseif ($id == 10) {
    # arp_config.php - IPv6
    echo "This module enables the detection of IPv6 man-in-the-middle attack.";
} elseif ($id == 11) {
    # orgipadmin.php - IP exclusion
    echo "Here you can exclude certain IP and/or MAC addresses from being shown in the web interface. They will still be logged, but will ";
    echo "no longer show up in search results, etc.<br /><br /> This is useful for addresses of which you know will be regularly contacting the honeypot ";
    echo "(scheduled network scans, etc).";
} elseif ($id == 12) {
    # argosconfig.php - Template
    echo "<b>All Traffic</b> - This will redirect all traffic of the selected sensor to the Argos honeypot.<br />";
    echo "<b>Top 100 *</b> - A top 100 list is populated by the most active attackers (source IP address) for every ";
    echo "Possible Malicious attack that did not result in a known Malicious attack as detected by Nepenthes.";
} elseif ($id == 13) {
    # argosconfig.php - Timespan
    echo "The timespan determines the period of time over which a Top 100 list is generated. No timespan means it's generated over ";
    echo "all the data available.<br /><br />";
    echo "This option does not apply when redirecting All Traffic.";
} elseif ($id == 14) {
    # menu.php - Input Debugging
    echo "<b>Tainted</b> - The tainted array contains every variable (user input) that has not been checked in any way by the extractvars function, ";
    echo "but is allowed to be used on the current page. Error checking is usually done at a later stage for these variables.<br />";
    echo "<b>Clean</b> - The clean array contains every variable (user input) that has been through sufficient input checking to be considered safe ";
    echo "to use on the current page.<br />";
    echo "<b>Unallowed</b> - The unallowed array contains every variable received that is not used on the current page and thus should never be seen ";
    echo "in the first place.";
} elseif ($id == 15) {
    # detectedproto.php
    echo "These are the detected protocols on your network. This list is generated by checking the protocol header field of any incoming packet.";
} elseif ($id == 16) {
    # report_edit.php && report_new.php - Always send report
    echo "Normally a report is only sent when the report is relevant (ie, there are detected attacks). ";
    echo "Checking this option will always send the report regardless of it's relevance.";
} elseif ($id == 17) {
    # report_edit.php && report_new.php - Report type
    echo "<b>Summary</b> - A summary report only gives you the totals of the detected attacks.<br />";
    echo "<b>Details</b> - A detail report only gives you the attack info details (IP source address/port, attack type).<br />";
    echo "<b>IDMEF detail</b> - An IDMEF detail report is the same as a normal details report but in a specified XML (IDMEF) format.<br />";
    echo "<b>Cymru markup</b> - Cymru markup: " .htmlentities("<ASN> | <IP> | <time> <info> | <ASN description>"). "<br />";
    echo "<b>Nepenthes markup</b> - Nepenthes markup: " .htmlentities("[<time>] <source> -> <url> <md5>"). "<br />";
} elseif ($id == 18) {
    # report_edit.php && report_new.php - Mail Priority
    echo "This option sets the X-Priority header of the mail.";
} elseif ($id == 19) {
    # useredit, myaccount, usernew - Page configuration
    echo "These options will let you configure the way certain pages appear for you. <br />";
    echo "To the right you can select which page to configure, below you can enable/disable options for the selected page";
} elseif ($id == 20) {
    # mod_topcountries.php
    echo "These statistics are the amount of distinct attackers per country.";
} elseif ($id == 21) {
    # orgedit.php - IP ranges
    echo "These are the IP address ranges of your domain. They are used to determine which attacks are originating from your own domain.";
} elseif ($id == 22) {
    # report_edit.php, report_new.php
    echo "A public RSS feed means it will not require any form of authentication to access this feed. <b>Be careful</b>!<br /><br />";
    echo "Destination IP addresses can be optionally censored.";
} elseif ($id == 23) {
    # arp_static.php - Add ARP to single vlan/sensor
    echo "To detect ARP poisoning, add the MAC/IP address pair for the host you want to monitor.";
} elseif ($id == 24) {
    # arp_static.php - Add DHCP to single vlan/sensor
    echo "To detect Rogue DHCP servers, add the IP address for DHCP servers of the network here.";
} elseif ($id == 25) {
    # arp_static.php - Add DHCP to single vlan/sensor
    echo "To detect Rogue DHCP servers, add the IP address of a DHCP server to all VLANs of this sensor.<br />";
} elseif ($id == 26) {
    # arp_static.php - Add IPv6 to single vlan/sensor
    echo "To detect IPv6 Man-in-the-Middle attacks, add the IPv6 address for the routers of this sensor here.";
}

?>
