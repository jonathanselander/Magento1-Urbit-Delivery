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
    private $_openHours = null;

    public function __construct()
    {
        $this->setTemplate('vaimo/urbit/deliverydetails.phtml');
    }

    /** Get opening hours from urbit
     * @return array
     */
    public function getOpeningHours(){

        if ($this->_openHours == null) {
            $from = date('Y-m-d 00:00:01');
            $to = date('Y-m-d 23:59:00', strtotime('+4 day', strtotime($from)));

            $fromDate = new Zend_Date();
            $fromDate->setLocale('sv_SE')->setTimezone('CET');
            $fromDate->set($from);
            $toDate = new Zend_Date();
            $toDate->setLocale('sv_SE')->setTimezone('CET');
            $toDate->set($to);

            $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client())
                ->getOpeningHours($fromDate, $toDate);

            $this->_openHours = array();
            if (isset($api) && $api->getStatus() == "200") {
                $openingHours = $api->getResponse();
                foreach ($openingHours as $openHour) {
                    $day = substr($openHour["from"], 0, 10);
                    if ($openHour["closed"] == true) {
                        $this->_openHours[$day] = false;
                    } else {
                        $this->_openHours[$day] = array(
                            "from" => substr($openHour["from"], 11, 2) . ":" . substr($openHour["from"], 14, 2),
                            "to" => substr($openHour["to"], 11, 2) . ":" . substr($openHour["to"], 14, 2),
                        );
                    }
                }
            }
        }
        return $this->_openHours;
    }

    /**
     * Fetch a list of next N days eligible for delivery using UrbIt
     *
     * @return array
     */
    public function getDayList($openHours = array())
    {
        $result = array();
        if ($this->_openHours == null){
            $this->_openHours = $this->getOpeningHours();
        }

        foreach($openHours as $day => $hours){

            $now = strtotime('00:00:00');
            $date = strtotime($day);

            if($date < $now) continue;

            $datediff = $date - $now;
            $days_from_today = floor($datediff/(60*60*24));

            switch($days_from_today){
                case 0:
                    $result[] = array(
                        'label' => $this->_getHelper()->__($this->__("Today")),
                        'date' => date('Y-m-d', $date),
                    );
                    break;
                case 1:
                    $result[] = array(
                        'label' => $this->_getHelper()->__($this->__("Tomorrow")),
                        'date' => date('Y-m-d', $date),
                    );
                    break;
                default:
                    $result[] = array(
                        'label' => $this->_getHelper()->__(date('l', $date)) . ' ' . date('d/m', $date),
                        'date' => date('Y-m-d', $date),
                    );
            }
        }

        return $result;
    }

    public function getAllowedMethods(){
        return Mage::getModel("vaimo_urbit/carrier_onehour")->getAllowedMethods();
    }


    /**
     * Get a helper instance
     *
     * @return Vaimo_UrbIt_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('vaimo_urbit');
    }


    public function getRateCode(){
        if ($this->hasData("rate")) {
            $rate = $this->getData("rate");
            return $rate->getCode();
        }else{
            return $this->getNameInLayout();
        }
    }
}
