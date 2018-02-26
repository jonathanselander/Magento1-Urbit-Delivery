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
class Vaimo_Urbit_Block_DeliveryDetails extends Mage_Core_Block_Template
{
    protected $_openHours = null;

    /**
     * Vaimo_Urbit_Block_DeliveryDetails constructor.
     */
    public function __construct()
    {
        $this->setTemplate('vaimo/urbit/deliverydetails.phtml');

        parent::__construct();
    }

    /**
     * Get delivery hours from Urb-it API
     * API returns information about: closing_time, opening_time, closed, last_delivery, first_delivery, pickup_delay
     * @return mixed
     */
    public function getDeliveryHours()
    {
        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());

        /** @var Vaimo_UrbIt_Model_Urbit_Api_Response $response */
        $response = $api->getDeliveryHours();

        $items = $response->getResponse()['items'];

        return $items;
    }

    /**
     * Get options with possible delivery days for frontend selectbox
     * @param array $openHours
     * @return array
     */
    public function getDayList($openHours = array())
    {
        $optionArray = array();

        foreach ($openHours as $item) {
            if ($item['closed'] == 1) {
                continue;
            }

            $now = strtotime('00:00:00');
            $date = strtotime($item['first_delivery']);

            if ($date < $now) {
                continue;
            }

            $dateDiff = $date - $now;
            $days_from_today = floor($dateDiff / (60 * 60 * 24));

            switch ($days_from_today) {
                case 0:
                    $optionArray[] = array(
                        'label' => $this->_getHelper()->__($this->__("Today")),
                        'date'  => date('Y-m-d', $date),
                    );
                    break;
                case 1:
                    $optionArray[] = array(
                        'label' => $this->_getHelper()->__($this->__("Tomorrow")),
                        'date'  => date('Y-m-d', $date),
                    );
                    break;
                default:
                    $optionArray[] = array(
                        'label' => date('d/m', $date),
                        'date'  => date('Y-m-d', $date),
                    );
            }
        }

        return $optionArray;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return Mage::getModel("vaimo_urbit/carrier_onehour")->getAllowedMethods();
    }

    /**
     * Get a helper instance
     *
     * @return Vaimo_UrbIt_Helper_Data|Mage_Core_Helper_Abstract
     */
    protected function _getHelper()
    {
        return Mage::helper('vaimo_urbit');
    }

    /**
     * @return string
     */
    public function getRateCode()
    {
        if ($this->hasData("rate")) {
            return $this->getData("rate")->getCode();
        }

        return $this->getNameInLayout();
    }
}
