#!/usr/bin/env bash
export DEBIAN_FRONTEND=noninteractive
echo ">>> Updating system software (this will take a while)..."
apt-get -y update
DEBIAN_FRONTEND=noninteractive DEBIAN_PRIORITY=critical apt-get -q -y -o "Dpkg::Options::=--force-confdef" -o "Dpkg::Options::=--force-confold" upgrade
DEBIAN_FRONTEND=noninteractive DEBIAN_PRIORITY=critical apt-get -q -y -o "Dpkg::Options::=--force-confdef" -o "Dpkg::Options::=--force-confold" dist-upgrade
apt-get -y update
echo ">>> System software updated!"

echo ">>> Installing Apache..."
apt-get -y install apache2
echo ">>> Apache installed!"

echo ">>> Enabling mod_rewrite..."
a2enmod rewrite
echo ">>> mod_rewrite enabled!"

echo ">>> Installing OpenSSL..."
apt-get -y install openssl
echo ">>> OpenSSL installed!"

echo ">>> Enabling apache ssl..."
a2enmod ssl
a2ensite default-ssl
echo ">>> apache ssl enabled!"

echo ">>> Creating docroot directory..."
mkdir -p /var/www/cruithne
echo ">>> docroot directory created!"

echo ">>> Restarting apache..."
service apache2 restart

echo ">>> Installing and configuring php 7.2..."
add-apt-repository ppa:ondrej/php
apt-get -y update
apt-get -y install php7.2 libapache2-mod-php7.2 php7.2-common php7.2-gd php7.2-mysql php7.2-curl php7.2-intl php7.2-xsl php7.2-mbstring php7.2-zip php7.2-bcmath php7.2-iconv php7.2-soap php7.2-mcrypt
sed -i "s/;date\.timezone =$/date\.timezone = 'America\/New_York'/" /etc/php/7.2/cli/php.ini
sed -i "s/;date\.timezone =$/date\.timezone = 'America\/New_York'/" /etc/php/7.2/apache2/php.ini
sed -i 's/;opcache\.save_comments/opcache\.save_comments/' /etc/php/7.2/cli/php.ini
sed -i 's/;opcache\.save_comments/opcache\.save_comments/' /etc/php/7.2/apache2/php.ini
sed -i 's/short_open_tag = Off/short_open_tag = On/' /etc/php/7.2/cli/php.ini
sed -i 's/short_open_tag = Off/short_open_tag = On/' /etc/php/7.2/apache2/php.ini
sed -i 's/memory_limit = [0-9]+M/memory_limit = 1G/' /etc/php/7.2/apache2/php.ini
phpenmod mcrypt
echo ">>> php 7.2 installed and configured!"

echo ">>> Installing and configuring xdebug..."
# For PHP 7.2 it looks like there is not xdebug 7.2 specific package.
# START XDEBUG - PHP 7.2 SPECIFIC BUILDING FROM SOURCE

# Download stable release of xdebug from https://xdebug.org/download.php
apt-get -y install php7.2-dev
wget -c "https://xdebug.org/files/xdebug-2.5.3.tgz"
# Extract archive
tar -xf xdebug-2.5.3.tgz
cd xdebug-2.5.3/
# Build extension
phpize
./configure
make && make install
ln -sf /etc/php/7.2/mods-available/xdebug.ini /etc/php/7.2/cli/conf.d/20-xdebug.ini
# END XDEBUG - PHP 7.2 SPECIFIC BUILDING FROM SOURCE

echo "[xdebug]" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo "zend_extension=xdebug.so" > /etc/php/7.2/mods-available/xdebug.ini
echo "xdebug.remote_autostart=0" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo "xdebug.remote_enable=1" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo "xdebug.remote_port=9000" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo "xdebug.remote_connect_back=1" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo "xdebug.remote_handler=dbgp" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo "xdebug.remote_host=127.0.0.1" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo "xdebug.remote_log = 1" | sudo tee -a /etc/php/7.2/apache2/php.ini
echo ">>> xdebug installed and configured!"

echo ">>> Installing Composer..."
apt-get -y install composer
echo ">>> Composer installed!"

