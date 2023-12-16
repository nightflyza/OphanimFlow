#!/bin/sh

FETCH="/usr/bin/fetch"
APACHE_VERSION="apache24"
APACHE_DATA_PATH="/usr/local/www/apache24/data/"
APACHE_CONFIG_DIR="/usr/local/etc/apache24/"
APACHE_INIT_SCRIPT="/usr/local/etc/rc.d/apache24"
APACHE_CONFIG_PRESET_NAME="httpd24f8.conf"
APACHE_CONFIG_NAME="httpd.conf"
PHP_CONFIG_PRESET="php8.ini"
PHP_CONFIG_PATH="/usr/local/etc/php.ini"
MYSQL_INIT_SCRIPT="/usr/local/etc/rc.d/mysql-server"

CONFIGS_URL="https://raw.githubusercontent.com/nightflyza/UBinstaller/master/configs/"

set PATH=/usr/local/bin:/usr/local/sbin:$PATH


#bootstraping pkgng
pkg info

#packages installing
pkg install -y bash
pkg install -y sudo
pkg install -y gmake
pkg install -y libtool
pkg install -y autoconf
pkg install -y m4
pkg install -y automake
pkg install -y vim-tiny
pkg install -y memcached
pkg install -y redis
pkg install -y mysql57-client
pkg install -y mysql57-server
pkg install -y apache24
pkg install -y php82
pkg install -y mod_php82
pkg install -y php82-bcmath
pkg install -y php82-ctype
pkg install -y php82-curl
pkg install -y php82-dom
pkg install -y php82-extensions
pkg install -y php82-filter
pkg install -y php82-ftp
pkg install -y php82-gd
pkg install -y php82-hash
pkg install -y php82-iconv
pkg install -y php82-imap
pkg install -y php82-json
pkg install -y php82-mbstring
pkg install -y php82-mysqli
pkg install -y php82-opcache
pkg install -y php82-openssl
pkg install -y php82-pdo
pkg install -y php82-pdo_sqlite
pkg install -y php82-phar
pkg install -y php82-posix
pkg install -y php82-session
pkg install -y php82-simplexml
pkg install -y php82-snmp
pkg install -y php82-soap
pkg install -y php82-sockets
pkg install -y php82-sqlite3
pkg install -y php82-tokenizer
pkg install -y php82-xml
pkg install -y php82-xmlreader
pkg install -y php82-xmlwriter
pkg install -y php82-zip
pkg install -y php82-zlib
pkg install -y php82-pecl-memcached
pkg install -y php82-pecl-redis
pkg install -y git

#building specific software from ports
portsnap fetch && portsnap extract && portsnap update
cd /usr/ports/databases/pecl-memcached &&  make BATCH=yes install

#installing pmacct with mysql support
cd /usr/ports/net-mgmt/pmacct/ && make  WITH="MYSQL" BATCH=yes install


#generating mysql password
GEN_MYS_PASS=`dd if=/dev/urandom count=128 bs=1 2>&1 | md5 | cut -b-8`
MYSQL_PASSWD="mys"${GEN_MYS_PASS}


#
# Preconfiguring software
#

# start services
${APACHE_INIT_SCRIPT} start
${MYSQL_INIT_SCRIPT} start

#Setting MySQL root password
mysqladmin -u root password ${MYSQL_PASSWD}

#downloading and deploying webserver and php presets
$FETCH ${CONFIGS_URL}${APACHE_CONFIG_PRESET_NAME}
mv {APACHE_CONFIG_PRESET_NAME} ${APACHE_CONFIG_DIR}${APACHE_CONFIG_NAME}

$FETCH ${CONFIGS_URL}${PHP_CONFIG_PRESET}
mv ${PHP_CONFIG_PRESET} ${PHP_CONFIG_PATH}

#restarting web server
${APACHE_INIT_SCRIPT} restart

#editing sudoers
echo "User_Alias OPHANIM = www" >> /usr/local/etc/sudoers
echo "OPHANIM         ALL = NOPASSWD: ALL" >> /usr/local/etc/sudoers

echo "New MySQL password is ${MYSQL_PASSWD}"

# to be continued
#cat dist/dumps/ophanimflow.sql | /usr/local/bin/mysql -u root --password=${MYSQL_PASSWD}
#perl -e "s/mylogin/root/g" -pi /etc/stargazer/config
#perl -e "s/newpassword/${MYSQL_PASSWD}/g" -pi /etc/stargazer/config