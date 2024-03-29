<?php
/**
 * @file
 * A single location to store configuration.
 */

/**
 * Define for file includes. The certs directory is best stored out of web root so moving the directory
 * and updating the reference to BASE_PATH is the best way to ensure things keep working
 */
define('BASE_PATH',dirname(__FILE__));

/**
 * Define which app type you are using:
 * Private - private app method
 * Public - standard public app method
 * Partner - partner app method
 */
define("XRO_APP_TYPE",     "Private");

/**
 * Set a user agent string that matches your application name as set in the Xero developer centre
 */
$useragent = "HNG Xero API test";

/**
 * Set your callback url or set 'oob' if none required
 */
define("OAUTH_CALLBACK",     'oob');

/**
 * Set your CONSUMER KEY and SECRET
 */
define("CONSUMER_KEY",     '');
define("CONSUMER_SECRET",     '');

/**
 * Application specific settings
 * Not all are required for given application types
 * consumer_key: required for all applications
 * consumer_secret:  for partner applications, set to: s (cannot be blank)
 * rsa_private_key: application certificate private key - not needed for public applications
 * rsa_public_key:  application certificate public cert - not needed for public applications
 */

$signatures = array(
    'consumer_key'     => CONSUMER_KEY,
    'shared_secret'    => CONSUMER_SECRET,
    // API versions
    'core_version'=> '2.0',
    'payroll_version'=> '1.0',
    'file_version' => '1.0'
);

if (XRO_APP_TYPE=="Private"||XRO_APP_TYPE=="Partner") {
    $signatures['rsa_private_key']= BASE_PATH . '/certs/privatekey.pem';
    $signatures['rsa_public_key']= BASE_PATH . '/certs/publickey.cer';
}
