<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_encode}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2008-2009 Tagged Inc. 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_encode
/**
 * Handle encoding/decoding of stuff.
 *
 * This used to be tag_encode and tag_decode. Optimizations have been added
 * for commonly executed functions.
 *
 * @package tgiframework
 * @subpackage global
 * @author terry chay <tychay@php.net>  original base convert code
 * @author mark jen <markjen@tagged.com> various optimizations and utf encoding 
 * and escaping
 */
class tgif_encode {
    // {{{ + $_base_convert_map
    private static $_base_convert_map = array(
        '0','1','2','3','4','5','6','7','8','9',
        'a','b','c','d','e','f','g','h','i','j',
        'k','l','m','n','o','p','q','r','s','t',
        'u','v','w','x','y','z','A','B','C','D',
        'E','F','G','H','I','J','K','L','M','N',
        'O','P','Q','R','S','T','U','V','W','X',
        'Y','Z','-','_'
    );
    // }}}
    // {{{ + $_base_decode_map
    private static $_base_decode_map = array(
        '0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
        'a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,
        'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,
        'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35,'A'=>36,'B'=>37,'C'=>38,'D'=>39,
        'E'=>40,'F'=>41,'G'=>42,'H'=>43,'I'=>44,'J'=>45,'K'=>46,'L'=>47,'M'=>48,'N'=>49,
        'O'=>50,'P'=>51,'Q'=>52,'R'=>53,'S'=>54,'T'=>55,'U'=>56,'V'=>57,'W'=>58,'X'=>59,
        'Y'=>60,'Z'=>61,'-'=>62,'_'=>63,
    );
    // }}}
    // {{{ + create_key($string[,$length])
    /**
     * Creates a key out of an arbitrary string.
     *
     * Performs 3.5 times faster than old method of
     * <code>tag_encode::int(md5($string));</code>
     * Tried other hashing algortihms (crc32, sha1). crc32 is 10% faster than
     * md5, sha1 is a few percent slower than md5. The Tagged website uses md5
     * for backward compatibility.
     * 
     * 15 is an optimal # of digits to use from md5 because it's 60 bits which 
     * fits as an int and can easily be converted to base64 (a 10-char base64). 
     * However, any length between 1 and 32 will work (md5s are 128-bit which
     * is 32 x 4-bit). Also consider the default length as 5 so that crc32()
     * hashing will kick in.
     *
     * There may be a negative sign cycle in the crc32() hash. Beware! Small
     * fix, we grab from the end of the number because if we grab from the
     * beginning, we may lose the 0 digits in the randomizer
     *
     * @author terry chay <tychay@php.net> added crc32 switch
     * @param string $string arbitrary string to create key from
     * @param int $length how large a string to return
     * @return string base64 encoded md5 of the string
     */
    public static function create_key($string, $length=10)
    {
        if ($length == 5) { //when the crc32 kicks in
            $hexdigits = 8; //it's a little off but it'll work fine
        } elseif ($length == 10) { //fits in a base64 int
            $hexdigits = 15;
        } else {
            // max length is 22 base64 digits
            $length = min($length,22);
            // we need 1.5x hex digits for each base64 digit
            $hexdigits = min(ceil($length/2)*3,32);
        }
        // md5 the string as the key
        $num64 = ($hexdigits <= 8) // 8 * 4(base 16) = 32
               ? substr(self::int_to_base64(crc32($string)), -$length)
               : self::hex_to_base64(substr(md5($string), 0, $hexdigits));
        return str_pad($num64, $length, '0', STR_PAD_LEFT);
    }
    // }}}
    // Integer manipulations
    // {{{ + int_to_base64($number)
    /**
     * Converts a base 10 number to a base 64 encoded string.
     *
     * This performs 4 times faster than the old tag_encode::int
     *
     * Other approaches tried that were slower:
     * - indexing into a string instead of an array
     * - using an array-based hash instead of regular array
     * 
     * @param integer $number
     * @return string
     * @author mark jen <markjen@tagged.com>
     */
    public static function int_to_base64($number) {
        $number = abs($number); // only do positive numbers
        $encoded_arr = array();
        do {
            $digit = $number & 63; // grab the least digit of the number
            $encoded_arr[] = self::$_base_convert_map[$digit];
        } while ($number = $number >> 6);
        return implode(array_reverse($encoded_arr));
    }
    // }}}
    // {{{ + base64_to_int($string)
    /**
     * Takes a base 64 encoded string and turns it back into a base 10 number.
     *
     * This performs 3.5 times faster than 
     * <code>tag_encode::base_convert($n, 64, 10);</code>
     * A little less than 4 times faster than the old tag_decode::int
     * 
     * Also supports decoding from other bases < 64, but probably not too useful
     * because built-in base_convert() supports up to 36.
     *
     * This was ported from tag_decode.
     * 
     * @param string $string
     * @return int
     */
    public static function base64_to_int($string)
    {
        $digits = str_split($string);
        $decoded = 0;
        for($i=0,$lim=count($digits); $i<$lim; $i++) {
            $decoded *= 64;
            $decoded += self::$_base_decode_map[$digits[$i]];
        }
        return $decoded;
    }
    // }}}
    // {{{ + hex_to_base64($hex)
    /**
     * Turns a hex encoded string into a base 64 encoded string.
     *
     * This performs 3.5 times faster than the old tag_encode::base_convert.
     * This requires 64-bit integer support.
     *
     * Other approaches tried that were slower:
     * - 3-2 pulldown: every 3 chars of hex = 2 chars of base 64. creating a
     *   static map of all possible combinations and mapping straight to base
     *   64.
     * 
     * @param string $hex
     * @return string
     * @author mark jen <markjen@tagged.com>
     * @author terry chay <tychay@php.net> added chunking for 32 bit integers
     * (damn MacPorts with it's shitty 32-bit compiler -- an +universal doesn't
     * like ssl).
     */
    public static function hex_to_base64($hex)
    {
        global $_tgif_encode_chunk_size;
        $len = strlen($hex);
        // Handle chunking for 32 and 64 bit integers. Ick!
        if (PHP_INT_SIZE == 4) { //32-bit
            $chunk_size = 6;
            $pulldown = 4;
        } else { //PHP_INT_SIZE == 8, 64-bit
            $chunk_size = 15;
            $pulldown = 10;
        }
        if ($len > $chunk_size) {
            // if we're bigger than 15 chars, we need to split into 15 char
            // chunks (otherwise we'll overflow hexdec)
            $first_chunk_size = $len%$chunk_size;
            $encoded = self::hex_to_base64(substr($hex, 0, $first_chunk_size));
            $other_chunks = str_split(substr($hex, $first_chunk_size), $chunk_size);
            foreach($other_chunks as $chunk) {
                // each 15 chars of 16-bit encode turns into 10 chars of 64-bit encode
                $encoded .= str_pad(self::hex_to_base64($chunk), $pulldown, '0', STR_PAD_LEFT);
            }
            // strip leading 0s
            $encoded = ltrim($encoded, '0');
            if (strlen($encoded) == 0) return '0';
            else return $encoded;
        }
        $base_10_key = hexdec($hex); //Watch out! this might turn into a float!
        return self::int_to_base64($base_10_key);
    }
    // }}}
    // {{{ + base_convert($number_string, $from_base, $to_base)
    /**
     * A better version of {@link base_convert()}
     *
     * This funciton is deprecated. use the built-in base_convert for normal
     * stuff. For base64 stuff, use the functions above.
     *
     * This is different in two ways from base_convert():
     * - it handles up to base 64
     * - it doesn't run into max integer problems
     */
    public static function base_convert($number_string, $from_base, $to_base)
    {
        $string_map = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        // {{{ build numbers array in destination base
        $numbers = array();
        $max = strlen($number_string);
        for ($i=0; $i<$max; ++$i) {
            $numbers[$i] = strpos($string_map, $number_string{$i});
        }
        /* // another version of the above may be faster
        $numbers = str_split($number_string);
        for ($i=0; $i<$max; ++$i) {
        $numbers[$i] = strpos($string_map, $numbers[$i]);
        }
        /* */
        // }}}
        $return = '';
        do {
            $remainder = 0;
            $newlen = 0;
            for ($i =0; $i<$max; ++$i) {
                $remainder = $remainder * $from_base + $numbers[$i];
                if ($remainder >= $to_base) {
                    $numbers[$newlen++] = (int) ($remainder / $to_base);
                    $remainder = $remainder % $to_base;
                } elseif ($newlen > 0) {
                    $numbers[$newlen++] = 0;
                }
            }
            $max = $newlen;
            $return = $string_map{$remainder} . $return;
        } while ($newlen != 0);
        return $return;
    }
    // }}}
    // video_param() deleted as TAGGED specific
    // {{{ + make_utf8($string)
    /**
     * Returns the input string as a properly encoded UTF-8 string
     *
     * @param string $str
     * @return string
     */
    public static function make_utf8($str)
    {
        $encoding_list = array_slice(mb_list_encodings(), 25);
        $detected_encoding = mb_detect_encoding($str, $encoding_list);
        if ($detected_encoding == 'ASCII' || $detected_encoding == 'UTF-8') {
            return $str;
        } else if ($detected_encoding == '') {
            trigger_error('string with unknown encoding: "'.$str.'"', E_USER_ERROR);
        } else {
            // convert from anything other than UTF-8
            return mb_convert_encoding($str, 'UTF-8', $detected_encoding);
        }
    }
    // }}}
    // Output escaping functions 
    // {{{ + js($string)
    /**
     * Encode a string to be used as a string literal for javascript
     *
     * @param string $str
     * @return string
     * @todo we need i18n compatible strlen() and substr()
     */
    public static function js($str)
    {
        //$str = str_replace(array("\r","\n"), array('',"\n"), $str);
        $new_str = '';
        for($i = 0; $i<strlen($str); $i++) {
            $char = substr($str,$i,1);
            switch ($char) {
                case "\r": break;
                case "\n": $new_str .= '\n'; break;
                default: $new_str .= '\\x' . dechex(ord($char)); break;
            }
        }
        return $new_str;
    }
    // }}}
    // {{{ + html_attribute($str)
    /**
     * Takes a string and htmlspecialchars encodes it for a HTML attribute
     *
     * @return void
     * @author mark jen <markjen@tagged.com>
     **/
    public static function html_attribute($str)
    {
        $str = tag_encode::make_utf8($str);
        try {
            return htmlspecialchars($str, ENT_QUOTES, 'UTF-8', false);
        } catch (Exception $e) {
            trigger_error('html_attribute htmlspecialchars error for string:'.$str, E_USER_NOTICE);
            return '';
        }
    }
    // }}}
    // {{{ + html_body($str)
    /**
     * Takes a string and htmlspecialchars encodes it for normal escaped HTML output
     *
     * @return void
     * @author mark jen <markjen@tagged.com>
     **/
    public static function html_body($str)
    {
        $str = tag_encode::make_utf8($str);
        try {
            return htmlspecialchars($str, ENT_NOQUOTES, 'UTF-8', false);
        } catch (Exception $e) {
            trigger_error('html_body htmlspecialchars error for string:'.$str, E_USER_NOTICE);
            return '';
        }
    }
    // }}}
}
// }}}
?>
