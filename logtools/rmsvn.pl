#!/usr/bin/perl

####################################
# SVN dir cleanup script           #
# SURFids 2.10                     #
# Changeset 002                    #
# 02-03-2009                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

# Removes all .svn directories in the given directory
# Usage: ./rmsvn.pl /opt/surfnetids/

#####################
# Changelog:
# 002 Fixed references to tunnel scripts
# 001 Initial version
#####################

##################
# Modules used
##################

##################
# Variables used
##################
do '/etc/surfnetids/surfnetids-log.conf';

##################
# Main script
##################
if (!$ARGV[0]) {
  print "No directory to clean is given!\n";
  print "Usage: ./rmsvn.pl /opt/surfnetids/\n";
  exit 1;
} else {
  $startdir = $ARGV[0];
  chomp($startdir);
}

sub rmsvn {
  my ($dir, $file, $newdir);
  $dir = $_[0];
  opendir(DH, $dir);
  foreach (readdir(DH)) {
    $file = $_;
    if ($file !~ /^(\.|\.\.)$/) {
      if ($file ne "svnroot") {
        if (-d "$dir$file") {
          if ($file =~ /^\.svn$/) {
            `rm -r $dir$file/`;
          } else {
            $newdir = "$dir$file/";
            &rmsvn($newdir);
          }
        }
      }
    }
  }
  close(DH);
}

rmsvn($startdir);
