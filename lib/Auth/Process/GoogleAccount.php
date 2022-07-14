<?php

namespace SimpleSAML\Module\fbs\Auth\Process;

use SimpleSAML\Module\fbs\Auth\Common;

/**
 * Google Account mapper to foreningenbs.no-account
 */
class GoogleAccount extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * @var \SimpleSAML\Module\fbs\Auth\Common
     */
    private $api;

    /**
     * @var string
     */
    private $attribute_prefix;

    /**
     * Initialize consent filter
     *
     * Validates and parses the configuration
     *
     * @param array $config   Configuration information
     * @param mixed $reserved For future use
     */
    public function __construct($config, $reserved)
    {
        assert('is_array($config)');
        parent::__construct($config, $reserved);

        $this->attribute_prefix = $config['attribute_prefix'];

        $this->api = new Common($config['api_url'], $config['hmac_key']);
    }

    /**
     * Process a authentication response
     *
     * This function saves the state, and redirects the user to the page where
     * the user can authorize the release of the attributes.
     *
     * @param array &$state The state of the response.
     */
    public function process(&$state)
    {
        assert('is_array($state)');
        assert('array_key_exists("UserID", $state)');
        assert('array_key_exists("Destination", $state)');
        assert('array_key_exists("entityid", $state["Destination"])');
        assert('array_key_exists("metadata-set", $state["Destination"])');
        assert('array_key_exists("entityid", $state["Source"])');
        assert('array_key_exists("metadata-set", $state["Source"])');

        $spEntityId = $state['Destination']['entityid'];
        $idpEntityId = $state['Source']['entityid'];

        $metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();

        // The attribute prefix is used to let us know if the authentication
        // data is coming from Google OIDC or not.
        // It must be configured the same for auth source and this processing filter.

        if (!isset($state['Attributes'][$this->attribute_prefix . 'email_verified'])) {
            return;
        }

        if (!$state['Attributes'][$this->attribute_prefix . 'email_verified']) {
            throw new \SimpleSAML\Error\Error('email not verified');
        }

        $email = $state['Attributes'][$this->attribute_prefix . 'email'][0];
        $state['fbs:email'] = $email;

        $usernames = $this->api->listUsersByEmail($email);

        // only one user? use it
        if (count($usernames) == 1) {
            $user = $this->api->getUser($usernames[0]);
            if (!is_array($user)) {
                throw new \SimpleSAML\Error\Error('could not fetch user details');
            }

            $state['Attributes'] = $user;
            return;
        }

        $state['fbs:usernames'] = $usernames;

        // Save state and redirect
        $id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
        $url = \SimpleSAML\Module::getModuleURL('fbs/google_login_error.php');
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
    }

    public static function finishLogoutRedirect(\SimpleSAML\IdP $idp, array $state)
    {
        $id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
        $url = \SimpleSAML\Module::getModuleURL('fbs/google_login_error.php');
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
    }
}
