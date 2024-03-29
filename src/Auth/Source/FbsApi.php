<?php

namespace SimpleSAML\Module\fbs\Auth\Source;

use SimpleSAML\Module\fbs\Auth\Common;

/**
 * Specialized authentication module for FBS' auth system
 *
 * @see https://github.com/blindern/users-api
 */
class FbsApi extends \SimpleSAML\Module\core\Auth\UserPassBase
{
    /**
     * @var Common
     */
    private $api;

    public function __construct(array $info, array &$config)
    {
        parent::__construct($info, $config);
        $this->api = new Common($config['api_url'], $config['hmac_key']);
    }

    protected function login(string $username, string $password): array
    {
        $usernames = $this->api->listUsersByQuery($username);

        foreach ($usernames as $username) {
            $user = $this->api->tryCredentials($username, $password);
            if ($user !== false) {
                return $user;
            }
        }

        throw new \SimpleSAML\Error\Error(\SimpleSAML\Error\ErrorCodes::WRONGUSERPASS);
    }
}
