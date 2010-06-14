<?php
####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 28-07-2008                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 Changed error message
#############################################

function ga_dood() {
  echo "Could not connect to the database!\n";
}

$pgconn = pg_connect("host=$c_pgsql_host port=$c_pgsql_port user=$c_pgsql_user password=$c_pgsql_pass dbname=$c_pgsql_dbname")
	or die(ga_dood());
?>
