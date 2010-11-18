<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_http}
 *
 * @package tgiframework
 * @copyright 2007-2009 Tagged, Inc. c.2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_http
/**
 * Utility functions handling url manipulation and http redirection

 * @package tgiframework
 * @author terry chay <tychay@php.net>
 */
class tgif_http
{
    // STATIC: HEADERS
    // {{{ + write_static_headers()
    /**
     * @todo write static headers
     */
    public static function write_static_headers()
    {

    }
    // }}}
    // STATIC: REDIRECT
    // {{{ + redirect($url[,$post])
    /**
     * @uses tgif_http::url_encode()
     */
    public static function redirect($url,$post=null)
    {
        // Ugly, but until we do the right thing with logging, necessary.
        //PageViewLogger::setRedirect();

        //echo'<plaintext>';print_r(array($GLOBALS['_TAG'],$url,$post,debug_backtrace()));die;
        $url = self::url_fullize($url);
        if (is_null($post)) {
            header(sprintf('Location: %s', $url));
            exit;
        }
        // We support post type redirect also
        if (!is_string($post)) {
            $post = self::url_encode($post);
        }
        echo '<html><head><title>Redirecting</title></head><body onload="document.forms.redirect_form.submit()">';
        printf('<p>Redirecting to %1$s&hellip;</p><form name="redirect_form" id="redirect_form" method="POST" action="%1$s">', $url);
        foreach ($post as $key=>$value) {
            printf('<input type-"hidden" name="%s" value="%s" />', htmlspecialchars($key), htmlspecialchars($value));
        }
        echo '</form></body></html>';
        exit();
    }
    // }}}
    // STATIC: URL UTILITIES
    // {{{ + self_url()
    /**
     * @returns string the URL of itself
     */
    public static function self_url()
    {
        static $self;
        if ( !$self ) {
            $scheme = self::self_url_scheme();
            $port = ($_SERVER['SERVER_PORT'] == '80')
                  ? ''
                  : (':'.$_SERVER['SERVER_PORT']);
            $self = $scheme . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
        }
        return $self;
    }
    // }}}
    // {{{ + self_url_scheme()
    /**
     * @returns string returns http or https
     */
    public static function self_url_scheme()
    {
        static $protocol;
        if ( !$protocol ) {
            $s = empty( $_SERVER['HTTPS'])
               ? ''
               : ($_SERVER['HTTPS'] == "on")
               ? 's'
               : '';
            $protocol = strtolower($_SERVER['SERVER_PROTOCOL']);
            $protocol = substr($protocol, 0, strpos($protocol, '/')) . $s;
        }
        return $protocol;
    }
    // }}}
    // {{{ + url_fullize($url)
    /**
     * Returns the URL of itself
     */
    public static function url_fullize($url)
    {
        $url_parts = self::parse_url($url);
        if ( !isset($url_parts['scheme']) ) {
            $url_parts['scheme']    = self::self_url_scheme();
        }
        if ( !isset($url_parts['host']) ) {
            $url_parts['host']      = $_SERVER['SERVER_NAME'];
        }
        if ( !isset($url_parts['port']) && ($_SERVER['SERVER_PORT']!=='80') ) {
            $url_parts['port']      = $_SERVER['SERVER_PORT'];
        }
        var_dump($url_parts);
        return self::glue_url($url_parts);
    }
    // }}}
    // {{{ + parse_url($url)
    /**
     * Like {@link parse_url()} but it puts the query string into an array
     * hash.
     * @uses tgif_url::url_decode()
     */
    public static function parse_url($url)
    {
        $parts = parse_url($url);
        if (empty($parts['query'])) {
            $parts['query'] = array();
        } else {
            $parts['query'] = self::url_decode($parts['query']);
        }
        return $parts;
    }
    // }}}
    // {{{ + glue_url($url_parts)
    /**
     * The inverse of {@link parse_url()}.
     *
     * This can also handle a hashed parse like in
     * {@link tgif_http::parse_url()}
     * @return string
     * @uses tgif_http::url_encode()
     */
    public static function glue_url($url_parts)
    {
        if (!is_array($url_parts)) { return false; }
        $return = (isset($url_parts['scheme']))
                  ? $url_parts['scheme'].':'.((strtolower($url_parts['scheme']) == 'mailto') ? '':'//')
                  : '';
        $return .= (isset($url_parts['user']))
                   ? $url_parts['user'].($url_parts['pass'] ? ':'.$url_parts['pass']:'').'@'
                   : '';
        $return .= (isset($url_parts['host'])) ? $url_parts['host'] : '';
        $return .= (isset($url_parts['port'])) ? ':'.$url_parts['port'] : '';
        $return .= (isset($url_parts['path'])) ? $url_parts['path'] : '';
        if (!empty($url_parts['query'])) {
            $return .= '?';
            $return .= (is_array($url_parts['query']))
                       ? self::url_encode($url_parts['query'])
                       : $url_parts['query'];

        }
        $return .= isset($url_parts['fragment']) ? '#'.$url_parts['fragment'] : '';
        return $return;
    }
    // }}}
    // {{{ + url_decode($data)
    /**
     * Turns a string into a hash
     *
     * This just calls {@link parse_str()}.
     * @param $data string The string to turn into a hash.
     * @return array
     */
    public static function url_decode($data)
    {
        if (!$data) { return array(); }
        return parse_str($data);
        /*
        $return = array();
        $query_parts = explode('&', $data);
        foreach ($query_parts as $query_part) {
            $query_stuff = explode('=',$query_part);
            $key = $query_stuff[0];
            if (sizeof($query_stuff)<2) {
                $value = '';
            } elseif (sizeof($query_stuff)>2) {
                unset($query_stuff[0]);
                $value = implode('=',$query_stuff);
            } else {
                $value = $query_stuff[1];
            }
            $returns[urldecode($key)] = urldecode($value);
        }
        return $returns;
        /* */
    }
    // }}}
    // {{{ + url_encode($data)
    /**
     * Turns a hash into a url encoded stirng
     *
     * This just calles {@link http_build_query()}
     * @return string
     */
    public static function url_encode($data)
    {
        if (empty($data)) { return ''; }
        return http_build_query($data);
        /*
        $return = '';
        foreach ($data as $key=>$value) {
            $return .= sprintf('%s=%s&',urlencode($key),urlencode($value));
        }
        return substr($return,0,-1);
        /* */
    }
    // }}}
    // {{{ + append_query($url, $query)
    /**
     * Allows you to add a query string onto a URL
     * @uses tgif_http::parse_url()
     * @uses tgif_http::glue_url()
     */
    public static function append_query($url,$query)
    {
        $parts = self::parse_url($url);
        $parts['query'] = array_merge($parts['query'],$query);
        return self::glue_url($parts);
    }
    // }}}
    // {{{ + is_secure_request()
    /**
     * Checks to see if current request is for a secure page.
     *
     * To modify this, subclass tgif_http and modify using parent::
     * @return boolean
     */
    public static function is_secure_request()
    {
        static $secure_page;

        if (is_null($secure_page)) {
            $secure_page = false;
            /*
            // if we're on an import page but not import_start
            if( 
            isset($_SERVER['REQUEST_URI']) &&
            ( strpos($_SERVER['REQUEST_URI'],'import_') !== FALSE ||
              strpos($_SERVER['REQUEST_URI'],'secure_login') !== FALSE ) )
            {
                $secure_page = true;
                return $secure_page;
            }
            */
            if(defined('SSL_REQUEST') && SSL_REQUEST){
                $secure_page = true;
                return $secure_page;
            }
        }

        return $secure_page;
    }
    // }}}
    // STATIC: HTTP FORWARDING DECODING
    // {{{ _ip_regex
    /**
     * A regular expression used to match IP addresses.
     * @const string
     */
    const _ip_regex = '/^([0-9]{1,3})\\.([0-9]{1,3})\\.([0-9]{1,3})\\.([0-9]{1,3})$/';
    // }}}
    // {{{ + get_ips()
    /**
     * Returns the path the request took to get here as an array of IP
     * addresses.
     * @return array
     */
    public static function get_ips()
    {
        return
            isset ($_SERVER['HTTP_X_FORWARDED_FOR']) ?
            explode(",", str_replace(" ", "", $_SERVER['HTTP_X_FORWARDED_FOR'])) :
            (isset($_SERVER['REMOTE_ADDR']) ? array($_SERVER['REMOTE_ADDR']) : array());
    }
    // }}}
    // {{{ + get_routable_ips()
    /**
     * Given a list of IPs, filters to keep only the routable ones
     * (unless only one was given, in which it returns that one).
     * @param array
     * @return array
     */
    public static function get_routable_ips($ips = null)
    {
        if ($ips === null) { $ips = self::get_ips(); }
        if (! $ips) return $ips;
        $routableIPs = array_values(array_filter($ips, array( "tgif_http", "is_ip_routable" )));
        if (! $routableIPs) $routableIPs = $ips; //array($ips[count($ips)-1]);
        return $routableIPs;
    }
    // }}}
    // {{{ + _ip_parts($ip)
    /**
     * Returns the parts of an IP address, or false if the address can't be
     * parsed.
     * @return array|false
     */
    private static function _ip_parts($ip) {
        $matches = array();
        if (preg_match(self::_ip_regex, $ip, $matches)) {
            // Make sure all the parts are in [0,255].
            for ($i = 1; $i <= 4; $i++) {
                if ($matches[$i] > 255) {
                    return false;
                }
            }
            return array_slice($matches, 1);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ + is_routable_ip($ip)
    /**
     * Check if an IP address is routable.
     * @param string $ip
     * @return boolean
     */
    public static function is_ip_routable($ip)
    {
        // Not "routable":
        // 127.0.0.0/8
        // 10.0.0.0/8
        // 192.168.0.0/16
        // 172.16.0.0-172.31.255.255
        $ip_parts = self::_ip_parts($ip);
        if ($ip_parts) {
            return
                ! ($ip_parts[0] == 127 ||
                   $ip_parts[0] == 10 ||
                   ($ip_parts[0] == 192 && $ip_parts[1] == 168) ||
                   ($ip_parts[0] == 172 && ($ip_parts[1] >= 16 && $ip_parts[1] < 32)));
        } else {
            return false;
        }
    }
    // }}}
    // {{{ + get_from_ips()
    /**
     * Static function gets array of routable IPs (numerical values) in
     * X_Forwarded_For HTTP request header
     * @return array of numerical representation of routable IPs in
     * X_Forwarded_For list
     */
    static function get_from_ips($num_to_return = 5)
    {
        $routableIPs = self::get_routable_ips();
        if (! $routableIPs) return $routableIPs;
        $routableIPs = array_reverse(array_slice($routableIPs, -$num_to_return));
        $numericIPs = array_map(array("tgif_http", "_ip_to_number"), $routableIPs);
        $hexIPs = array_map("dechex", $numericIPs);
        return $hexIPs;
    }
    // }}}
    // {{{ + get_user_ip()
    /**
     * Return the routable IP address closest to the user, or null.
     *
     * @return string|null The hex representation of the IP address
     * closest to the user, or null if we couldn't determine the
     * address.
     */
    static function get_user_ip()
    {
        $routableIPs = self::get_routable_ips();
        if (! $routableIPs) return null;
        $ip = $routableIPs[0];
        return dechex(self::_ip_to_number($ip));
    }
    // }}}
    // {{{ + _ip_to_number($ip)
    /**
     * Convert a string IP address into its (unsigned) numeric form.
     * @param string $ip
     * @return integer|false numerical represenation of passed IP
     */
    private static function _ip_to_number($ip)
    {
        $ip_parts = self::_ip_parts($ip);
        if ($ip_parts) {
            $numValue = 0;
            foreach ($ip_parts as $part) {
                $numValue = $numValue * 256 + $part;
            }
            return $numValue;
        } else {
            return false;
        }
    }
    // }}}
    // STATIC: HTTP LANGUAGE
    // {{{ + parse_accept_language()
    /**
     * Parses the 'Accept language:' HTTP header sent from a client if it
     * exists.
     *
     * For more information, see {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html}
     *
     * @return array The list of accepted languages in order of quality value.
     * @author Mark Jen
     */
    public static function parse_accept_language($value = null) {
        $ret = array();

        // check for a passed in value. If none specified, check for the $_SERVER superglobal value.
        // If still none specified, then no accept language is available.
        if (is_null($value)) {
            if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                return $ret;
            }
            $value = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }

        // check for an empty string
        if (empty($value)) {
            return $ret;
        }

        // according to the spec, the format is something like:
        // 'da, en-gb;q=0.8, en;q=0.7'

        $languages = explode(',', $value);
        foreach ($languages as $language) {
            $langParts = explode(';', $language);
            switch(count($langParts)) {
                case 1: // this means no q value was specified. default is 1.0
                    $ret[] = array('lang'=>strtolower(trim($langParts[0])), 'q'=>floatval(1));
                    break;
                case 2: // this means the language and q value was specified
                    $qParts = explode('=', $langParts[1]);
                    // make sure q value is properly specified
                    if (count($qParts) == 2 && strtolower(trim($qParts[0])) == 'q' && is_numeric($qParts[1])) {
                        $q = $qParts[1];
                    } else {
                        // invalid q value, putting in default
                        $q = 1;
                    }
                    $ret[] = array('lang'=>strtolower(trim($langParts[0])), 'q'=>floatval($q));
                    break;
                default: // this is an error. skip this language and go on
                    trigger_error('Invalid language found in HTTP Accept Language header: '.$value, E_USER_NOTICE);
                    break;
            }
        }

        // now we need to sort the return array by q value
        $qValues = array();
        foreach($ret as $i => $language) {
            $qValues[$i] = $language['q'];
        }
        array_multisort($qValues, SORT_DESC, $ret);

        return $ret;
    }
    // }}}
}
// }}}
?>
