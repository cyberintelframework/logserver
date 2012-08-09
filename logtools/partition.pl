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

$sql = "SELECT * FROM pg_tables WHERE tablename = 'temp_attacks'";
$sth = $dbh->prepare($sql);
$sth->execute();
$tempdetails = $sth->rows;

if ($tempdetails > 0) {
	$sql = "SELECT * FROM temp_details";
	$sth = $dbh->prepare($sql);
	$sth->execute();
	$countdetails = $sth->rows;
	if ($countdetails == 0) {
		$tempdetails = 0;
		print "Dropping table: temp_details\n";
		$sql = "DROP TABLE temp_details";
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
# DETAILS
if ($tempdetails == 0) {
	print "Creating temporary table: temp_details\n";
	$sql = "create table temp_details as select * from details";
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
	# DETAILS
	print "Truncating table: details\n";
	$sql = "truncate details";
	$sth = $dbh->prepare($sql);
	$sth->execute();
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
	# DETAILS
	$sql = "ALTER SEQUENCE details_id_seq RESTART WITH 1";
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

		# Copy detail records
		$sql = "SELECT type, text FROM temp_details WHERE attackid = ?";
		$detail_sth = $dbh->prepare($sql);
		$detail_sth->execute($row[0]);
		while (@detrow = $detail_sth->fetchrow_array) {
			$d_type = $detrow[0];
			$d_text = $detrow[1];
			print "Moving detail record\n";
			$sql = "INSERT INTO details (attackid, sensorid, type, text) VALUES (?, ?, ?, ?)";
			$insert_sth = $dbh->prepare($sql);
			$insert_sth->execute($aid, $sid, $d_type, $d_text);
		}
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

$sql = "DROP TABLE temp_details";
$sth = $dbh->prepare($sql);
$sth->execute();
$dbh->commit;

$sql = "DROP TABLE temp_attacks";
$sth = $dbh->prepare($sql);
$sth->execute();
$dbh->commit;
