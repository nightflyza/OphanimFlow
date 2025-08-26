#!/bin/sh

######################## CONFIG SECTION ########################

#fetch software
FETCH="/usr/bin/wget"

#unzip binary
UNZIP="/usr/bin/unzip"

# path to your apache data dir
APACHE_DATA_PATH="/var/www/html/"

# application path
APP_PATH="of/"

#update log file
LOG_FILE="/var/log/ophanimflowupdate.log"

#restore point dir
RESTORE_POINT="/tmp/of_restore"

#updater distro
UPDATER_DISTRO="dist/presets/debian/autoofupdate.sh"

#defaults
APP_RELEASE_URL="https://codeload.github.com/nightflyza/OphanimFlow/zip/refs/heads/main"
APP_RELEASE_NAME="main.zip"
APP_RELEASE_DIR="OphanimFlow-main"

######################## END OF CONFIG ########################

echo "=== Start OphanimFlow auto update ==="
cd ${APACHE_DATA_PATH}${APP_PATH}

echo "=== Downloading latest release ==="
$FETCH -O ${APP_RELEASE_NAME} ${APP_RELEASE_URL}

if [ -f ${APP_RELEASE_NAME} ];
then
echo "=== Creating restore point ==="
mkdir ${RESTORE_POINT} 2> /dev/null
rm -fr ${RESTORE_POINT}/*

echo "=== Move new release to safe place ==="
cp -R ${APP_RELEASE_NAME} ${RESTORE_POINT}/

echo "=== Backup current data ==="

mkdir ${RESTORE_POINT}/config
mkdir ${RESTORE_POINT}/content

# backup of actual configs and administrators
cp .htaccess ${RESTORE_POINT}/ 2> /dev/null
cp ./config/alter.ini ${RESTORE_POINT}/config/
cp ./config/mysql.ini ${RESTORE_POINT}/config/
cp ./config/ymaps.ini ${RESTORE_POINT}/config/
cp ./config/yalf.ini ${RESTORE_POINT}/config/
cp -R ./content/users ${RESTORE_POINT}/content/
mv ./gdata ${RESTORE_POINT}/gdata_bak

echo "=== web directory cleanup ==="
rm -fr ${APACHE_DATA_PATH}${APP_PATH}/*

echo "=== Unpacking new release ==="
cp  -R ${RESTORE_POINT}/${APP_RELEASE_NAME} ${APACHE_DATA_PATH}${APP_PATH}/

echo `date` >> ${LOG_FILE}
echo "====================" >> ${LOG_FILE}
$UNZIP ${APP_RELEASE_NAME} 2>> ${LOG_FILE}
mv ${APP_RELEASE_DIR}/* ./
rm -fr ${APP_RELEASE_NAME}
rm -fr ${APP_RELEASE_DIR}

echo "=== Restoring configs ==="
rm -fr ./gdata
mv ${RESTORE_POINT}/gdata_bak ./gdata
cp -R ${RESTORE_POINT}/* ./
rm -fr ${APP_RELEASE_NAME}

echo "=== Setting FS permissions ==="
chmod -R 777 content/ config/ exports/ gdata/

echo "=== Updating autoupdater ==="
cp -R ${UPDATER_DISTRO} /bin/

echo "=== Deleting restore poing ==="
rm -fr ${RESTORE_POINT}
echo "SUCCESS: OphanimFlow update successfully completed."

#release file not dowloaded
else
echo "ERROR: No new OphanimFlow release file found, update aborted"
fi
