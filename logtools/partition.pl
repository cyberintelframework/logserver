#!/usr/bin/perl

##################
# Modules used
##################
use DBI;
use Time::localtime qw(localtime);
use Data::Dumper;

##################
# Variables used
##################
do '/etc/surfnetids/surfnetids-log.conf';
require "$c_surfidsdir/scripts/logfunctions.inc.pl";

sub usage() {
    print "Usage: $0\n";
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

# Admin user info for the database (needs to have admin rights, create table, grant, etc)
$c_pgsql_pass = "";
$c_pgsql_user = "";

# Postgresql database info
$c_pgsql_host = "localhost";
$c_pgsql_dbname = "idsserver";

# The port number where the postgresql database is running on.
$c_pgsql_port = "5432";

# Connection string used by the perl scripts.
$c_dsn = "DBI:Pg:dbname=$c_pgsql_dbname;host=$c_pgsql_host;port=$c_pgsql_port";

##################
# Checking args
##################
##################
# Main script
##################

$chk = dbconnectnoauto();
if (!$dbh) {
	print "Could not connect to the database: $@\n";
	exit 1;
}

$sql = "SELECT * FROM pg_tables WHERE tablename = 'temp_attacks'";
$sth = $dbh->prepare($sql);
$sth->execute();
$tempattacks = $sth->rows;

if ($tempattacks > 0) {
	$sql = "SELECT * FROM temp_attacks";
	$sth = $dbh->prepare($sql);
	$sth->execute();
	$countattacks = $sth->rows;
	if ($countattacks == 0) {
		$tempattacks = 0;
		print "Dropping table: temp_attacks\n";
		$sql = "DROP TABLE temp_attacks";
		$sth = $dbh->prepare($sql);
		$sth->execute();
	}
}

##############################
# Create temporary tables
##############################

# ATTACKS
if ($tempattacks == 0) {
	print "Creating temporary table: temp_attacks\n";
	$sql = "create table temp_attacks as select * from attacks";
	$sth = $dbh->prepare($sql);
	$sth->execute();
}

################
# MAIN LOOP
################
eval {
	##############################
	# Truncate tables
	##############################
	# ATTACKS
	print "Truncating table: attacks\n";
	$sql = "truncate attacks cascade";
	$sth = $dbh->prepare($sql);
	$sth->execute();

	##############################
	# Reset sequence
	##############################
	# ATTACKS
	$sql = "ALTER SEQUENCE attacks_id_seq RESTART WITH 1";
	$sth = $dbh->prepare($sql);
	$sth->execute();

	##############################
	# Fill tables
	##############################

	$sql = "SELECT * FROM temp_attacks";
	$sth = $dbh->prepare($sql);
	$er = $sth->execute();
	while (@row = $sth->fetchrow_array) {
		print "Converting attack ". $row[0] . "\n"; 
		$ts = $row[1];
		$sev = $row[2];
		$source = $row[3];
		$sport = $row[4];
		$dest = $row[5];
		$dport = $row[6];
		$sid = $row[7];
		$src_mac = $row[8];
		$dst_mac = $row[9];
		$atype = $row[10];

		# Copy attack record
		$sql = "SELECT surfids3_attack_add_raw(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$attack_sth = $dbh->prepare($sql);
		$attack_sth->execute($ts, $sev, $source, $sport, $dest, $dport, $sid, $src_mac, $dst_mac, $atype);
		@aidrow = $attack_sth->fetchrow_array;
		$aid = $aidrow[0];
		print  "New attack ID: $aid\n";
		print "\n";
	}

	$sql = "UPDATE version SET version = 1 WHERE type = 'partition_conversion'";
	$sth = $dbh->prepare($sql);
	$sth->execute();
	$dbh->commit;
}

# Catch errors during main loop
if ($@) {
	warn "Error during main loop: $@\n";
	exit 1;
}

##############################
# Delete temporary tables
##############################

$sql = "DROP TABLE temp_attacks";
$sth = $dbh->prepare($sql);
$sth->execute();
$dbh->commit;
