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

class Vaimo_UrbIt_Model_Urbit_Api_Response
{

    private $_success = false;
    private $_status = "";
    private $_response = "";
    private $_errorMessage = "";
    private $_developerErrorMessage = "";

    public function __construct($arguments = array())
    {
        $this->_success = true;
        if (isset($arguments["info"])) {
            if ($arguments["info"]["http_code"] != 200) {
                $this->_success = false;
            }
            $this->_status = $arguments["info"]["http_code"];
        }

        if (isset($arguments["response"])) {
            if (isset($arguments["response"]["status"]) && $arguments["response"]["status"] != 200) {
                $this->_errorMessage = isset($arguments["response"]["message"]) ? $arguments["response"]["message"] : "";
                $this->_developerErrorMessage = isset($arguments["response"]["developer_message"]) ? $arguments["response"]["developer_message"] : "";
            }
            $this->_response = $arguments["response"];
        }
    }

    public function getSuccess()
    {
        return $this->_success;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    public function getDeveloperErrorMessage()
    {
        return $this->_developerErrorMessage;
    }

    public function getResponse()
    {
        return $this->_response;
    }

}