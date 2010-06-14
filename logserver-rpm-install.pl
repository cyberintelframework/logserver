#!/usr/bin/perl

use DBI;

#############################
# Configuration info
#############################

$dbname = "idsserver";
$dbhost = "localhost";
$dbport = "5432";

# This is the info needed to login to the database 
# with admin privileges. Password will be prompted.
$adminuser = "postgres";

# Usernames and passwords
$webuser = "idslog";
$webuserpass = "";
$nepenthespass = "";
$pofuserpass = "";
$argospass = "";

# GeoIP database
# True = Download the latest GeoIP database
# False = Don't dowload the GeoIP database
$install_geoip = "true";

#############################
# DO NOT EDIT BELOW
#############################
$targetdir = "/opt/surfnetids";
$geoiploc = "http://www.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz";
@version_ar = (10300, 10400, 20000, 20002, 20003, 30000);

if ("$webuser" eq "") {
    print "Web user not configured!\n";
    exit 1;
}
if ("$webuserpass" eq "") {
    print "Web user password not configured!\n";
    exit 1;
}
if ("$nepenthespass" eq "") {
    print "Nepenthes user password not configured!\n";
    exit 1;
}
if ("$pofuserpass" eq "") {
    print "P0f user password not configured!\n";
    exit 1;
}
if ("$argospass" eq "") {
    print "Argos user password not configured!\n";
    exit 1;
}

if ("$webuser" ne "idslog") {
  @arsql = `ls -l $targetdir/sql/ | grep sql | grep -v "nepenthes.sql" | awk '{print \$NF}'`;
  foreach $sqlfile (@arsql) {
    chomp($sqlfile);
    `sed 's/idslog;/\"$webuser\";/' $targetdir/sql/$sqlfile > $targetdir/sql/$sqlfile.new`;
    `mv $targetdir/sql/$sqlfile.new $targetdir/sql/$sqlfile`;
  }
  `sed 's/idslog/$webuser/' $targetdir/webinterface/.htaccess > $targetdir/htaccess.dist`;
  `cp $targetdir/htaccess.dist $targetdir/webinterface/.htaccess`;
}

if ("$dbname" ne "idsserver") {
  `sed 's/idsserver/$dbname/' $targetdir/webinterface/.htaccess > $targetdir/htaccess.dist`;
  `cp $targetdir/htaccess.dist $targetdir/webinterface/.htaccess`;
}

if ("$dbhost" ne "localhost") {
  `sed 's/localhost/$dbhost/' $targetdir/webinterface/.htaccess > $targetdir/htaccess.dist`;
  `cp $targetdir/htaccess.dist $targetdir/webinterface/.htaccess`;
}

if ("$dbport" ne "5432") {
  `sed 's/5432/$dbport/' $targetdir/webinterface/.htaccess > $targetdir/htaccess.dist`;
  `cp $targetdir/htaccess.dist $targetdir/webinterface/.htaccess`;
}

# Creating all the database stuff
print "Creating database user: $webuser!\n";
`createuser -h $dbhost -p $dbport -S -D -E -R -U "$adminuser" "$webuser"`;
print "Creating database user: nepenthes!\n";
`createuser -h $dbhost -p $dbport -S -D -E -R -U "$adminuser" nepenthes`;
print "Creating database user: pofuser!\n";
`createuser -h $dbhost -p $dbport -S -D -E -R -U "$adminuser" pofuser`;
print "Creating database user: argos!\n";
`createuser -h $dbhost -p $dbport -S -D -E -R -U "$adminuser" argos`;

# Setting up passwords for created users
open(PASS, "> $targetdir/.pass.sql");
print PASS "ALTER ROLE $webuser ENCRYPTED PASSWORD '$webuserpass';\n";
print PASS "ALTER ROLE nepenthes ENCRYPTED PASSWORD '$nepenthespass';\n";
print PASS "ALTER ROLE pofuser ENCRYPTED PASSWORD '$pofuserpass';\n";
print PASS "ALTER ROLE argos ENCRYPTED PASSWORD '$argospass';\n";
close(PASS);

`psql -h $dbhost -p $dbport -f $targetdir/.pass.sql -U $adminuser postgres`;
`rm -f $targetdir/.pass.sql`;

