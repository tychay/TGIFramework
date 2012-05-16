#!/bin/bash
# vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker:

# This boostraps the framework, be sure to execute from base directory (not
# this directory) i.e.: $ ./bs/bootstrap.sh

#DO_UPGRADE='1' #Set this to upgrade
# EDITME: Set the full path to binaries {{{
if [ $1 ]; then
    DISTRIBUTION=$1
else
    if [ "`which port`" != '' ]; then
        DISTRIBUTION='macports'
    elif [ "`which yum`" != '' ]; then
        DISTRIBUTION='fedora'
    elif [ "`which apt-get`" != '' ]; then
        DISTRIBUTION='ubuntu'
    else
        DISTRIBUTION='???'
    fi
fi
echo "### Distribution is $DISTRIBUTION";
# Should it run as sudo? 
SUDO='sudo'

# execute from the base directory not this one
BASE_DIR=`pwd`

PHP=`which php`
if [ $PHP != '/usr/bin/php' ]; then
    echo "### Do to env POSIXness on Linux, we can not depend on /usr/bin/env. Files such as generate_gloabl_version.php assumes PHP are located at /usr/bin/php which is not the case for you. You may need to update these bin/* scripts for this to work."
fi
APACHECTL=`which apachectl`
# Set this to php-memcached instead of php-memcache
LIBMEMCACHED=""
PHP_INI=/etc/php.ini # TODO: check php --ini

# MacPorts: {{{
if [ $DISTRIBUTION = "macports" ]; then
# Instructions for installing: {{{
# http://forums.macnn.com/79/developer-center/322362/tutorial-installing-apache-2-php-5-a/
# 0) Have XCode installed
# 1) install MacPorts Pkg http://www.macports.org/install.php
# 2) create a new terminal
# 3) $ sudo port -v selfupdate                          #update ports
# 4) $ sudo port install apache2                        #install apache
#    $ sudo port install mysql5 +server                 #install mysql
#    $ sudo port install php5 +apache2 +pear
#                                                       #install php+pear
# 5) $ cd /opt/local/apache2/modules                    $install mod_php
#    $ sudo /opt/local/apache2/bin/apxs -a -e -n "php5" libphp5.so
# 6) $ sudo vim /opt/local/apache2/conf/httpd.conf
#    #DocumentRoot "/opt/local/apache2/htdocs"          #preserve default site
#    DocumentRoot "/Library/WebServer/Documents"
#    ...
#    # User home directories
#    Include conf/extra/httpd-userdir.conf              # user home dirs
#    Include conf/extra/mod_php.conf                    # mod php loader
#    # If you generated a cert into: /opt/local/apache2/conf/server.crt
#    Include conf/extra/httpd-ssl.conf                  # ssl support
#    #also consider conf/extra/httpd-autoindex.conf (Fancy directory listing)
#    #              conf/extra/httpd-default.conf (Some default settings)
#    #              conf/extra/httpd-vhosts.conf (virtual hosts)
#    ....
#    #DirectoryIndex index.html
#    DirectoryIndex index.html index.php
# 6) $ vim ~/.profile
#    alias apache2ctl='sudo /opt/local/apache2/bin/apachectl'
#    alias mysqlstart='sudo mysqld_safe5 &'
#    alias mysqlstop='mysqladmin5 -u root -p shutdown' 
#    
#    # remember to start a new shell
# 7) $ sudo launchctl load -w /Library/LaunchDaemons/org.macports.apache2.plist
#    $ sudo launchctl load -w /Library/LaunchDaemons/org.macports.mysql5.plist
# 8) $ sudo mkdir /opt/local/var/db/mysql5
#    $ sudo chown mysql:mysql /opt/local/var/db/mysql5
#    $ sudo -u mysql mysql_install_db5
#    $ mysqlstart
#    $ mysqladmin5 -u root password [yourpw]
# 9) $ sudo cp /opt/local/etc/php5/php.ini-production /opt/local/etc/php5/php.ini
# 10)# TODO: PDO mysql is missing!!!!  # http://c6s.co.uk/webdev/119
#    $ sudo port install php5-sqlite 
#    $ sudo port install php5-mysql 
#    $ sudo port install php5-tidy 
#    $ sudo port install php5-zip 
#    $ sudo port install php5-curl 
#    $ sudo port install php5-big_int   #bcmath substitute
#    # copy other ini files as necessary (most of them are in res/php.ini, but
#    # be sure to edit xdebug.ini before copying
# 11)$ apache2ctl start
# AFTER:
# --)$ sudo port load memcached
# install sqlite3
# }}}
    if [ $DO_UPGRADE ]; then
        $SUDO port -v selfupdate
        #$SUDO port upgrade outdated
    fi
    $SUDO port install memcached
    #PHP=/opt/local/bin/php
    APACHECTL=/opt/local/apache2/bin/apachectl
    #PHP_INI=/opt/local/etc
    PHP_INI=/opt/local/etc/php5/php.ini
    # Set path to libmemcached (to use php-memcached instead of php-memcache)
    LIBMEMCACHED=/opt/local
