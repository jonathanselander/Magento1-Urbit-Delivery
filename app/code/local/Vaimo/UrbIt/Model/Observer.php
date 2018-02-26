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
 * Class Vaimo_UrbIt_Model_Observer
 */
class Vaimo_UrbIt_Model_Observer
{
    /**
     * Create cart by call to Urb-it API
     * @param $observer
     */
    public function createCart($observer)
    {
        /*$order = Mage::getModel('sales/order')->loadByIncrementId('145000025');
        print_r($order);exit;*/

        $cart = Mage::getModel('checkout/cart')->getQuote();
        $cartItems = $cart->getAllItems();

        $result = array();
        $apiItems = array();

        foreach ($cartItems as $item) {
            $product = $item->getProduct();

            $apiItems[] = array(
                'sku'      => $product->getSku(),
                'name'     => $product->getName(),
                'vat'      => $this->priceFormat($this->getTaxPercent($product->getTaxClassId())),
                'price'    => $this->priceFormat($product->getPrice()),
                'quantity' => $item->getQty(),
            );
        }

        $result['items'] = $apiItems;

        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());
        $responseObj = $api->createCart($result);

        //save cart id to session
        if (isset($responseObj->response['id'])) {
            Mage::getSingleton('core/session')->setCartIdFromApi($responseObj->response['id']);
            Mage::log($responseObj->response['id']);
        }
    }

    /**
     * Create checkout by call to Urb-it API
     * @param $observer
     */
    public function createCheckout($observer)
    {
        $bodyForRequest = array(
            'cart_reference' => Mage::getSingleton('core/session')->getCartIdFromApi(),
        );

        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());
        $responseObj = $api->createCheckout($bodyForRequest);

        //TODO: check errors
        if (isset($responseObj->response['id'])) {
            Mage::getSingleton('core/session')->setCheckoutIdFromApi($responseObj->response['id']);
            Mage::log($responseObj->response['id']);
        }
    }

    /**
     * Save Urbit delivery information in magento order's custom fields
     * @param $observer
     * @return Vaimo_UrbIt_Model_Observer
     */
    public function saveOrderUrbitInformation($observer)
    {
        $checkoutId = Mage::getSingleton('core/session')->getCheckoutIdFromApi();

        $deliveryInfo = $this->getDeliveryInfoFromSession();

        $dateString = $deliveryInfo['day'] == "now" ? $this->getNowDeliveryTime() :
            $deliveryInfo['day'] . " " . $deliveryInfo['hour'] . ":" . $deliveryInfo['minute'] . ":00";

        $deliveryDate = DateTime::createFromFormat('Y-m-d H:i:s', $dateString, new DateTimeZone("UTC"));
        $formattedDeliveryDate = $deliveryDate->format('Y-m-d\TH:i:sP');

        $nowTime = new DateTime(null, new DateTimeZone("UTC"));
        $nowTimestamp = $nowTime->getTimestamp();

        $preparationTime = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/order_now_validation_time');

        if ($preparationTime) {
            $nowTimestamp += (int)$preparationTime * 60;
        }

        $attributes = array(
            'urbit_checkout_id'          => $checkoutId,
            'urbit_update_checkout_time' => $nowTimestamp,
            'urbit_triggered'            => 'false',
            'urbit_delivery_time'        => $formattedDeliveryDate,
            'urbit_message'              => $deliveryInfo['message'],
            'urbit_first_name'           => $deliveryInfo['firstname'],
            'urbit_last_name'            => $deliveryInfo['lastname'],
            'urbit_street'               => $deliveryInfo['address'],
            'urbit_city'                 => $deliveryInfo['city'],
            'urbit_postcode'             => $deliveryInfo['postcode'],
            'urbit_phone_number'         => $deliveryInfo['phone'],
            'urbit_email'                => $deliveryInfo['email']
        );

        $event = $observer->getEvent();
        $order = $event->getOrder();

        foreach ($attributes as $attrName => $attrValue) {
            $order->setData($attrName, $attrValue);
        }

        return $this;
    }

    /**
     * Find orders, which should be updated by Urb-it API (PUT request)
     */
    public function checkOrdersForUpdateCheckouts()
    {
        $orderCollection = Mage::getModel("sales/order")->getCollection();

        //get current timestamp
        $nowTime = new DateTime(null, new DateTimeZone("UTC"));
        $nowTimestamp = $nowTime->getTimestamp();

        foreach ($orderCollection as $order) {
            $isTriggered = $order->getUrbitTriggered();

            if (isset($isTriggered) && $isTriggered == 'false') {
                $orderUpdateCheckoutTime = $order->getUrbitUpdateCheckoutTime();

                if (isset($orderUpdateCheckoutTime) && $orderUpdateCheckoutTime != "" && (int)$orderUpdateCheckoutTime <= $nowTimestamp) {
                    $this->sendUpdateCheckout($order->getId());
                    $order->setData('urbit_triggered', 'true');
                    $order->save();
                }
            }
        }
    }

    /**
     * Check order's new status. If status = config's trigger status => send PUT with delivery info by Urb-it API
     * @param $observer
     */
    public function checkOrdersStatusForUpdateCheckout($observer)
    {
        Mage::log('update status');
        $order = $observer->getOrder();
        $configStatusTrigger = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/order_status_trigger');
        $checkoutId = $order->getUrbitCheckoutId();
        $isTriggered = $order->getUrbitTriggered();

        if ($checkoutId && $isTriggered == 'false') {
            $orderStatus = $order->getState();

            if ($orderStatus == $configStatusTrigger) {
                $this->sendUpdateCheckout($order->getId());
                $order->setData('urbit_triggered', 'true');
                $order->save();
            }
        }

    }

    /**
     * Update checkout information by PUT request to Urb-it API
     * @param $orderId
     */
    public function sendUpdateCheckout($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);

        $checkoutId = $order->getUrbitCheckoutId();

        if (!$checkoutId) {
            return;
        }

        $requestArray = array(
            'delivery_time' => $order->getUrbitDeliveryTime(),
            'message'       => $order->getUrbitMessage(),
            'recipient'     => array(
                'first_name'   => $order->getUrbitFirstName(),
                'last_name'    => $order->getUrbitLastName(),
                'address_1'    => $order->getUrbitStreet(),
                'address_2'    => "",
                'city'         => $order->getUrbitCity(),
                'postcode'     => $order->getUrbitPostcode(),
                'phone_number' => $order->getUrbitPhoneNumber(),
                'email'        => $order->getUrbitEmail()
            )
        );

        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());
        $responseObj = $api->updateCheckout($checkoutId, $requestArray);
    }

    /**
     * Get Urb-it delivery information, saved in session
     * @return array
     */
    protected function getDeliveryInfoFromSession()
    {
        $session = Mage::getSingleton('checkout/session');

        return array(
            'day'       => $session->getData('shipping_day', ''),
            'hour'      => $session->getData('shipping_hour', ''),
            'minute'    => $session->getData('shipping_minute', ''),
            'firstname' => $session->getData('shipping_firstname', ''),
            'lastname'  => $session->getData('shipping_lastname', ''),
            'address'   => $session->getData('shipping_address', ''),
            'city'      => $session->getData('shipping_city', ''),
            'email'     => $session->getData('shipping_email', ''),
            'message'   => $session->getData('shipping_message', ''),
            'postcode'  => $session->getData('shipping_postcode', ''),
            'phone'     => $session->getData('shipping_telephone', '')
        );
    }

    /**
     * Get nearest possible delivery time (for NOW delivery option)
     * result = current time + 1h 30m + store order preparation time
     * @return string
     */
    protected function getNowDeliveryTime()
    {
        $nowTime = new DateTime(null, new DateTimeZone("UTC"));
        $deliveryTime = strtotime('+1 hour +30 minutes', strtotime($nowTime->format('Y-m-d H:i:s')));

        $preparationTime = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/order_now_validation_time');

        if ($preparationTime) {
            $deliveryTime += (int)$preparationTime * 60;
        }

        $nextPossible = new DateTime();
        $nextPossible->setTimestamp($deliveryTime);

        return $nextPossible->format("Y-m-d H:i:s");
    }

    /**
     * format price for Urb-it API (ex. 59.99 => 5999 integer)
     * @param $price
     * @return float|int
     */
    protected function priceFormat($price)
    {
        return number_format((float)$price, 2, '.', '') * 100;
    }

    protected function getTaxPercent($productClassId)
    {
        $store = Mage::app()->getStore('default');
        $taxCalculation = Mage::getModel('tax/calculation');
        $request = $taxCalculation->getRateRequest(null, null, null, $store);

        return $taxCalculation->getRate($request->setProductClassId($productClassId));
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function saveShippingDetails($observer)
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $event = $observer->getEvent();
        $request = $event->getRequest();
        $shippingDelivery = new Varien_Object($request->getPost('shipping_delivery', array()));
        $shippingMethod = $request->getParam('shipping_method', false);

        if (!$shippingDelivery->isEmpty()) {
            $session = Mage::getSingleton('checkout/session');

            $session->setData('shipping_firstname', $shippingDelivery->getFirstname());
            $session->setData('shipping_lastname', $shippingDelivery->getLastname());
            $session->setData('shipping_day', $shippingDelivery->getDay());
            $session->setData('shipping_hour', $shippingDelivery->getHour());
            $session->setData('shipping_minute', $shippingDelivery->getMinute());
            $session->setData('shipping_address', $shippingDelivery->getStreet());
            $session->setData('shipping_city', $shippingDelivery->getCity());
            $session->setData('shipping_email', $shippingDelivery->getEmail());
            $session->setData('shipping_message', $shippingDelivery->getMessage());

            //in case of standard checkout, this will be empty
            if ($shippingDelivery->getTelephone() !== "") {
                $session->setData('shipping_telephone', $shippingDelivery->getTelephone());
            } else {
                $session->setData('shipping_telephone', $quote->getShippingAddress()->getTelephone());
            }

            //in case of standard checkout, this will be empty
            if ($shippingDelivery->getPostcode() !== "") {
                $session->setData('shipping_postcode', $shippingDelivery->getPostcode());
            } else {
                $session->setData('shipping_postcode', $quote->getShippingAddress()->getPostcode());
            }

            $session->setData('shipping_method', $shippingMethod);
        }
    }

    /**
     * @param $observer
     */
    public function insertBlock($observer)
    {
        $block = $observer->getBlock();
        $type = (string)$block->getType();

        if (
            $type === 'catalog/product_price'
            && Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/enable_price_block') === true
            && Mage::app()->getRequest()->getControllerName() !== 'product'
            && Mage::helper("vaimo_urbit/data")->isAvailableForOneHourDelivery($block->getProduct())
        ) {
            $_child = clone $block;

            $_child->setType('test/block');
            $block->setChild('child', $_child);
            $block->setTemplate('vaimo/urbit/price.phtml');
        }
    }
}
