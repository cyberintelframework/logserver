#!/usr/bin/perl

####################
# Modules used
####################
use DBI;
use Time::localtime qw(localtime);

####################
# Variables used
####################
do '/etc/surfnetids/surfnetids-log.conf';
our $source = 'log-janitor.pl';
our $sensor = 'unkown';
our $tap = 'unknown';
our $remoteip = '0.0.0.0';
our $pid = $$;
our $g_vlanid = 0;

##################
# Functions
##################
require "$c_surfidsdir/scripts/logfunctions.inc.pl";

####################

dbconnect();

$epoch = time();
#print "NOW: $epoch \n";
$epoch = $epoch - (24 * 7 * 3600);
#print "TIME: $epoch \n";

$sql_count = "SELECT COUNT(id) as count FROM syslog";
$sth = $dbh->prepare($sql_count);
$er = $sth->execute();
@row = $sth->fetchrow_array;
$count = $row[0];
#print "COUNT1: $count \n";

$sql = "DELETE FROM syslog WHERE timestamp < epoch_to_ts($epoch)";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

$sql_count = "SELECT COUNT(id) as count FROM syslog";
$sth = $dbh->prepare($sql_count);
$er = $sth->execute();
@row = $sth->fetchrow_array;
$count = $row[0];
#print "COUNT2: $count \n";