fi
# }}}
# Fedora/CentOS: {{{
if [ $DISTRIBUTION = 'fedora' ]; then
    # Set path to libmemcached (to use php-memcached instead of php-memcache)
    LIBMEMCACHED=/usr
fi
# }}}
# Ubuntu/Debian: {{{
if [ $DISTRIBUTION = 'ubuntu' ]; then
    check_dpkg() { dpkg -l $1 | grep ^ii | wc -l; }
    # Set path to libmemcached (to use php-memcached instead of php-memcache)
    LIBMEMCACHED=/usr
    # ubuntu has separate ini files for apache vs. cli.
    PHP_INI=/etc/php5/apache2/php.ini
    # build environment for installing on ubuntu
    if [ $DO_UPGRADE ]; then
        $SUDO apt-get update
    fi
    # Need libpcre3-dev to compile APC
    if [ `check_dpkg libpcre3-dev` ]; then
        $SUDO apt-get install libpcre3-dev
    fi
    # Need curl to grab packages
    if [ `check_dpkg curl` ]; then
        $SUDO apt-get install curl
    fi
    # Needed to unzip YUI packages
    if [ `check_dpkg zip` ]; then
        $SUDO apt-get install zip
    fi
    # Needed to execute YUI compressor
    if [ `check_dpkg default-jre` ]; then
        $SUDO apt-get install default-jre
    fi
    # Needed to generate version numbers
    if [ `check_dpkg git` ]; then
        $SUDO apt-get install git
    fi
    echo "### REMEMBER! On ubuntu, there are two different directories for CLI PHP and APACHE2 PHP configuration. Both must be updated for this script to work properly"
fi
# }}}
# }}}
# shell function declarations {{{
pear_installed () { pear list -a | grep ^$1 | wc -l ; }
# {{{  pecl_update_or_install()
# $1 = package name
# $2 = package name in pecl (may have -beta or the like)
# $3 = if set, yum package name
# $4 = if set, package name in macports
# $5 = if set, package name in ubuntu
pecl_update_or_install () {
    if [ `$PHP_EXT_TEST $1` ]; then
        if [ $DO_UPGRADE ]; then
            if [ "$DISTRIBUTION" = 'fedora' ] && [ "$3" != '' ]; then
                echo "### UPDATING $1...";
                $SUDO yum update $3
            elif [ "$DISTRIBUTION" = 'macports' ] && [ "$4" != '' ]; then
                echo "### $1 is already up-to-date"
            elif [ "$DISTRIBUTION" = 'ubuntu' ] && [ "$5" != '' ]; then
                echo "### UPDATING $1...";
                $SUDO apt-get update $5
            else
                echo "### UPGRADING $1...";
                $SUDO pecl upgrade $2
            fi
        fi
    else
        echo "### INSTALLING $1";
        if [ "$DISTRIBUTION" = 'fedora' ] && [ "$3" != '' ]; then
            $SUDO yum install $3
        elif [ "$DISTRIBUTION" = 'macports' ] && [ "$4" != '' ]; then
            $SUDO port install $3
        elif [ "$DISTRIBUTION" = 'ubuntu' ] && [ "$5" != '' ]; then
            $SUDO apt-get install $5
        else
            $SUDO pecl install $2
            if [ "$1" = 'xdebug' ]; then
                echo '### Be sure to add to your php.ini: zend_extension="<something>/xdebug.so" NOT! extension=xdebug.so'
            else
                echo "### Be sure to add to your php.ini: extension=$1.so"
            fi
        fi
        PACKAGES_INSTALLED="$1 $PACKAGES_INSTALLED"
    fi
}
# }}}
# {{{  pear_update_or_install()
# $1 = package name
# $2 = package name in pear (may have -beta or the like)
# $3 = pear channel
pear_update_or_install () {
    if [ $2 ]; then
        pkg_path=$2;
    else
        pkg_path=$1;
    fi
    if [ `pear_installed $1` ]; then
        echo "### UPGRADING $1...";
        $SUDO pear upgrade $pkg_path
    else
        echo "### INSTALLING $1";
        if [ $3 ]; then
            $SUDO pear channel-discover $3
        fi
        $SUDO pear install $pkg_path
    fi
}
# }}}
# }}}
# UTILS {{{
PHP_EXT_TEST=$BASE_DIR/bs/extension_installed.php
PHP_VERSION_TEST=$BASE_DIR/bs/version_compare.php
# }}}
# PACKAGES {{{
# php extensions {{{
# RUNKIT {{{
#RUNKIT='runkit'
# Runkit is still in beta.
# New version at https://github.com/zenovich/runkit/
RUNKIT='channel://pecl.php.net/runkit-0.9'
# Note that Runkit 0.9 doesn't compile in PHP 5.2+
if [ `$PHP_VERSION_TEST 5.2` ]; then
    RUNKIT='cvs'
