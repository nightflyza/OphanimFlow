#!/bin/sh

DISTRO_URL="https://codeload.github.com/nightflyza/OphanimFlow/zip/refs/heads/main"
DISTRO_NAME="main.zip"
DISTRO_DIR="OphanimFlow-main"

FETCH="/usr/bin/fetch"
APACHE_VERSION="apache24"
PRESETS_PATH="dist/presets/freebsd/"
APACHE_DATA_PATH="/usr/local/www/apache24/data/"
APACHE_CONFIG_DIR="/usr/local/etc/apache24/"
APACHE_INIT_SCRIPT="/usr/local/etc/rc.d/apache24"
APACHE_CONFIG_PRESET_NAME="httpd24f8.conf"
APACHE_CONFIG_NAME="httpd.conf"
PHP_CONFIG_PRESET="php8.ini"
PHP_CONFIG_PATH="/usr/local/etc/php.ini"
MYSQL_INIT_SCRIPT="/usr/local/etc/rc.d/mysql-server"
MYSQL_CONFIG_PRESET="57_my.cnf"
MYSQL_CONFIG_PATH="/usr/local/etc/mysql/my.cnf"
WEB_DIR="of"
DUMP_PATH="dist/dumps/ophanimflow.sql"
LANDING_PATH="dist/landing/"
CRONTAB_PRESET="dist/crontab/crontab.preconf"

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

#downloading and unpacking app distro
$FETCH -o ${DISTRO_NAME} ${DISTRO_URL}
unzip ${DISTRO_NAME}
mkdir ${APACHE_DATA_PATH}${WEB_DIR}
mv ${DISTRO_DIR}/* ${APACHE_DATA_PATH}${WEB_DIR}/
rm -fr ${DISTRO_DIR} ${DISTRO_NAME}
cd ${APACHE_DATA_PATH}${WEB_DIR}/

# preconfiguring OS
cat ${PRESETS_PATH}loader.preconf >> /boot/loader.conf
cat ${PRESETS_PATH}rc.preconf >> /etc/rc.conf
cat ${PRESETS_PATH}sysctl.preconf >> /etc/sysctl.conf
cat ${PRESETS_PATH}firewall.conf > /etc/firewall.conf
chmod a+x /etc/firewall.conf

#deploying database, webserver and php presets
cp -R ${PRESETS_PATH}${MYSQL_CONFIG_PRESET} ${MYSQL_CONFIG_PATH}
cp -R ${PRESETS_PATH}${APACHE_CONFIG_PRESET_NAME} ${APACHE_CONFIG_DIR}${APACHE_CONFIG_NAME}
cp -R ${PRESETS_PATH}${PHP_CONFIG_PRESET} ${PHP_CONFIG_PATH}

#preconfiguring app
chmod -R 777 config content exports gdata

#creating collector config and data storage placeholders
mkdir /ofstorage
touch /etc/of.conf
touch /etc/pretag.map
chmod -R 777 /etc/of.conf /etc/pretag.map /ofstorage

#setting landing page
cp -R ${LANDING_PATH} ${APACHE_DATA_PATH}

#loading default crontab preset
crontab ${CRONTAB_PRESET}

# start services
${APACHE_INIT_SCRIPT} start
${MYSQL_INIT_SCRIPT} start

#Setting MySQL root password
TMP_PASS=`tail -n 1 /root/.mysql_secret`
mysqladmin -u root -p${TMP_PASS} password ${MYSQL_PASSWD}


#restarting database and web server
${MYSQL_INIT_SCRIPT} restart
${APACHE_INIT_SCRIPT} restart

#adding sudoers
echo "User_Alias OPHANIM = www" >> /usr/local/etc/sudoers
echo "OPHANIM         ALL = NOPASSWD: ALL" >> /usr/local/etc/sudoers

echo "New MySQL password is ${MYSQL_PASSWD}"

# configuring database
cat ${DUMP_PATH} | /usr/local/bin/mysql -u root --password=${MYSQL_PASSWD}
perl -e "s/oph/root/g" -pi config/mysql.ini
perl -e "s/newpassword/${MYSQL_PASSWD}/g" -pi config/mysql.ini
perl -e "s/hamster/localhost/g" -pi config/mysql.ini
perl -e "s/rootanimflow/ophanimflow/g" -pi config/mysql.ini

#setting up updater 
cp -R ${PRESETS_PATH}autoofupdate.sh /bin/
chmod a+x /bin/autoofupdate.sh

#here we go?
echo "========== Installation finished! ============="
echo "Please, reboot your server to check correct"
echo "startup of all services. You cah access web"
echo "interface by URL http://thishost/${WEB_DIR}/"
echo "with login admin and password demo"
echo "================================================"