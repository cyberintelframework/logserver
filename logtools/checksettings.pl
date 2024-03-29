#!/usr/bin/perl

####################################
# Check settings script            #
# SURFids 3.00                     #
# Changeset 002                    #
# 02-03-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#####################
# Changelog:
# 002 Fixed references to tunnel scripts & config
# 001 Initial version
#####################

##################
# Modules used
##################
use DBI;
use Time::localtime qw(localtime);

##################
# Variables used
##################
do '/etc/surfnetids/surfnetids-log.conf';
require "$c_surfidsdir/scripts/logfunctions.inc.pl";

##################
# Main script
##################
$chk = dbconnect();
$sql = "SELECT id, arp, tap FROM sensors WHERE status = 1 AND NOT tap = ''";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

while (@row = $sth->fetchrow_array) {
    $id = $row[0];
    $arp = $row[1];
    $tap = $row[2];

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
                    print "killing $pid from $tap\n";
                	`kill -9 $pid`;
                }
            } else {
                $pid = `ps -ef | grep -v grep | grep detectarp | grep $tap | wc -l`;
                chomp($pid);
                if ("$pid" eq "0") {
                    print "Starting detectarp $tap\n";
                    system("$c_surfidsdir/scripts/detectarp.pl $tap &");
                }    
            }
        }
    }
}