fi
# }}}
# APC {{{
#http://pecl.php.net/package/apc
APC='apc'
if [ `$PHP_VERSION_TEST 5.3` ]; then
    APC='apc-beta'
fi
#APC='http://pecl.php.net/get/APC'
# }}}
INCLUED='inclued-beta' #2010-02-22 it went beta, see http://pecl.php.net/package/inclued
# MEMCACHE {{{
MEMCACHE_PKG='memcache'
MEMCACHE='memcache'
MEMCACHE_PORT=''
if [ $LIBMEMCACHED ]; then
    MEMCACHE='memcached-beta'
    MEMCACHE_PKG='memcached'
    MEMCACHE_PORT='php5-memcached +igbinary'
fi
# }}}
# }}}
# pear packages {{{
#SAVANT='http://phpsavant.com/Savant3-3.0.0.tgz'
#FIREPHP_CHANNEL='pear.firephp.org'
#FIREPHP='FirePHPCore'
#PHPDOC='PhpDocumentor'
# }}}
# downloads {{{
# YUI & YUI compressor {{{
YUI='yui'
YUI_VERSION='2.9.0'
YUI_BIN="yui_${YUI_VERSION}"
YUI_PKG="${YUI_BIN}.zip"
#YUI_URL="http://yuilibrary.com/downloads/yui2/${YUI_PKG}"
YUI_URL="http://yui.zenfs.com/releases/yui2/${YUI_PKG}"

YUIC='yuicompressor'
YUIC_VERSION='2.4.7'
YUIC_BIN="${YUIC}-${YUIC_VERSION}"
YUIC_PKG="${YUIC_BIN}.zip"
#YUIC_URL="http://www.julienlecomte.net/yuicompressor/${YUIC_PKG}"
YUIC_URL="http://yui.zenfs.com/releases/yuicompressor/${YUIC_PKG}"
# }}}
# WEBGRIND {{{
WEBGRIND='webgrind'
WEBGRIND_VERSION='1.0'
WEBGRIND_BIN="${WEBGRIND}-release-${WEBGRIND_VERSION}"
WEBGRIND_PKG="${WEBGRIND_BIN}.zip"
WEBGRIND_URL="http://webgrind.googlecode.com/files/${WEBGRIND_PKG}"
# }}}
# RUNKIT {{{
RUNKIT='runkit'
RUNKIT_VERSION='1.0.3'
RUNKIT_DIR="${RUNKIT}-${RUNKIT_VERSION}"
RUNKIT_PKG="${RUNKIT_DIR}.tgz"
RUNKIT_URL="https://github.com/downloads/zenovich/runkit/${RUNKIT_PKG}"
# }}}
# }}}
PACKAGES_INSTALLED=""
# }}}
# Make directories {{{
if [ ! -d packages ]; then
    mkdir packages
fi
if [ ! -d build ]; then
    mkdir build
fi
# }}}
# Install/update PEAR {{{
if [ `which pear` ]; then
    $SUDO pear config-set php_bin $PHP
    if [ $DO_UPGRADE ]; then
        $SUDO pear list-upgrades
        if [ $DISTRIBUTION = 'fedora' ]; then
            $SUDO pear uninstall apc
            $SUDO pear uninstall memcache
        fi
        $SUDO pear upgrade-all
        $SUDO pear channel-update pear.php.net
        $SUDO pear channel-update pecl.php.net
    fi
else
    $SUDO $PHP -q bs/go-pear.php
fi
if [ `which pecl` ]; then
    $SUDO pear config-set php_ini $PHP_INI
    $SUDO pecl config-set php_ini $PHP_INI
