#!/usr/bin/perl

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
    print "Usage: $0 <sensor_id>\n";
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
    $id = $ARGV[0];
} else {
    print "ERROR: No sensor ID given!\n";
    usage();
    exit 1;
}

##################
# Main script
##################

$chk = dbconnect();
$sql = "SELECT id, keyname FROM sensors WHERE id = ?";
$sth = $dbh->prepare($sql);
$er = $sth->execute($id);
if ($sth->rows == 0) {
    print "ERROR: Could not find a sensor record for the given sensor ID ($id)\n";
    exit 1;
}
@row = $sth->fetchrow();
$keyname = $row[1];
$rrdlabel = $keyname . "-";

print "Deleting sensor $id and all attached records\n";
while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Are you sure? [y/n]: ");
    chomp($confirm);
}
if ($confirm =~ /^(n|N)$/) {
    print "Exiting...\n";
    exit 0;
}

# Deleting RRD
$sql = "DELETE FROM rrd WHERE label LIKE '$rrdlabel%'";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting sensor_notes
$sql = "DELETE FROM sensor_notes WHERE keyname = $keyname";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting sensor_details
$sql = "DELETE FROM sensor_details WHERE keyname = $keyname";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting argos
$sql = "DELETE FROM argos WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting argos_ranges
$sql = "DELETE FROM argos_ranges WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting arp_cache
$sql = "DELETE FROM arp_cache WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting arp_static
$sql = "DELETE FROM arp_static WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting groupmembers
$sql = "DELETE FROM groupmembers WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting report_content
$sql = "DELETE FROM report_content WHERE sensor_id = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting sniff_protos
$sql = "DELETE FROM sniff_protos WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting attacks
$sql = "DELETE FROM attacks WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting details
$sql = "DELETE FROM details WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting deactivated_attacks
$sql = "DELETE FROM deactivated_attacks WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting deactivated_details
$sql = "DELETE FROM deactivated_details WHERE sensorid = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();

# Deleting sensors
$sql = "DELETE FROM sensors WHERE id = $id";
$sth = $dbh->prepare($sql);
$er = $sth->execute();
