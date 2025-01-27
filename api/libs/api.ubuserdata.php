<?php

class UbUserData {
    /**
     * System caching object instance
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Ubilling full URL
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Ubilling API key
     *
     * @var string
     */
    protected $apiKey = '';

    // some predefined stuff here
    const KEY_USERDATA = 'USERDATA';

    /**
     * Default caching timeout in seconds
     *
     * @var int
     */
    protected $cachingTimeout = 86400;

    public function __construct() {
        $this->initCache();
        $this->setOptions();
    }

    protected function setOptions() {
        global $ubillingConfig;
        $ubUrl = $ubillingConfig->getAlterParam('UBILLING_URL');
        $ubKey = $ubillingConfig->getAlterParam('UBILLING_API_KEY');
        if (!empty($ubUrl) and $ubKey) {
            $this->apiKey = $ubKey;
            $this->apiUrl = $ubUrl . '/?module=remoteapi&key=' . $ubKey . '&action=userbyip&ip=';
        }
    }

    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    public function getRemoteUserData($ip) {
        $result = array();
        if ($this->apiUrl and $this->apiKey) {
            $fullUrl = $this->apiUrl . $ip;

            $remoteApi = new OmaeUrl($fullUrl);
            $response = $remoteApi->response();
            $response = @json_decode($response, true);
            if (!empty($response['result'])) {
                $result = $response['userdata'];
            }
        }
        return ($result);
    }

    public function getUserData($ip) {
        $result = array();
        $cachedData = $this->cache->get(self::KEY_USERDATA, $this->cachingTimeout);
        if (!is_array($cachedData)) {
            $cachedData = array();
        }
        if (isset($cachedData[$ip])) {
            $result = $cachedData[$ip];
        } else {
            $userData = $this->getRemoteUserData($ip);
            if (!empty($userData)) {
                $cachedData[$ip] = $userData;
                $result = $userData;
                $this->cache->set(self::KEY_USERDATA, $cachedData, $this->cachingTimeout);
            }
        }

        return ($result);
    }
}
