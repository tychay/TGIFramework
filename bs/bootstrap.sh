#!/bin/sh
# vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker:
# This boostraps the framework, be sure to execute from base directory
# EDITME: Set the full path to binaries {{{
# Should it run as sudo? {{{
SUDO='sudo'
# }}}
BASE_DIR=`pwd`
PHP=`which php`
APACHECTL=`which apachectl`
PHP_INI=/etc/php.ini # check php --ini
# MacPorts
#PHP=/opt/local/bin/php
APACHECTL=/opt/local/apache2/bin/apachectl
PHP_INI=/opt/local/etc/php.ini
# }}}
# UTILS {{{
PHP_EXT_TEST=$BASE_DIR/bs/extension_installed.php
PHP_VERSION_TEST=$BASE_DIR/bs/version_compare.php
pear_installed () { pear list | grep ^$1 ; }
# }}}
# PACKAGES {{{
# RUNKIT {{{
#RUNKIT='runkit'
# Runkit is still in beta.
RUNKIT='channel://pecl.php.net/runkit-0.9'
# Note that Runkit 0.9 doesn't compile in PHP 5.2+
if [ `$PHP_VERSION_TEST 5.2` ]; then
    RUNKIT='cvs'
fi
# }}}
APC='apc'
#APC='http://pecl.php.net/get/APC'
MEMCACHE='memcache'

SAVANT='http://phpsavant.com/Savant3-3.0.0.tgz'
# }}}
if [ ! -d packages ]; then
    mkdir packages
fi
# Install/update PEAR {{{
if [ `which pear` ]; then
    $SUDO pear config-set php_bin $PHP
    $SUDO pear upgrade pear
else
    $SUDO $PHP -q bs/go-pear.php
fi
if [ `which pecl` ]; then
    $SUDO pecl config-set php_ini $PHP_INI
fi
# }}}
# Install runkit {{{
if [ `$PHP_EXT_TEST runkit` ]; then
    echo '### RUNKIT INSTALLED';
    if [ $RUNKIT != 'cvs' ]; then
        $SUDO pecl upgrade $RUNKIT
    fi
else
    echo '### INSTALLING RUNKIT';
# TODO: add test for PHP 5.2
    if [ $RUNKIT == 'cvs' ]; then
        $SUDO pecl install $RUNKIT
        RUNKIT="$BASE_DIR/packages/pecl/runkit"
    else
        pushd packages
            if [ ! -d pecl/runkit ]; then
                cvs -d :pserver:cvsread@cvs.php.net:/repository checkout  pecl/runkit
            fi
            pushd pecl/runkit
                cvs update
                phpize
                ./configure --enable-runkit
                make
                make test
                $SUDO make install
            popd
         popd packages
    fi
fi
# }}}
# Install APC {{{
if [ `$PHP_EXT_TEST apc` ]; then
    echo '### APC INSTALLED';
    $SUDO pecl upgrade $APC
else
    echo '### INSTALLING APC';
    $SUDO pecl install $APC
fi
# }}}
# Install memcache {{{
if [ `$PHP_EXT_TEST memcache` ]; then
    echo '### MEMCACHE INSTALLED';
    $SUDO pecl upgrade $MEMCACHE
else
    echo '### INSTALLING MEMCACHE';
    $SUDO pecl install $MEMCACHE
fi
# }}}
# Install Savant3 {{{
if [ `pear_installed Savant3` ]; then
    $SUDO pear update $SAVANT
else
    $SUDO pear install $SAVANT
fi
# }}}
echo '### You may need to add  'extension=apc.so', 'extension=runkit.so', and echo 'extension=memcache.so' to php.ini and restart.'
$SUDO $APACHECTL restart
