#!/bin/sh
# vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker:
# This boostraps the framework, be sure to execute from base directory (not this directory) i.e.: $ ./bs/bootstrap.sh
# EDITME: Set the full path to binaries {{{
# Should it run as sudo? {{{
SUDO='sudo'
# }}}
BASE_DIR=`pwd`
PHP=`which php`
APACHECTL=`which apachectl`
LIBMEMCACHED=""
PHP_INI=/etc/php.ini # TODO: check php --ini
DISTRIBUTION='fedora'
DO_UPGRADE='' #Set this to upgrade

# MacPorts
if [ $DISTRIBUTION = "macports" ]; then
    #PHP=/opt/local/bin/php
    APACHECTL=/opt/local/apache2/bin/apachectl
    #PHP_INI=/opt/local/etc
    PHP_INI=/opt/local/etc/php.ini
fi

# Path to libmemcached (to use php-memcached instead of php-memcache)
#LIBMEMCACHED=/opt/local
# }}}
# shell function declarations {{{
pear_installed () { pear list -a | grep ^$1 | wc -l ; }
# {{{  pecl_update_or_install()
# $1 = package name
# $2 = package name in pecl (may have -beta or the like)
pecl_update_or_install () {
    if [ `$PHP_EXT_TEST $1` ]; then
        if [ $DO_UPGRADE ]; then
            if [ $DISTRIBUTION = 'fedora' ]; then
                $SUDO yum update php-pecl-$1
            else
                echo "### UPGRADING $1...";
                $SUDO pecl upgrade $2
            fi
        fi
    else
        if [ $DISTRIBUTION = 'fedora' ]; then
            $SUDO yum install php-pecl-$1
        else
            echo "### INSTALLING $1";
            $SUDO pecl install $2
            if [ $1 = 'xdebug']; then
                echo 'be sure to add to your php.ini: zend_extension="<something>/xdebug.so" NOT! extension=xdebug.so'
            else
                echo "Be sure to add to your php.ini: extension=$1.so"
            fi
        fi
        $PACKAGES_INSTALLED="$1 $PACKAGES_INSTALLED"
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
APC='apc'
if [ `$PHP_VERSION_TEST 5.3` ]; then
    APC='apc-beta'
fi
#APC='http://pecl.php.net/get/APC'
# }}}
INCLUED='inclued-alpha'
XDEBUG='xdebug'
# MEMCACHE {{{
MEMCACHE_PKG='memcache'
MEMCACHE='memcache'
if [ $LIBMEMCACHED ]; then
    MEMCACHE='memcached-beta'
    MEMCACHE_PKG='memcached'
fi
# }}}
# }}}
# pear packages {{{
SAVANT='http://phpsavant.com/Savant3-3.0.0.tgz'
# FIREPHP {{{
FIREPHP_CHANNEL='pear.firephp.org'
FIREPHP='FirePHPCore'
# }}}
PHPDOC='PhpDocumentor'
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
pecl_update_or_install xdebug $XDEBUG
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
pecl_update_or_install xdebug $XDEBUG
# }}}
# Install inclued {{{
if [ `$PHP_EXT_TEST inclued` ]; then
    if [ $DO_UPGRADE ]; then
        echo '### UPGRADING INCLUED...';
        $SUDO pecl upgrade $INCLUED
    fi
else
    echo '### INSTALLING INCLUED';
    $SUDO pecl install $INCLUED
    echo 'be sure to add to your php.ini: extension=inclued.so'
fi
# }}}
# Install memcache {{{
if [ `$PHP_EXT_TEST $MEMCACHE_PKG` ]; then
    if [ $MEMCACHE_PKG == 'memcache' ]; then
        if [ $DO_UPGRADE ]; then
            if [ $DISTRIBUTION = 'fedora' ]; then
                $SUDO yum install php-pecl-memcache
            else
                echo "### UPGRADING ${MEMCACHE_PKG}...";
                $SUDO pecl upgrade $MEMCACHE
            fi
        fi 
    else
        echo "### $MEMCACHE_PKG ALREADY INSTALLED";
    fi
else
    echo "### INSTALLING ${MEMCACHE_PKG}...";
    if [ $MEMCACHE_PKG == 'memcache' ]; then
        if [ $DISTRIBUTION = 'fedora' ]; then
            $SUDO yum install php-pecl-memcache
        else
            $SUDO pecl install $MEMCACHE
        fi
    else
        if [ $DISTRIBUTION = 'memcache' ]; then
            $SUDO yum install php-pecl-$MEMCACHE_PKG
        else
            pushd packages
                pecl download $MEMCACHE
            popd
            pushd build
                rm -rf *
                gzip -dc ../packages/${MEMCACHE_PKG}*.tgz | tar xf -
                pushd ${MEMCACHE_PKG}*
                    phpize
                    ./configure --with-libmemcached-dir=${LIBMEMCACHED}
                    make
                    $SUDO make install
                popd
            popd
        fi
    fi
    $PACKAGES_INSTALLED="$MEMCACHE_PKG $PACKAGES_INSTALLED"
    echo "### Be sure to add to your php.ini: extension=${MEMCACHE_PKG}.so"
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
    #sudo pear channel-discover pear.firephp.org
    $SUDO pear install firephp/$FIREPHP
fi
# }}}
# Install PEAR::PhpDocumentor {{{
if [ `pear_installed PhpDocumentor` != '0' ]; then
    $SUDO pear upgrade $PHPDOC
else
    $SUDO pear install $PHPDOC
fi
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
echo '### You may need to add stuff to your /etc/php.ini (or etc/php.d/) and restart'
echo '### NB: There is a bug in Savant PHP Fatal error:  Method Savant3::__tostring() cannot take arguments in /usr/share/pear/Savant3.php on line 241'
$SUDO $APACHECTL graceful
