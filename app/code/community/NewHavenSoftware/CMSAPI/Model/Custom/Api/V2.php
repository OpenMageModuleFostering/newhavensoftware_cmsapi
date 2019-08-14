<?php
class NewHavenSoftware_CMSAPI_Model_Custom_Api_V2 extends Mage_Api_Model_Resource_Abstract {
  public function CustomFields($orderIncrementId) {
    try {

      /**
       * Use CLASS_NAME/container/field for the values
       * Example:
       *   Amasty_Orderattr_Model_Sales_Order_Api/Custom/customer_po
       * For the class call to work, the class must accept an order increment ID and have an info method
       *
       */

      $results = '';
      //The path is always in the section/group/field
      $customFields = Mage::getStoreConfig('cmsapi/config/customfields');
      // comma separated list of section.fields for instance
      // ad_code,customer_number
      $fields = explode(',', $customFields);
      $object = Mage::getModel('sales/order')
        ->getCollection()
        ->addAttributeToFilter('state', array('neq' => Mage_Sales_Model_Order::STATE_CANCELED))
        ->addAttributeToFilter('increment_id', $orderIncrementId)
        ->getFirstItem();
      foreach ($fields as $field) {
        if (strpos($field,'=') !== false) {
            // we have a global assignment use this value
            $results .= $field . ',';
        } else {
          if (md5($field) == '59e0b38e37da9ccacccbed1029498761') {
            $results .= $field . "=((sales/order)) " . print_r($object,true) . ',';
            continue; //skip to next field
          }
          $objectValue = $this->getCustomFieldValue($object,$field,$orderIncrementId);
          $results .= $this->getFieldName($field) . "=" . $objectValue . ',';
        }
      }

      $results = rtrim($results, ', \t\n\r\0\x0B'); //Clean up the last comma and any spaces

      if ($results == '') {
        return 'test=value,more=values';
      } else {
        return $results;
      }
    } catch (Exception $e) {
      return "error=" . $e->getMessage();
    }
  }

  private function getCustomFieldValue($obj,$field,$orderIncID,$delim = '/') {
    $result = '';
    //if (is_array($obj) || is_object($obj)) {
      if (strstr($field,$delim)) { // contains paths, recurs into getCustomFieldValue
        $field = trim(trim($field),$delim); // clean up any delimiters before or after the strings -- double trim
        $fields = explode($delim,$field,2); // get only the first field limiting the return elements by 2 -- i.e. $fields[1] will contain the rest of the string
        $firstField = $fields[0];
        // Debug code -- print out the object
        if (md5($fields[1]) == '59e0b38e37da9ccacccbed1029498761') {
          if (!isset($obj[$firstField])) { // only if $obj doesn't contain this value
            $this->getClassForOrder($obj,$firstField,$orderIncID); // if $firstField is a class
          }
          return ("((" . $firstField . ")) " . print_r($obj,true));
        }
        if (isset($obj[$firstField])) { // if firstField exists pass the value as the object/array
          $result = $this->getCustomFieldValue($obj[$firstField],$fields[1],$orderIncID,$delim);
        } else {
          // see if this is a class
          $this->getClassForOrder($obj,$firstField,$orderIncID);
          $result = $this->getCustomFieldValue($obj,$fields[1],$orderIncID,$delim);
        }
      } else {
        $result = $this->findValue($obj,$field);
      }
    //}
    return $result;
  }

  private function getFieldName($field) {
    return basename($field);
  }

  private function getClassForOrder(&$obj,$className,$orderIncID) {
    if (class_exists($className)) {
      try {
        $CustomApi = new $className;
        $customAttributes = $CustomApi->info($orderIncID);
        $obj = $customAttributes; // returns the new object
      } catch (Exception $e) {
        // No error, just return the object
      }
    }
  }

  private function findValue($obj,$field) {
    $result = '';
    $keyName = 'key';
    $valueName = 'value';
    if (!isset($obj[$field])) {
      // the field name doesn't exist as an associative key here, see if this object contains key or value
      foreach ($obj as $key => $value) {
        if ($obj[$key] == $field) {
          $result = $obj[$valueName];
          break; // using key/value pairs
        }
        if (is_array($value) || is_object($value)) {
          $result = $this->findValue($value,$field);
          break;
        }
      }
    } else { //$obj[$field] is set, use it
      $result = $obj[$field];
    }
    return $result;
  }

}
?>
