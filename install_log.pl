#!/usr/bin/perl

####################################
# Installation script              #
# SURFnet IDS                      #
# Version 1.04.01                  #
# 01-02-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

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
$logfile = "install_log.pl.log";

$geoiploc = "http://www.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz";

$err = 0;

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
  `mkdir -p $configdir/ 2>$logfile`;
  printmsg("Creating $configdir/:", $?);
  if ($? != 0) { $err++; }
}

if (! -e "$targetdir/") {
  `mkdir -p $targetdir/ 2>$logfile`;
  printmsg("Creating $targetdir/:", $?);
  if ($? != 0) { $err++; }
}

if ( -e "$configdir/surfnetids-log.conf") {
  $ts = time();
  `mv -f $configdir/surfnetids-log.conf $configdir/surfnetids-log.conf-$ts 2>$logfile`;
  printmsg("Creating backup of surfnetids-log.conf:", $?);
  if ($? != 0) { $err++; }
}

`cp surfnetids-log.conf $configdir/ 2>$logfile`;
printmsg("Copying configuration file:", $?);
if ($? != 0) { $err++; }

`cp -r ./* $targetdir/ 2>$logfile`;
printmsg("Copying surfnetids files:", $?);
if ($? != 0) { $err++; }
`rm $targetdir/surfnetids-log.conf 2>$logfile`;

####################
# Setting up crontab
####################

open(CRONTAB, ">> /etc/crontab");
open(CRONLOG, "crontab.log");
while (<CRONLOG>) {
  $line = $_;
  chomp($line);
  if ($line ne "") {
    @ar_line = split(/ /, $line);
    $check = $ar_line[6];
    chomp($check);
    $file = `cat crontab.log | grep -F "$line" | awk '{print \$7}' | awk -F"/" '{print \$NF}'`;
    chomp($file);
    $chk = checkcron($file);
    if ($chk == 0) {
      printmsg("Adding crontab rule for $file:", "info");
      print CRONTAB $line ."\n";
    }
  }
}
close(CRONTAB);
close(CRONLOG);

printdelay("Restarting cron:");
`/etc/init.d/cron restart 2>$logfile`;
printresult($?);

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
  `mv -f $apachedir/surfnetids-log-apache.conf $targetdir/surfnetids-log-apache.conf-$ts 2>$logfile`;
  printmsg("Creating backup of surfnetids-log-apache.conf:", $?);
  if ($? != 0) { $err++; }
}

`cp $targetdir/surfnetids-log-apache.conf $apachedir 2>$logfile`;
printmsg("Setting up $apachev configuration:", $?);
if ($? != 0) { $err++; }

printdelay("Restarting the $apachev server:");
`/etc/init.d/$apachev restart 2>$logfile`;
printresult($?);
if ($? != 0) { $err++; }

print "\n";

####################
# Setting up Postgresql
####################

$confirm = "a";
while ($confirm !~ /^(n|N|y|Y)$/) {
  $confirm = &prompt("Is the database already installed? [y/n]: ");
}

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

