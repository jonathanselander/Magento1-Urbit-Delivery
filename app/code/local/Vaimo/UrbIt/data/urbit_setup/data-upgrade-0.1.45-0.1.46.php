<?php
/**
 * Copyright (c) 2009-2015 Vaimo AB
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
 * @copyright   Copyright (c) 2009-2015 Vaimo AB
 */

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
/* @var Mage_Cms_Model_Block $block */

$installer->startSetup();

$content = "Text to be editable";

$block = Mage::getModel('cms/block');
$block->setTitle('Urb-it - Text Block');
$block->setIdentifier('urbit_text_block');
$block->setIsActive(1);
$block->setContent($content);
$block->save();

$category = Mage::getModel('catalog/category')->loadByAttribute('name', 'Urbit');
if(!$category){
    $category = Mage::getModel('catalog/category');
    $category->setName('Urb-it');
    $category->setUrlKey('urb-it');
    $category->setIsActive(1);
    $category->setDisplayMode('PAGE');
    $category->setLandingPage($block->getId());
    $category->setIsAnchor(0);
    $category->setIncludeInMenu(0);
    $category->setStoreId(Mage::app()->getStore()->getId());
    $parentCategory = Mage::getModel('catalog/category')->load(2);
    $category->setPath($parentCategory->getPath());
}
$category->save();

$installer->endSetup();