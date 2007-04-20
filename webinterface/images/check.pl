#!/usr/bin/perl -w

@file_ar = `ls -l | grep -v check.pl | grep -v total | awk '{print \$NF}'`;

foreach $file (@file_ar) {
  chomp($file);
  $count = `grep -R $file /home/surfnetids/logserver/branches/1.04beta/ | grep -v .svn | wc -l`;
  chomp($count);
  print "$file: $count\n";
}

