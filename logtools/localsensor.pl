#!/usr/bin/perl

###################################
# Local sensor script             #
# SURFids 2.10                    #
# Changeset 003                   #
# 12-11-2008                      #
# Jan van Lith & Kees Trippelvitz #
###################################

#####################
# Changelog:
# 003 Completely redone the script
# 002 Added usage info on failure
# 001 Initial version
#####################

##################
# Modules used
##################
use DBI;
use Time::localtime qw(localtime);
use Getopt::Std;

##################
# Handling opts
##################
sub usage() {
    print "Usage: ./localsensor.pl -i <interface name> -s <sensor name> -o <organisation name>\n";
    print "\n";
    print "   -i <interface name>                   Interface that has to be added as a sensor\n";
    print "   -s <sensor name>                      Name of the sensor, defaults to Nepenthes\n";
    print "   -o <organisation name>                Organisation name, defaults to LOCAL\n";
    print "\n";
    print "Example: ./localsensor.pl -i eth0 -s mySensor -o SURFnet\n";
    print "\n";
}

getopt('iso', \%opts);

$sensor = $opts{"s"};
$if = $opts{"i"};
$org = $opts{"o"};

if ($if eq "") {
  usage();
  exit;
}

if ($sensor eq "") {
  $sensor = "Nepenthes";
}

if ($org eq "") {
  $org = "LOCAL";
}

##################
# Variables used
##################
do "/etc/surfnetids/surfnetids-log.conf";
require "$c_surfidsdir/scripts/logfunctions.inc.pl";
$ts = time;

##################
# Main script
##################

$ifip = getifip($if);
if ($ifip eq "false") {
  print "Could not retrieve IP address for interface $if\n";
  exit;
}
$ifmac = getifmac($if);
if ($ifmac eq "false") {
  print "Could not retrieve MAC address for interface $if\n";
  exit;
}

dbconnect();

# First check if the IP address already exists in the database
$chk = dbnumrows("SELECT id FROM sensors WHERE tapip = '$ifip'");
if ($chk > 0) {
  print "Sensor with IP address $ifip already exists\n";
  exit;
}

# First check if the sensor name already exists in the database
$chk = dbnumrows("SELECT id FROM sensors WHERE keyname = '$sensor'");
if ($chk > 0) {
  print "Sensor with name $sensor already exists\n";
  exit;
}

$sth = dbquery("SELECT value FROM serverinfo WHERE name = 'updaterev'");
@row = $sth->fetchrow_array;
$rev = $row[0];
if ($rev eq "") {
  $rev = 0;
}

# First check if the organisation already exists in the database
$chk = dbnumrows("SELECT id FROM organisations WHERE organisation = '$org'");
if ($chk > 0) {
  $sth = dbquery("SELECT id FROM organisations WHERE organisation = '$org'");
  @row = $sth->fetchrow_array;
  $orgid = $row[0];
} else {
  $sth = dbquery("INSERT INTO organisations (organisation) VALUES ('$org')");

  $sth = dbquery("SELECT id FROM organisations WHERE organisation = '$org'");
  @row = $sth->fetchrow_array;
  $orgid = $row[0];
}

if ($orgid ne "") {
  $sql = "INSERT INTO sensors (keyname, remoteip, localip, lastupdate, laststart, status, uptime, tap, tapip, mac, netconf, organisation, rev, sensormac) ";
  $sql .= " VALUES ('$sensor', '$ifip', '$ifip', $ts, $ts, 1, 0, '$if', '$ifip', '$ifmac', 'dhcp', $orgid, $rev, '$ifmac')";
  $chk = dbnumrows($sql);
  if ($chk != 0) {
    print "Sensor successfully added to the database!\n";
  }
}
