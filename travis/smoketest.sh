#!/usr/bin/env bash

echo "Testing against Symfony\n"
echo "=======================\n"
echo "\n"

mkdir workspace
cd workspace

echo "# Cloning Symfony\n"
git clone https://github.com/symfony/symfony
cd symfony
echo "# Installing Symfony Dependencies\n"
composer install

echo "# Searching for a class\n"
../../bin/phpactor class:search "Request"

echo "# Finding method references\n"
../../bin/phpactor references:method "Symfony\\Component\\Filesystem\\Filesystem"
