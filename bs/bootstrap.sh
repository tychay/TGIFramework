#!/bin/sh
# vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker:
# This boostraps the framework, be sure to execute from base directory
# Should it run as sudo? {{{
SUDO='sudo'
# }}}
# Set the full path to binaries {{{
BASE_DIR=`pwd`
PHP=`which php`
APACHECTL=`which apachectl`
# MacPorts
#PHP=/opt/local/bin/php
APACHECTL=/opt/local/apache2/bin/apachectl
# }}}
# PACKAGES {{{
# Runkit is still in beta. Note that Runkit 0.9 doesn't compile in CVS
RUNKIT='runkit'
#RUNKIT='channel://pecl.php.net/runkit-0.9'
#RUNKIT=''

APC='apc'
#APC=''

MEMCACHE='memcache'
#MEMCACHE=''

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
# }}}
# Install runkit {{{
if [ "$RUNKIT" != "" ]; then
# TODO: add test for PHP 5.2
if [ "TRUE" ]; then
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
if [ "$APC" != "" ]; then
$SUDO pecl install $APC
fi
# }}}
# Install memcache {{{
if [ "$MEMCACHE" != "" ]; then
$SUDO pecl install $MEMCACHE
fi
# }}}
# Install Savant3 {{{
if [ "$SAVANT" != "" ]; then
$SUDO pear install $SAVANT
fi
# }}}
echo You may need to add 'extension=apc.so' and restart.
$SUDO $APACHECTL restart
