#!/usr/bin/perl

###################################
# Check settings script           #
# SURFnet IDS                     #
# Version 2.10.01                 #
# 29-10-2007                      #
# Jan van Lith & Kees Trippelvitz #
###################################

# settings to check for changes in the ARP settings for a local sensor
# and to update the lastupdate timestamp for a local sensor
# Ignore this file if you also installed the tunnel server package
# of the SURFids

# NOTICE: Single sensor setup only

#####################
# Changelog:
# 2.10.01 Initial version
#####################

##################
# Modules used
##################
use DBI;
use Time::localtime qw(localtime);

##################
# Variables used
##################
do '/etc/surfnetids/surfnetids-tn.conf';
require "$c_surfidsdir/scripts/tnfunctions.inc.pl";

##################
# Main script
##################
$chk = connectdb();
$sql = "SELECT arp, tap FROM sensors WHERE keyname = 'nepenthes'";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

@row = $sth->fetchrow_array;
$arp = $row[0];
$tap = $row[1];

if (!$tap) {
  $tap = "";
}

if ("$tap" ne "") {
  if ("$arp" ne "") {
    if ("$arp" eq "0") {
      $pid = `ps -ef | grep -v grep | grep detectarp | grep $tap | awk '{print \$2}'`;
      chomp($pid);
      if (!pid) {
        $pid = "";
      }
      if ("$pid" ne "") {
        `kill -9 $pid`;
      }
    } else {
      $pid = `ps -ef | grep -v grep | grep detectarp | grep $tap | wc -l`;
      chomp($pid);
      if ("$pid" eq "0") {
        system("$c_surfidsdir/scripts/detectarp.pl $tap &");
      }    
    }
  }
}
$ts = time;
$sql = "UPDATE sensors SET lastupdate = '$ts' WHERE keyname = 'nepenthes'";
$sth = $dbh->prepare($sql);
$er = $sth->execute();
