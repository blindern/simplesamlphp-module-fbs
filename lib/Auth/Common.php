<?php

namespace SimpleSAML\Module\fbs\Auth;

/**
 * Specialized authentication module for FBS' auth system
 *
 * @see https://github.com/blindern/users-api
 */
class Common {

    private $api_url;
    private $hmac_key;

    public function __construct($api_url, $hmac_key) {
        $this->api_url = $api_url;
        $this->hmac_key = $hmac_key;
    }

    public function listUsersByEmail($email)
    {
        if (strpos($email, '@') === false) return [];

        // Comma disallowed since it is used to separate multiple searches.
        if (strpos($email, ',') !== false) return [];

        $json = json_decode($this->apiGet('/users?emails=' . urlencode($email)), true);

        $usernames = [];
        foreach ($json['result'] as $user) {
            $usernames[] = $user['username'];
        }

        return $usernames;
    }

    public function listUsersByPhoneNumber($phoneNumber)
    {
        // Comma disallowed since it is used to separate multiple searches.
        if (strpos($phoneNumber, ',') !== false) return [];

        $json = json_decode($this->apiGet('/users?phoneNumbers=' . urlencode($phoneNumber)), true);

        $usernames = [];
        foreach ($json['result'] as $user) {
            $usernames[] = $user['username'];
        }

        return $usernames;
    }

    /**
     * List users by lookup via query. This will lookup by both username, email and phone.
     */
    public function listUsersByQuery($query) {
        // Test for username match.
        $json = json_decode($this->apiGet('/user/' . rawurlencode($query)), true);
        if (!empty($json['result'])) {
            return array($json['result']['username']);
        }

        // Test for email match.
        $emailMatches = $this->listUsersByEmail($query);
        if (count($emailMatches) > 0) {
            return $emailMatches;
        }

        // Test for phone number match.
        $phoneNumberMatches = $this->listUsersByPhoneNumber($query);
        if (count($phoneNumberMatches) > 0) {
            return $phoneNumberMatches;
        }

        return [];
    }

    /**
     * Generate a HMAC-hash
     */
    public function generateHMACHash($time, $method, $uri, $post_variables)
    {
        $data = json_encode(array((string)$time, $method, $uri, (array)$post_variables));
        return hash_hmac('sha256', $data, $this->hmac_key);
    }

    /**
     * Run GET to the API with HMAC
     */
    public function apiGet($uri) {
        $uri = $this->api_url . $uri;
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

    /**
     * Take the output from a user in users-api and convert
     * it to attributes we want to use in simplesamlphp
     */
    public function fillAttributes($reply) {
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

    /**
     * Try to login with a specified username and password
     *
     * @return false on error, array with attributes on success
     * @throws
     */
    public function tryCredentials($username, $password) {
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
            throw new \SimpleSAML\Error\Error('curl_exec failed');
        }

        if ($status != 200) {
            throw new \SimpleSAML\Error\Error('unknown users-api error');
        }

        $reply = json_decode($reply, true);

        if ($reply['status']['code'] != 200) {
            return false;
        }

        return $this->fillAttributes($reply['result']);
    }

    /**
     * Get details for a specified username
     *
     * @return false on error, array with attributes on success
     */
    public function getUser($username) {
        $json = json_decode($this->apiGet('/user/' . rawurlencode($username)), true);
        if (empty($json['result'])) {
            return false;
        }

        return $this->fillAttributes($json['result']);
    }
}