if ($confirm =~ /^(n|N)$/) {
  printmsg("Creating SURFnet IDS database:", "info");
  $e = 1;
  while ($e != 0) {
    `sudo -u postgres createdb -q -U $dbuser -W -O $dbuser $dbname 2>$logfile`;
    printmsg("Creating SURFnet IDS database:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  printmsg("Creating webinterface database user:", "info");
  $e = 1;
  while ($e != 0) {
    `sudo -u postgres createuser -q -A -D -E -P -R -U $dbuser -W $webuser 2>$logfile`;
    printmsg("Creating webinterface database user:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  printmsg("Creating nepenthes database user:", "info");
  $e = 1;
  while ($e != 0) {
    `sudo -u postgres createuser -q -A -D -E -P -R -U $dbuser -W nepenthes 2>$logfile`;
    printmsg("Creating nepenthes database user:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  printmsg("Creating p0f database user:", "info");
  $e = 1;
  while ($e != 0) {
    `sudo -u postgres createuser -q -A -D -E -P -R -U $dbuser -W pofuser 2>$logfile`;
    printmsg("Creating p0f database user:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  printmsg("Creating SURFnet IDS tables:", "info");
  $e = 1;
  while ($e != 0) {
    `sudo -u postgres psql -q -f $targetdir/sql/postgres_settings.sql -U $dbuser -W $dbname 2>$logfile`;
    printmsg("Creating SURFnet IDS tables:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("Database creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
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

  open(SQL, ">>$targetdir/sql/postgres_insert.sql");
  print SQL "INSERT INTO servers (server) VALUES ('$server')";
  close(SQL);

  print "\n";

  $e = 1;
  while ($e != 0) {
    `sudo -u postgres psql -q -f $targetdir/sql/postgres_insert.sql -U $dbuser -W $dbname 2>$logfile`;
    printmsg("Adding necessary records to the database:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("Insert query failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }
} elsif ($confirm =~ /^(Y|y)$/) {
  $confirm = "a";
  while ($confirm !~ /^(1\.02|1\.03|skip)$/) {
    $confirm = &prompt("Upgrade database from which version [1.02/1.03/skip]?: ");
  }

  if ($confirm =~ /^(1\.02|1\.03)$/) {
    if ($confirm eq "1.02") {
      $e = 1;
      while ($e != 0) {
        `sudo -u postgres psql -q -f $targetdir/sql/changes102-103.sql -U $dbuser -W $dbname 2>$logfile`;
        printmsg("Upgrading the database from 1.02 to 1.03:", $?);
        if ($? != 0) { $err++; }
        $e = $?;
        if ($? != 0) {
          $confirm = "a";
          while ($confirm !~ /^(n|N|y|Y)$/) {
            $confirm = &prompt("Upgrade failed. Try again? [y/n]: ");
          }
          if ($confirm =~ /^(n|N)$/) {
            $e = 0;
          }
        }
      }
      $e = 1;
      while ($e != 0) {
        `sudo -u postgres psql -q -f $targetdir/sql/changes103-104.sql -U $dbuser -W $dbname 2>$logfile`;
        printmsg("Upgrading the database from 1.03 to 1.04:", $?);
        if ($? != 0) { $err++; }
        $e = $?;
        if ($? != 0) {
          $confirm = "a";
          while ($confirm !~ /^(n|N|y|Y)$/) {
            $confirm = &prompt("Upgrade failed. Try again? [y/n]: ");
          }
          if ($confirm =~ /^(n|N)$/) {
            $e = 0;
          }
        }
      }
    } elsif ($confirm eq "1.03") {
      $e = 1;
      while ($e != 0) {
        `sudo -u postgres psql -q -f $targetdir/sql/changes103-104.sql -U $dbuser -W $dbname 2>$logfile`;
        printmsg("Upgrading the database from 1.03 to 1.04:", $?);
        if ($? != 0) { $err++; }
        $e = $?;
        if ($? != 0) {
          $confirm = "a";
          while ($confirm !~ /^(n|N|y|Y)$/) {
            $confirm = &prompt("Upgrade failed. Try again? [y/n]: ");
          }
          if ($confirm =~ /^(n|N)$/) {
            $e = 0;
          }
        }
      }
    }
  } else {
    printmsg("Skipping database installation/upgrade:", "info");
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
  $confirm = "a";
  while ($confirm !~ /^(y|Y|n|N)$/) {
    $confirm = &prompt("Do you want to add your main interface as a sensor? [y/n]: ");
  }

  if ($confirm =~ /^(y|Y|n|N)$/) {
    $validip = 1;
    while ($validip != 0) {
      $sensorip = &prompt("Enter the IP address where Nepenthes is listening on: ");
      $validip = validip($sensorip);
    }

    $if = getif($sensorip);
    if ($if eq "false") {
      $if = "IF";
    }
    $mac = getmac($sensorip);
    if ($mac eq "false") {
      $mac = "00:00:00:00:00:00";
    }

    $ts = time();

    open(SQL, ">>$targetdir/sql/singlesensor.sql");
    print SQL "INSERT INTO sensors (keyname, remoteip, localip, tap, mac, tapip, laststart, status, organisation) VALUES ('sensor', '$sensorip', '$sensorip', '$if', '$mac', '$sensorip', $ts, 1, (SELECT id FROM organisations WHERE organisation = 'NEPENTHES'))";
    close(SQL);

    $e = 1;
    while ($e != 0) {
      `sudo -u postgres psql -q -f $targetdir/sql/singlesensor.sql -U $dbuser -W $dbname 2>$logfile`;
      printmsg("Adding necessary records to the database:", $?);
      if ($? != 0) { $err++; }
      $e = $?;
      if ($? != 0) {
        $confirm = "a";
        while ($confirm !~ /^(n|N|y|Y)$/) {
          $confirm = &prompt("Insert query failed. Try again? [y/n]: ");
        }
        if ($confirm =~ /^(n|N)$/) {
          $e = 0;
        }
      }
    }
  }
}

`wget -V >/dev/null 2>/dev/null`;
if ($? == 0) {
  print "\n"; 
  $confirm = "a";
  while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Do you want to download the latest GeoIP database? [y/n]: ");
  }
  if ($confirm =~ /^(Y|y)$/) {
    printmsg("Downloading GeoIP database:", "info");
    `wget $geoiploc`;
    if ($? != 0) { $err++; }
    print "\n";

    printdelay("Unzipping GeoIP database:");
    `gunzip GeoLiteCity.dat.gz 2>$logfile`;
    if ($? != 0) { $err++; }
    printresult($?);

    printdelay("Installing GeoIP database:");
    `mv GeoLiteCity.dat $targetdir/include/ 2>$logfile`;
    if ($? != 0) { $err++; }
    printresult($?);
  }
} else {
  printmsg("Skipping GeoIP database download:", "info");
}

$ec = 0;
`rm -f $targetdir/crontab.log 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/surfnetids-log-apache.conf 2>/dev/null`;
if ($? != 0) { $ec++; }
#`rm -f $targetdir/postgres_insert.sql 2>/dev/null`;
#if ($? != 0) { $ec++; }
#`rm -f $targetdir/postgres_settings.sql 2>/dev/null`;
#if ($? != 0) { $ec++; }
#`rm -f $targetdir/singlesensor.sql 2>/dev/null`;
#if ($? != 0) { $ec++; }
`rm -f $targetdir/install_log.pl 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/functions_log.pl 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/install_log.pl.log 2>/dev/null`;
if ($? != 0) { $ec++; }
printmsg("Cleaning up the temporary files:", $ec);
$ec = 0;

print "\n";
if ($err > 0) {
  print "[${r}Warning${n}] $err error(s) occurred while installing. Check out the logfile 'install_tn.pl.log'.\n";
  print "\n";
}

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
