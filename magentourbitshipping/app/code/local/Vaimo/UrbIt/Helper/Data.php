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
class Vaimo_UrbIt_Helper_Data extends Mage_Core_Helper_Data
{
    protected $_carrierCode = "urbit_onehour";

    /**
     * Retrieve true if only specific products are allowed to be delivered with UrbIt
     *
     * @return bool
     */
    public function isOnlySpecificProductsAllowed()
    {
        return Mage::getStoreConfigFlag('carriers/' . $this->_carrierCode . '/only_specific_products_allowed');
    }

    public function isAvailableForOneHourDelivery($product=null)
    {
        if (!isset($product)) {
            $product = Mage::registry("current_product");
        }
        if ($product && $product->getId()) {
            if ($this->isOnlySpecificProductsAllowed()) {
                return Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getId(), 'available_for_urbit', Mage::app()->getStore());
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve a list of order statuses compatible with UrbIt
     *
     * @return array
     */
    public function getCompatibleStatuses()
    {
        $statuses = Mage::getStoreConfig('carriers/' . $this->_carrierCode . '/status_list');
        return preg_split('/[,;]\s*/', $statuses);
    }
}
