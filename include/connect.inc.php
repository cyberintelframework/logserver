<?php
####################################
# SURFids 2.00.04                  #
# Changeset 001                    #
# 13-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 version 2.00
#############################################

  $pgconn = pg_connect("host=$c_pgsql_host port=$c_pgsql_port user=$c_pgsql_user password=$c_pgsql_pass dbname=$c_pgsql_dbname");
  if (!$pgconn) {
    die('Not connected : ' . pg_last_error($pgconn));
  }
?>
