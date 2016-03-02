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

        $this->api_url = $config['api_url'];
        $this->hmac_key = $config['hmac_key'];
    }

    /**
     * Fetch all valid users which should be attempted logged in
     */
    protected function get_usernames($username) {
        // verify the user exists
        $json = json_decode($this->apiGet($this->api_url . '/user/' . rawurlencode($username)), true);
        if (!empty($json['result'])) {
            return array($json['result']['username']);
        }

        // check if email and disallow any commas
        if (strpos($username, '@') !== false && strpos($username, ',') === false) {
            // lookup as a email
            $json = json_decode($this->apiGet($this->api_url . '/users?emails=' . urlencode($username)), true);

            $usernames = array();
            foreach ($json['result'] as $user) {
                $usernames[] = $user['username'];
            }

            return $usernames;
        }

        return array();
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

        $usernames = $this->get_usernames($username);

        foreach ($usernames as $username) {
            $data = array(
                'username' => $username,
                'password' => $password);
            $time = time();
            $hash = $this->generateHMACHash($time, 'POST', '/users-api/simpleauth', $data);

            $ch = curl_init($this->api_url . '/simpleauth');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'X-API-Time: '.$time,
                'X-API-Hash: '.$hash));
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

            $reply = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($reply === false) {
                throw new SimpleSAML_Error_Error('curl_exec failed');
            }

            if ($status != 200) {
                throw new SimpleSAML_Error_Error('unknown users-api error');
            }

            $reply = json_decode($reply, true);

            if ($reply['status']['code'] != 200) {
                break;
            }

            $reply = $reply['result'];

            // serialize attributes data
            // the attributes should contain one-dimensional arrays
            $res = array(
                'unique_id' => array($reply['unique_id']),
                'id' => array($reply['id']),
                'username' => array($reply['username']),
                'email' => array($reply['email']),
                'realname' => array($reply['realname']),
                'phone' => array($reply['phone']),
                'groups' => array(),
                'groupsowner' => array()
            );

            foreach ($reply['groups'] as $group) {
                $res['groups'][] = $group['name'];
            }

            foreach ($reply['groupsowner_relation'] as $group => $ownedby) {
                $res['groupsowner'][] = $group;
            }

            $res['groups_relation_data'] = array(json_encode($reply['groups_relation']));
            $res['groups_data'] = array(json_encode($reply['groups']));
            $res['groupsowner_relation_data'] = array(json_encode($reply['groupsowner_relation']));

            return $res;
        }

        throw new SimpleSAML_Error_Error('WRONGUSERPASS');
    }

    /**
     * Generate a HMAC-hash
     */
    protected function generateHMACHash($time, $method, $uri, $post_variables)
    {
        $data = json_encode(array((string)$time, $method, $uri, (array)$post_variables));
        return hash_hmac('sha256', $data, $this->hmac_key);
    }

    /**
     * Run GET to the API with HMAC
     */
    protected function apiGet($uri) {
        $hash_uri = preg_replace('/^.+?\\/\\/.+?\\//', '/', $uri);

        $time = time();
        $hash = $this->generateHMACHash($time, 'GET', $hash_uri, array());

        $opts = array(
            'http' => array(
                'header' => "X-API-Time: $time\r\nX-API-Hash: $hash"
            )
        );

        $context = stream_context_create($opts);
        return file_get_contents($uri, false, $context);
    }
}
