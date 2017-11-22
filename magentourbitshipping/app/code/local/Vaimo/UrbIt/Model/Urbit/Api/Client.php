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

class Vaimo_UrbIt_Model_Urbit_Api_Client
{

    private $_apiVersion = 1;
    private $_apiFormat = "json";
    private $_apiDomain = "com";
    private $_apiEnvironment = "";
    private $_apiUrl = "";
    private $_apiOAuthConsumerKey = "YOUR CONSUMER KEY";
    private $_apiOAuthConsumerSecret = "YOUR CONSUMER SECRET";
    private $_apiOAuthToken = "YOUR OAUTH TOKEN";
    private $_apiDebug = true;

    private $_carrierCode = "urbit_onehour";

    public function __construct()
    {
        $this->_apiEnvironment = Mage::getStoreConfig("carriers/" . $this->_carrierCode . "/api_environment");
        $this->_apiUrl = "https://" . $this->_apiEnvironment . $this->_apiDomain . "/api/";
        $this->_apiOAuthConsumerSecret = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/consumer_secret');
        $this->_apiOAuthConsumerKey = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/consumer_key');
        $this->_apiOAuthToken = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/oauth_token');
        $this->_apiDebug = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/api_debug');
    }

    public function doCachedCall($method, $apiPath, $data = array(), $lifetime=3600){
        $cache = Mage::app()->getCache();
        $key = $apiPath . http_build_query($data);
        $response = unserialize($cache->load($key));
        if($response == false) {
            $response = $this->doCall($method, $apiPath, $data);
            if($response->getStatus() == 200) {
                $cache->save(serialize($response), $key, $tags = array("urbit_api"), $lifetime);
            }
        }
        return $response;
    }

    public function doCall($method, $apiPath, $data = array())
    {
        //Debug request if setting is active
        $this->_apiDebugLog("Request", $data);

        $url = $this->_apiUrl . $apiPath;
        $time = time();
        $nonce = md5($time . " " . rand(1, 400));
        $curl = curl_init($url);

        $oauth = array(
            'oauth_consumer_key' => $this->_apiOAuthConsumerKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => "$time",
            'oauth_token' => $this->_apiOAuthToken,
            'oauth_version' => '1.0',
        );

        if($method == "GET"){
            $jsonData = "";
            $queryString = http_build_query($data);
        }else{
            $jsonData = json_encode($data);
            $data = array();
            $queryString = "";
        }

        $oauth['oauth_signature'] = $this->_createOAuthSignature($url, $method, array_merge($data, $oauth), $jsonData);

        $headers = array(
            'Content-Type: application/json',
            $this->_createAuthorizationHeader($oauth),
            "Accept: application/vnd.urb-it." . $this->_apiDomain . "+" . $this->_apiFormat . "; version=" . $this->_apiVersion . ";",
        );

        //ob_start();
        //$out = fopen('php://output', 'w');

        $options = array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            //CURLOPT_VERBOSE => 1,
            //CURLOPT_STDERR => $out
        );

        if($method == "POST"){
            $options[CURLOPT_POSTFIELDS] = $jsonData;
            $headers[] = 'Content-Length: ' . strlen($jsonData);
        }else{
            $options[CURLOPT_URL] = $url . "?" . $queryString;
        }

        curl_setopt_array($curl, $options);

        $response = json_decode(curl_exec($curl), true); //as array
        $info = curl_getinfo($curl);
        //$debug = ob_get_clean();
        curl_close($curl);

        $responseObject = Mage::getModel("vaimo_urbit/urbit_api_response", array("info" => $info, "response" => $response));

        // Force debug if http_status = 200
        $this->_apiDebugLog("Headers", $headers, $responseObject->getSuccess() == false ? true : false);
        $this->_apiDebugLog("Data", $jsonData, $responseObject->getSuccess() == false ? true : false);
        $this->_apiDebugLog("Response", $responseObject->getResponse(), $responseObject->getSuccess() == false ? true : false);

        return $responseObject;
    }

    private function _createAuthorizationHeader($oauth)
    {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach ($oauth as $key => $value) {
            $values[] = "$key=\"" . $value . "\"";
        }
        $r .= implode(', ', $values);

        return $r;
    }

    private function _createOAuthSignature($url, $method, $params, $content)
    {
        $r = array();
        foreach ($params as $key => $value) {
            $r[] = "$key=" . $value;
        }

        $uriParams = implode('&', $r);

        if($content != "") {
            $uriParams .= '&content=' . rawurlencode($content);
        }

        $message = $method . "&" . rawurlencode($url) . "&" . rawurlencode($uriParams);

        $hmac = hash_hmac('sha1', $message, rawurlencode($this->_apiOAuthConsumerSecret), true);
        return base64_encode($hmac);
    }

    private function _apiDebugLog($type, $object, $forceLog = false)
    {
        if ($forceLog || $this->_apiDebug) {
            Mage::log($type . ": " . print_r($object, true), null, "vaimo_urbit.log", true);
        }
    }
}
