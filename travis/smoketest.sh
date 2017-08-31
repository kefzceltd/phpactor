#!/usr/bin/env bash

echo "Testing against Symfony\n"
echo "=======================\n"
echo "\n"

mkdir workspace
cd workspace

echo "# Cloning and installing Symfony"
composer create-project symfony/symfony
cd symfony

echo "# Searching for a class"
../../bin/phpactor class:search "Request"

echo "# Finding method references"
../../bin/phpactor references:method "Symfony\\Component\\Filesystem\\Filesystem"
