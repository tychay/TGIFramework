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
    // {{{ + validate_address
    /**
     * @param text $email The email to validate
     * @return string if empty it is invalid else
     *  it returns the sanitized e-mail address
     */
    static function validate_address($email)
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
        
        // Whoa, made it this far? Check for domain existance!
        if (!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            return '';
        }
        return $email;
    }
    // }}}
}
// }}}
?>