fi
# }}}
# Install APC {{{
pecl_update_or_install apc $APC php-pecl-apc php5-apc php-apc
# }}}
# Install runkit {{{
if [ `$PHP_EXT_TEST runkit` ]; then
    if [ $DO_UPGRADE ]; then
        echo '### UPGRADING RUNKIT....';
        if [ $RUNKIT != 'cvs' ]; then
            $SUDO pecl upgrade $RUNKIT
        fi
    fi
else
    echo '### INSTALLING RUNKIT';
# TODO: add test for PHP 5.2
    if [ $RUNKIT != 'cvs' ]; then
        $SUDO pecl install $RUNKIT
        RUNKIT="$BASE_DIR/packages/pecl/runkit"
    else
        pushd packages
            if [ ! -f $RUNKIT_PKG ]; then
                curl -L -O $RUNKIT_URL
            fi
            if [ ! -d $RUNKIT_DIR ]; then
                tar xvf $RUNKIT_PKG
            fi
            pushd $RUNKIT_DIR
                make distclean
                phpize
                chmod a+x ./configure
                ./configure --enable-runkit
                make
                make test
                $SUDO make install
            popd
            #if [ ! -d pecl/runkit ]; then
            #    # PHP migrated to svn
            #    #cvs -d :pserver:cvsread@cvs.php.net:/repository checkout  pecl/runkit
            #    svn co http://svn.php.net/repository/pecl/runkit/trunk/ pecl/runkit
            #fi
            #pushd pecl/runkit
            #    #cvs update
            #    svn update
            #    make distclean
            #    # Apply patch for bug http://pecl.php.net/bugs/bug.php?id=13363
            #    patch -p0 <$BASE_DIR/bs/runkit-bug13363.diff 
            #    phpize
            #    ./configure --enable-runkit
            #    make
            #    make test
            #    $SUDO make install
            #popd
        popd
    fi
    echo 'be sure to add to your php.ini: extension=runkit.so'
fi
# }}}
# Install igbinary {{{
# TODO: -enable-memcached-igbinary (in php-pecl-memcached)
# igbinary http://opensource.dynamoid.com/
# performance settings: http://ilia.ws/archives/211-Igbinary,-The-great-serializer.html#extended  
#if [ `$PHP_EXT_TEST igbinary` ]; then
#    echo '### igbinary installed'
#else
#    if [ $DISTRIBUTION = 'macports' ]; then
#        $SUDO port install php5-igbinary
#    else
#        pushd packages
#            curl -O http://opensource.dynamoid.com/igbinary-1.0.2.tar.gz
#        popd
#        pushd build
#            gzip -dc ../packages/igbinary-1.0.2.tar.gz | tar xf -
#            pushd igbinary-1.0.2
#                phpize
#                ./configure
#                make
#                $SUDO make install
#            popd
#        popd
#    fi
#fi
pecl_update_or_install igbinary igbinary '' php5-igbinary
# }}}
# Install XDEBUG {{{
pecl_update_or_install xdebug xdebug php-pecl-xdebug php5-xdebug php5-xdebug
# }}}
# Install big_int {{{ http://pecl.php.net/package/big_int
#pecl_update_or_install big_int big_int
# BUG: Cannot find config.m4 (in big_int-1.0.7)
if [ `$PHP_EXT_TEST big_int` ]; then
    if [ $DO_UPGRADE ]; then
        echo "### No way to hande upgrading with big_int currently"
    fi
else
    #$SUDO pecl install $2
    pushd packages
        $SUDO pecl download big_int
        tar zxf big_int-*.tgz
        pushd big_int-*
            phpize
            chmod a+x configure
            ./configure
            make
            sudo make install
        popd
    popd
    echo "### Be sure to add to your php.ini: extension=$1.so"
    PACKAGES_INSTALLED="big_int $PACKAGES_INSTALLED"
fi
#echo "### big_int..."
#if [ `$PHP_EXT_TEST big_int` ]; then
#    $SUDO pecl upgrade big_int
#else
#    $SUDO pecl install big_int
#fi
# }}}
# Install inclued {{{
# No fedora package for inclued
pecl_update_or_install inclued $INCLUED '' ''
# }}}
# Install mailparse {{{
# Needed fr parsing RFC822 e-mails
pecl_update_or_install mailparse mailparse php-pecl-mailparse php5-mailparse
# }}}
# Install memcache(d) with igbinary {{{
if [ $DISTRIBUTION = 'fedora' ] && [ $MEMCACHE = 'memcached' ]; then
    if [ `$PHP_EXT_TEST $1` ]; then
        echo "### memcached already installed, doing nothing"
    else
        rpm -Uvh http://rpms.famillecollet.com/remi-release-12.rpm
        $SUDO rpm -Uvh http://rpms.famillecollet.com/remi-release-12.rpm
        $SUDO yum --enablerepo=remi install libmemcached php-pecl-memcached

