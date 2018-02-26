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
    /**
     * @var Vaimo_UrbIt_Model_Urbit_Api_Client
     */
    protected $apiClient;

    /**
     * @var string|int
     */
    protected $apiPickupLocationId;

    /**
     * Vaimo_UrbIt_Model_Urbit_Api constructor.
     * @param Vaimo_UrbIt_Model_Urbit_Api_Client $apiClient
     */
    public function __construct(Vaimo_UrbIt_Model_Urbit_Api_Client $apiClient)
    {
        $this->apiClient = $apiClient;
        $this->apiPickupLocationId = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/pickup_location_id');
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

        return $this->apiClient->doCall("POST", "delivery/validate", $data);
    }

    /**
     * @param $quote Mage_Sales_Model_Quote
     * @param string $deliveryType
     * @param $datetime
     * @return array
     */
    protected function _prepareValidateDelivery($quote, $deliveryType = "", $datetime)
    {
        $data = array(
            "delivery_type"        => $deliveryType,
            "postal_code"          => $quote->getShippingAddress()->getPostcode(),
            "delivery_expected_at" => $datetime->get('yyyy-MM-ddTHH:mm:ss.000000Z'),
            "pickup_location"      => array(
                "id" => $this->apiPickupLocationId,
            ),
            "articles"             => array(),
        );

        // only config + simple products supported (config products will only send the simple product)
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductType() === "simple") {
                $data["articles"][] = array(
                    "identifier"  => $item->getSku(),
                    "quantity"    => $item->getQty(),
                    "description" => $item->getName(),
                );
            }
        }

        return $data;
    }

    /**
     * POST request to Urb-it API for create cart
     * @param $cartInfo
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function createCart($cartInfo)
    {
        /* $response = $this->apiClient->doCall(
             "GET",
             "deliveryhours"
         );

         print_r($response->getResponse()['items']);exit;*/

        return $this->apiClient->doCall("POST", "carts", $cartInfo);
    }

    /**
     * POST request to Urb-it API for create checkout
     * @param $args
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function createCheckout($args)
    {
        return $this->apiClient->doCall("POST", "checkouts", $args);
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
    public function createOrder($order, $comment, $deliveryType, $datetime)
    {
        $data = $this->_prepareCreateOrder($order, $comment, $deliveryType, $datetime);

        return $this->apiClient->doCall("POST", "order/create", $data);
    }

    /**
     * PUT request to Urb-it API for update delivery information
     * @param $checkoutId
     * @param $args
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function updateCheckout($checkoutId, $args)
    {
        return $this->apiClient->doCall("PUT", "checkouts/" . $checkoutId . "/delivery", $args);
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

        if (count($streets) > 0) {
            $street1 = isset($streets[0]) ? $streets[0] : "";
            $street2 = isset($treets[1]) ? $streets[1] : "";

            if (count($streets) > 2) {
                $comment .= "\n#Följande gatuaddressrad rymdes inte:";
                for ($i = 2; $i < count($streets) - 1; $i++) {
                    $comment .= $streets[$i];
                }
            }
        }

        $data = array(
            "retailer_reference_id" => $order->getIncrementId(),
            "delivery_type"         => $deliveryType,
            "delivery_expected_at"  => $datetime->get('yyyy-MM-ddTHH:mm:ss.000000Z'),
            "pickup_location"       => array(
                "id" => $this->apiPickupLocationId,
            ),
            "articles"              => array(),
            "delivery"              => array(
                "address"          => array(
                    "street"       => $street1,
                    "street2"      => $street2,
                    "postal_code"  => str_replace(" ", "", $order->getShippingAddress()->getPostcode()),
                    "city"         => $order->getShippingAddress()->getCity(),
                    "country"      => $order->getShippingAddress()->getCountry(),
                    "company_name" => $order->getShippingAddress()->getCompany(),
                    "care_of"      => "",
                ),
                "first_name"       => $order->getShippingAddress()->getFirstname(),
                "last_name"        => $order->getShippingAddress()->getLastname(),
                "email"            => $order->getCustomerEmail(),
                "cell_phone"       => $order->getShippingAddress()->getTelephone(),
                "consumer_comment" => $comment,
            ),
            "total_amount_excl_vat" => $order->getSubtotal() + $order->getShippingAmount(),
        );

        // only config + simple products supported (config products will only send the simple product)
        /** var $item Mage_Sales_Model_Order_Item */
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() === "simple") {
                $data["articles"][] = array(
                    "identifier"  => $item->getSku(),
                    "quantity"    => (int)$item->getQtyOrdered(),
                    "description" => $item->getName(),
                );
            }
        }

        return $data;
    }

    /**
     * @param $streets
     * @param $characterLimit
     * @return array
     */
    protected function _mergeStreetAddress($streets, $characterLimit)
    {
        $lines = explode("\n", $streets);
        $newlines = array();
        $newline = "";

        foreach ($lines as $line) {
            $line = trim($line);

            if ((strlen($newline) + strlen($line) + 2) < $characterLimit) {
                $newline .= ", " . $line;
            } else {
                $newlines[] = $newline;
                $newline = "";
            }
        }

        if (count($newlines) === 0) {
            $newlines[] = $newline;
        }

        return $newlines;
    }

    /**
     * Call to API. Request possible delivery hours
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function getDeliveryHours()
    {
        return $this->apiClient->doCall(
            "GET",
            "deliveryhours"
        );
    }

    /**
     * Call to API. Validate delivery address
     * @param string $street
     * @param string $postcode
     * @param string $city
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     */
    public function validateDeliveryAddress($street = '', $postcode = "", $city = "")
    {
        return $this->apiClient->doCall(
            "get",
            "address?" . http_build_query(
                array(
                    'street'   => $street,
                    'postcode' => $postcode,
                    'city'     => $city,
                )
            )
        );
    }

    /**
     * @param $postcode
     * @return Vaimo_UrbIt_Model_Urbit_Api_Response
     * @throws Zend_Cache_Exception
     */
    public function validatePostcode($postcode)
    {
        return $this->apiClient->doCachedCall(
            "POST",
            "postalcode/validate",
            array(
                "postal_code" => $postcode,
            ),
            3600
        );
    }
}
