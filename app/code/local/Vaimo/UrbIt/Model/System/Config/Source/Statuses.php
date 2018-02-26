<?php

class Vaimo_UrbIt_Model_System_Config_Source_Statuses
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = Mage::getModel('sales/order_status')->getResourceCollection()->getData();

        $optionArray = array();

        $optionArray[] = array(
            'value' => 'none',
            'label' => 'None'
        );

        foreach ($statuses as $status) {
            $optionArray[] = array(
                'value' => $status['status'],
                'label' => $status['label']
            );
        }

        return $optionArray;
    }
}
