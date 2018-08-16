#!/usr/bin/env bash
# install vagrant hosts updater
vagrant plugin install vagrant-hostsupdater

# ignore git file mods
git config core.fileMode false

echo ">>> Installing Composer dependencies..."
composer install
echo ">>> Composer dependencies installed!"

# vagrant up
vagrant up
