<?php
class NewHavenSoftware_CMSAPI_Model_GiftMessage_Api_V2 extends Mage_Api_Model_Resource_Abstract {
  public function GetGiftMessage($giftMessageId) {
    $message = Mage::getModel('giftmessage/message');
    if(!is_null($giftMessageId)) {
      $message->load((int)$giftMessageId);
    }
    return $message;
  }
}
?>