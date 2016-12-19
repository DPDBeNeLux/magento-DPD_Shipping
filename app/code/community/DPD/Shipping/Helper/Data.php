<?php
/**
 * Created by PHPro
 *
 * @package      DPD
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */

/**
 * Class DPD_Shipping_Helper_Data
 */
class DPD_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Sets default shipping method when selected in admin.
     *
     * @param $html
     * @return mixed
     */
    public function checkShippingDefault($html)
    {
        if ((Mage::getStoreConfig('carriers/dpdclassic/default') &&
                Mage::getStoreConfig('carriers/dpdclassic/sort_order') <= Mage::getStoreConfig('carriers/dpdparcelshops/sort_order')) ||
            (Mage::getStoreConfig('carriers/dpdclassic/default') && !Mage::getStoreConfig('carriers/dpdparcelshops/default'))
        ) {
            $html = $this->_selectNode($html, 's_method_dpdclassic_dpdclassic');
        } elseif (Mage::getStoreConfig('carriers/dpdparcelshops/default')) {
            $html = $this->_selectNode($html, 's_method_dpdparcelshops_dpdparcelshops');
        }
        return $html;
    }

    /**
     * Selects the radiobutton for default selected shipping method.
     *
     * @param $html
     * @param $node
     * @return mixed
     */
    protected function _selectNode($html, $node)
    {
        preg_match('(<input[^>]+id="' . $node . '"[^>]+>)s', $html, $matches);
        if (isset($matches[0])) {
            $checked = str_replace('/>', ' checked="checked" />', $matches[0]);
            $html = str_replace($matches[0],
                $checked, $html);
        }
        return $html;
    }

    /**
     * Adds custom html to parcelshops shipping method.
     *
     * @param $html
     * @return mixed
     */
    public function addHTML($html)
    {
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $block = Mage::app()->getLayout()->createBlock('dpd/carrier_parcelshop');
        $block->setShowUrl(true);
        preg_match('!<label for="(.*?)parcelshops">(.*?)<\/label>!s', $html, $matches);
        if (isset($matches[0])) {
            if ($quote->getDpdSelected()) {
                $html = str_replace($matches[0],
                    $matches[0] . '<div id="dpd">' . $block->setTemplate('dpd/parcelshopselected.phtml')->toHtml() . '</div>',
                    $html);
            } else {
                $html = str_replace($matches[0],
                    $matches[0] . '<div id="dpd">' . $block->setTemplate('dpd/parcelshoplink.phtml')->toHtml() . '</div>',
                    $html);
            }
        }
        return $html;
    }

    /**
     * Returns shipping address lat lng.
     *
     * @return string
     */
    public function getGoogleMapsCenter()
    {
        $address = Mage::getModel('checkout/cart')->getQuote()->getShippingAddress();
        $addressToInsert = $address->getStreet(1) . " ";
        if ($address->getStreet(2)) {
            $addressToInsert .= $address->getStreet(2) . " ";
        }
        $addressToInsert .= $address->getPostcode() . " " . $address->getCity() . " " . $address->getCountry();
		
		$cache = Mage::app()->getCache();
		$previousAddress = $cache->load("last_encoded_address");
		$previousGeodata = unserialize($cache->load("last_encoded_result"));
		
		Mage::helper('dpd')->log('Cached values', Zend_Log::INFO);
		Mage::helper('dpd')->log($previousAddress, Zend_Log::DEBUG);
		Mage::helper('dpd')->log($previousGeodata, Zend_Log::DEBUG);
		
		if($previousAddress
			&& $previousGeodata
			&& $previousAddress == $addressToInsert){
			Mage::helper('dpd')->log('Using cached geo location data', Zend_Log::INFO);
			$LATITUDE = $previousGeodata->lat;
			$LONGITUDE = $previousGeodata->lng;
			return $LATITUDE . ',' . $LONGITUDE;
		} else {
			$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($addressToInsert) . '&sensor=false';
			$source = file_get_contents($url);
			$obj = json_decode($source);
			Mage::helper('dpd')->log($obj, Zend_Log::DEBUG);
			if(isset($obj->results)
				&& count($obj->results) > 0){
				Mage::helper('dpd')->log('Google maps geo-encoding succeeded', Zend_Log::INFO);
				$cache->save($addressToInsert, "last_encoded_address");
				$cache->save(serialize($obj->results[0]->geometry->location), "last_encoded_result");
				$LATITUDE = $obj->results[0]->geometry->location->lat;
				$LONGITUDE = $obj->results[0]->geometry->location->lng;
				return $LATITUDE . ',' . $LONGITUDE;
			} else {
				Mage::helper('dpd')->log('Google maps geo-encoding failed' , Zend_Log::INFO);
				return false;
			} 
		}
    }

    /**
     * Logs bugs/info.
     * Zend_Log::DEBUG = 7
     * Zend_Log::ERR = 3
     * Zend_Log::INFO = 6
     *
     * @param $message
     * @param $level
     */
    public function log($message, $level)
    {
        $allowedLogLevel = Mage::getStoreConfig('carriers/dpdparcelshops/log_level');
        if ($level <= $allowedLogLevel) {
            Mage::log($message, $level, 'dpd.log');
        }
    }

    /**
     * Creates new IO object and inputs base 64 pdf string fetched from webservice.
     *
     * @param $pdfString
     * @param $folder
     * @param $name
     */
    public function generatePdfAndSave($pdfString, $folder, $name)
    {
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => Mage::getBaseDir('media') . "/dpd/" . $folder));
        $io->streamOpen($name . '.pdf', 'w+');
        $io->streamLock(true);
        $io->streamWrite($pdfString);
        $io->streamUnlock();
        $io->streamClose();
    }

    /**
     * True if the current version of Magento is Enterprise Edition.
     *
     * @return bool
     */
    public function isMageEnterprise()
    {
        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise') && Mage::getConfig()->getModuleConfig('Enterprise_AdminGws') && Mage::getConfig()->getModuleConfig('Enterprise_Checkout') && Mage::getConfig()->getModuleConfig('Enterprise_Customer');
    }

    /**
     * Returns the language based on storeId.
     *
     * @param $storeId
     * @return string language
     */
    public function getLanguageFromStore($storeId)
    {
        $locale = Mage::app()->getStore($storeId)->getConfig('general/locale/code');
        $localeCode = explode('_', $locale);

        return strtoupper($localeCode[0]);
    }

    /**
     * Calculates total weight of a shipment.
     *
     * @param $shipment
     * @return int
     */
    public function calculateTotalShippingWeight($shipment)
    {
        $weight = 0;
        $shipmentItems = $shipment->getAllItems();
        foreach ($shipmentItems as $shipmentItem) {
            $orderItem = $shipmentItem->getOrderItem();
            if(!$orderItem->getParentItemId()){
            $weight = $weight + ($shipmentItem->getWeight() * $shipmentItem->getQty());
            }
        }

        return $weight;
    }

    /**
     * Check if on Onestepcheckout page or if Onestepcheckout is the refferer
     *
     * @return bool
     */
    public function getIsOnestepCheckout()
    {
        if (strpos(Mage::helper("core/url")->getCurrentUrl(), 'onestepcheckout') !== false || strpos(Mage::app()->getRequest()->getHeader('referer'), 'onestepcheckout') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Return our custom js when the check for onestepcheckout returns true.
     *
     * @return string
     */
    public function getOnestepCheckoutJs()
    {
        if ($this->getIsOnestepCheckout()) {
            return 'dpd/onestepcheckout_shipping.js';
        }
        return '';
    }
}
