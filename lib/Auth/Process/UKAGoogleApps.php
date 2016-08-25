<?php

/**
 * UKA Google Apps Authentication Processing filter
 *
 * Allows the user to select which account at UKA to login to,
 * if any at all is possible.
 */
class sspmod_fbs_Auth_Process_UKAGoogleApps extends SimpleSAML_Auth_ProcessingFilter
{
    private $_userfile;

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

        $this->_userfile = $config['userfile'];
        $this->_accounts_url = $config['accounts_url'];
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

        $usernames = $this->getUsernames($state['Attributes']['username'][0]);

        // only one user? use it
        if (count($usernames) == 1) {
            $selected = $usernames[0];
            if (strpos($selected, "@") === false) {
                $selected .= "@blindernuka.no";
            }

            $state['Attributes']['gapps-mail'] = array($selected);
            return;
        }

        $state['fbs:usernames'] = $usernames;

        /**
         * If the consent module is active on a bridge $state['saml:sp:IdP']
         * will contain an entry id for the remote IdP. If not, then the
         * consent module is active on a local IdP and nothing needs to be
         * done.
         */
        if (isset($state['saml:sp:IdP'])) {
            $idpEntityId = $state['saml:sp:IdP'];
            $idpmeta         = $metadata->getMetaData($idpEntityId, 'saml20-idp-remote');
            $state['Source'] = $idpmeta;
        }

        $statsData = array('spEntityID' => $spEntityId);

        // User interaction nessesary. Throw exception on isPassive request
        if (isset($state['isPassive']) && $state['isPassive'] == true) {
            SimpleSAML_Stats::log('consent:nopassive', $statsData);
            throw new SimpleSAML_Error_NoPassive(
                'Unable to give consent on passive request.'
            );
        }

        // Save state and redirect
        $id  = SimpleSAML_Auth_State::saveState($state, 'fbs:request');
        $url = SimpleSAML_Module::getModuleURL('fbs/select_user.php');
        SimpleSAML_Utilities::redirectTrustedURL($url, array('StateId' => $id));
    }

    private function getUsernames($username) {
        $users = $this->loadUsernames();

        if (isset($users[$username])) {
            return $users[$username];
        }

        return array();
    }

    private function loadUsernames() {
        $data = json_decode(file_get_contents($this->_accounts_url), true);
        if ($data === false) {
            return $this->loadUsernamesFromCache();
        }

        $users = array();
        foreach ($data as $account) {
            foreach ($account['users'] as $user) {
                if (!isset($users[$user['username']])) {
                    $users[$user['username']] = array();
                }

                $users[$user['username']][] = $account['accountname'];
            }
        }

        $this->saveUsernamesToCache($users);

        return $users;

    }

    private function loadUsernamesFromCache() {
        $data = file_get_contents($this->_userfile);

        if ($data === false) {
            return array();
        }

        return unserialize($data);
    }

    private function saveUsernamesToCache($users) {
        file_put_contents($this->_userfile, serialize($users));
    }
}
