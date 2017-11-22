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
    /**
     * unique internal shipping method identifier
     *
     * @var string [a-z0-9_]
     */
    private $_carrierCode = "urbit_onehour";
    private $_showMethod = false;
    private $_error = null;
    private $_helper = null;

    /** @var $_api Vaimo_UrbIt_Model_Urbit_Api */
    private $_api = null;

    /**
     * @param null $api For unit testing purposes only
     */
    public function __construct($api = null)
    {
        if ($api != null) {
            $this->_api = $api;
        } else {
            $this->_api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());
        }
        $this->_helper = Mage::helper("vaimo_urbit/data");
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
     */
    public function getAllowedMethods()
    {
        return array(
            'urbit_specific' => Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/method_specific'),
            'urbit_onehour' => Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/method_onehour')
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
        if ($this->_checkProductsIfAvailable($quote) == false) {
            $isAllowed = false;
            $this->_error = $this->_helper->__("One or many products not available for delivery with this shipping option.");
        }

        //check max weight
        $maxWeight = (int)Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/max_package_weight');
        if ($maxWeight > 0 && $this->_checkWeightIfAvailable($maxWeight, $quote) == false) {
            $isAllowed = false;
            $this->_error = $this->_helper->__("Maximum weight limit reached for this shipping option.");
        }

        if($this->_validPostCode($quote) == false){
            $isAllowed = false;
        }

        //check country if not SE
        $countryId = $quote->getShippingAddress()->getCountryId();
        if ($countryId != "SE") {
            $isAllowed = false;
        }

        if (!Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/active')) {
            $isAllowed = false;
        }

        return $isAllowed;
    }

    /**
     * @param $maxWeight
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    private function _checkWeightIfAvailable($maxWeight, Mage_Sales_Model_Quote $quote)
    {
        $weight = 0;
        /** @var  $item Mage_Sales_Model_Quote_Item */
        foreach ($quote->getAllItems() as $item) {
            $weight += (int)$item->getWeight();
        }
        return $maxWeight >= $weight ? true : false;
    }

    /**
     * Check if products is available (having )
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    private function _checkProductsIfAvailable(Mage_Sales_Model_Quote $quote)
    {
        $available = true;

        $specificProductsOnly = (bool)Mage::getStoreConfig("carriers/urbit_onehour/only_specific_products_allowed");
        if ($specificProductsOnly) {
            /** @var  $item Mage_Sales_Model_Quote_Item */
            foreach ($quote->getAllItems() as $item) {
                $productId = $item->getProductId();
                if (!Mage::getResourceModel('catalog/product')->getAttributeRawValue($productId, 'available_for_urbit', Mage::app()->getStore())) {
                    $available = false;
                }
            }
        }
        return $available;
    }

    /**
     * Returns the shipping rate after api call.
     * @param $allowedMethod
     * @param $title
     * @return Mage_Shipping_Model_Rate_Result_Method
     * @internal param $String
     */

    public function getShippingRate($allowedMethod = "", $title = "")
    {
        $handling = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/handling_fee');
        $price = 0; //no fixed price

        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_carrierCode);
        $method->setCarrierTitle(Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/title'));
        $method->setMethod($allowedMethod);
        $method->setMethodTitle($title);
        $method->setCost($price);
        $method->setPrice($price + $handling);

        return $method;
    }

    private function _getErrorObject($message)
    {
        /** @var $method Mage_Shipping_Model_Rate_Result */
        $result = Mage::getModel('shipping/rate_result');

        $error = Mage::getModel('shipping/rate_result_error');
        $error->setCarrier($this->_carrierCode);
        $error->setCarrierTitle(Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/title'));
        $error->setErrorMessage($message);

        $this->_showMethod = true;

        $result->append($error);

        return $result;
    }


    private function _validPostCode($quote)
    {
        //tmp fix: no need to change this code later.
        $shipping = Mage::app()->getRequest()->getParam("shipping");
        $payment = Mage::app()->getRequest()->getParam("payment");
        if(isset($payment) && !isset($shipping)) return true; //fix for quickcheckout and save payment.

        $postcodeCheckEnabled = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/enable_postcode_check');

        if ($postcodeCheckEnabled) {

            $postcode = $quote->getShippingAddress()->getPostcode();
            if ($postcode != "") {

                //hack to use urbits wp instead of api
                $validPostCode = $this->_checkZipcode($postcode);

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

        if ($field = "showmethod") {
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
    private function _checkZipcode($zipCode)
    {
        $postcodes = Mage::getStoreConfig('carriers/urbit_onehour/eligible_postcodes');
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
