#!/usr/bin/perl -w

##########################
# Variables
##########################

# Color codes
$n = "\033[0;39m";
$y = "\033[1;33m";
$r = "\033[1;31m";
$g = "\033[1;32m";

$targetdir = "/opt/surfnetids";
$configdir = "/etc/surfnetids";

##########################
# Includes
##########################

require "functions_log.pl";

##########################
# Dependency checks
##########################

$psqlcheck = `psql -V | head -n1 | awk '{print \$3}'`;
chomp($psqlcheck);
if ($psqlcheck !~ /^8\.1.*$/) {
  printmsg("Checking for PostgreSQL 8.1:", "false");
  exit;
}

##########################
# Main script
##########################

if (-e "$targetdir/webinterface/") {
  printmsg("SURFnet IDS logging server already installed:", "info");
  $confirm = "none";
  while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Overwrite old installation? [y/n]: ");
  }
  if ($confirm =~ /^(n|N)$/) {
    exit;
  }
}

if (! -e "$configdir/") {
  `mkdir -p $configdir/`;
  printmsg("Creating $configdir/:", $?);
}

if (! -e "$targetdir/") {
  `mkdir -p $targetdir/`;
  printmsg("Creating $targetdir/:", $?);
}

if ( -e "$configdir/surfnetids-log.conf") {
  $ts = time();
  `mv -f $configdir/surfnetids-log.conf $configdir/surfnetids-log.conf-$ts`;
  printmsg("Creating backup of surfnetids-log.conf:", $?);
}

`cp surfnetids-log.conf $configdir/`;
printmsg("Copying configuration file:", $?);

`cp -r ./* $targetdir/`;
printmsg("Copying surfnetids files:", $?);
`rm $targetdir/surfnetids-log.conf`;

####################
# Setting up crontab
####################

$crontab = `cat /etc/crontab | grep cronlog | wc -l`;
chomp($crontab);
if ($crontab == 0) {
  `cat $targetdir/crontab.log >> /etc/crontab`;
  printmsg("Adding crontab rules:", $?);
  `/etc/init.d/cron restart`;
  printmsg("Restarting cron:", $?);
}

####################
# Setting up Apache
####################

$apachev = "";
while ($apachev !~ /^(apache|apache2|apache-ssl)$/) {
  print "\n";
  $apachev = &prompt("Which apache are you using [apache/apache2/apache-ssl]?: ");
  if (! -e "/etc/$apachev/") {
    printmsg("Checking for $apachev:", "false");
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      printmsg("Apache server:", "$apachev");
      $confirm = &prompt("Is this correct? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $apachev = "none";
    }
  }
}

if ($apachev eq "apache2") {
  $apachedir = "/etc/$apachev/sites-enabled/";
} else {
  $apachedir = "/etc/$apachev/conf.d/";
}

while (! -d $apachedir) {
  $apachedir = &prompt("Location of the $apachev config dir: ");
  if (! -d $apachedir) {
    printmsg("Checking for $apachedir:", "false");
  }
}

if ( -e "$apachedir/surfnetids-log-apache.conf") {
  $ts = time();
  `mv -f $apachedir/surfnetids-log-apache.conf $apachedir/surfnetids-log-apache.conf-$ts`;
  printmsg("Creating backup of surfnetids-log-apache.conf:", $?);
}

`cp $targetdir/surfnetids-log-apache.conf $apachedir`;
printmsg("Setting up $apachev configuration:", $?);

`/etc/init.d/$apachev restart`;
printmsg("Restarting the $apachev server:", $?);

print "\n";

####################
# Setting up Postgresql
####################

$dbuser = "";
while ($dbuser eq "") {
  $dbuser = &prompt("Enter the connecting database user [postgres]: ");
  if ($dbuser eq "") {
    $dbuser = "postgres";
  }
}

$dbname = "";
while ($dbname eq "") {
  $dbname = &prompt("Enter the name of the database [idsserver]: ");
  if ($dbname eq "") {
    $dbname = "idsserver";
  }
}

$webuser = "";
while ($webuser eq "") {
  $webuser = &prompt("Enter the name of the web user [idslog]: ");
  if ($webuser eq "") {
    $webuser = "idslog";
  }
}

print "\n";

printmsg("Creating SURFnet IDS database:", "info");
$err = 1;
while ($err != 0) {
  `sudo -u postgres createdb -q -U $dbuser -W -O $dbuser $dbname`;
  printmsg("Creating SURFnet IDS database:", $?);
  $err = $?;
  if ($? != 0) {
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $err = 0;
    }
  }
}

print "\n";

