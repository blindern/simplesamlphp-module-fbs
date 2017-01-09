<?php

use \sspmod_fbs_Auth_Process_GoogleAccount;

/**
 * Google Account mapper to foreningenbs.no-account
 */
class sspmod_fbs_Auth_Process_GoogleAccount extends SimpleSAML_Auth_ProcessingFilter
{
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

        $this->api = new sspmod_fbs_Auth_Common($config['api_url'],
                                          $config['hmac_key']);
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

        $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

        // not a google login? just go to next filter
        // (of our auth backends only google should provide the email_verified attribute)
        if (!isset($state['Attributes']['email_verified'])) {
            return;
        }

        if (!$state['Attributes']['email_verified']) {
            die('email not verified');
        }


        $email = $state['Attributes']['email'][0];
        $usernames = $this->api->getUsernames($email);

        // only one user? use it
        if (count($usernames) == 1) {
            $user = $this->api->getUser($usernames[0]);
            if (!is_array($user)) {
                throw new SimpleSAML_Error_Error('could not fetch user details');
            }

            $state['Attributes'] = $user;
            return;
        }

        $state['fbs:usernames'] = $usernames;

        // Save state and redirect
        $id  = SimpleSAML_Auth_State::saveState($state, 'fbs:request');
        $url = SimpleSAML_Module::getModuleURL('fbs/google_login_error.php');
        SimpleSAML_Utilities::redirectTrustedURL($url, array('StateId' => $id));
    }

    public static function finishLogoutRedirect(SimpleSAML_IdP $idp, array $state)
    {
        $id  = SimpleSAML_Auth_State::saveState($state, 'fbs:request');
        $url = SimpleSAML_Module::getModuleURL('fbs/google_login_error.php');
        SimpleSAML_Utilities::redirectTrustedURL($url, array('StateId' => $id));
    }
}
