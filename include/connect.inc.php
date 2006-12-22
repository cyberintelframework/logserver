<?php
####################################
# SURFnet IDS                      #
# Version 1.02.01                  #
# 03-05-2006                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

  $pgconn = pg_connect("host=$c_pgsql_host port=$c_pgsql_port user=$c_pgsql_user password=$c_pgsql_pass dbname=$c_pgsql_dbname");
  if (!$pgconn) {
    die('Not connected : ' . pg_last_error($pgconn));
  }
?>
