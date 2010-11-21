#!/bin/sh
# vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker:
# This boostraps the framework, be sure to execute from base directory (not this directory) i.e.: $ ./bs/bootstrap.sh
# TODO: -enable-memcached-igbinary (in php-pecl-memcached), igbinary http://opensource.dynamoid.com/, settings http://ilia.ws/archives/211-Igbinary,-The-great-serializer.html#extended  

# EDITME: Set the full path to binaries {{{
if [ $1 ]; then
    DISTRIBUTION=$1
else
    #DISTRIBUTION='macports'
    DISTRIBUTION='fedora'
fi
# Should it run as sudo? 
SUDO='sudo'

# execute from the base directory not this one
BASE_DIR=`pwd`

PHP=`which php`
APACHECTL=`which apachectl`
# Set this to php-memcached instead of php-memcache
LIBMEMCACHED=""
PHP_INI=/etc/php.ini # TODO: check php --ini
DO_UPGRADE='1' #Set this to upgrade

# MacPorts:
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
    $SUDO port -v selfupdate
    $SUDO port install memcached
    #$SUDO port upgrade outdated
    #PHP=/opt/local/bin/php
    APACHECTL=/opt/local/apache2/bin/apachectl
    #PHP_INI=/opt/local/etc
    PHP_INI=/opt/local/etc/php5/php.ini
    # Set path to libmemcached (to use php-memcached instead of php-memcache)
    LIBMEMCACHED=/opt/local
fi

if [ $DISTRIBUTION = 'fedora' ]; then
    # Set path to libmemcached (to use php-memcached instead of php-memcache)
    LIBMEMCACHED=/usr/lib64
fi
# }}}
# shell function declarations {{{
pear_installed () { pear list -a | grep ^$1 | wc -l ; }
# {{{  pecl_update_or_install()
# $1 = package name
# $2 = package name in pecl (may have -beta or the like)
# $3 = if set, package name in macports
pecl_update_or_install () {
    if [ `$PHP_EXT_TEST $1` ]; then
        if [ $DO_UPGRADE ]; then
            if [ $DISTRIBUTION = 'fedora' ]; then
                echo "### UPDATING $1...";
                $SUDO yum update php-pecl-$1
            elif [ $DISTRIBUTION = 'macports' ] && [ "$3" != '' ]; then
                echo "### $1 is already up-to-date"
            else
                echo "### UPGRADING $1...";
                $SUDO pecl upgrade $2
            fi
        fi
    else
        echo "### INSTALLING $1";
        if [ $DISTRIBUTION = 'fedora' ]; then
            $SUDO yum install php-pecl-$1
        elif [ $DISTRIBUTION = 'macports' ] && [ "$3" != '' ]; then
            $SUDO port install $3
        else
            $SUDO pecl install $2
            if [ $1 = 'xdebug']; then
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
XDEBUG='xdebug'
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
# WEBGRIND {{{
YUIC='yuicompressor'
YUIC_VERSION='2.4.2'
YUIC_BIN="${YUIC}-${YUIC_VERSION}"
YUIC_PKG="${YUIC_BIN}.zip"
YUIC_URL="http://www.julienlecomte.net/yuicompressor/${YUIC_PKG}"
# }}}
# }}}
# web software {{{
# WEBGRIND {{{
WEBGRIND='webgrind'
WEBGRIND_VERSION='1.0'
WEBGRIND_BIN="${WEBGRIND}-release-${WEBGRIND_VERSION}"
WEBGRIND_PKG="${WEBGRIND_BIN}.zip"
WEBGRIND_URL="http://webgrind.googlecode.com/files/${WEBGRIND_PKG}"
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
            $SUDO pear uninstall APC
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
pecl_update_or_install apc $APC php5-apc
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
            if [ ! -d pecl/runkit ]; then
                # PHP migrated to svn
                #cvs -d :pserver:cvsread@cvs.php.net:/repository checkout  pecl/runkit
                svn co http://svn.php.net/repository/pecl/runkit/trunk/ pecl/runkit
            fi
            pushd pecl/runkit
                #cvs update
                svn update
                make distclean
                # Apply patch for bug http://pecl.php.net/bugs/bug.php?id=13363
                patch -p0 <$BASE_DIR/bs/runkit-bug13363.diff 
                phpize
                ./configure --enable-runkit
                make
                make test
                $SUDO make install
            popd
         popd
    fi
    echo 'be sure to add to your php.ini: extension=runkit.so'
fi
# }}}
# Install XDEBUG {{{
pecl_update_or_install igbinary igbinary php5-igbinary
# }}}
# Install XDEBUG {{{
pecl_update_or_install xdebug $XDEBUG php5-xdebug
# }}}
# Install big_int {{{ http://pecl.php.net/package/big_int
echo "### big_int..."
if [ `$PHP_EXT_TEST big_int` ]; then
    $SUDO pecl upgrade big_int
else
    $SUDO pecl install big_int
fi
# }}}
# Install inclued {{{
# No fedora package for inclued
if [ $DISTRIBUTION = 'fedora' ]; then
    DISTRIBUTION='xfedora';
fi
pecl_update_or_install inclued $INCLUED ''
if [ $DISTRIBUTION = 'xfedora' ]; then
    DISTRIBUTION='fedora';
fi
# }}}
# Install memcache {{{
pecl_update_or_install $MEMCACHE_PKG $MEMCACHE "$MEMCACHE_PORT"
#pushd packages
#pecl download $MEMCACHE
#popd
#pushd build
#rm -rf *
#gzip -dc ../packages/${MEMCACHE_PKG}*.tgz | tar xf -
#pushd ${MEMCACHE_PKG}*
#phpize
#./configure --with-libmemcached-dir=${LIBMEMCACHED}
#make
#$SUDO make install
#popd
#popd
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
# Install YUI Compressor {{{
pushd packages
    if [ ! -f ${YUIC_PKG} ]; then
        echo "### Downloading $YUIC_URL..."
        curl -O $YUIC_URL;
    fi
popd
pushd build
    if [ ! -d ${YUIC_BIN} ]; then
        echo "### Unpacking ${YUIC_PKG}..."
        unzip $BASE_DIR/packages/${YUIC_PKG}
    fi
popd
pushd framework/bin
    if [ ! -f ${YUIC_BIN}.jar ]; then
        echo "### INSTALLING ${YUIC_BIN}.jar..."
        cp $BASE_DIR/build/$YUIC_BIN/build/${YUIC_BIN}.jar .
    fi
popd
# }}}
# Install samples {{{
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
popd
# }}}
# Install WebGrind {{{
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
            echo "### Update profilerDir to point to $BASE_DIR/samples/traces"
            vim +20 config.php
        fi
    popd
popd
# }}}
#echo "### Running phpdoc"
#./bs/phpdoc.sh
if [ "$PACKAGES_INSTALLED" ]; then
    echo '### You may need to add stuff to your $PHP_INI (or /etc/php.d/) and restart'
    echo "###  $PACKAGES_INSTALLED"
fi
$SUDO $APACHECTL graceful