#        $SUDO yum install libmemcached-devel
#        pushd packages
#            pecl download memcached
#            curl -O http://launchpadlibrarian.net/56440579/libmemcached-0.44.tar.gz
#        popd
#        pushd build
#            rm -rf *
#            mkdir mybuild\
#                mybuild/BUILD\
#                mybuild/RPMS\
#                mybuild/RPMS/i386\
#                mybuild/SOURCES\
#                mybuild/SPECS\
#                mybuild/SRPMS
#            cp ../packages/memcached*.tgz mybuild/SOURCES
#            cp ../packages/libmemcached*.tar.gz mybuild/SOURCES
#            #cat "topdir: ${BASE_DIR}/build/mybuild" > ~/.rpmrc
#            rpmbuild -bb --define "_topdir ${BASE_DIR}/build/mybuild" ../res/libmemcached.spec
#            cp mybuild/RPMS/x86_64/libmemcached-0.44-1.fc12.x86_64.rpm ../packages
#            cp mybuild/RPMS/x86_64/libmemcached-devel-0.44-1.fc12.x86_64.rpm ../packages
#            $SUDO rpm -i ../packages/libmemcached-*.rpm
#            rpmbuild -bb --define "_topdir ${BASE_DIR}/build/mybuild" ../res/php-pecl-memcached.spec
#            cp mybuild/RPMS/x86_64/php-pecl-memcached-1.0.2-1.fc12.x86_64.rpm ../packages
#            $SUDO rpm -i ../packages/php-pecl-memcached*.rpm
#            #gzip -dc ../packages/memcached*.tgz | tar xf -
#            #pushd memcached*
#            #    phpize
#            #    ./configure --with-libmemcached-dir=${LIBMEMCACHED} -enable-memcached-igbinary
#            #    make
#            #    $SUDO make install
#            #popd
#        popd
    fi
else
    pecl_update_or_install $MEMCACHE_PKG $MEMCACHE php-pecl-$MEMCACHE "$MEMCACHE_PORT" php5-memcached
fi
# }}}
# Install PEAR packages: {{{ Savant, FirePHP, PhpDocumentor
# Old download was: SAVANT='http://phpsavant.com/Savant3-3.0.0.tgz'
# Old Savant PEAR repository   $SUDO pear channel-discover savant.pearified.com
$SUDO pear channel-discover phpsavant.com
pear_update_or_install Savant3 savant/Savant3 phpsavant.com
#echo '### NB: There is a bug in Savant PHP Fatal error:  Method Savant3::__tostring() cannot take arguments in /usr/share/pear/Savant3.php on line 241'
$SUDO pear channel-discover pear.firephp.org
pear_update_or_install FirePHPCore firephp/FirePHPCore pear.firephp.org
pear_update_or_install PhpDocumentor
# }}}
# FRAMEWORK: Install YUI && YUI Compressor {{{
pushd packages
    if [ ! -f ${YUI_PKG} ]; then
        echo "### Downloading $YUI_URL..."
        curl -O $YUI_URL;
    fi
    if [ ! -f ${YUIC_PKG} ]; then
        echo "### Downloading $YUIC_URL..."
        curl -O $YUIC_URL;
    fi
popd
pushd build
    if [ ! -d yui ]; then
        echo "### Unpacking ${YUI_PKG}..."
        unzip $BASE_DIR/packages/${YUI_PKG}
    fi
    if [ ! -d ${YUIC_BIN} ]; then
        echo "### Unpacking ${YUIC_PKG}..."
        unzip $BASE_DIR/packages/${YUIC_PKG}
    fi
popd
pushd framework/res
    if [ ! -f ${YUIC_BIN}.jar ]; then
        echo "### INSTALLING ${YUIC_BIN}.jar..."
        cp $BASE_DIR/build/$YUIC_BIN/build/${YUIC_BIN}.jar .
    fi
popd
# }}}
if [ "$PACKAGES_INSTALLED" ]; then
    echo '### You may need to add stuff to your $PHP_INI (or /etc/php.d/) and restart'
    echo "###  $PACKAGES_INSTALLED"
fi
$SUDO $APACHECTL graceful
