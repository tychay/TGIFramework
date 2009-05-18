#!/bin/sh
# vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker:
# This clears the stuff in the bootstrap framework for a rebuild. If you'd
# like to clean everything for distribution, call it this way
# "bs/clean.sh all"
# EDITME: Set the full path to binaries {{{
if [ $1 == 'all' ]; then
# Clean directories {{{
    if [ -d packages ]; then
        rm -rf packages
    fi
    if [ -d build ]; then
        rm -rf build
    fi
# }}}
fi
# compiles {{{
if [ -d packages/pecl/runkit ]; then
    pushd packages/pecl/runkit
        make distclean
    popd
fi
# }}}
# Clean binaries {{{
echo "### Deleting YUICompressor jar..."
rm -f framework/bin/yuicompressor-*.jar
# }}}
# Clean samples {{{
if [ -f samples/config/global_version.php ]; then
    echo "### Deleting global_version config...."
    rm -f samples/config/global_version.php
fi
pushd samples
    if [ -d traces ]; then
        rm -rf traces
    fi
    if [ -d inclued ]; then
        rm -rf inclued
    fi
    if [ -f www/.htaccess ]; then
        rm -f www/.htaccess
    fi
    if [ -f www/webgrind]; then
        rm -rf www/webgrind
    fi
    if [ -f www/phpdoc]; then
        rm -f www/pohpdoc
    fi
popd
# }}}
