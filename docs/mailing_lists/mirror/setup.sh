#!/bin/bash

# Setting up a new server to mirror lists.boost.org

# This has already been done on lists.boost.org.cpp.al

# run as root

# This script is a record of steps that were taken during the original install

set -xe

adduser mlman

apt-get update
apt-get install -y python2 python3-pip apache2 php

mkdir /opt/mailman
chown mlman:mlman /opt/mailman
mkdir /home/hyper-archives
chown mlman:mlman /home/hyper-archives
mkdir -p /home/www/lists.boost.org
chown -R mlman:mlman /home/www/lists.boost.org

cp lists.boost.org.conf /etc/apache2/sites-available
rm /etc/apache2/sites-enabled/000-default.conf || true
rm /etc/apache2/sites-enabled/default-ssl.conf || true

a2enmod cgi
a2enmod ssl
a2enmod rewrite
a2ensite lists.boost.org

# (at least) these apache modules are added after installation is complete.
# /etc/apache2/mods-available/php8.1.conf
# /etc/apache2/mods-available/php8.1.load
# /etc/apache2/mods-enabled/cgi.load
# /temp/apache2/mods-enabled/mpm_event.conf
# /temp/apache2/mods-enabled/mpm_event.load
# /etc/apache2/mods-enabled/mpm_prefork.conf
# /etc/apache2/mods-enabled/mpm_prefork.load
# /etc/apache2/mods-enabled/php8.1.conf
# /etc/apache2/mods-enabled/php8.1.load
# /etc/apache2/mods-enabled/proxy.conf
# /etc/apache2/mods-enabled/proxy.load
# /etc/apache2/mods-enabled/proxy_html.conf
# /etc/apache2/mods-enabled/proxy_html.load
# /etc/apache2/mods-enabled/rewrite.load
# /etc/apache2/mods-enabled/socache_shmcb.load
# /etc/apache2/mods-enabled/ssl.conf
# /etc/apache2/mods-enabled/ssl.load
# /etc/apache2/mods-enabled/xml2enc.load

echo "Install the sync-mailman.sh script"

mkdir -p /home/mlman/scripts
cp setup.sh /home/mlman/scripts/
cp sync-mailman.sh /home/mlman/scripts/
chown -R mlman:mlman /home/mlman/scripts

echo "Set up a crontab"

crontabfile='/var/spool/cron/crontabs/mlman'
echo '0 5 * * * /home/mlman/scripts/sync-mailman.sh >> /tmp/sync-output.txt 2>&1' >> $crontabfile
chmod 600 $crontabfile
chown mlman:crontab $crontabfile

echo "Run the sync once"

su - mlman -c /home/mlman/scripts/sync-mailman.sh

echo "Installing icons"

cp /opt/mailman/default/icons/* /usr/share/apache2/icons/

echo "A few settings were done to fully copy the 'source' server configuration even if ultimately they are not be required."

echo "Make the following changes manually."

echo "Add an apache user, set shell to /usr/sbin/nologin"

adduser apache

code ='
export APACHE_RUN_USER=apache
export APACHE_RUN_GROUP=apache
'

echo "Run the apache service as the apache user. Add export APACHE_RUN_USER=apache, export APACHE_RUN_GROUP=apache to /etc/apache2/envvars" 

text="""
               #  # CREST For GNU mailman
               #  ScriptAlias /mailman/   /opt/mailman/default/cgi-bin/
               #  <Directory /opt/mailman/default/cgi-bin>
               #         # AllowOverride None
               #         Options ExecCGI FollowSymLinks
               #         Require all granted
               #         # Order allow,deny
               #         # Allow from all

               #        RewriteEngine on
               #        RewriteBase /mailman/
               #        RewriteCond %{SERVER_PORT} ^80$
               #        RewriteRule ^(admin.cgi.*)$ https://%{SERVER_NAME}/mailman/$1 [L,R=301]
               #</Directory>
"""

echo "Configuration in conf-available/serve-cgi-bin.conf, <IfDefine ENABLE_USR_LIB_CGI_BIN> section, not currently used."
echo $text

echo "\n Set up Let's Encrypt for the site"

