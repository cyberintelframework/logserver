#!/usr/bin/perl

####################################
# Move sensor script               #
# SURFids 3.00                     #
# Changeset 001                    #
# 13-11-2009                       #
# Kees Trippelvitz                 #
####################################

# This script moves all records related to a sensor ID to a new sensor ID

#####################
# Changelog:
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

sub usage() {
    print "Usage: ./move_sensor.pl <old_sensor_id> <new_sensor_id>\n";
}

sub prompt() {
    my ($prompt);
    $prompt = $_[0];
    chomp($prompt);
    print $prompt;
    $| = 1;       # force a flush after our print
    $_ = <STDIN>; # get the input from STDIN
    chomp;
    return "$_";
}

##################
# Checking args
##################
if ($ARGV[0]) {
    $old_id = $ARGV[0];
} else {
    print "ERROR: No old sensor ID given!\n";
    usage();
    exit 1;
}
if ($ARGV[1]) {
    $new_id = $ARGV[1];
} else {
    print "ERROR: No new sensor ID given!\n";
    usage();
    exit 1;
}

##################
# Main script
##################

print "Moving all records from sensorID $old_id to sensorID $new_id.\n";
while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Are you sure? [y/n]: ");
    chomp($confirm);
}
if ($confirm =~ /^(n|N)$/) {
    print "Exiting...\n";
    exit 0;
}

$chk = dbconnect();
$sql = "SELECT id FROM sensors WHERE id = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();
if ($sth->rows == 0) {
    print "ERROR: Could not find a sensors record for old_sensor_id ($old_id)\n";
    exit 1;
}

$sql = "SELECT id FROM sensors WHERE id = $new_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();
if ($sth->rows == 0) {
    print "ERROR: Could not find a sensors record for new_sensor_id ($new_id)\n";
    exit 1;
}

# Updating argos
$sql = "UPDATE argos SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating argos_ranges
$sql = "UPDATE argos_ranges SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating arp_cache
$sql = "UPDATE arp_cache SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating arp_static
$sql = "UPDATE arp_static SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating groupmembers
$sql = "UPDATE groupmembers SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating report_content
$sql = "UPDATE report_content SET sensor_id = $new_id WHERE sensor_id = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating sniff_protos
$sql = "UPDATE sniff_protos SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating attacks
$sql = "UPDATE attacks SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating deactivated_attacks
$sql = "UPDATE deactivated_attacks SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Updating deactivated_details
$sql = "UPDATE deactivated_details SET sensorid = $new_id WHERE sensorid = $old_id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

