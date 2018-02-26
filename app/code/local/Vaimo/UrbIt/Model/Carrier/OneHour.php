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

class Vaimo_UrbIt_Model_Carrier_OneHour extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_showMethod = false;

    protected $_error = null;

    protected $_helper = null;

    /** @var $_api Vaimo_UrbIt_Model_Urbit_Api */
    protected $_api = null;

    /**
     * @param null $api For unit testing purposes only
     */
    public function __construct($api = null)
    {
        $this->_api = $api !== null ? $api : Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());
        $this->_helper = Mage::helper("vaimo_urbit/data");

        parent::__construct();
    }

    public function getFormBlock()
    {
        return 'vaimo_urbit/deliveryDetails';
    }

    /**
     * Collect rates for this shipping method based on information in $request
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return bool|false|Mage_Core_Model_Abstract
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        $result = Mage::getModel('shipping/rate_result');

        if ($this->checkIfRateAllowed($quote) === true) {
            foreach ($this->getAllowedMethods() as $method => $title) {
                $result->append($this->getShippingRate($method, $title));
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * This method is used when viewing / listing Shipping Methods with Codes programmatically
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            'urbit_specific' => Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/method_specific'),
        );
    }

    /**
     * @param $quote Mage_Sales_Model_Quote
     * @return bool
     */
    public function checkIfRateAllowed($quote)
    {
        $isAllowed = true;

        //check if all products is allowed
        /*if ($this->_checkProductsIfAvailable($quote) === false) {
            $isAllowed = false;
            $this->_error = $this->_helper->__("One or many products not available for delivery with this shipping option.");
        }

        //check max weight
        $maxWeight = (int)Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/max_package_weight');
        if ($maxWeight > 0 && $this->_checkWeightIfAvailable($maxWeight, $quote) === false) {
            $isAllowed = false;
            $this->_error = $this->_helper->__("Maximum weight limit reached for this shipping option.");
        }

        if ($this->_validPostCode($quote) === false) {
            $isAllowed = false;
        }

        //check country if not SE
        $countryId = $quote->getShippingAddress()->getCountryId();
        if ($countryId !== "SE") {
            $isAllowed = false;
        }

        if (!Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/active')) {
            $isAllowed = false;
        }*/

        return $isAllowed;
    }

    /**
     * @param $maxWeight
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    protected function _checkWeightIfAvailable($maxWeight, Mage_Sales_Model_Quote $quote)
    {
        $weight = 0;
        /** @var  $item Mage_Sales_Model_Quote_Item */
        foreach ($quote->getAllItems() as $item) {
            $weight += (float)$item->getWeight() * (int)$item->getQty();
        }

        return $maxWeight >= $weight;
    }

    /**
     * Check if products is available (having )
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _checkProductsIfAvailable(Mage_Sales_Model_Quote $quote)
    {
        $available = true;

        $specificProductsOnly = (bool)Mage::getStoreConfig("carriers/" . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . "/only_specific_products_allowed");
        if ($specificProductsOnly) {
            /** @var  $item Mage_Sales_Model_Quote_Item */
            foreach ($quote->getAllItems() as $item) {
                $productId = $item->getProductId();
                if (!Mage::getResourceModel('catalog/product')
                    ->getAttributeRawValue($productId, 'available_for_urbit', Mage::app()->getStore())) {
                    $available = false;
                }
            }
        }

        return $available;
    }

    /**
     * Returns the shipping rate after api call.
     * @param string $allowedMethod
     * @param string $title
     * @return false|Mage_Core_Model_Abstract
     * @internal param $String
     */

    public function getShippingRate($allowedMethod = "", $title = "")
    {
        $handling = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/handling_fee');
        $price = 0; //no fixed price

        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier(Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE);
        $method->setCarrierTitle(Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/title'));
        $method->setMethod($allowedMethod);
        $method->setMethodTitle($title);
        $method->setCost($price);
        $method->setPrice($price + $handling);

        return $method;
    }

    protected function _getErrorObject($message)
    {
        /** @var $method Mage_Shipping_Model_Rate_Result */
        $result = Mage::getModel('shipping/rate_result');

        $error = Mage::getModel('shipping/rate_result_error');
        $error->setCarrier(Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE);
        $error->setCarrierTitle(Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/title'));
        $error->setErrorMessage($message);

        $this->_showMethod = true;

        $result->append($error);

        return $result;
    }

    protected function _validPostCode($quote)
    {
        //tmp fix: no need to change this code later.
        $shipping = Mage::app()->getRequest()->getParam("shipping");
        $payment = Mage::app()->getRequest()->getParam("payment");

        if ($payment && !$shipping) {
            return true;
        } //fix for quickcheckout and save payment.

        $postcodeCheckEnabled = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/enable_postcode_check');

        if ($postcodeCheckEnabled) {

            $postcode = $quote->getShippingAddress()->getPostcode();
            if ($postcode !== "") {
                //hack to use urbits wp instead of api
                $validPostCode = $this->_checkZipCode($postcode);
            } else {
                $validPostCode = false;
            }
        } else {
            $validPostCode = true;
        }

        return $validPostCode;
    }

    public function getConfigData($field)
    {
        $data = parent::getConfigData($field);

        if ($field === "showmethod") {
            return $this->_showMethod;
        }

        return $data;
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
}
