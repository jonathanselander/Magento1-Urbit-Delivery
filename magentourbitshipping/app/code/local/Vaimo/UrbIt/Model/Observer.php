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

class Vaimo_UrbIt_Model_Observer
{
    /**
     * Create an UrbIt delivery
     *
     * @param Varien_Event_Observer $observer
     */
    public function createUrbitOrder($observer)
    {
        $orderId = $observer->getData('order_ids');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        $shipping = $order->getShippingMethod();

        $session = Mage::getSingleton('checkout/session');
        $telephone = $session->getData('shipping_telephone');
        $comment = $session->getData('shipping_comment');
        $method = $session->getData('shipping_method');
        $day = $session->getData('shipping_day');
        $hour = $session->getData('shipping_hour');
        $minute = $session->getData('shipping_minute');

        /** @var Vaimo_UrbIt_Helper_Data $helper */
        $helper = Mage::helper('vaimo_urbit');

        if (strpos($method, "specific") !== false) {
            $type = "Specific";
            $datetime = new Zend_Date();
            $datetime->setLocale("sv_SE")->setTimezone("CET");
            $datetime->set($day . " " . $hour . ":" . $minute);
        } else {
            $type = "OneHour";
            $datetime = Zend_Date::now();
            $datetime->setLocale("sv_SE")->setTimezone("CET");
            $datetime->addMinute(60);
        }

        if (
            strpos($shipping, 'urbit_onehour') !== false &&
            in_array($order->getStatus(), $helper->getCompatibleStatuses())
        ) {
            Mage::dispatchEvent('vaimo_urbit_createorder_before', array("order" => $order));

            $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());
            $order->getShippingAddress()->setTelephone($telephone);
            $responseObj = $api->createOrder($order, $comment, $type, $datetime);

            $response = $responseObj->getResponse();
            Mage::getSingleton('checkout/session')->setUrbitResponse($response);

            if ($responseObj->getSuccess()) {
                $order->addStatusHistoryComment($helper->__('Order created in urb·it: %s', $response["order_number"]));
                $order->setIsVisibleOnFront(false)
                    ->setIsCustomerNotified(false);
            } else {
                $order->addStatusHistoryComment(
                    $helper->__('Order failed to be created delivery in urb·it: %s', $responseObj->getErrorMessage())
                );
                $order->setIsVisibleOnFront(false)
                    ->setIsCustomerNotified(false);
            }

            $order->setShippingResponse(json_encode($response));

            Mage::dispatchEvent(
                'vaimo_urbit_createorder_after',
                array("order" => $order, "urbit_response" => $response)
            );

            $order->save();
        }
    }

    public function validateUrbitOrder($observer)
    {
        $session = Mage::getSingleton('checkout/session');
        $method = $session->getData('shipping_method', false);

        /** @var Vaimo_UrbIt_Helper_Data $helper */
        $helper = Mage::helper('vaimo_urbit');

        if (in_array($method, array('urbit_onehour_urbit_onehour','urbit_onehour_urbit_specific'))) {
            $day = $session->getData("shipping_day", '');
            $hour = $session->getData("shipping_hour", '');
            $minute = $session->getData("shipping_minute", '');
            $postCode = $session->getData("shipping_postcode", '');

            if (strpos($method, "specific") !== false) {
                $type = 'Specific';
                $datetime = new Zend_Date();
                $datetime->setLocale('sv_SE')->setTimezone('CET');
                $datetime->set($day . " " . $hour . ":" . $minute);
            } else {
                $type = 'OneHour';
                $datetime = Zend_Date::now();
                $datetime->setLocale('sv_SE')->setTimezone('CET');
                $datetime->addMinute(60);
            }

            if ($datetime) {
                $quote = Mage::getModel('checkout/cart')->getQuote();

                if ($quote->getShippingAddress()->getPostcode() != "") {
                    $quote->getShippingAddress()->setPostcode($postCode);
                }

                $api = Mage::getModel('vaimo_urbit/urbit_api', new Vaimo_UrbIt_Model_Urbit_Api_Client());
                $responseObject = $api->validateDelivery($quote, $type, $datetime);
                $response = $responseObject->getResponse();

                if (isset($response['status'])) {
                    if ($response['status'] == 404 && strpos($response['status'], "postcode") >= 0) {
                        Mage::throwException($helper->__("Urbit cannot be delivered to postcode %s", $postCode));
                    } else {
                        Mage::throwException($helper->__("Urbit cannot be delivered at this time, please try another date/time."));
                    }

                }

                Mage::getSingleton('core/session')->setLastUrbitResponse($response);
            } else {
                Mage::throwException($helper->__("Please specify a date of delivery with Urbit shipping method."));
            }
        }
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

                $session->setData('shipping_comment', $shippingDelivery->getComment());
                $session->setData('shipping_day', $shippingDelivery->getDay());
                $session->setData('shipping_hour', $shippingDelivery->getHour());
                $session->setData('shipping_minute', $shippingDelivery->getMinute());

                //in case of standard checkout, this will be empty
                if ($shippingDelivery->getTelephone() != "") {
                    $session->setData('shipping_telephone', $shippingDelivery->getTelephone());
                }else{
                    $session->setData('shipping_telephone', $quote->getShippingAddress()->getTelephone());
                }

                //in case of standard checkout, this will be empty
                if ($shippingDelivery->getPostcode() != "") {
                    $session->setData('shipping_postcode', $shippingDelivery->getPostcode());
            }else{
                $session->setData('shipping_postcode', $quote->getShippingAddress()->getPostcode());
            }

            $session->setData('shipping_method', $shippingMethod);
        }
    }

    public function insertBlock($observer){
        $block = $observer->getBlock();
        $type = $block->getType();
        $category = Mage::registry('current_category');

        if (Mage::getStoreConfig('carriers/urbit_onehour/enable_price_block') == true &&
            Mage::app()->getRequest()->getControllerName()!='product') {
            if(Mage::helper("vaimo_urbit/data")->isAvailableForOneHourDelivery($block->getProduct())) {
                if ($type == 'catalog/product_price') {
                    $_child = clone $block;
                    $_child->setType('test/block');
                    $block->setChild('child', $_child);
                    $block->setTemplate('vaimo/urbit/price.phtml');
                }
            }
        }
    }
}