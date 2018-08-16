#!/usr/bin/env bash
echo ">>> Installing Homebrew..."
xcode-select --install
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
# Add path to bash_profile if it does not exist
sudo grep -q -F 'export PATH=/usr/local/bin:/usr/local/sbin:$PATH' ~/.bash_profile || echo -e '\nexport PATH=/usr/local/bin:/usr/local/sbin:$PATH' >> ~/.bash_profile
source ~/.bash_profile
brew update
brew upgrade
echo ">>> Homebrew Installed!"

echo ">>> Installing VirtualBox..."
brew cask install virtualbox
echo ">>> VirtualBox Installed!"

echo ">>> Installing Vagrant..."
brew cask install vagrant
echo ">>> Vagrant Installed!"

echo ">>> Installing Node & NPM..."
brew install node
echo ">>> Node & NPM Installed!"

echo ">>> Installing PHP 7.1..."
brew tap homebrew/homebrew-php
brew install php71
# Add path to bash_profile if it does not exist
sudo grep -q -F 'export PATH="/usr/local/opt/php@7.1/bin:$PATH"' ~/.bash_profile || echo -e '\nexport PATH="/usr/local/opt/php@7.1/bin:$PATH"' >> ~/.bash_profile
sudo grep -q -F 'export PATH="/usr/local/opt/php@7.1/sbin:$PATH"' ~/.bash_profile || echo -e '\nexport PATH="/usr/local/opt/php@7.1/sbin:$PATH"' >> ~/.bash_profile
source ~/.bash_profile
echo ">>> PHP 7.1 Installed!"

echo ">>> Installing PHP Mcrypt via Pecl..."
pecl install mcrypt-1.0.0
echo ">>> PHP Mcrypt Installed!"

echo ">>> Installing MySQL..."
brew install mysql
echo ">>> MySQL Installed!"