printmsg("Creating webinterface database user:", "info");
$err = 1;
while ($err != 0) {
  `sudo -u postgres createuser -q -A -D -E -P -R -U $dbuser -W $webuser`;
  printmsg("Creating webinterface database user:", $?);
  $err = $?;
  if ($? != 0) {
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $err = 0;
    }
  }
}

print "\n";

printmsg("Creating nepenthes database user:", "info");
$err = 1;
while ($err != 0) {
  `sudo -u postgres createuser -q -A -D -E -P -R -U $dbuser -W nepenthes`;
  printmsg("Creating nepenthes database user:", $?);
  $err = $?;
  if ($? != 0) {
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $err = 0;
    }
  }
}

print "\n";

printmsg("Creating p0f database user:", "info");
$err = 1;
while ($err != 0) {
  `sudo -u postgres createuser -q -A -D -E -P -R -U $dbuser -W pofuser`;
  printmsg("Creating p0f database user:", $?);
  $err = $?;
  if ($? != 0) {
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $err = 0;
    }
  }
}

print "\n";

printmsg("Creating SURFnet IDS tables:", "info");
$err = 1;
while ($err != 0) {
  `sudo -u postgres psql -q -f $targetdir/postgres_settings.sql -U $dbuser -W $dbname 2>/dev/null`;
  printmsg("Creating SURFnet IDS tables:", $?);
  $err = $?;
  if ($? != 0) {
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $err = 0;
    }
  }
}

print "\n";

# Setting server hostname and configuring config files.
$server = "";
while ($server eq "") {
  $server = &prompt("Server hostname.domainname or IP (example: test.domain.nl): ");
  if ($server ne "") {
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      printmsg("Server hostname/IP address:", "$server");
      $confirm = &prompt("Is this correct? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $server = "";
    }
  }
}

open(SQL, ">>$targetdir/postgres_insert.sql");
print SQL "INSERT INTO servers (server) VALUES ('$server')";
close(SQL);

print "\n";

$err = 1;
while ($err != 0) {
  `sudo -u postgres psql -q -f $targetdir/postgres_insert.sql -U $dbuser -W $dbname 2>/dev/null`;
  printmsg("Adding necessary records to the database:", $?);
  $err = $?;
  if ($? != 0) {
    $confirm = "a";
    while ($confirm !~ /^(n|N|y|Y)$/) {
      $confirm = &prompt("Insert query failed. Try again? [y/n]: ");
    }
    if ($confirm =~ /^(n|N)$/) {
      $err = 0;
    }
  }
}

print "\n";
print "You are currently installing the SURFnet IDS logging package.\n";
print "This package can be used for a single sensor setup or a multi\n";
print "sensor setup. If you're not planning on using the SURFnet IDS\n";
print "tunnel package, choose single sensor setup.\n";
print "\n";

$setup = "none";
while ($setup !~ /^(single|multi)$/) {
  $setup = &prompt("Single or multi sensor setup? [single/multi]: ");
}
printmsg("SURFnet IDS setup:", $setup);

if ($setup eq "single") {
  $validip = 1;
  while ($validip != 0) {
    $sensorip = &prompt("Enter the IP address where Nepenthes is listening on: ");
    $validip = validip($sensorip);
  }

  $ts = time();

  open(SQL, ">>$targetdir/singlesensor.sql");
  print SQL "INSERT INTO sensors (keyname, remoteip, localip, tap, tapip, laststart, status, organisation) VALUES ('sensor', '$sensorip', '$sensorip', 'if', '$sensorip', $ts, 1, 2)";
  close(SQL);

  $err = 1;
  while ($err != 0) {
    `sudo -u postgres psql -q -f $targetdir/singlesensor.sql -U $dbuser -W $dbname 2>/dev/null`;
    printmsg("Adding necessary records to the database:", $?);
    $err = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("Insert query failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $err = 0;
      }
    }
  }
}

$ec = 0;
`rm -f $targetdir/crontab.log`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/surfnetids-log-apache.conf`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/postgres_insert.sql`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/postgres_settings.sql`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/singlesensor.sql`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/install_log.pl`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/functions_log.pl`;
if ($? != 0) { $ec++; }
printmsg("Cleaning up the temporary files:", $ec);
$ec = 0;

print "#####################################\n";
print "# ${g}SURFnet IDS installation complete${n} #\n";
print "#####################################\n";
print "\n";
print "Interesting configuration files:\n";
print "  ${g}/etc/crontab${n}\n";

print "Still needs configuration:\n";
print "  ${g}$configdir/surfnetids-log.conf${n}\n";
print "\n";
print "For more information go to http://ids.surfnet.nl/\n";
