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
MYSQL_CONFIG_PRESET="80_my.cnf"
MYSQL_CONFIG_PATH="/usr/local/etc/mysql/my.cnf"
MYSQL_SECURE="/root/.mysql_secret"
WEB_DIR="of"
DUMP_PATH="dist/dumps/ophanimflow.sql"
LANDING_PATH="dist/landing/"
CRONTAB_PRESET="dist/crontab/crontab.preconf"

set PATH=/usr/local/bin:/usr/local/sbin:$PATH

requiredpackages="gmake bash sudo libtool m4 vim-tiny memcached redis 
mysql80-client mysql80-server apache24 php84 mod_php84 
php84-bcmath php84-ctype php84-curl php84-dom php84-extensions 
php84-filter php84-ftp php84-gd php84-hash php84-iconv php84-imap 
php84-json php84-mbstring php84-mysqli php84-opcache php84-openssl 
php84-pdo php84-pdo_sqlite php84-phar php84-posix php84-session 
php84-simplexml php84-snmp php84-soap php84-sockets php84-sqlite3 
php84-tokenizer php84-xml php84-xmlreader php84-xmlwriter 
php84-zip php84-zlib php84-pecl-memcached php84-pecl-redis 
git pmacct"

#bootstraping pkgng
pkg info

#packages installing
pkg install -y bash
pkg install -y sudo
pkg install -y libtool
pkg install -y m4
pkg install -y vim-tiny
pkg install -y memcached
pkg install -y redis
pkg install -y mysql80-client
pkg install -y mysql80-server
pkg install -y apache24
pkg install -y php84
pkg install -y mod_php84
pkg install -y php84-bcmath
pkg install -y php84-ctype
pkg install -y php84-curl
pkg install -y php84-dom
pkg install -y php84-extensions
pkg install -y php84-filter
pkg install -y php84-ftp
pkg install -y php84-gd
pkg install -y php84-hash
pkg install -y php84-iconv
pkg install -y php84-imap
pkg install -y php84-json
pkg install -y php84-mbstring
pkg install -y php84-mysqli
pkg install -y php84-opcache
pkg install -y php84-openssl
pkg install -y php84-pdo
pkg install -y php84-pdo_sqlite
pkg install -y php84-phar
pkg install -y php84-posix
pkg install -y php84-session
pkg install -y php84-simplexml
pkg install -y php84-snmp
pkg install -y php84-soap
pkg install -y php84-sockets
pkg install -y php84-sqlite3
pkg install -y php84-tokenizer
pkg install -y php84-xml
pkg install -y php84-xmlreader
pkg install -y php84-xmlwriter
pkg install -y php84-zip
pkg install -y php84-zlib
pkg install -y php84-pecl-memcached
pkg install -y php84-pecl-redis
pkg install -y git
pkg install -y portsnap

#building specific software from ports
portsnap fetch && portsnap extract && portsnap update

#fresh gmake
cd /usr/ports/devel/gmake && make BATCH=yes install 

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
cat ${PRESETS_PATH}local.preconf > /etc/rc.local
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

# Setting MySQL root password
if [ -f ${MYSQL_SECURE} ];
then
echo "Secure MySQL installation"
TMP_PASS=`tail -n 1 ${MYSQL_SECURE}`
echo "Temporary password is ${TMP_PASS}"
mysqladmin -u root -p${TMP_PASS} password ${MYSQL_PASSWD}
else
echo "Insecure MySQL installation"
mysqladmin -u root password ${MYSQL_PASSWD}
fi

#restarting database and web server
${MYSQL_INIT_SCRIPT} restart
${APACHE_INIT_SCRIPT} restart

#adding sudoers
echo "User_Alias OPHANIM = www" >> /usr/local/etc/sudoers
echo "OPHANIM         ALL = NOPASSWD: ALL" >> /usr/local/etc/sudoers

echo "New MySQL password is ${MYSQL_PASSWD}"

# configuring database
cat ${DUMP_PATH} | /usr/local/bin/mysql -u root --password=${MYSQL_PASSWD}
perl -e "s/mylogin/root/g" -pi config/mysql.ini
perl -e "s/newpassword/${MYSQL_PASSWD}/g" -pi config/mysql.ini

#setting up updater 
cp -R ${PRESETS_PATH}autoofupdate.sh /bin/
chmod a+x /bin/autoofupdate.sh

#checking installed packages
missing_packages=""
for pkg in $requiredpackages; do
    if ! pkg info -q "$pkg"; then
        echo "❌ $pkg [MISSING]"
        missing_packages="$missing_packages $pkg"
    else
        echo "✅ $pkg [OK]"
    fi
done

if [ -n "$missing_packages" ]; then
    echo "Following packages is missing: $missing_packages"
    exit 1
else
    echo "All required packages are installed."
    exit 0
fi

#here we go?
echo "========== Installation finished! ============="
echo "Please, reboot your server to check correct"
echo "startup of all services. You cah access web"
echo "interface by URL http://thishost/${WEB_DIR}/"
echo "with login admin and password demo"
echo "================================================"