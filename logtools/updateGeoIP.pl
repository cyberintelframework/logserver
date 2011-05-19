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

$csvfile = "http://geolite.maxmind.com/download/geoip/database/GeoIPCountryCSV.zip";
$datfile = "http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz";

##################
# Main script
##################
dbconnect();
%countries = ();
$i = 0;

##################
# UPDATE DAT FILE
##################
chdir("/tmp/");
`wget -q -O /tmp/GeoLiteCity.dat.gz $datfile`;
if ($? != 0) {
    print "Failed to fetch new GeoIP DAT file\n";
    exit;
}
`gunzip /tmp/GeoLiteCity.dat.gz`;
`mv /tmp/GeoLiteCity.dat $c_surfidsdir/include/`;

exit;

##################
# UPDATE CSV FILE
##################
`wget -q -O /tmp/GeoIPCountryCSV.zip $csvfile`;

#if ($? != 0) {
#    print "Failed to fetch new GeoIP CSV file\n";
#    exit;
#}

# Unzip
chdir("/tmp/");
`unzip GeoIPCountryCSV.zip`;

# Truncate geo tables
`sudo -u postgres psql -d $c_pgsql_dbname -c "TRUNCATE geolocations"`;
`sudo -u postgres psql -d $c_pgsql_dbname -c "TRUNCATE geonewblocks"`;

open(GEOIP, "< GeoIPCountryWhois.csv");
while (<GEOIP>) {
    $line = $_;
    chomp($line);
    @tempar = split(/\"/,$line);
    $ipstart = $tempar[1];
    $ipend = $tempar[3];
    $abbr = $tempar[9];
    $country = $tempar[11];

    # Check Country for geolocations
    if (exists $countries{$abbr}) {
        $cid = $countries{$abbr};
    } else {
        $i++;

        # Add new country
        $sql = "INSERT INTO geolocations (locid, country, abbr) VALUES (?, ?, ?)";
        $sth = $dbh->prepare($sql);
        $er = $sth->execute($i, $country, $abbr);

        $countries{$abbr} = $i;
        $cid = $i;
    }

    print "$ipstart - $ipend\n";
    # Get ip range
    $temp = `ipcalc $ipstart - $ipend | grep -v deaggregate`;
    @tempar = split(/\n/, $temp);
    foreach $range (@tempar) {
        chomp($range);
        print "\t$range\n";
        $sql = "INSERT INTO geonewblocks (locid, iprange) VALUES ($cid, '$range')";
        $sth = $dbh->prepare($sql);
        $er = $sth->execute();
    }
    

    # Check block
    #$sql = "INSERT INTO geoblocks (locid, ipstart, ipend) VALUES ($cid, '$ipstart', '$ipend')";
    #$sth = $dbh->prepare($sql);
    #$er = $sth->execute();
}
close(GEOIP);

# Cleanup
if (-e "/tmp/GeoIPCountryCSV.zip") {
#    `rm /tmp/GeoIPCountryCSV.zip`;
}
if (-e "/tmp/GeoLiteCity.dat.gz") {
#    `rm /tmp/GeoLiteCity.dat.gz`;
}
if (-e "/tmp/GeoIPCountryWhois.csv") {
#    `rm /tmp/GeoIPCountryWhois.csv`;
}
