<?php

/**
 * Class Vaimo_UrbIt_PostcodeController
 */
class Vaimo_UrbIt_DateController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get options for possible delivery days selectbox on frontend Urb-it form's
     * AJAX
     */
    public function dayOptionsAction()
    {
        $openHours = $this->getDeliveryHours();

        $optionArray = array();

        $isShowNow = false;
        $utcTimeZone = new DateTimeZone("UTC");
        $nowDeliveryTime = $this->getNowDeliveryTime();

        foreach ($openHours as $item) {
            if ($item['closed'] == 1) {
                continue;
            }

            $deliveryTimestamp = $this->getNextPossibleDeliveryTime();

            $firstDeliveryObj = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item['first_delivery'], $utcTimeZone);
            $firstDeliveryObj->setTimestamp(strtotime('+5 minutes', $firstDeliveryObj->getTimestamp()));

            $lastDeliveryObj = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item['last_delivery'], $utcTimeZone);
            $lastDeliveryObj->setTimestamp(strtotime('-5 minutes', $lastDeliveryObj->getTimestamp()));

            $firstDeliveryTimestamp = $firstDeliveryObj->getTimestamp();
            $lastDeliveryTimestamp = $lastDeliveryObj->getTimestamp();

            if ($lastDeliveryTimestamp < $deliveryTimestamp) {
                continue;
            }

            if ($firstDeliveryTimestamp <= $nowDeliveryTime && $lastDeliveryTimestamp >= $nowDeliveryTime) {
                $isShowNow = true;
            }

            $dateDiff = $lastDeliveryTimestamp - $deliveryTimestamp;
            $days_from_today = floor($dateDiff / (60 * 60 * 24));

            switch ($days_from_today) {
                case 0:
                    $optionArray[] = array(
                        'label' => $this->_getHelper()->__($this->__("Today")),
                        'date'  => date('Y-m-d', $firstDeliveryTimestamp),
                    );
                    break;
                case 1:
                    $optionArray[] = array(
                        'label' => $this->_getHelper()->__($this->__("Tomorrow")),
                        'date'  => date('Y-m-d', $firstDeliveryTimestamp),
                    );
                    break;
                default:
                    $optionArray[] = array(
                        'label' => date('d/m', $firstDeliveryTimestamp),
                        'date'  => date('Y-m-d', $firstDeliveryTimestamp),
                    );
            }
        }

        //exit;

        if ($isShowNow) {
            $nowOption = array(
                'label' => 'Now',
                'date'  => 'now'
            );

            array_unshift($optionArray, $nowOption);
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($optionArray));
    }

    /**
     * Get nearest possible specific delivery time
     * result = Preparation time (defined in BO) + 1h30 min + 15 min
     * @return false|float|int
     */
    protected function getNextPossibleDeliveryTime()
    {
        $nowTime = $this->getNowDeliveryTime();

        //add 15 min (900s) to now delivery time
        return $nowTime + 900;
    }

    /**
     * Get now delivery time
     * result = Preparation time (defined in BO) + 1h30 min
     * @return false|float|int
     */
    protected function getNowDeliveryTime()
    {
        $nowTime = new DateTime(null, new DateTimeZone("UTC"));

        $deliveryTime = strtotime('+1 hour +30 minutes', strtotime($nowTime->format('Y-m-d H:i:s')));
        $preparationTime = Mage::getStoreConfig('carriers/' . Vaimo_UrbIt_Model_System_Config_Source_Environment::CARRIER_CODE . '/order_now_validation_time');

        if ($preparationTime) {
            $deliveryTime += (int)$preparationTime * 60;
        }

        return $deliveryTime;
    }

    /**
     * Get options for possible delivery hours selectbox on frontend Urb-it form's
     * AJAX
     */
    public function hourOptionsAction()
    {
        $startDate = $this->getRequest()->getPost('selected_date');
        $openHours = $this->getDeliveryHours();

        $possibleTime = $this->getNextPossibleDeliveryTime();
        $nextPossible = new DateTime();
        $nextPossible->setTimestamp($possibleTime);

        $hours = array();
        $from_dates = array();
        $to_dates = array();
        $utcTimeZone = new DateTimeZone("UTC");

        foreach ($openHours as $item) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item['first_delivery'], $utcTimeZone);
            $date->setTimestamp(strtotime('+5 minutes', $date->getTimestamp()));

            if ($startDate == $date->format('Y-m-d')) {
                $date2 = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item['last_delivery'], $utcTimeZone);
                $date2->setTimestamp(strtotime('-5 minutes', $date2->getTimestamp()));

                $from_dates[] = $date->format('Y-m-d H:i:s');
                $to_dates[] = $date2->format('Y-m-d H:i:s');
            }
        }

        $fromTime = $from_dates[0];
        $endTime = $to_dates[0];

        $date = new DateTime($fromTime);
        $startHour = $date->format('H');

        $nextPossibleHour = $nextPossible->format('H');

        if ($startDate == $nextPossible->format('Y-m-d') && $nextPossibleHour < $startHour) {
            $newStartDateTimestamp = strtotime('+10 minutes', $date->getTimestamp());
            $newStartDate = new DateTime(null, new DateTimeZone("UTC"));
            $newStartDate->setTimestamp($newStartDateTimestamp);

            $startHour = $newStartDate->format('H');
        }

        //if chosen date == today
        if ($startDate == $nextPossible->format('Y-m-d') && $nextPossibleHour > $startHour) {
            $startHour = $nextPossibleHour;
        }

        $date = new DateTime($endTime);
        $endHour = $date->format('H');

        //add to array hours between first delivery hour and last delivery hour
        for (; $startHour <= $endHour; $startHour++) {
            $hours[] = (int)$startHour;
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($hours));
    }

    /**
     * Get options for possible delivery minutes selectbox on frontend Urb-it form's
     * AJAX
     */
    public function minuteOptionsAction()
    {
        $startDate = $this->getRequest()->getPost('selected_date');
        $startHour = $this->getRequest()->getPost('selected_hour');
        $openHours = $this->getDeliveryHours();

        $possibleTime = $this->getNextPossibleDeliveryTime();
        $nextPossible = new DateTime(null, new DateTimeZone("UTC"));
        $nextPossible->setTimestamp($possibleTime);

        $minutesArray = ["00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55"];
        $possibleMinutesResult = null;
        $utcTimeZone = new DateTimeZone("UTC");

        foreach ($openHours as $item) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item['first_delivery'], $utcTimeZone);
            $date->setTimestamp(strtotime('+5 minutes', $date->getTimestamp()));
            $date2 = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item['last_delivery'], $utcTimeZone);
            $date2->setTimestamp(strtotime('-5 minutes', $date2->getTimestamp()));

            if ($startDate == $date->format('Y-m-d')) {
                if ($startDate == $nextPossible->format('Y-m-d') && $startHour == $date->format('H') && $nextPossible->getTimestamp() < $date->getTimestamp()) {
                    //if chosen date == today and selected_hour == first_delivery hour and nearest possible delivery hour < first delivery hour
                    $newStartDateTimestamp = strtotime('+10 minutes', $date->getTimestamp());
                    $newStartDate = new DateTime(null, new DateTimeZone("UTC"));
                    $newStartDate->setTimestamp($newStartDateTimestamp);

                    $possibleMinutes = (int)$newStartDate->format('i');
                    $possibleMinutesResult = $this->getFutureMinutes($possibleMinutes);
                } elseif ($startDate == $nextPossible->format('Y-m-d') && $startHour == $nextPossible->format('H')) {
                    //if chosen date == today and selected_hour == nearest possible delivery hour
                    $possibleMinutes = $nextPossible->format('i');
                    $possibleMinutesResult = $this->getFutureMinutes($possibleMinutes);
                } elseif ($startHour == $date2->format('H')) {
                    //if selected_hour == last delivery hour
                    $possibleMinutes = $date2->format('i');
                    $possibleMinutesResult = $this->getPastMinutes($possibleMinutes);
                } else {
                    $possibleMinutesResult = $minutesArray;
                }
            }
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($possibleMinutesResult));
    }

    /**
     * Returns minutes, which is greater than param
     * @param $possibleMinutes
     * @return array
     */
    protected function getFutureMinutes($possibleMinutes)
    {
        $minutesArray = ["00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55"];
        $filteredMinutesArray = array();

        foreach ($minutesArray as $minute) {
            if ((int)$minute >= (int)$possibleMinutes) {
                $filteredMinutesArray[] = $minute;
            }
        }

        return $filteredMinutesArray;
    }

    /**
     * Returns minutes, which is lower than param
     * @param $possibleMinutes
     * @return array
     */
    protected function getPastMinutes($possibleMinutes)
    {
        $minutesArray = ["00", "05", "10", "15", "20", "25", "30", "35", "40", "45", "50", "55"];
        $filteredMinutesArray = array();

        foreach ($minutesArray as $minute) {
            if ((int)$minute <= (int)$possibleMinutes) {
                $filteredMinutesArray[] = $minute;
            }
        }

        return $filteredMinutesArray;
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

    protected function _getHelper()
    {
        return Mage::helper('vaimo_urbit');
    }
}