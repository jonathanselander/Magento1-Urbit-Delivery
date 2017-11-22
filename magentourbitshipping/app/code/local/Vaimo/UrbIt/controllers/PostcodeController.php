<?php

class Vaimo_UrbIt_PostcodeController extends Mage_Core_Controller_Front_Action {

    public function validateAction(){

        $postcode = $this->getRequest()->getParam("postcode", false);

        $api = Mage::getModel("vaimo_urbit/urbit_api", new Vaimo_UrbIt_Model_Urbit_Api_Client());
        $response = $api->validatePostcode($postcode);

        $status = false;
        if(isset($response) && $response->getStatus() == "200") $status = true;

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($status));
    }

}