echo ">>> Installing and configuring mysql..."
debconf-set-selections <<< 'mysql-server-5.7 mysql-server/root_password password dfDF34#$'
debconf-set-selections <<< 'mysql-server-5.7 mysql-server/root_password_again password dfDF34#$'
apt-get install mysql-server-5.7 -y
apt-get install mysql-client-5.7 -y
mysql -u root -pdfDF34#$ -e "create database cruithne;"
mysql -u root -pdfDF34#$ -e "grant all on cruithne.* to 'varien'@'%' identified by 'j7K9u3Lm2wA6';"
sed  '/skip-external-locking/a wait_timeout=30000' /etc/mysql/my.cnf
echo ">>> mysql installed and configured!"

echo ">>> Generating a self-signed certificate for Cruithne..."
SSL_DIR="/etc/ssl"
COMMON="cruithne.scottsprogram.local"
PASSPHRASE=""
COUNTRY="US"
STATE="Ohio"
ORGANIZATION="The Scotts Miracle-Gro Company"
CITY="Marysville"
ORGNAME="BT - eCommerce"
EMAIL="smgwebsupport@scotts.com"
SUBJ="
C=$COUNTRY
ST=$STATE
O=$ORGANIZATION
localityName=$CITY
commonName=$COMMON
organizationalUnitName=$ORGNAME
emailAddress=$EMAIL
"
# Create raw SSL files dir
mkdir -p "$SSL_DIR"
openssl genrsa -out "$SSL_DIR/cruithne.key" 2048
openssl req -new -subj "$(echo -n "$SUBJ" | tr "\n" "/")" -key "$SSL_DIR/cruithne.key" -out "$SSL_DIR/cruithne.csr" -passin pass:$PASSPHRASE
openssl x509 -req -days 365 -in "$SSL_DIR/cruithne.csr" -signkey "$SSL_DIR/cruithne.key" -out "$SSL_DIR/cruithne.crt"
echo ">>> Self-signed certificate created!"

echo ">>> Generating a self-signed certificate for MyGro..."
COMMON="cruithne.mygro.local"
SUBJ="
C=$COUNTRY
ST=$STATE
O=$ORGANIZATION
localityName=$CITY
commonName=$COMMON
organizationalUnitName=$ORGNAME
emailAddress=$EMAIL
"

openssl genrsa -out "$SSL_DIR/mygro.key" 2048
openssl req -new -subj "$(echo -n "$SUBJ" | tr "\n" "/")" -key "$SSL_DIR/mygro.key" -out "$SSL_DIR/mygro.csr" -passin pass:$PASSPHRASE
openssl x509 -req -days 365 -in "$SSL_DIR/mygro.csr" -signkey "$SSL_DIR/mygro.key" -out "$SSL_DIR/mygro.crt"
echo ">>> Self-signed certificate created!"

echo ">>> Setting up HTTPS configuration..."
cp /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf.bak
rm /etc/apache2/sites-available/000-default.conf
cp $(ls /vagrant/environment/config/000-d*) /etc/apache2/sites-available/000-default.conf
chmod 644 /etc/apache2/sites-available/000-default.conf
cp /etc/apache2/sites-available/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf.bak
rm /etc/apache2/sites-available/default-ssl.conf
cp $(ls /vagrant/environment/config/default-s*) /etc/apache2/sites-available/default-ssl.conf
chmod 644 /etc/apache2/sites-available/default-ssl.conf
echo ">>> HTTPS configuration completed!"

echo ">>> Restarting apache..."
service apache2 restart

echo ">>> Setting permissions and groups..."
chown -R www-data:www-data /var/www/
chmod -R 666 /var/www/
find /var/www/ -type f -exec chmod ug+rw {} \;
find /var/www/ -type d -exec chmod ug+rwxs {} \;
cp -R /root/.ssh $(echo "/home/$(who am i | awk '{print $1}')/")
chown -R `who am i | awk '{print $1}'`:`who am i | awk '{print $1}'` $(echo "/home/$(who am i | awk '{print $1}')/.ssh")
chmod 700 $(echo "/home/$(who am i | awk '{print $1}')/.ssh")
chmod 600 $(echo "/home/$(who am i | awk '{print $1}')/.ssh/*")
usermod -a -G www-data `who am i | awk '{print $1}'`
echo ">>> Permissions and groups set!"

echo ">>> Final restarts..."
service apache2 restart
