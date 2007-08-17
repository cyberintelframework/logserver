#!/bin/sh

# Defaults
CRONTAB=/etc/crontab
PREFIX=/opt/surfnetids
CONFDIR=/etc/surfnetids
WEBUSER=idslog
OPENVPNLOC=/usr/sbin/openvpn
AP="apache"

# Database connection defaults
DATABASENAME=idsserver
DATABASEUSER=postgres

echo "Starting the installation of the SURFnet IDS logging server."

croncheck=`cat $CRONTAB | grep "maillog\.pl" | wc -l`
if [ $croncheck == 0 ]; then
  cat $PREFIX/crontab.log >> $CRONTAB
else
  echo -e "No crontab modifications needed."
fi

####### Setting up Apache configuration ##########
echo -e "1. Apache"
echo -e "2. Apache-ssl"
echo -en "Which apache are you using [1/2]: "
while :
do
  read APACHE
  if [ ! -z $APACHE ]; then
    if [ $APACHE == "1" -o $APACHE == "2" ]; then
      if [ $APACHE == "1" ]; then
        AP="apache"
        break
      else
        AP="apache-ssl"
        break
      fi
    fi
  fi
done

cp $PREFIX/surfnetids-log-apache.conf /etc/$AP/conf.d/
/etc/init.d/$AP restart

####### Setting up PGSQL tables for the webinterface ############
echo -e "Do you want to create the PostgreSQL database tables? [Y/n]"
while :
do
  read CHOICE
  if [ ! -z $CHOICE ]; then
    if [ $CHOICE == "Y" -o $CHOICE == "y" ]; then
      echo -e "Enter the admin user that will connect to the database: [$DATABASEUSER]"
      read DBUSER
      if [ ! -z $DBUSER ]; then
        DATABASEUSER=$DBUSER
      fi

      echo -e "Enter the name of the database that will be created: [$DATABASENAME]"
      read DBNAME
      if [ ! -z $DBNAME ]; then
        DATABASENAME=$DBNAME
      fi
      break
    fi
    if [ $CHOICE == "n" ]; then
      echo -e "Use the /opt/surfnetids/postgres_settings.sql file to manually setup the database structure."
      echo -e "Create the users nepenthes and pofuser manually before using the postgres_settings.sql file."
      break
    fi
  fi
done

if [ $CHOICE == "Y" -o $CHOICE == "y" ]; then
  ####### Creating database
  echo -e "Creating database. Enter password to connect with user ($DATABASEUSER):"
  if [ $DATABASEUSER == "postgres" ]; then
    sudo -u postgres createdb -q -U $DATABASEUSER -W -O $DATABASEUSER $DATABASENAME
  else
    createdb -q -U $DATABASEUSER -W -O $DATABASEUSER $DATABASENAME
  fi

  ####### Creating webuser
  echo -e "Enter the name of the user that will be used by the webinterface: [$WEBUSER]"
  read WUSER
  if [ ! -z $WUSER ]; then
    WEBUSER=$WUSER
  fi

  echo -e "Creating new user ($WEBUSER):"
  if [ $DATABASEUSER == "postgres" ]; then
    sudo -u postgres createuser -q -A -D -E -P -U $DATABASEUSER -W $WEBUSER
  else
    createuser -q -A -D -E -P -U $DATABASEUSER -W $WEBUSER
  fi

  echo -e "Creating new user (nepenthes):"
  if [ $DATABASEUSER == "postgres" ]; then
    sudo -u postgres createuser -q -A -D -E -P -U $DATABASEUSER -W nepenthes
  else
    createuser -q -A -D -E -P -U $DATABASEUSER -W nepenthes
  fi

  echo -e "Creating new user (pofuser):"
  if [ $DATABASEUSER == "postgres" ]; then
    sudo -u postgres createuser -q -A -D -E -P -U $DATABASEUSER -W pofuser
  else
    createuser -q -A -D -E -P -U $DATABASEUSER -W pofuser
  fi

  ######## Creating tables
  echo -e "Creating tables. Enter password to connect with user ($DATABASEUSER):"
  if [ $DATABASEUSER == "postgres" ]; then
    sudo -u postgres psql -q -f $PREFIX/postgres_settings.sql -U $DATABASEUSER -W $DATABASENAME 2>/dev/null
  else
    psql -q -f $PREFIX/postgres_settings.sql -U $DATABASEUSER -W $DATABASENAME 2>/dev/null
  fi
  rm -f $PREFIX/postgres_settings.sql
fi

rm -f $PREFIX/crontab.log
rm -f $PREFIX/surfnetids-log-apache.conf

##### Setting up permissions for charts directory #######
chmod 777 $PREFIX/webinterface/charts/

##### Creating rrd directory #####
if [ ! -d /var/lib/rrd/ ]; then
  mkdir /var/lib/rrd/
else
  echo -e "No need to create /var/lib/rrd/."
fi

echo -e "#####################################"
echo -e "# SURFnet IDS installation complete #"
echo -e "#####################################"
echo -e ""
echo -e "Configuration files to edit:"
echo -e "  $CONFDIR/surfnetids-log.conf"
echo -e "  /etc/crontab"
echo -e ""
echo -e "For more information go to http://ids.surfnet.nl/"
echo -e "If you are upgrading from 1.01 to 1.02, check the website documentation on how to upgrade your database."
