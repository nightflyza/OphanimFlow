#!/bin/sh


DISTRO_URL="https://codeload.github.com/nightflyza/OphanimFlow/zip/refs/heads/main"
DISTRO_NAME="main.zip"
DISTRO_DIR="OphanimFlow-main"

DIALOG="dialog"
FETCH="/usr/bin/wget"
APACHE_VERSION="apache24"
PRESETS_PATH="dist/presets/debian/"
APACHE_DATA_PATH="/var/www/html/"
APACHE_CONFIG_DIR="/etc/apache2/"
APACHE_INIT_SCRIPT="/usr/sbin/service apache2"
APACHE_CONFIG_PRESET_NAME="apache2.conf"
APACHE_CONFIG_NAME="apache2.conf"
PHP_CONFIG_PRESET="php8.ini"
PHP_CONFIG_PATH="/etc/php/8.4/apache2/php.ini"
MYSQL_INIT_SCRIPT="/usr/sbin/service mariadb"
WEB_DIR="of"
DUMP_PATH="dist/dumps/ophanimflow.sql"
LANDING_PATH="dist/landing/"
CRONTAB_PRESET="dist/presets/debian/crontab.debian"


#initial repos update
echo "Preparing to installation.."
apt update
apt -y upgrade

#installation of basic software required for installer
echo "Installing basic software required for Debianstaller.."
apt install -y dialog
apt install -y net-tools
apt install -y gnupg2


#MariaDB setup
apt install -y software-properties-common dirmngr 
apt install -y mariadb-server 
apt install -y mariadb-client 
apt install -y libmariadb-dev 
apt install -y default-libmysqlclient-dev 
mariadb --version 

systemctl start mariadb  
systemctl enable mariadb  

#required software setup
apt install -y expat 
apt install -y libexpat1-dev 
apt install -y sudo 
apt install -y curl 
apt install -y apache2 
apt install -y libapache2-mod-php8.4 
apt install -y build-essential 
apt install -y libxmlrpc-c++8-dev 
apt install -y memcached 
apt install -y redis 
apt install -y php8.4-cli 
apt install -y php8.4-mysql 
apt install -y php8.4-mysqli 
apt install -y php8.4-mbstring 
apt install -y php8.4-bcmath 
apt install -y php8.4-curl 
apt install -y php8.4-gd 
apt install -y php8.4-snmp 
apt install -y php8.4-soap 
apt install -y php8.4-zip 
apt install -y php8.4-imap 
apt install -y php8.4-tokenizer 
apt install -y php8.4-xml 
apt install -y php8.4-xmlreader 
apt install -y php8.4-xmlwriter 
apt install -y php8.4-simplexml 
apt install -y php8.4-sqlite3 
apt install -y php8.4-sockets 
apt install -y php8.4-opcache 
apt install -y php8.4-json 
apt install -y php8.4-pdo 
apt install -y php8.4-pdo-sqlite 
apt install -y php8.4-phar 
apt install -y php8.4-posix 
apt install -y vim-tiny 
apt install -y elinks 
apt install -y mc 
apt install -y nano 
apt install -y mtr 
apt install -y expect 
apt install -y git 
apt install -y netdiag 
apt install -y htop 
apt install -y rsyslog 

#installing pmacct
apt install -y pmacct

#generating mysql password
GEN_MYS_PASS=`dd if=/dev/urandom count=128 bs=1 2>&1 | md5sum | cut -b-8`
MYSQL_PASSWD="mys"${GEN_MYS_PASS}


#
# Preconfiguring software
#

#downloading and unpacking app distro
$FETCH -O ${DISTRO_NAME} ${DISTRO_URL}
unzip ${DISTRO_NAME}
mkdir ${APACHE_DATA_PATH}${WEB_DIR}
mv ${DISTRO_DIR}/* ${APACHE_DATA_PATH}${WEB_DIR}/
rm -fr ${DISTRO_DIR} ${DISTRO_NAME}
cd ${APACHE_DATA_PATH}${WEB_DIR}/


#deploying database, webserver and php presets
cp -R ${PRESETS_PATH}${APACHE_CONFIG_PRESET_NAME} ${APACHE_CONFIG_DIR}${APACHE_CONFIG_NAME}
cp -R ${PRESETS_PATH}${PHP_CONFIG_PRESET} ${PHP_CONFIG_PATH}
cp -R ${PRESETS_PATH}000-default.conf ${APACHE_CONFIG_DIR}sites-enabled/000-default.conf

#preconfiguring app
chmod -R 777 config content exports gdata

#creating collector config and data storage placeholders
mkdir /ofstorage
touch /etc/of.conf
touch /etc/pretag.map
chmod -R 777 /etc/of.conf /etc/pretag.map /ofstorage

#setting landing page
cp -R ${LANDING_PATH}* ${APACHE_DATA_PATH}

#loading default crontab preset
crontab ${CRONTAB_PRESET}

# start services
${APACHE_INIT_SCRIPT} start
${MYSQL_INIT_SCRIPT} start


# updating sudoers
echo "User_Alias OPHANIM = www-data" >> /etc/sudoers.d/ophanim
echo "OPHANIM        ALL = NOPASSWD: ALL" >> /etc/sudoers.d/ophanim

echo "New MySQL password is ${MYSQL_PASSWD}"

#Setting MySQL root password
mysqladmin -u root password ${MYSQL_PASSWD}

# configuring database
cat ${DUMP_PATH} | /usr/bin/mysql -u root --password=${MYSQL_PASSWD}

# updating passwords and login in mysql.ini
perl -e "s/mylogin/root/g" -pi ./config/mysql.ini
perl -e "s/newpassword/${MYSQL_PASSWD}/g" -pi ./config/mysql.ini

#overriding binary paths in alter.config
cat ${PRESETS_PATH}alter_append >> ./config/alter.ini

#setting up updater 
cp -R ${PRESETS_PATH}autoofupdate.sh /bin/
chmod a+x /bin/autoofupdate.sh

#enabling required apache modules
/usr/sbin/a2enmod headers
/usr/sbin/a2enmod expires

#restarting database and web server
${MYSQL_INIT_SCRIPT} restart
${APACHE_INIT_SCRIPT} restart


#here we go?
echo "========== Installation finished! ============="
echo "Please, reboot your server to check correct"
echo "startup of all services. You cah access web"
echo "interface by URL http://thishost/${WEB_DIR}/"
echo "with login admin and password demo"
echo "================================================"
