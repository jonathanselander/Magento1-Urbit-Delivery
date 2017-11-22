<?php

class Vaimo_UrbIt_Test_Model_Api extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function testValidateDeliverySuccess()
    {
        $quote = $this->_createQuote();

        /** @var $api Vaimo_UrbIt_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock());

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($quote, $type, $datetime);

        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * @test
     */
    public function testValidateDeliveryServiceUnavailableError()
    {
        $quote = $this->_createQuote();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(503, "service_unavailable"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($quote, $type, $datetime);

        $this->assertEquals(503, $response->getStatus());
    }

    /**
     * @test
     */
    public function testValidateDeliveryUnprocessableEntityError()
    {
        $quote = $this->_createQuote();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(422, "unprocessable_entity"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($quote, $type, $datetime);

        $this->assertEquals(422, $response->getStatus());
    }

    /**
     * @test
     */
    public function testValidateDeliveryRetailUnauthorizedError()
    {
        $quote = $this->_createQuote();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(401, "retailer_unauthorized"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($quote, $type, $datetime);

        $this->assertEquals(401, $response->getStatus());
    }

    /**
     * @test
     */
    public function testValidateDeliveryInvalidPickupLocationError()
    {
        $quote = $this->_createQuote();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(500, "invalid_pickup_location"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($quote, $type, $datetime);

        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * @test
     */
    public function testValidateDeliveryInvalidDeliveryLocationError()
    {
        $quote = $this->_createQuote();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(500, "invalid_delivery_location"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($quote, $type, $datetime);

        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * @test
     */
    public function testValidateDeliveryInvalidDeliveryDateTimeError()
    {
        $quote = $this->_createQuote();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(500, "invalid_delivery_datetime"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($quote, $type, $datetime);

        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * @test
     */
    public function testValidateDeliveryUnauthorizedError()
    {
        $request = $this->_createQuote();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(401, "unauthorized"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->validateDelivery($request, $type, $datetime);

        $this->assertEquals(401, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderSuccess()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_UrbIt_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock());

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderServiceUnavailableError()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(503, "service_unavailable"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(503, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderUnprocessableEntityError()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(422, "unprocessable_entity"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(422, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderRetailUnauthorizedError()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(401, "retailer_unauthorized"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(401, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderInvalidPickupLocationError()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(500, "invalid_pickup_location"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderInvalidDeliveryLocationError()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(500, "invalid_delivery_location"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderInvalidDeliveryDateTimeError()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(500, "invalid_delivery_datetime"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(500, $response->getStatus());
    }

    /**
     * @test
     */
    public function testCreateOrderUnauthorizedError()
    {
        $order = $this->_createOrder();

        /** @var $api Vaimo_Urbit_Model_Urbit_Api */
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Test_Model_Api_ClientMock(401, "unauthorized"));

        $type = "onehour";
        $datetime = new Zend_Date();

        /** @var $response Vaimo_UrbIt_Model_Urbit_Api_Response */
        $response = $api->createOrder($order, "", $type, $datetime);

        $this->assertEquals(401, $response->getStatus());
    }


    private $_quote;

    /**
     * Fake shipping rate request
     * @return Mage_Sales_Model_Quote
     */
    private function _createQuote()
    {
        if (!Mage::getStoreConfig('payment/checkmo/active')) {
            Mage::app()->getStore()->setConfig('payment/checkmo/active', true);
        }

        if (!$this->_quote) {

            $customerObj = Mage::getModel('customer/customer');
            $customerObj->setId(1);
            $customerObj->setFirstName("test");
            $customerObj->setLastName("test");
            $customerObj->setEmail("info@vaimo.com");

            $storeId = $customerObj->getStoreId();

            $quote = Mage::getModel('sales/quote')->assignCustomer($customerObj);
            $storeObj = $quote->getStore()->load($storeId);
            $quote->setStore($storeObj);

            // add products to quote
            $product = Mage::getModel('catalog/product');
            $product->setId(1);
            $product->setTitle("Test product");
            $product->setPrice(100);
            $product->unsSkipCheckRequiredOption();

            $quoteItem = Mage::getModel('sales/quote_item')->setProduct($product)->setQty(1);

            $quote->addItem($quoteItem);
            $quoteItem->checkData();

            $_custom_address = array(
                'firstname' => 'Test',
                'lastname' => 'Test',
                'street' => array(
                    '0' => 'Sample address part1',
                    '1' => 'Sample address part2',
                ),
                'city' => 'Stockholm',
                'region_id' => '',
                'region' => '',
                'postcode' => '12065',
                'country_id' => 'SE',
                'telephone' => '0700000000',
            );
            $customAddress = Mage::getModel('customer/address');
            $customAddress->setData($_custom_address)
                ->setCustomerId(1)
                ->setIsDefaultBilling('1')
                ->setIsDefaultShipping('1')
                ->setSaveInAddressBook('1');


            // addresses
            $shippingAddress = new Mage_Sales_Model_Quote_Address();
            $shippingAddress->setData($customAddress);
            $billingAddress = new Mage_Sales_Model_Quote_Address();
            $billingAddress->setData($customAddress);
            $quote->setShippingAddress($customAddress);
            $quote->setBillingAddress($customAddress);

            // coupon code
            if (!empty($couponCode)) $quote->setCouponCode($couponCode);


            // shipping method an collect
            $quote->getShippingAddress()->setShippingMethod("urbit_onehour");
            $quote->getShippingAddress()->setCollectShippingRates(false);
            //$quote->getShippingAddress()->collectShippingRates();
            //$quote->collectTotals();
            $quote->setIsActive(0);

            $quote->reserveOrderId();

            // set payment method
            $quotePayment = $quote->getPayment(); // Mage_Sales_Model_Quote_Payment
            $quotePayment->setMethod("checkmo");
            $quote->setPayment($quotePayment);

            $this->_quote = $quote;
        }
        return $this->_quote;

    }

    private $_order;

    /**
     * Fake order
     * @return Mage_Sales_Model_Order
     */
    private function _createOrder()
    {
        if (!$this->_order) {
            $quote = $this->_createQuote();

            // convert quote to order
            $convertQuote = Mage::getSingleton('sales/convert_quote');

            $order = $convertQuote->addressToOrder($quote->getShippingAddress());
            $orderPayment = $convertQuote->paymentToOrderPayment($quote->getPayment());
            $paymentData = $orderPayment->getData();

            // convert quote addresses
            $order->setBillingAddress($convertQuote->addressToOrderAddress($quote->getBillingAddress()));
            $order->setShippingAddress($convertQuote->addressToOrderAddress($quote->getShippingAddress()));

            // set payment options
            $order->setPayment($orderPayment);

            if ($paymentData) {
                /*$order->getPayment()->setCcNumber($paymentData["ccNumber"]);
                $order->getPayment()->setCcType($paymentData["ccType"]);
                $order->getPayment()->setCcExpMonth($paymentData["ccExpMonth"]);
                $order->getPayment()->setCcExpYear($paymentData["ccExpYear"]);
                $order->getPayment()->setCcLast4(substr($paymentData["ccNumber"], -4));*/
            }
            // convert quote items
            foreach ($order->getQuote()->getAllItems() as $item) {
                // @var $item Mage_Sales_Model_Quote_Item
                $orderItem = $convertQuote->itemToOrderItem($item);

                $options = array();
                if ($productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct())) {

                    $options = $productOptions;
                }
                if ($addOptions = $item->getOptionByCode('additional_options')) {
                    $options['additional_options'] = unserialize($addOptions->getValue());
                }
                if ($options) {
                    $orderItem->setProductOptions($options);
                }
                if ($item->getParentItem()) {
                    $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
                }
                $order->addItem($orderItem);
            }

            $order->setCanShipPartiallyItem(false);

            $this->_order = $order;
        }

        return $this->_order;

    }

}