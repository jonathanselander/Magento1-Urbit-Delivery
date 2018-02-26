<?php
/**
 * Copyright (c) 2009-2016 Vaimo AB
 *
 * Vaimo reserves all rights in the Program as delivered. The Program
 * or any portion thereof may not be reproduced in any form whatsoever without
 * the written consent of Vaimo, except as provided by licence. A licence
 * under Vaimo's rights in the Program may be available directly from
 * Vaimo.
 *
 * Disclaimer:
 * THIS NOTICE MAY NOT BE REMOVED FROM THE PROGRAM BY ANY USER THEREOF.
 * THE PROGRAM IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE PROGRAM OR THE USE OR OTHER DEALINGS
 * IN THE PROGRAM.
 *
 * @category    Vaimo
 * @package     Vaimo_Urbit
 * @copyright   Copyright (c) 2009-2016 Vaimo AB
 */

/**
 * Class Vaimo_UrbIt_Model_Urbit_Api_Client
 */
class Vaimo_UrbIt_Model_Urbit_Api_Client
{
    /**
     * @var int
     */
    protected $apiVersion = 1;

    /**
     * @var string
     */
    protected $apiFormat = "json";

    /**
     * @var string
     */
    protected $apiDomain = "com";

    /**
     * @var mixed|string
     */
    protected $apiEnvironment = "";

    /**
     * @var string
     */
    protected $apiUrl = "";

    /**
     * @var mixed|string
     */
    protected $apiOAuthConsumerKey = "YOUR CONSUMER KEY";

    /**
     * @var mixed|string
     */
    protected $apiOAuthConsumerSecret = "YOUR CONSUMER SECRET";

    /**
     * @var string
     */
    protected $apiOAuthToken = "YOUR OAUTH TOKEN";

    /**
     * @var mixed|string
     */
    protected $apiXKey = "";

    /**
     * @var mixed|string
     */
    protected $apiBearerJWTToken = "";

    /**
     * @var bool|mixed
     */
    protected $apiDebug = true;

    /**
     * Vaimo_UrbIt_Model_Urbit_Api_Client constructor.
     */
    public function __construct()
    {
        $this->apiEnvironment = Mage::getStoreConfig("carriers/" . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . "/api_environment");
        $this->apiUrl = "https://" . $this->apiEnvironment . $this->apiDomain . "/v2/";
        $this->apiOAuthConsumerSecret = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/consumer_secret');
        $this->apiOAuthConsumerKey = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/consumer_key');
        $this->apiXKey = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/x_api_key');
        $this->apiBearerJWTToken = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/bearer_token');
        $this->apiDebug = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/api_debug');
    }

    /**
     * @param string $method
     * @param string $apiPath
     * @param array $data
     * @param int $lifetime
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     * @throws Zend_Cache_Exception
     */
    public function doCachedCall($method, $apiPath, $data = array(), $lifetime = 3600)
    {
        $cache = Mage::app()->getCache();
        $key = $apiPath . http_build_query($data);

        /** @var Vaimo_UrbIt_Model_Urbit_Api_Response|false $response */
        $response = unserialize($cache->load($key));

        if ($response === false) {
            $response = $this->doCall($method, $apiPath, $data);

            if ($response->getSuccess()) {
                $cache->save(serialize($response), $key, array("urbit_api"), $lifetime);
            }
        }

        return $response;
    }

    /**
     * @param $method
     * @param $apiPath
     * @param array $data
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function doCall($method, $apiPath, $data = array())
    {
        //Debug request if setting is active
        $this->_apiDebugLog("Request", $data);

        $url = $this->apiUrl . $apiPath;
        $curl = curl_init($url);

        if ($method === "GET") {
            $jsonData = "";
            $queryString = http_build_query($data);
        } else {
            $jsonData = json_encode($data);
            $queryString = "";
        }

        $headers = array(
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiXKey,
            'Authorization: ' . $this->apiBearerJWTToken,
        );

        $options = array(
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
        );

        if ($method === "POST" || $method === "PUT") {
            $options[CURLOPT_POSTFIELDS] = $jsonData;
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        } else {
            $options[CURLOPT_URL] = $url . "?" . $queryString;
        }

        curl_setopt_array($curl, $options);

        $response = json_decode(curl_exec($curl), true); //as array
        $info = curl_getinfo($curl);

        curl_close($curl);

        /** @var Vaimo_UrbIt_Model_Urbit_Api_Response $responseObject */
        $responseObject = Mage::getModel("vaimo_urbit/urbit_api_response", array("info" => $info, "response" => $response, "method" => $method));

        $this->_apiDebugLog("Headers", $headers);
        $this->_apiDebugLog("Data", $jsonData);
        $this->_apiDebugLog("Response", $responseObject->getResponse());

        return $responseObject;
    }

    /**
     * @param $type
     * @param $object
     * @param bool $forceLog
     */
    protected function _apiDebugLog($type, $object, $forceLog = false)
    {
        if ($forceLog || $this->apiDebug) {
            Mage::log($type . ": " . print_r($object, true), null, "vaimo_urbit.log", true);
        }
    }
}