# Checking main database
$chk = `psql -U $adminuser -h $dbhost -p $dbport --list | grep $dbname | wc -l 2>/dev/null`;
chomp($chk);

if ($chk == 0) {
    print "Creating database!\n";
    `createdb -h $dbhost -p $dbport -U "$adminuser" -O "$adminuser" "$dbname"`;

    # Creating main database
    print "Setting up database!\n";
    `psql -h $dbhost -p $dbport -q -f $targetdir/sql/all.sql -U "$adminuser" "$dbname"`;
} else {
    print "Database already exists, skipping creation!\n";
    print "Checking current database version!\n";

    if ($dbh) {
        $dbh->disconnect;
    }
    # Testing our database connection
    $c_pgsql_pass = $adminuserpass;
    $c_pgsql_user = $adminuser;
    $c_pgsql_host = $dbhost;
    $c_pgsql_dbname = "idsserver";
    $c_pgsql_port = $dbport;
    $c_dsn = "DBI:Pg:dbname=$c_pgsql_dbname;host=$c_pgsql_host;port=$c_pgsql_port";
    $dbh = DBI->connect($c_dsn, $c_pgsql_user, $c_pgsql_pass, { PrintError => 0});
    $err = $DBI::errstr ? $DBI::errstr : "";

    $sql = "SELECT version FROM version";
    $q = $dbh->prepare($sql);
    $ec = $q->execute();
    $curver = 0;
    if ($q->rows > 0) {
        @row = $q->fetchrow_array;
        $curver = $row[0];
    } else {
	# Version check 10300
        $sql = "SELECT data_type FROM information_schema.columns ";
        $sql .= " WHERE table_name = 'binaries' AND column_name = 'bin'";
        $q = $dbh->prepare($sql);
        $ec = $q->execute();
        $num = $q->rows;
        if ($num == 1) {
            @row = $q->fetchrow_array;
            $datatype = $row[0];
            if ($datatype eq "character varying") {
                $curver = 10300;
            }
	}

	if ($curver == 0) {
            # Version check 20004
            $sql = "SELECT table_name FROM information_schema.tables ";
            $sql .= " WHERE table_name = 'serverinfo' ";
            $q = $dbh->prepare($sql);
            $ec = $q->execute();
            $num = $q->rows;
            if ($num == 1) {
                $curver = 20004;
            }
	}

	if ($curver == 0) {
            # Version check 20002
            $sql = "SELECT table_name FROM information_schema.tables ";
            $sql .= " WHERE table_name = 'ostypes' ";
            $q = $dbh->prepare($sql);
            $ec = $q->execute();
            $num = $q->rows;
            if ($num == 1) {
                $curver = 20002;
            }
	}

	if ($curver == 0) {
            # Version check 20000
            $sql = "SELECT table_name FROM information_schema.tables ";
            $sql .= " WHERE table_name = 'argos' ";
            $q = $dbh->prepare($sql);
            $ec = $q->execute();
            $num = $q->rows;
            if ($num == 1) {
                $curver = 20000;
            }
	}

	if ($curver == 0) {
            # Version check 10400
            $sql = "SELECT table_name FROM information_schema.tables ";
            $sql .= " WHERE table_name = 'report_template_threshold' ";
            $q = $dbh->prepare($sql);
            $ec = $q->execute();
            $num = $q->rows;
            if ($num == 1) {
                $curver = 10400;
            }
	}
    }
    print "Detected current database version: $curver\n";

    foreach $v (@version_ar) {
        if ($v > $curver) {
            print "Going to apply SQL changes for $v\n";
            `psql -h $dbhost -p $dbport -q -f $targetdir/sql/$v.sql -U "$adminuser" "$dbname"`;
        }
    }
}


# Downloading GeoIP database if needed
if ("$install_geoip" eq "true") {
    print "Downloading new GeoIP database!\n";
    `wget -q -O "$targetdir/GeoLiteCity.dat.gz" $geoiploc`;
    `gunzip $targetdir/GeoLiteCity.dat.gz`;
    `mv $targetdir/GeoLiteCity.dat $targetdir/include/`;
    print "Finished installing new GeoIP database!\n";
}

