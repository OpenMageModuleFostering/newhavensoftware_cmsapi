<?php
class NewHavenSoftware_CMSAPI_Model_Sales_Order_Api_V2 extends Mage_Sales_Model_Order_Api_V2 {
  public function info($orderIncrementId) {
    $result = parent::info($orderIncrementId);

    // Deserialize item['product_options'] and reserialize as json
    foreach($result['items'] as $key => $item) {
      $product_options = unserialize($item['product_options']);
      $result['items'][$key]['product_options'] = json_encode($product_options);
    }
    return $result;
  }
}