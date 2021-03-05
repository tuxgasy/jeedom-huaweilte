PROGRESS_FILE=/tmp/dependancy_huaweilte_in_progress
if [ ! -z $1 ]; then
    PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
wget -O - https://repository.salamek.cz/deb/salamek.gpg.key|sudo apt-key add -
echo 25 > ${PROGRESS_FILE}
echo "deb https://repository.salamek.cz/deb/pub all main" | sudo tee /etc/apt/sources.list.d/salamek.cz.list
echo 50 > ${PROGRESS_FILE}
apt-get update
echo 80 > ${PROGRESS_FILE}
apt-get install -y python3-huawei-lte-api python3-pyudev
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}
