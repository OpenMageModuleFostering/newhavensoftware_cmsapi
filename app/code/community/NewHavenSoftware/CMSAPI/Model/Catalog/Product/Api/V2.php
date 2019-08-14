<?php
class NewHavenSoftware_CMSAPI_Model_Catalog_Product_Api_V2 extends Mage_Catalog_Model_Product_Api_V2 {
    /**
     *  Set additional data before product saved
     *
     *  @param    Mage_Catalog_Model_Product $product
     *  @param    array $productData
     *  @return   object
     */
    
    protected function _prepareDataForSave ($product, $productData)
    {
        if (property_exists($productData, 'website_ids') && is_array($productData->website_ids)) {
            $product->setWebsiteIds($productData->website_ids);
        }

        if (property_exists($productData, 'additional_attributes')) {
            if (property_exists($productData->additional_attributes, 'single_data')) {
                foreach ($productData->additional_attributes->single_data as $_attribute) {
                    $_attrCode = $_attribute->key;
                    $productData->$_attrCode = $_attribute->value;
                }
            }
            if (property_exists($productData->additional_attributes, 'multi_data')) {
                foreach ($productData->additional_attributes->multi_data as $_attribute) {
                    $_attrCode = $_attribute->key;
                    $productData->$_attrCode = $_attribute->value;
                }
            }
            unset($productData->additional_attributes);
        }

        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            $_attrCode = $attribute->getAttributeCode();

            //Unset data if object attribute has no value in current store
            if (Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID !== (int) $product->getStoreId()
                && !$product->getExistsStoreValueFlag($_attrCode)
                && !$attribute->isScopeGlobal()
            ) {
                $product->setData($_attrCode, false);
            }

            if ($this->_isAllowedAttribute($attribute) && (isset($productData->$_attrCode))) {
                $product->setData(
                    $_attrCode,
                    $productData->$_attrCode
                );
            }
        }

        if (property_exists($productData, 'categories') && is_array($productData->categories)) {
            $product->setCategoryIds($productData->categories);
        }

        if (property_exists($productData, 'websites') && is_array($productData->websites)) {
            foreach ($productData->websites as &$website) {
                if (is_string($website)) {
                    try {
                        $website = Mage::app()->getWebsite($website)->getId();
                    } catch (Exception $e) { }
                }
            }
            $product->setWebsiteIds($productData->websites);
        }

        if (Mage::app()->isSingleStoreMode()) {
            $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }

        if (property_exists($productData, 'stock_data')) {
            $_stockData = array();
            foreach ($productData->stock_data as $key => $value) {
                $_stockData[$key] = $value;
            }
            $product->setStockData($_stockData);
        }

        if (property_exists($productData, 'tier_price')) {
             $tierPrices = Mage::getModel('catalog/product_attribute_tierprice_api_V2')
                 ->prepareTierPrices($product, $productData->tier_price);
             $product->setData(Mage_Catalog_Model_Product_Attribute_Tierprice_Api_V2::ATTRIBUTE_CODE, $tierPrices);
        }
        
        /*
         * Check for configurable products array passed through API Call
         * http://www.stephenrhoades.com/?p=338 Had to significantly modify it
        */
        //Mage::log("configurable_attributes exists?");
        //Mage::log(property_exists($productData, 'configurable_products'));
        if(property_exists($productData, 'configurable_products') && is_array($productData->configurable_products)) {
            //Mage::log($productData->configurable_products);
            $_configurableProductsData = array();
            foreach ($productData->configurable_products as $key => $value) {
                $_configurableProductsData[$value->key] = $value->value;
            }
            //Mage::log($_configurableProductsData);
            $product->setConfigurableProductsData($_configurableProductsData);        	
        }
        
        //Mage::log(property_exists($productData, 'configurable_attributes'));
        if(property_exists($productData, 'configurable_attributes') && is_array($productData->configurable_attributes)) {
            //Mage::log("configurable_attributes");
            //Mage::log($productData->configurable_attributes);
            $_configurableAttributesData = array();
            $_valuesData = array();
            $_dataData = array();
            $_vvData = array();
            $_attributesCurrent;
            foreach($productData->configurable_attributes as $key => $data) {
                //Mage::log($key);
                //Mage::log($data);
                foreach($data->values as $valueKey => $valueData) {
                    foreach($valueData->value[0] as $vvKey => $vvData) {
                        $_vvData[$vvKey] = $vvData;
                    }
                    $_valuesData[$valueData->key] = $_vvData;
                }
                if ($data->id == 0) {
                    $data->id = null;
                }
                $data->values = $_valuesData;
                //Check to see if these values exist, otherwise try and populate from existing values
                $data->label 	      = (!empty($data->label)) 	         ? $data->label		        : $product->getResource()->getAttribute($data->attribute_code)->getStoreLabel();
                $data->frontend_label = (!empty($data->frontend_label))  ? $data->frontend_label 	: $product->getResource()->getAttribute($data->attribute_code)->getFrontendLabel();
                foreach($data as $dataKey => $dataValue) {
                  $_dataData[$dataKey] = $dataValue;    
                }
                $_configurableAttributesData[$key] = $_dataData;
                
                /* need to check here if the attribute is already assigned to the product
                 * Basically the sync needs to happen here, CMS will just send a current list each time
                 * but Magento doesn't include dupe detection
                 */
                $_attributesCurrent = $product->getConfigurableAttributesAsArray($product);
                Mage::log($_configurableAttributesData);
                Mage::log($_attributesCurrent);
            }
            //Mage::log("finished array");
            //Mage::log($_configurableAttributesData);
          
            $product->setConfigurableAttributesData($_configurableAttributesData);
            $product->setCanSaveConfigurableAttributes(1);
        }
    }
}
?>
