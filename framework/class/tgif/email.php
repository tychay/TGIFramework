<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_email}
 *
 * @package tgiframework
 * @subpackage utilities
 * @copyright 2007-2009 Tagged, Inc. c.2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_email
/**
 * Utility functions handling e-mails
 *
 * @package tgiframework
 * @subpackage utilities
 * @author terry chay <tychay@php.net>
 */
class tgif_email
{
    // STATIC: EMAILADDRESS
    // {{{ + parse_addresses($text)
    /**
     * Just a wrapper for mailparse's parse_address function.
     *
     * If the pecl package isn't isntalled it attempts to use the PEAR package.
     *
     * @return array A list of e-mails with a hash with the following parameters
     * - display: name for display purposes
     * - address: the e-mail address
     * - is_group: true if newsgroup, false otherwise
     */
    static function parse_addresses($text)
    {
        if ( function_exists('mailparse_rfc822_parse_addresses') ) {
            return mailparse_rfc822_parse_addresses($text);
        }

        // try PEAR but deal with strict standards
        $error_level = error_reporting(E_ALL);
        require_once 'PEAR.php';
        require_once 'Mail/RFC822.php';
        $res = Mail_RFC822::parseAddressList($text);
        if ( PEAR::isError($res) )  {
            trigger_error($res->getMessage());
            error_reporting($error_level);
            return array();
        }
        error_reporting($error_level);
        $returns = array();
        foreach ($res as $parse) {
            $display_name = ($parse->personal) ? $parse->personal : implode('',$parse->comment); 
            // strip quote marke
            if ( substr($display_name,0,1)=='"' ) {
                $display_name = substr($display_name,1,-1);
            }
            $returns[] = array(
                'display'   => $display_name,
                'address'   => $parse->mailbox.'@'.$parse->host,
                'is_group'  => false,
            );
        }
        return $returns;
    }
    // }}}
    // {{{ + make_address($email[,$name])
    /**
     * Turns it to "$name" <$email>.
     *
     * This doesn't support () commenting in e-mail. Sorry! It does, however
     * respect {@link http://www.ietf.org/rfc/rfc0822.txt?number=822 the rfc822}
     * quoting rule (note the atoms in the email address are unquoteable so
     * e-mail is passed through).
     *
     * @return string
     * @todo I'm not sure of the rule of linefeeds here. It might be a good idea
     * to filter those out.
     */
    static function make_address($email,$name='')
    {
        if ( strcmp($name,$email) === 0) {
            $name = '';
        }
        if (!$name) {
            return $email;
        }
        $name = trim($name); //sometimes passed in whitespace (empty last name field?)
        $name = str_replace(
            array('\\','"',"\r"),
            array('\\\\','\\"',"\\\r"),
            //array('\\',"\r"),
            //array('\\\\',"\\\r"),
            $name
        );
        return sprintf('"%s" <%s>', $name, $email);
        //return sprintf('%s <%s>', $name, $email);
    }
    // }}}
    // {{{ + validate_address($email[,$skipDomainCheck])
    /**
     * @param text $email The email to validate
     * @return string if empty it is invalid else
     *  it returns the sanitized e-mail address
     */
    static function validate_address($email,$skipDomainCheck=false)
    {
        // Set test to pass
        $valid = true;
        // remove ""
        if (preg_match('!<(.*)>!ims', $email, $matches)) {
            $email = $matches[1];
        }
        $email = strtolower(trim($email));
        // Find the last @ in the email
        $findats = strrpos($email, "@");
        // Check to see if any @'s were found
        if (is_bool($findats) && !$findats) {
            return '';
        }

        // Phew, it's still ok, continue...
        // Let's split that domain up.
        $domain = substr($email, $findats+1);
        $local = substr($email, 0, $findats);

        // Find the local and domain lengths
        $locallength = strlen($local);
        $domainlength = strlen($domain);

        // Check local (first part)
        if ($locallength < 1 || $locallength > 64) {
            return '';
        }
        // Better check the domain too
        if ($domainlength < 1 || $domainlength > 256) {
            return '';
        }
        
        // Can't be having dots at the start or end
        if ($local[0] == '.' || $local[$locallength-1] == '.') {
            return '';
        }
        // Don't want 2 (or more) dots in the email
        if ((preg_match('/\\.\\./', $local)) || (preg_match('/\\.\\./', $domain))) {
            return '';
        }
        // Make sure the domain has valid chars
        if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            return '';
        }
        // Make sure the local has valid chars, make sure it's quoted right
        if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
                return '';
            }
        }

        if ($skipDomainCheck) {
            return $email;
        }

        // Whoa, made it this far? Check for domain existance!
        if (!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            return '';
        }
        // example.com (and others?) are known to be illegal
        if ( in_array($domain, array('example.com')) ) {
            return '';
        }
        return $email;
    }
    // }}}
}
// }}}
?>
