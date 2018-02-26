<?php

class Vaimo_UrbIt_Test_Model_Api_ClientMock
{
    protected $_status = 200;
    protected $_responseName = null;

    public function __construct($status = 200, $responseName = null)
    {
        $this->_status = $status;
        $this->_responseName = $responseName;
    }

    /**
     * @param $method Not used in mock
     * @param $apiPath
     * @param array $data Not used in mock
     * @return mixed
     * @throws Exception
     */
    public function doCall($method, $apiPath, $data = array())
    {
        $response = null;
        $info = array(
            "http_code" => 200,
        );
        if (!$this->_responseName) {
            switch ($apiPath) {
                case "delivery/validate":
                    $response = $this->_mockApiValidateDeliveryResponseSuccess();
                    break;
                case  "order/create";
                    $response = $this->_mockApiCreateOrderResponseSuccess();
                    break;
                default:
                    throw new Exception('Not implemented');
            }
        } else {
            switch ($this->_responseName) {
                case "service_unavailable":
                    $info = array(
                        "http_code" => 503,
                    );
                    $response = $this->_mockApiServiceUnavailableErrorResponse();
                    break;
                case "unprocessable_entity":
                    $info = array(
                        "http_code" => 422,
                    );
                    $response = $this->_mockApiUnprocessableEntityErrorResponse();
                    break;
                case "retailer_unauthorized":
                    $info = array(
                        "http_code" => 401,
                    );
                    $response = $this->_mockApiRetailerUnauthorizedErrorResponse();
                    break;
                case "invalid_pickup_location":
                    $info = array(
                        "http_code" => 500,
                    );
                    $response = $this->_mockApiInvalidPickupLocationErrorResponse();
                case "invalid_delivery_location":
                    $info = array(
                        "http_code" => 500,
                    );
                    $response = $this->_mockApiInvalidDeliveryLocationErrorResponse();
                    break;
                case "invalid_delivery_datetime":
                    $info = array(
                        "http_code" => 500,
                    );
                    $response = $this->_mockApiInvalidDeliveryDateTimeErrorResponse();
                    break;
                case "unauthorized":
                    $info = array(
                        "http_code" => 401,
                    );
                    $response = $this->_mockApiUnauthorizedErrorResponse();
                    break;
                default:
                    throw new Exception('Not implemented');
            }
        }
        return Mage::getModel("vaimo_urbit/urbit_api_response", array("info" => $info, "response" => $response));
    }

    protected function _mockApiCreateOrderResponseSuccess()
    {
        return json_decode('{
          "order_number": 1,
          "order_id": "00000000-0000-0000-0000-000000000000",
          "reference_id": "1000093500",
          "order_type": "OneHour",
          "order_items": [
            {
              "retailer_reference_id": "1000093569",
              "item_description": "Apple iPhone 6 (small) 16 GB",
              "quantity": 1
            },
            {
              "retailer_reference_id": "1000021548",
              "item_description": "Samsung Galaxy S5",
              "quantity": 1
            }
          ],
          "delivery": {
            "address": {
              "street": "Kungsgatan 20",
              "postal_code": "11442",
              "city": "Stockholm",
              "country": "Sweden"
            },
            "first_name": "Anna",
            "last_name": "Andersson",
            "email": "anna.andersson@sverige.se",
            "cell_phone": "+46768123456",
            "consumer_comment": "The door code is something between 0000 and 9999"
          },
          "pickup_location": {
            "pickup_location_id": "00000000-0000-0000-0000-000000000000",
            "address": {
              "street": "Sibyllegatan 18",
              "postal_code": "11442",
              "city": "Stockholm",
              "country": "Sweden",
              "position": {
                "longitude": 59.337459,
                "latitude": 18.081053
              }
            },
            "name": "Testelektronikbolaget Butik 5",
            "phone": "+4678123456",
            "authentication_code": "C3E8X"
          },
          "created_at": "2014-11-10T08:09:37.2884638+00:00",
          "modified_at": null}
        ', true);
    }

