<?php

####################################
# SURFids 2.10                     #
# Changeset 001                    #
# 18-11-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################
# Contributors:                    #
# Peter Arts                       #
####################################

#############################################
# Changelog:
# 001 Initial release
#############################################

include '../include/config.inc.php';
include '../include/connect.inc.php';
include '../include/functions.inc.php';

# Starting the session
session_start();
header("Cache-control: private");

# Checking if the user is logged in
if (!isset($_SESSION['s_admin'])) {
  $address = getaddress();
  pg_close($pgconn);
  header("location: ${address}login.php");
  exit;
}

# Retrieving some session variables
$s_hash = md5($_SESSION['s_hash']);

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
    echo "Both the sensor and attached attack records are stored in an archive table.";
} elseif ($id == 2) {
    # binaryhist.php - UPX
    echo "UPX is a well-known compression often used for malicious files. This shows the result of the UPX check.";
} elseif ($id == 3) {
    # binaryhist.php - File
    echo 'The output of the linux program "file".';
} elseif ($id == 4) {
    # Attack stages
    echo "<b>Possible Malicious Attack</b>: This is a possible attack. This can be in fact any connection that is made to the sensor (portscans, random network traffic, etc). <br />";
    echo "<b>Malicious Attack</b>: At this point it&#39;s certain that the connection that was made to the sensor was a malicious connection.<br />";
    echo "<b>Malicious Attack - Argos</b>: Argos detected a control flow diversion (e.g. caused by a buffer overflow or code injection).<br />";
    echo "<b>Malicious Attack - ARP Poisoning</b>: ARP poisoning is an ethernet layer attack where the attacker changes the ARP tables, which in effect redirects all traffic through his computer. He can use this attack to sniff traffic.<br />";
    echo "<b>Malicious Attack - Rogue DHCP server</b>: A Rogue DHCP server is an unauthorized DHCP server. This can cause serious network disruption.<br />";
    echo "<b>Malware Offered</b>: A piece of malware is offered to the honeypot. The honeypot will try to download it.<br />";
    echo "<b>Malware Downloaded</b>: The malware was succesfully downloaded to the honeypot.";
} elseif ($id == 5) {
    # arp_cache.php - ARP cache
    echo "The ARP cache is a table with the translation of MAC addresses into IP addresses. Note that the ARP cache table you see here is not the actual ";
    echo " table that is present on the system, but the internal table of our detection script.";
} elseif ($id == 6) {
    # logcheck.php - Cross Domain
    echo "Cross domain attacks are attacks that have been detected by sensors that do not belong to your own organisation.";
} elseif ($id == 7) {
    # orgedit.php - Generate OID
    echo "An OID is a randomly generated string of 32 alphanumeric characters that is unique. You can supply this string ";
    echo " in the configuration file of the sensor. This will make the sensor send the string to the server when it will ";
    echo " request it's certificates. This ensures that sensors you create with this string in the configuration file will ";
    echo " always be categorized under this organisation.<br /><br />";
    echo " <b>FAQ - L08</b>";
} elseif ($id == 8) {
    # arpadmin.php - DHCP add all
    echo "This option will add a given IP address to all VLANs of the current sensor as a valid DHCP server.";
    echo "This is useful, for example, when you want to add your IP helper addresses to the table for all your VLANs.";
} elseif ($id == 9) {
    # arpadmin.php - ARP Poisoning configuration
    echo "To enable ARP Poisoning detection you will need to add the MAC/IP address pair of the host you want to monitor to this table.";
    echo "This is particularly useful for monitoring your gateways.";
} elseif ($id == 10) {
    # arpadmin.php - Single pair add
    echo "To detect ARP poisoning, add the MAC/IP address pair for the host you want to monitor and tag it as a router/gateway.<br /><br />";
    echo "To detect Rogue DHCP servers, add the MAC/IP address pair for the DHCP server (or DHCP helper) and tag it as a DHCP server.<br /><br />";
    echo "The DHCP servers in this table don't need to have the correct MAC address as only the IP address is used in Rogue DHCP server ";
    echo " detection.";
}

?>
