#!/usr/bin/perl

####################################
# Installation script              #
# SURFids 2.10                     #
# Changeset 001                    #
# 26-02-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

###############################################
# Changelog:
# 001 Initial release
###############################################

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
$installdir = $0;
$installdir =~ s/install_log.pl//g;
$logfile = "${installdir}install_log.pl.log";

$geoiploc = "http://www.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz";

$err = 0;

##########################
# Includes
##########################

require "${installdir}functions_log.pl";

##########################
# Dependency checks
##########################

$psqlcheck = `psql -V | head -n1 | awk '{print \$3}' 2>&1 2>/dev/null`;
chomp($psqlcheck);
if ($psqlcheck !~ /^8\..*$/) {
  printmsg("Checking for PostgreSQL:", "false");
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

if (-e "$logfile") {
  `rm -f $logfile 2>/dev/null`;
}

if (! -e "$configdir/") {
  `mkdir -p $configdir/ 2>>$logfile`;
  printmsg("Creating $configdir/:", $?);
  if ($? != 0) { $err++; }
}

if (! -e "$targetdir/") {
  `mkdir -p $targetdir/ 2>>$logfile`;
  printmsg("Creating $targetdir/:", $?);
  if ($? != 0) { $err++; }
}

if (-e "$configdir/surfnetids-log.conf") {
  $ts = time();

  `mv -f $configdir/surfnetids-log.conf $configdir/surfnetids-log.conf-$ts 2>>$logfile`;
  printmsg("Creating backup of surfnetids-log.conf:", $?);
  if ($? != 0) { $err++; }
}

`cp -r ./* $targetdir/ 2>>$logfile`;
printmsg("Copying surfnetids files:", $?);
if ($? != 0) { $err++; }

####################
# Setting up crontab
####################

open(CRONTAB, ">> /etc/crontab");
open(CRONLOG, "${installdir}crontab.log");
while (<CRONLOG>) {
  $line = $_;
  chomp($line);
  if ($line ne "") {
    @ar_line = split(/ /, $line);
    $check = $ar_line[6];
    chomp($check);
    $file = `cat ${installdir}crontab.log | grep -F "$line" | awk '{print \$7}' | awk -F"/" '{print \$NF}'`;
    chomp($file);
    if ("$file" ne "") {
      $chk = checkcron($file);
      if ($chk == 0) {
        printmsg("Adding crontab rule for $file:", "info");
        print CRONTAB $line ."\n";
      }
    }
  }
}
close(CRONTAB);
close(CRONLOG);

printdelay("Restarting cron:");
`/etc/init.d/cron restart 2>>$logfile`;
printresult($?);

####################
# Setting up Apache
####################

#$apachev = "";
#while ($apachev !~ /^(pache|apache2|apache-ssl)$/) {
#  print "\n";
# $apachev = &prompt("Which apache are you using [apache/apache2/apache-ssl]?: ");
#  if (! -e "/etc/$apachev/") {
#    printmsg("Checking for $apachev:", "false");
#    $confirm = "a";
#    while ($confirm !~ /^(n|N|y|Y)$/) {
#      printmsg("Apache server:", "$apachev");
#      $confirm = &prompt("Is this correct? [y/n]: ");
#    }
#    if ($confirm =~ /^(n|N)$/) {
#      $apachev = "none";
#    }
#  }
#}

$apachev = "apache2";
$apachedir = "/etc/$apachev/sites-enabled";
$apachesiteadir = "/etc/$apachev/sites-available/";

while (! -d $apachedir) {
  $apachedir = &prompt("Location of the $apachev config dir: ");
  if (! -d $apachedir) {
    printmsg("Checking for $apachedir:", "false");
  }
}

if (-e "$apachesiteadir/surfnetids-log-apache.conf") {
  $ts = time();
  `mv -f $apachesiteadir/surfnetids-log-apache.conf $targetdir/surfnetids-log-apache.conf-$ts 2>>$logfile`;
  printmsg("Creating backup of surfnetids-log-apache.conf:", $?);
  if ($? != 0) { $err++; }
}

`cp $installdir/surfnetids-log-apache.conf $apachesiteadir 2>>$logfile`;
printmsg("Setting up $apachev configuration:", $?);
if ($? != 0) { $err++; }

printdelay("Enabling SURFids site for $apachev:");
`a2ensite surfnetids-log-apache.conf 2>>$logfile`;
printresult($?);
if ($? != 0) { $err++; }

printdelay("Enabling Auth_PGSQL for $apachev:");
`a2enmod 000_auth_pgsql 2>>$logfile`;
printresult($?);
if ($? != 0) { $err++; }

printdelay("Restarting the $apachev server:");
`/etc/init.d/$apachev restart 2>>$logfile`;
printresult($?);
if ($? != 0) { $err++; }

print "\n";

####################
# Setting up Postgresql
####################

$confirm = "a";
while ($confirm !~ /^(install|upgrade)$/) {
  $confirm = &prompt("Do you want to install or upgrade the database? [install/upgrade]: ");
}

$dbuser = "";
while ($dbuser eq "") {
  $dbuser = &prompt("Enter the connecting database user [postgres]: ");
  if ($dbuser eq "") {
    $dbuser = "postgres";
  }
}


$dbhost = "";
while ($dbhost eq "") {
  $dbhost = &prompt("Enter the IP address of the database host [localhost]: ");
  if ($dbhost eq "") {
    $dbhost = "localhost";
  }
}

$dbport = "";
while ($dbport eq "") {
  $dbport = &prompt("Enter the connection port of the database host [5432]: ");
  if ($dbport eq "") {
    $dbport = "5432";
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
  chomp($webuser);
  if ($webuser eq "") {
    $webuser = "idslog";
  }
}

`cp $targetdir/htaccess.dist $targetdir/webinterface/.htaccess 2>>$logfile`;

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

print "\n";

if ($confirm =~ /^(install)$/) {
  $e = 1;
  while ($e != 0) {
    if ($dbhost != "localhost") {
      printmsg("Creating SURFnet IDS database [$dbname]:", "info");
      `createdb -h $dbhost -p $dbport -q -U "$dbuser" -W -O "$dbuser" "$dbname" 2>>$logfile`;
    } else {
      `sudo -u postgres createdb -q -O "$dbuser" "$dbname" 2>>$logfile`;
    }
    printmsg("Creating SURFnet IDS database [$dbname]:", $?);
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

  printmsg("Creating webinterface database user [$webuser]:", "info");
  $e = 1;
  while ($e != 0) {
    if ($dbhost != "localhost") {
      `createuser -h $dbhost -p $dbport -q -A -D -E -P -R -U "$dbuser" -W "$webuser" 2>>$logfile`;
    } else {
      `sudo -u postgres createuser -q -A -D -E -P -R "$webuser" 2>>$logfile`;
    }
    printmsg("Creating webinterface database user [$webuser]:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("User creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  printmsg("Creating nepenthes database user [nepenthes]:", "info");
  $e = 1;
  while ($e != 0) {
    if ($dbhost != "localhost") {
      `createuser -h $dbhost -p $dbport -q -A -D -E -P -R -U "$dbuser" -W nepenthes 2>>$logfile`;
    } else {
      `sudo -u postgres createuser -q -A -D -E -P -R nepenthes 2>>$logfile`;
    }
    printmsg("Creating nepenthes database user [nepenthes]:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("User creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  printmsg("Creating p0f database user [pofuser]:", "info");
  $e = 1;
  while ($e != 0) {
    if ($dbhost != "localhost") {
      `createuser -h $dbhost -p $dbport -q -A -D -E -P -R -U "$dbuser" -W pofuser 2>>$logfile`;
    } else {
      `sudo -u postgres createuser -q -A -D -E -P -R pofuser 2>>$logfile`;
    }
    printmsg("Creating p0f database user [pofuser]:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("User creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  printmsg("Creating argos database user [argos]:", "info");
  $e = 1;
  while ($e != 0) {
    if ($dbhost != "localhost") {
      `createuser -h $dbhost -p $dbport -q -A -D -E -P -R -U "$dbuser" -W argos 2>>$logfile`;
    } else {
      `sudo -u postgres createuser -q -A -D -E -P -R argos 2>>$logfile`;
    }
    printmsg("Creating argos database user [argos]:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("User creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  $e = 1;
  while ($e != 0) {
    if ($dbhost != "localhost") {
      printmsg("Creating SURFnet IDS tables:", "info");
      `psql -h $dbhost -p $dbport -q -f $targetdir/sql/postgres_settings.sql -U "$dbuser" -W "$dbname" 2>>$logfile`;
    } else {
      `sudo -u postgres psql -q -f $targetdir/sql/postgres_settings.sql "$dbname" 2>>$logfile`;
    }
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
} elsif ($confirm =~ /^(upgrade)$/) {
  printmsg("Creating argos database user [argos]:", "info");
  $e = 1;
  while ($e != 0) {
    if ($dbhost != "localhost") {
      `createuser -h $dbhost -p $dbport -q -A -D -E -P -R -U "$dbuser" -W argos 2>>$logfile`;
    } else {
      `sudo -u postgres createuser -q -A -D -E -P -R argos 2>>$logfile`;
    }
    printmsg("Creating argos database user [argos]:", $?);
    if ($? != 0) { $err++; }
    $e = $?;
    if ($? != 0) {
      $confirm = "a";
      while ($confirm !~ /^(n|N|y|Y)$/) {
        $confirm = &prompt("User creation failed. Try again? [y/n]: ");
      }
      if ($confirm =~ /^(n|N)$/) {
        $e = 0;
      }
    }
  }

  print "\n";

  $confirm = "a";
  while ($confirm !~ /^(1\.03|1\.04|skip)$/) {
    $confirm = &prompt("Upgrade database from which version [1.03/1.04/skip]?: ");
  }

  if ($confirm =~ /^(1\.03|1\.04)$/) {
    if ($confirm eq "1.03") {
      $e = 1;
      while ($e != 0) {
        if ($dbhost != "localhost") {
          `psql -h $dbhost -p $dbport -q -f $targetdir/sql/changes103-104.sql -U "$dbuser" -W "$dbname" 2>>$logfile`;
        } else {
          `sudo -u postgres psql -q -f $targetdir/sql/changes103-104.sql "$dbname" 2>>$logfile`;
        }
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
      $e = 1;
      while ($e != 0) {
        if ($dbhost != "localhost") {
          `psql -h $dbhost -p $dbport -q -f $targetdir/sql/changes104-200.sql -U "$dbuser" -W "$dbname" 2>>$logfile`;
        } else {
          `sudo -u postgres psql -q -f $targetdir/sql/changes104-200.sql "$dbname" 2>>$logfile`;
        }
        printmsg("Upgrading the database from 1.04 to 2.00:", $?);
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
    } elsif ($confirm eq "1.04") {
      $e = 1;
      while ($e != 0) {
        if ($dbhost != "localhost") {
          `psql -h $dbhost -p $dbport -q -f $targetdir/sql/changes104-200.sql -U "$dbuser" -W "$dbname" 2>>$logfile`;
        } else {
          `sudo -u postgres psql -q -f $targetdir/sql/changes104-200.sql "$dbname" 2>>$logfile`;
        }
        printmsg("Upgrading the database from 1.04 to 2.00:", $?);
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
} else {
  printmsg("Skipping database installation/upgrade:", "info");
}

print "\n";

`wget -V >/dev/null 2>/dev/null`;
if ($? == 0) {
  print "\n"; 
  $confirm = "a";
  while ($confirm !~ /^(n|N|y|Y)$/) {
    $confirm = &prompt("Do you want to download the latest GeoIP database? [y/n]: ");
  }
  if ($confirm =~ /^(Y|y)$/) {
    printmsg("Downloading GeoIP database:", "info");
    `wget -O "$targetdir/GeoLiteCity.dat.gz" $geoiploc`;
    if ($? != 0) { $err++; }
    print "\n";

    printdelay("Unzipping GeoIP database:");
    `gunzip $targetdir/GeoLiteCity.dat.gz 2>>$logfile`;
    if ($? != 0) { $err++; }
    printresult($?);

    printdelay("Installing GeoIP database:");
    `mv $targetdir/GeoLiteCity.dat $targetdir/include/ 2>>$logfile`;
    if ($? != 0) { $err++; }
    printresult($?);
  }
} else {
  printmsg("Skipping GeoIP database download:", "info");
}

####################
# Removing obsolete files
####################
if (-e "$targetdir/logserver_remove.txt") {
  @list = `cat $targetdir/logserver_remove.txt`;
  foreach $tar (@list) {
    chomp($tar);
    if ("$tar" ne "") {
      if ($tar !~ /.*\.\..*/) {
        if (-e "$targetdir/$tar") {
          printmsg("Removing $tar:", "info");
        }
      }
    }
  }
}

####################
# Cleaning up
####################
$ec = 0;
`rm -f $targetdir/crontab.log 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/surfnetids-log-apache.conf 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/install_log.pl 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/functions_log.pl 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm -f $targetdir/install_log.pl.log 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm $targetdir/surfnetids-log.conf 2>/dev/null`;
if ($? != 0) { $ec++; }
`rm $targetdir/logserver_remove.txt 2>/dev/null`;
if ($? != 0) { $ec++; }
printmsg("Cleaning up the temporary files:", $ec);
$ec = 0;

rmsvn($targetdir);

$webpass = "enter_database_password_here";

$file = readfile("${installdir}surfnetids-log.conf"); 
$dbhost_str = "\$c_pgsql_host = \"$dbhost\"";
$dbport_str = "\$c_pgsql_port = \"$dbport\"";
$dbname_str = "\$c_pgsql_dbname = \"$dbname\"";
$webuser_str = "\$c_pgsql_user = \"$webuser\"";
$webpass_str = "\$c_pgsql_pass = \"$webpass\"";

$file =~ s/\\n/<newline>/gi;
$file =~ s/\$c_pgsql_host =.*/$dbhost_str\;/gi;
$file =~ s/\$c_pgsql_port =.*/$dbport_str\;/gi;
$file =~ s/\$c_pgsql_dbname =.*/$dbname_str\;/gi;
$file =~ s/\$c_pgsql_user =.*/$webuser_str\;/gi;
$file =~ s/\$c_pgsql_pass =.*/$webpass_str\;/gi;
$file =~ s/<newline>/\\n/gi;
open(FILE, ">$configdir/surfnetids-log.conf") ;
print FILE ($file);
close(FILE);
printmsg("Building surfnetids-log.conf configuration file:", $?);

print "\n";
if ($err > 0) {
  print "[${r}Warning${n}] $err error(s) occurred while installing. Check out the logfile 'install_log.pl.log' for more info.\n";
  print "\n";
}
if (-e "${installdir}install_log.pl.log") {
  `cat ${installdir}install_log.pl.log | grep -v NOTICE: > ${installdir}install_log.pl.log.new`;
  `mv ${installdir}install_log.pl.log.new ${installdir}install_log.pl.log`;
}

print "#####################################\n";
print "# ${g}SURFnet IDS installation complete${n} #\n";
print "#####################################\n";
print "\n";
print "Interesting configuration files:\n";
print "  ${g}/etc/crontab${n}\n";
print "  ${g}$apachev config files${n}\n";
print "\n";
print "Still needs configuration:\n";
print "  ${g}$configdir/surfnetids-log.conf${n}\n";
print "  ${g}$targetdir/webinterface/.htaccess${n}\n";
print "\n";
print "For more information go to http://ids.surfnet.nl/\n";
