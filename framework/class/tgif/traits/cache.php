<?php
/**
 * Holder of {@link tgif_traits_cache}
 *
 * @package tgiframework
 * @subpackage ui
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
/**
 * Trait that used for global cache
 *
 * @package tgiframework
 * @subpackage traits
 * @author terry chay <tychay@php.net>
 * @author benny situ <bennysitu@gmail.com>
 */
trait tgif_traits_cache {

    /**
     * For caching this
     *
     * @var tgif_global_loader
     */
    private $_loader;

    public function setLoader($loader)
    {
        $this->_loader = $loader;
    }

    public function cacheSelf()
    {
        if ( $this->_loader ) {
            $this->_loader->saveToCache($this);
        }
    }

}