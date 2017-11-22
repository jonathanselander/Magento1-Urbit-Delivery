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

class Vaimo_UrbIt_Model_Urbit_Api
{

    private $_apiClient;
    private $_apiPickupLocationId;
    private $_carrierCode = "urbit_onehour";

    public function __construct($apiClient)
    {
        $this->_apiClient = $apiClient;
        $this->_apiPickupLocationId = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/pickup_location_id');
    }

    /**
     * Validate onehour delivery as an shipping option
     *
     * @param $quote Mage_Sales_Model_Quote
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function validateDelivery($quote, $deliveryType, $datetime)
    {
        // call webservice
        $data = $this->_prepareValidateDelivery($quote, $deliveryType, $datetime);
        return $this->_apiClient->doCall("POST", "delivery/validate", $data);
    }

    /**
     * @param $quote Mage_Sales_Model_Quote
     * @param string $deliveryType
     * @param $datetime
     * @return array
     */
    private function _prepareValidateDelivery($quote, $deliveryType = "", $datetime)
    {
        $data = array(
            "delivery_type" => $deliveryType,
            "postal_code" => $quote->getShippingAddress()->getPostcode(),
            "delivery_expected_at" => $datetime->get('yyyy-MM-ddTHH:mm:ss.000000Z'),
            "pickup_location" => array(
                "id" => $this->_apiPickupLocationId
            ),
            "articles" => array(),
        );

        // only config + simple products supported (config products will only send the simple product)
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductType() == "simple") {
                $data["articles"][] = array(
                    "identifier" => $item->getSku(),
                    "quantity" => $item->getQty(),
                    "description" => $item->getName(),
                );
            }
        }

        return $data;
    }

    /**
     * Create an order to UrbIt if selected as an shipping option
     *
     * @param $order Mage_Sales_Model_Order
     * @param string $comment
     * @param $deliveryType
     * @param $datetime
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function createOrder($order, $comment = "", $deliveryType, $datetime)
    {
        // call webservice
        $data = $this->_prepareCreateOrder($order, $comment, $deliveryType, $datetime);
        $response = $this->_apiClient->doCall("POST", "order/create", $data);
        return $response;
    }

    /**
     * @param $order Mage_Sales_Model_Order
     * @param string $comment
     * @param $deliveryType
     * @param $datetime
     * @return array
     */
    protected function _prepareCreateOrder($order, $comment, $deliveryType, $datetime)
    {
        $streets = $this->_mergeStreetAddress($order->getShippingAddress()->getStreet(-1), 50);
        $street1 = "";
        $street2 = "";
        if(sizeof($streets) > 0){
            $street1 = isset($streets[0]) ? $streets[0] : "";
            $street2 = isset($treets[1]) ? $streets[1] : "";
            if(sizeof($streets) > 2){
                $comment = $comment . "\n#Följande gatuaddressrad rymdes inte:";
                for($i=2; $i<sizeof($streets)-1; $i++){
                    $comment = $comment . $streets[$i];
                }
            }
        }

        $data = array(
            "retailer_reference_id" => $order->getIncrementId(),
            "delivery_type" => $deliveryType,
            "delivery_expected_at" => $datetime->get('yyyy-MM-ddTHH:mm:ss.000000Z'),
            "pickup_location" => array(
                "id" => $this->_apiPickupLocationId
            ),
            "articles" => array(),
            "delivery" => array(
                "address" => array(
                    "street" => $street1,
                    "street2" => $street2,
                    "postal_code" => str_replace(" ", "", $order->getShippingAddress()->getPostcode()),
                    "city" => $order->getShippingAddress()->getCity(),
                    "country" => $order->getShippingAddress()->getCountry(),
                    "company_name" => $order->getShippingAddress()->getCompany(),
                    "care_of" => "",
                ),
                "first_name" => $order->getShippingAddress()->getFirstname(),
                "last_name" => $order->getShippingAddress()->getLastname(),
                "email" => $order->getCustomerEmail(),
                "cell_phone" => $order->getShippingAddress()->getTelephone(),
                "consumer_comment" => $comment
            ),
            "total_amount_excl_vat" => $order->getSubtotal() + $order->getShippingAmount()
        );

        // only config + simple products supported (config products will only send the simple product)
        /** var $item Mage_Sales_Model_Order_Item */
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() == "simple") {
                $data["articles"][] = array(
                    "identifier" => $item->getSku(),
                    "quantity" => (int)$item->getQtyOrdered(),
                    "description" => $item->getName(),
                );
            }
        }

        return $data;
    }

    private function _mergeStreetAddress($streets, $characterLimit) {
        $lines = explode("\n", $streets);
        $newlines = array();
        $newline = "";
        foreach($lines as $line){
            $line = trim($line);
            if ((strlen($newline) + strlen($line) + 2) < $characterLimit){
                $newline = $newline . ", "  . $line;
            }else{
                $newlines[] = $newline;
                $newline = "";
            }
        }
        if(sizeof($newlines) == 0){
            $newlines[] = $newline;
        }
        return $newlines;
    }

    public function getOpeningHours($fromDate, $toDate)
    {
        $data = array(
            "from" => $fromDate->get('yyyy-MM-ddTHH:mm:ss.000000Z'),
            "to" => $toDate->get('yyyy-MM-ddTHH:mm:ss.000000Z')
        );

        // call webservice
        $response = $this->_apiClient->doCachedCall("GET", "openinghours", $data, 3600);
        return $response;
    }

    public function validatePostcode($postcode){

        $data = array(
            "postal_code" => $postcode
        );

        // call webservice
        $response = $this->_apiClient->doCachedCall("POST", "postalcode/validate", $data, 3600);

        return $response;

    }



}
