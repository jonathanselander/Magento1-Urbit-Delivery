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

$url = Mage::getUrl("urbit/postcode/validate");
$content = "<h3>F&aring; ditt k&ouml;p personligt &ouml;verl&auml;mnat i Stockholms innerstad</h3>
<div>&nbsp;</div>
<h3>Vi erbjuder v&aring;ra kunder i Stockholms innerstad ett nytt s&auml;tt att handla. Med urb-it f&aring;r du ditt k&ouml;p inom en timme eller den tidpunkt du sj&auml;lv v&auml;ljer.</h3>
<div>
    <h3 dir=\"ltr\">Oavsett om du befinner dig p&aring; kontoret, lunchcaf&eacute;et, gymmet eller hemma f&aring;r du din best&auml;llning personligt &ouml;verl&auml;mnad precis d&auml;r du &auml;r, f&ouml;r endast 129kr.</h3>
    <h1 dir=\"ltr\">S&aring; fungerar urb-it</h1>
    <h3 dir=\"ltr\"><strong>Du handlar</strong>. Handla i denna butik. V&auml;lj att f&aring; ditt k&ouml;p inom en timme eller n&auml;r det passar dig.</h3>
    <h3 dir=\"ltr\"><strong>urb-it h&auml;mtar</strong>. Du f&aring;r ett meddelande n&auml;r en av v&aring;ra urbers har h&auml;mtat upp din best&auml;llning och &auml;r p&aring; v&auml;g till dig.*</h3>
    <h3 dir=\"ltr\"><strong>urb-it l&auml;mnar</strong>. Urbern l&auml;mnar &ouml;ver varan till dig personligen, var du &auml;n befinner dig i Stockholms innerstad.</h3>
    <h3 dir=\"ltr\">Du kan alltid f&ouml;lja ditt k&ouml;p i&nbsp;<a href=\"https://itunes.apple.com/se/app/urb-it-1-hour/id945691318?mt=8\" target=\"_blank\">urb-its app</a>. Beh&ouml;ver du flytta dig innan din urber &auml;r framme kan du alltid skicka ett personligt meddelande till din henne/honom och ange din nya adress.</h3>
    <h3 dir=\"ltr\">Du kan handla med urb-it n&auml;r som helst p&aring; dygnet och du kan f&aring; ditt k&ouml;p under butikens &ouml;ppettider.</h3>
    <p>*Du som k&ouml;pare har full koll p&aring; vem det &auml;r som tar hand om ditt uppdrag. Alla urbers genomg&aring;r en grundlig licensieringsprocess och utbildning innan de b&ouml;rjar utf&ouml;ra uppdrag.</p>
</div>
<h1>Kan du handla med urb-it?</h1>
<script type=\"text/javascript\">// <![CDATA[

function validatePostcode() {
    var postcode = jQuery('input[name=\"postal_code\"]').val();
    jQuery(\".urb-it-validator .success\").hide();
    jQuery(\".urb-it-validator .failed\").hide();
    jQuery.get(\"" . $url . "?postcode=\" + postcode, function (data, status) {
        if(data == true){
            jQuery(\".urb-it-validator .success\").show();
        }else{
            jQuery(\".urb-it-validator .failed\").show();
        }
    });
}

// ]]></script>
<div class=\"urb-it-validator\">
    <div><input class=\"urbit-postcode\" type=\"text\" placeholder=\"Postnummer\" name=\"postal_code\" /> <input class=\"urbit-submit button\" onclick=\"validatePostcode(); return false;\" type=\"button\" value=\"OK\" />
        <p class=\"message success\" style=\"display: none;\">Du kan handla med urb-it!</p>
        <p class=\"message failed\" style=\"display: none;\">Just nu kan du inte handla med urb-it p&aring; det postnumret. Kolla nytt postnummer</p>
    </div>
</div>";

$block = Mage::getModel('cms/block')->load('urbit_text_block');
if(isset($block)) {
    $block->setContent($content);
    $block->save();
}

$installer->endSetup();