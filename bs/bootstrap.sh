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
#PHP_INI=/opt/local/etc
PHP_INI=/opt/local/etc/php.ini
# }}}
# UTILS {{{
PHP_EXT_TEST=$BASE_DIR/bs/extension_installed.php
PHP_VERSION_TEST=$BASE_DIR/bs/version_compare.php
pear_installed () { pear list -a | grep ^$1 | wc -l ; }
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
INCLUED='inclued-alpha'
XDEBUG='xdebug'
MEMCACHE='memcache'

SAVANT='http://phpsavant.com/Savant3-3.0.0.tgz'
FIREPHP_CHANNEL='pear.firephp.org'
FIREPHP='FirePHPCore'

WEBGRIND='webgrind'
WEBGRIND_VERSION='1.0'
WEBGRIND_PKG="${WEBGRIND}-release-${WEBGRIND_VERSION}"
WEBGRIND_URL="http://webgrind.googlecode.com/files/${WEBGRIND_PKG}.zip"
# }}}
# Make directories {{{
if [ ! -d packages ]; then
    mkdir packages
fi
# }}}
# Install/update PEAR {{{
if [ `which pear` ]; then
    $SUDO pear config-set php_bin $PHP
    $SUDO pear upgrade pear
else
    $SUDO $PHP -q bs/go-pear.php
fi
if [ `which pecl` ]; then
    $SUDO pear config-set php_ini $PHP_INI
    $SUDO pecl config-set php_ini $PHP_INI
fi
# }}}
# Install APC {{{
if [ `$PHP_EXT_TEST apc` ]; then
    echo '### APC INSTALLED';
    $SUDO pecl upgrade $APC
else
    echo '### INSTALLING APC';
    $SUDO pecl install $APC
    echo 'be sure to add to your php.ini: extension=apc.so'
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
    echo 'be sure to add to your php.ini: extension=runkit.so'
fi
# }}}
# Install XDEBUG {{{
if [ `$PHP_EXT_TEST xdebug` ]; then
    echo '### XDEBUG INSTALLED';
    $SUDO pecl upgrade $XDEBUG
else
    echo '### INSTALLING XDEBUG';
    $SUDO pecl install $XDEBUG
    echo 'be sure to add to your php.ini: zend_extension="<something>/xdebug.so"'
fi
# }}}
# Install inclued {{{
if [ `$PHP_EXT_TEST inclued` ]; then
    echo '### INCLUED INSTALLED';
    $SUDO pecl upgrade $INCLUED
else
    echo '### INSTALLING INCLUED';
    $SUDO pecl install $INCLUED
    echo 'be sure to add to your php.ini: extension=inclued.so'
fi
# }}}
# Install memcache {{{
if [ `$PHP_EXT_TEST memcache` ]; then
    echo '### MEMCACHE INSTALLED';
    $SUDO pecl upgrade $MEMCACHE
else
    echo '### INSTALLING MEMCACHE';
    $SUDO pecl install $MEMCACHE
    echo 'be sure to add to your php.ini: extension=memcache.so'
fi
# }}}
# Install PEAR::Savant3 {{{
if [ `pear_installed Savant3` != '0' ]; then
#    $SUDO pear upgrade savant/$SAVANT
    echo "No way of upgrading Savant3"
else
#    $SUDO pear channel-discover savant.pearified.com
#    $SUDO pear install savant/$SAVANT
    $SUDO pear install $SAVANT
fi
# }}}
# Install PEAR::FirePHP {{{
if [ `pear_installed firephp/FirePHPCore` ]; then
    $SUDO pear upgrade firephp/$FIREPHP
else
    $SUDO pear channel-discover $FIREPHP_CHANNEL
    $SUDO pear install firephp/$FIREPHP
fi
# }}}
# Install samples {{{
pushd samples
    if [ ! -d traces ]; then
        mkdir traces
        chmod 777 traces
    fi
    if [ ! -d inclued ]; then
        mkdir inclued
        chmod 777 inclued
    fi
    if [ ! -f www/.htaccess ]; then
        echo "### Building .htaccess file for samples"
        cat res/default.htaccess | sed "s|{{{BASE_DIR}}}|${BASE_DIR}|" >www/.htaccess
    fi
popd
# }}}
# Install WebGrind {{{
pushd packages
    if [ ! -f ${WEBGRIND_PKG}.zip ]; then
        echo "### Downloading $WEBGRIND_URL"
        curl -O $WEBGRIND_URL;
    fi
popd
pushd samples/www
    if [ ! -d ${WEBGRIND} ]; then
        echo "### Unpacking ${WEBGRIND_PKG}.zip"
        unzip $BASE_DIR/packages/${WEBGRIND_PKG}.zip
    fi
    pushd $WEBGRIND
        if [ ! -f .htaccess ]; then
            cp ../../res/webgrind.htaccess .htaccess
            echo "### Update profilerDir to point to $BASE_DIR/samples/traces"
            vim +20 config.php
        fi
    popd
popd
# }}}
echo '### You may need to add  stuff to your php.ini and restart'
$SUDO $APACHECTL restart
