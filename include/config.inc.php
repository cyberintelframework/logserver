<?php

####################################
# SURFids 3.00                     #
# Changeset 001                    #
# 13-09-2007                       #
# Jan van Lith & Kees Trippelvitz  #
####################################

#############################################
# Changelog:
# 001 version 3.00
#############################################

  $config_handle = @fopen("/etc/surfnetids/surfnetids-log.conf", "r");
  if ($config_handle) {
    while (!feof($config_handle)) {
      $buffer = fgets($config_handle);
      eval($buffer);
    }
  }
?>
