<?php

/**
 * Specialized authentication module for FBS' auth system
 *
 * @see https://github.com/blindern/users-api
 */
class sspmod_fbs_Auth_Source_FbsApi extends sspmod_core_Auth_UserPassBase {

    private $api_url;
    private $hmac_key;

    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct($info, $config) {
        assert('is_array($info)');
        assert('is_array($config)');

        /* Call the parent constructor first, as required by the interface. */
        parent::__construct($info, $config);

        $this->api = new sspmod_fbs_Auth_Common($config['api_url'],
                                          $config['hmac_key']);
    }

    /**
     * Attempt to log in using the given username and password.
     *
     * On a successful login, this function should return the users attributes. On failure,
     * it should throw an exception. If the error was caused by the user entering the wrong
     * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
     *
     * Note that both the username and the password are UTF-8 encoded.
     *
     * @param string $username  The username the user wrote.
     * @param string $password  The password the user wrote.
     * @return array  Associative array with the users attributes.
     */
    protected function login($username, $password) {
        assert('is_string($username)');
        assert('is_string($password)');

        $usernames = $this->api->getUsernames($username);

        foreach ($usernames as $username) {
            $user = $this->api->tryCredentials($username, $password);
            if ($user !== false) {
                return $user;
            }
        }

        throw new SimpleSAML_Error_Error('WRONGUSERPASS');
    }
}
