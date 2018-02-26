<?php

/**
 * Class Vaimo_UrbIt_PostcodeController
 */
class Vaimo_UrbIt_PostcodeController extends Mage_Core_Controller_Front_Action
{
    /**
     * @throws Zend_Cache_Exception
     */
    public function validateAction()
    {
        $postcode = $this->getRequest()->getParam("postcode", false);

        $postcodeCheckEnabled = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/enable_postcode_check');
        if ($postcodeCheckEnabled) {

            if ($postcode !== "") {
                //hack to use urbits wp instead of api
                $validPostCode = $this->_checkZipCode($postcode);
            } else {
                $validPostCode = false;
            }

            if (!$validPostCode) {
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody(json_encode($validPostCode));
                return;
            }
        }

        /** @var Vaimo_UrbIt_Model_Urbit_Api $api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());

        $response = $api->validatePostcode($postcode);

        $status = $response->getSuccess();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($status));
    }

    /**
     * Check if postcode is eligible
     *
     * @param $zipCode
     * @return bool
     */
    protected function _checkZipCode($zipCode)
    {
        $postcodes = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/eligible_postcodes');
        $postcodes = preg_split('/,\s*/', $postcodes);
        if (is_array($postcodes)) {
            foreach ($postcodes as $postcode) {
                $result = fnmatch($postcode, $zipCode);
                if ($result) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * validate delivery address by Urb-it API (GET request)
     */
    protected function validateDeliveryAddressAction()
    {
        $street = $this->getRequest()->getParam("street", false);
        $postcode = $this->getRequest()->getParam("postcode", false);
        $city = $this->getRequest()->getParam("city", false);

        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());

        $responseObj = $api->validateDeliveryAddress($street, $postcode, $city);

        $result = array(
            'ajaxCheckValidateDelivery' => $responseObj->hasError() ? 'false' : 'true',
            'error_code'                => $responseObj->getHttpCode(),
            'error_message'             => $responseObj->getErrorMessage(),
        );

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }
}