    protected function _mockApiValidateDeliveryResponseSuccess()
    {
        return json_decode('{
          "delivery_type": "OneHour",
          "postal_code": "11442",
          "delivery_expected_at": "2014-11-10T09:09:37.2884638+00:00",
          "pickup_location": {
            "id": "3313205a-a51d-4b81-a00c-9c81f1f0c111"
          },
          "articles": [
            {
              "identifier": "1000093569",
              "quantity": 1,
              "description": "Apple iPhone 6 (small) 16 GB"
            },
            {
              "identifier": "1000021548",
              "quantity": 1,
              "description": "Samsung Galaxy S5"
            }
          ]
        }
        ', true);
    }

    /**
     * 503    ServiceUnavailable
     * Retailer service is temporarily unavailable.
     */
    protected function _mockApiServiceUnavailableErrorResponse()
    {
        return json_decode('{
          "status": 503,
          "code": "503",
          "message": "Service unavailable",
          "developer_message": "Retailer service is temporarily unavailable.",
          "more_info": "https://retailer.urb-it.se/api/docs"
        }', true);
    }

    /**
     * 401    Unauthorized
     * HMAC authorization failed.
     */
    protected function _mockApiUnauthorizedErrorResponse()
    {
        return json_decode('{
          "status": 401,
          "code": "401",
          "message": "Unauthorized",
          "developer_message": "HMAC authorization failed.",
          "more_info": "https://retailer.urb-it.se/api/docs"
        }', true);
    }

    /**
     * 422    RET-001    UnprocessableEntity
     * Your request was understood, but contained invalid parameters
     */
    protected function _mockApiUnprocessableEntityErrorResponse()
    {
        return json_decode('{
          "status": 422,
          "code": "422",
          "message": "Unprocessable entity",
          "developer_message":"Your request was understood, but contained invalid parameters.",
          "more_info": "https://retailer.urb-it.se/api/docs"
        }', true);
    }

    /**
     * 401    RET-001    RetailerUnauthorized
     * The Retailer is unauthorized to make the request.
     */
    protected function _mockApiRetailerUnauthorizedErrorResponse()
    {
        return json_decode('{
          "status": 401,
          "code": "RET-001",
          "message": "Retailer unauthorized",
          "developer_message": "The Retailer is unauthorized to make the request.",
          "more_info": "https://retailer.urb-it.se/api/docs"
        }', true);
    }

    /**
     * 500    RET-002    InvalidDeliveryLocation
     * The delivery location is located outside of the Urb-it availability zone.
     */
    protected function _mockApiInvalidDeliveryLocationErrorResponse()
    {
        return json_decode('{
          "status": 500,
          "code": "RET-002",
          "message": "The delivery location is located outside of the Urb-it availability zone.",
          "developer_message": "HMAC authorization failed.",
          "more_info": "https://retailer.urb-it.se/api/docs"
        }', true);
    }

    /**
     * 500    RET-003    InvalidPickupLocation
     * No valid Pickup location was found for the given Pickup location identifier.
     */
    protected function _mockApiInvalidPickupLocationErrorResponse()
    {
        return json_decode('{
          "status": 500,
          "code": "RET-003",
          "message": "Invalid pickup location",
          "developer_message": "No valid Pickup location was found for the given Pickup location identifier.",
          "more_info": "https://retailer.urb-it.se/api/docs"
        }', true);
    }

    /**
     * 500    RET-004    InvalidDeliveryDateTime
     * The specified delivery date and time is invalid for the specified Delivery type.
     */
    protected function _mockApiInvalidDeliveryDateTimeErrorResponse()
    {
        return json_decode('{
          "status": 500,
          "code": "RET-004",
          "message": "Invalid delivery datetime",
          "developer_message": "The specified delivery date and time is invalid for the specified Delivery type.",
          "more_info": "https://retailer.urb-it.se/api/docs"
        }', true);
    }

}
