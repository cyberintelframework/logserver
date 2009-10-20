#!/usr/bin/perl

#####################################
# SURFids 3.02                      #
# Changeset 001                     #
# 20-10-2009                        #
# Kees Trippelvitz                  #
#####################################

#####################
# Changelog:
# 001 Initial version
#####################

##################
# Variables used
##################
do '/etc/surfnetids/surfnetids-log.conf';
$geoiploc = "http://www.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz";
$quiet = 0;

##################
# Main script
##################

# Downloading
if ($quiet == 1) {
    print "Downloading new GeoIP database...";
    `wget -q -O "/tmp/GeoLiteCity.dat.gz" $geoiploc`;
} else {
    print "Downloading new GeoIP database...\n";
    `wget -O "/tmp/GeoLiteCity.dat.gz" $geoiploc`;
}
if ($? == 0) {
    print "OK\n";

    # Unpacking
    if ($quiet == 1) {
        print "Unpacking new GeoIP database...";
        `gunzip /tmp/GeoLiteCity.dat.gz`;
    } else {
        print "Unpacking new GeoIP database...\n";
        `gunzip -v /tmp/GeoLiteCity.dat.gz`;
    }
    if ($? == 0) {
        print "OK\n";
    } else {
        print "Failed\n";
    }

    # Copying
    print "Installing new GeoIP database...";
    `mv /tmp/GeoLiteCity.dat $c_surfidsdir/include/`;
    if ($? == 0) {
        print "OK\n";
    } else {
        print "Failed\n";
    }
} else {
    print "Failed\n";
}

