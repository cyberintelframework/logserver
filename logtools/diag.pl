#!/usr/bin/perl

sub getAge {
	$file = $_[0];
	$posix = $_[1];
	$ts = (stat($file))[9];
	$diff = time() - $ts;
	$days = $diff / (3600 * 24);
	if ($posix == 1) {
	        $days = floor($days);
	}
}

##################
# Modules used
##################
use DBI;

##################
# Variables used
##################
do '/etc/surfnetids/surfnetids-log.conf';
require "$c_surfidsdir/scripts/logfunctions.inc.pl";

# PERL CHECKS
######################
# Location
$loc = `whereis perl | awk '{print \$2}'`;
chomp($loc);
print "[Perl] Binary location: \t\t $loc\n";

# Version
($major,$minor,$patch) = $] =~ /(\d+)\.(\d{3})(\d{3})/;
$minor = $minor + 0;
$patch = $patch + 0; 
print "[Perl] Version: \t\t\t $major.$minor.$patch\n";

# Modules
unless (eval "require Net::SMTP") {
	print "[Perl] Module Net::SMTP: \t\t Failed\n";
} else {
	print "[Perl] Module Net::SMTP: \t\t OK\n";
}

unless (eval "require MIME::Lite") {
	print "[Perl] Module MIME::Lite: \t\t Failed\n";
} else {
	print "[Perl] Module MIME::Lite: \t\t OK\n";
}

unless (eval "require GnuPG") {
	print "[Perl] Module GnuPG: \t\t\t Failed\n";
} else {
	print "[Perl] Module GnuPG: \t\t\t OK\n";
}

unless (eval "require POSIX") {
	print "[Perl] Module POSIX: \t\t\t Failed\n";
	$posix = 0;
} else {
	print "[Perl] Module POSIX: \t\t\t OK\n";
	use POSIX qw(floor);
	$posix = 1;
}

# DB CHECKS
######################
# Connection
eval {
	$dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass, {RaiseError => 1});
};
if ($@) {
	print "[Database] Connection: \t\t\t " .$DBI::errstr . "\n";
} else {
	print "[Database] Connection: \t\t\t OK\n";
}

# Version
$r = dbquery("SELECT * FROM version");
@row = $r->fetchrow_array;
print "[Database] Schema version: \t\t " .$row[0]. "\n";

# PHP
######################
# Location
$loc = `whereis php | awk '{print \$2}'`;
chomp($loc);
print "[PHP] Binary location: \t\t\t $loc\n";

# Version
if ($loc =~ /^\/.*php$/) {
	$ver = `$loc -v | head -n1`;
	chomp($ver);
	print "[PHP] Version: \t\t\t\t $ver\n";
}

# INFO
######################
# GeoIP
$file = $c_surfidsdir . "/include/GeoLiteCity.dat";
if (-e $file) {
	$age = getAge($file, $posix);
	print "[GeoIP] Age data file: \t\t\t $age days\n";
}
