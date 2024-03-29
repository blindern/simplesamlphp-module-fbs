<?php

namespace SimpleSAML\Module\fbs\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Module\fbs\Auth\Common;

/**
 * Vipps Logg Inn mapper to foreningenbs.no-account
 */
class Vipps extends \SimpleSAML\Auth\ProcessingFilter
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
    public function process(array &$state): void
    {
        Assert::keyExists($state, "Attributes");

        // The attribute prefix is used to let us know if the authentication
        // data is coming from Vipps or not.
        // It must be configured the same for auth source and this processing filter.

        if (!isset($state['Attributes'][$this->attribute_prefix . 'email_verified'])) {
            return;
        }

        if (!$state['Attributes'][$this->attribute_prefix . 'email_verified']) {
            throw new \SimpleSAML\Error\Exception('email not verified');
        }

        $email = $state['Attributes'][$this->attribute_prefix . 'email'][0];

        // Skip country code.
        $phoneNumber = substr($state['Attributes'][$this->attribute_prefix . 'phone_number'][0], 2);

        $state['fbs:email'] = $email;
        $state['fbs:phoneNumber'] = $phoneNumber;

        $usernames = $this->api->listUsersByEmail($email);

        // Try phone number if no matches on email.
        if (count($usernames) == 0) {
            $usernames = $this->api->listUsersByPhoneNumber($phoneNumber);
        }

        // only one user? use it
        if (count($usernames) == 1) {
            $user = $this->api->getUser($usernames[0]);
            if (!is_array($user)) {
                throw new \SimpleSAML\Error\Exception('could not fetch user details');
            }

            $state['Attributes'] = $user;
            return;
        }

        $state['fbs:usernames'] = $usernames;

        // Save state and redirect
        $id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
        $url = \SimpleSAML\Module::getModuleURL('fbs/vipps-login-error');
        $httpUtils = new \SimpleSAML\Utils\HTTP();
        $httpUtils->redirectTrustedURL($url, array('StateId' => $id));
    }

    public static function finishLogoutRedirect(\SimpleSAML\IdP $idp, array $state)
    {
        $id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
        $url = \SimpleSAML\Module::getModuleURL('fbs/vipps-login-error');
        $httpUtils = new \SimpleSAML\Utils\HTTP();
        $httpUtils->redirectTrustedURL($url, array('StateId' => $id));
    }
}
