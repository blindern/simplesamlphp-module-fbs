<?php

namespace SimpleSAML\Module\fbs\Auth\Process;

use SimpleSAML\Assert\Assert;

/**
 * UKA Google Apps Authentication Processing filter
 *
 * Allows the user to select which account at UKA to login to,
 * if any at all is possible.
 */
class UKAGoogleApps extends \SimpleSAML\Auth\ProcessingFilter
{
    private $_userfile;
    private $_accounts_url;
    private $_accounts_url_auth_token;

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
        $this->_accounts_url_auth_token = $config['accounts_url_auth_token'];
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

        $spEntityId = $state['Destination']['entityid'];
        $idpEntityId = $state['Source']['entityid'];

        $metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();

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
            \SimpleSAML\Stats::log('consent:nopassive', $statsData);
            throw new \SimpleSAML\Module\saml\Error\NoPassive(
                'Unable to give consent on passive request.'
            );
        }

        // Save state and redirect
        $id  = \SimpleSAML\Auth\State::saveState($state, 'fbs:request');
        $url = \SimpleSAML\Module::getModuleURL('fbs/select-user');
        $httpUtils = new \SimpleSAML\Utils\HTTP();
        $httpUtils->redirectTrustedURL($url, array('StateId' => $id));
    }

    private function getUsernames($username) {
        $users = $this->loadUsernames();

        if (isset($users[$username])) {
            return $users[$username];
        }

        return array();
    }

    private function loadUsernames() {
        $context = stream_context_create([
            'http' => [
                'header' => "Authorization: Bearer {$this->_accounts_url_auth_token}\r\n",
            ],
        ]);

        $data = file_get_contents($this->_accounts_url, false, $context);
        $data = json_decode($data, true);
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
