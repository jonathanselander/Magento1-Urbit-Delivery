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
 * Class Vaimo_UrbIt_Model_Urbit_Api_Response
 *
 * @property array $info
 * @property array $response
 * @property string $success
 * @property string $status
 * @property string $errorMessage
 *
 * @property string $httpCode
 * @property string $httpMethod
 *
 * @method array getInfo()
 * @method array getResponse()
 * @method string getStatus()
 * @method string getErrorMessage()
 */
class Vaimo_UrbIt_Model_Urbit_Api_Response
{
    const NO_ERROR = "success";
    const HAS_ERROR = "error";

    const HTTP_STATUS_GET = "get";
    const HTTP_STATUS_POST = "post";
    const HTTP_STATUS_PUT = "put";

    const HTTP_STATUS_SUCCESS_GET = "200";
    const HTTP_STATUS_SUCCESS_POST = "201";
    const HTTP_STATUS_SUCCESS_PUT = "204";

    const HTTP_STATUS_ERROR_BAD_REQUEST = "400";
    const HTTP_STATUS_ERROR_UNAUTHORISED = "404";
    const HTTP_STATUS_ERROR_NOT_FOUND = "404";
    const HTTP_STATUS_ERROR_CONFLICT = "409";
    const HTTP_STATUS_ERROR_UNPROCESSABLE_ENTITY = "422";
    const HTTP_STATUS_ERROR_TOO_MANY_REQUESTS = "429";

    const HTTP_STATUS_SERVER_ERROR = "500";
    const HTTP_STATUS_SERVER_ERROR_SERVICE_UNAVAILABLE = "503";
    const HTTP_STATUS_SERVER_ERROR_GATEWAY_TIMEOUT = "504";

    /**
     * @var array
     */
    protected $info = array(
        "http_code" => null,
        "http_method" => null,
    );

    /**
     * @var array
     */
    protected $response = array(
        "status" => null,
        "message" => null,
        "errors" => null,
        "invalid_properties" => null,
    );

    protected $method = "";

    /**
     * @var bool
     */
    protected $success = false;

    /**
     * @var string
     */
    protected $status = "";

    /**
     * @var string
     */
    protected $errorMessage = "";

    /**
     * Vaimo_UrbIt_Model_Urbit_Api_Response constructor.
     * @param array $arguments
     * @throws Exception
     */
    public function __construct($arguments = array())
    {
        if (!is_array($arguments)) {
            $cls = get_class($this);
            throw new RuntimeException("Passed ot {$cls} argument should be an array");
        }

        $this->info = array_merge(
            $this->info,
            isset($arguments["info"]) ? $arguments["info"] : array()
        );

        $this->response = array_merge(
            $this->response,
            isset($arguments["response"]) ? $arguments["response"] : array()
        );

        $this->method = isset($arguments["method"]) ? $arguments["method"] : "";

        $this->processResponse();
    }

    protected function processInfo()
    {
        $statuses = array(
            self::HTTP_STATUS_GET  => self::HTTP_STATUS_SUCCESS_GET,
            self::HTTP_STATUS_POST => self::HTTP_STATUS_SUCCESS_POST,
            self::HTTP_STATUS_PUT  => self::HTTP_STATUS_SUCCESS_PUT,
        );

        $code = isset($statuses[$this->getHttpMethod()]) ? $statuses[$this->getHttpMethod()] : "";

        $this->success = $code === $this->getHttpCode() ? self::NO_ERROR : self::HAS_ERROR;
    }

    protected function processResponse()
    {
        $args = (object) $this->response;

        $this->processInfo();
        $hasError = $this->hasError();

        switch (true) {
            case isset($args->message) && $args->message == "An error has occurred.":
            case $hasError:
                $this->errorMessage = isset($args->message) ? $args->message: "An error has occurred.";
                $this->httpCode = isset($args->code) ? $args->code : $this->getHttpCode();
                break;
            default:
                break;
        }
    }

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success === self::NO_ERROR;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return !$this->getSuccess();
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        //return isset($this->info["http_method"]) ? (string) $this->info["http_method"] : "";
        return $this->method;
    }

    /**
     * @return string
     */
    public function getHttpCode()
    {
        return isset($this->info["http_code"]) ? (string) $this->info["http_code"] : "";
    }

    /**
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (preg_match("/^get(.*)$/", $name, $matches)) {
            $param = isset($matches[1]) ? lcfirst($matches[1]) : false;

            if ($param && property_exists($this, $param)) {
                return $this->{$param};
            }
        }

        $cls = get_class($this);

        throw new RuntimeException("Try to call unknown method {$cls}::{$name}()");
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get($name)
    {
        $cls = get_class($this);

        if (property_exists($this, $name)) {
            $getter = "get" . trim($name, "_");

            if (method_exists($this, $getter)) {
                return $this->{$getter}();
            }

            if (strpos($name, "_") !== 0) {
                return $this->{$name};
            }

            throw new RuntimeException("Property {$cls}::\${$name} is totally protected and not allow for get");
        }

        throw new RuntimeException("Try to get unknown property {$cls}::\${$name}");
    }
}
