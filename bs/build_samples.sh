#!/bin/bash
# vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker:
#
# This installs the samples website. Be sure to have bootstrapped everything
# first and execute from the base directory
# e.g.
#
#   ./bs/build_samples.php
#
# This boostraps the framework, be sure to execute from base directory (not this directory) i.e.: $ ./bs/bootstrap.sh

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
# }}}
# }}}
# Make directories {{{
if [ ! -d packages ]; then
    mkdir packages
fi
if [ ! -d build ]; then
    mkdir build
fi
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
pushd samples/www/m/res
    if [ ! -d yui ]; then
        mkdir yui
    fi
    if [ ! -d yui/${YUI_VERSION} ]; then
        echo "### INSTALLING yui/${YUI_VERSION}..."
        mv $BASE_DIR/build/yui ./yui/${YUI_VERSION}
    fi
popd
# }}}
# SAMPLES: Install samples {{{
echo "### Building global_version config...."
./framework/bin/generate_global_version.php samples/config/global_version.php
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
        echo "### Building .htaccess file for samples...."
        cat res/default.htaccess | sed "s|{{{BASE_DIR}}}|${BASE_DIR}|" >www/.htaccess
    fi
    if [ ! -d www/m/dyn ]; then
        mkdir www/m/dyn
        chmod 777 www/m/dyn
    fi
popd
# }}}
# SAMPLES: Install WebGrind {{{
pushd packages
    if [ ! -f ${WEBGRIND_PKG} ]; then
        echo "### Downloading $WEBGRIND_URL..."
        curl -O $WEBGRIND_URL;
    fi
popd
pushd samples/www
    if [ ! -d ${WEBGRIND} ]; then
        echo "### Unpacking ${WEBGRIND_PKG}..."
        unzip $BASE_DIR/packages/${WEBGRIND_PKG}
    fi
    pushd $WEBGRIND
        if [ ! -f .htaccess ]; then
            cp ../../res/webgrind.htaccess .htaccess
            echo "### Update profilerDir to point to $BASE_DIR/samples/traces:"
            read IGNORE
            vim +20 config.php
        fi
    popd
popd
# }}}
#echo "### Running phpdoc"
#./bs/phpdoc.sh
$SUDO $APACHECTL graceful
