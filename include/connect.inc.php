<?php
####################################
# SURFnet IDS                      #
# Version 2.00.01                  #
# 13-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 2.00.01 version 2.00
# 1.04.01 Changed config variable names
# 1.03.01 Released as part of the 1.03 package
# 1.02.01 Initial release
#############################################

  $pgconn = pg_pconnect("host=$c_pgsql_host port=$c_pgsql_port user=$c_pgsql_user password=$c_pgsql_pass dbname=$c_pgsql_dbname");
  if (!$pgconn) {
    die('Not connected : ' . pg_last_error($pgconn));
  }
?>
