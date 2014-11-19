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
 * Class DPD_Shipping_Block_Carrier_Parcelshop
 */
class DPD_Shipping_Block_Carrier_Parcelshop extends Mage_Core_Block_Template
{
    /**
     * Used to check if the url has to be shown or not. (click here to select..)
     *
     * @var
     */
    private $_showurl;
    /**
     * Array of all configdata to pass to javascript.
     *
     * @var array
     */
    private $_configArray = array();

    /**
     * Check if the url has to be shown or not. (click here to select..)
     *
     * @param $bool
     */
    public function setShowUrl($bool)
    {
        if ($bool && !Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_display'))
            $this->_showurl = $bool;
        return;
    }

    /**
     * Returns showurl variable.
     *
     * @return mixed
     */
    public function getShowUrl()
    {
        return $this->_showurl;
    }

    /**
     * Gets all parcelshops from dpd webservice based on shipping address.
     *
     * @return mixed
     */
    public function getParcelShops()
    {
        $coordinates = explode(',', Mage::Helper('dpd')->getGoogleMapsCenter());
        $parcelshops = Mage::getSingleton('dpd/webservice')->getParcelShops($coordinates[1], $coordinates[0]);
        return $parcelshops;
    }

    /**
     * Get all config data to pass to javascript (array) and jsonencode.
     *
     * @return mixed
     */
    public function getConfig()
    {
        $center = explode(",", Mage::Helper('dpd')->getGoogleMapsCenter());
        $this->_configArray["saveParcelUrl"] = $this->getUrl('dpd/ajax/saveparcel', array('_secure' => true));
        $this->_configArray["invalidateParcelUrl"] = $this->getUrl('dpd/ajax/invalidateparcel', array('_secure' => true));
        $this->_configArray["windowParcelUrl"] = $this->getUrl('dpd/ajax/windowindex', array('_secure' => true));
        $this->_configArray["ParcelUrl"] = $this->getUrl('dpd/ajax/index', array('_secure' => true));
        $this->_configArray["gmapsCenterlat"] = $center[0];
        $this->_configArray["gmapsCenterlng"] = $center[1];
        $this->_configArray["gmapsHeight"] = Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_height') . 'px';
        $this->_configArray["loadingmessage"] = '<span class="message">'.$this->__('Loading DPD parcelshop map based on your address...').'</span>';
        $this->_configArray["gmapsWidth"] = Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_width') . 'px';
        $this->_configArray["gmapsIcon"] = Mage::getDesign()->getSkinUrl('images/dpd/icon_parcelshop.png');
        $this->_configArray["gmapsIconShadow"] = Mage::getDesign()->getSkinUrl('images/dpd/icon_parcelshop_shadow.png');
        $this->_configArray["gmapsCustomIcon"] = (Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_icon') ? Mage::getBaseUrl('media') . "dpd/" . Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_icon') : "");
        $this->_configArray["gmapsDisplay"] = (bool)Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_display');
        $this->_configArray["loaderimage"] = $this->getSkinUrl('images/dpd/ajax-loader.gif');
        $this->_configArray["freeShippingOnCustom"] = (bool)Mage::getStoreConfig('carriers/dpdparcelshops/custom_parcelshops_free_shipping');
        return Mage::helper('core')->jsonEncode($this->_configArray);
    }

    /**
     * Render the html to show all shops. (to keep template files clean from to much functional php)
     *
     * @return string
     */
    public function getShopsHtml()
    {
        $html = "";
        $openinghours = "";
        $counter = 0;
        foreach ($this->getParcelShops()->parcelShop as $shop) {
            $html .= '<div class="shop-data" id="shop' . $shop->parcelShopId . '">';
            $html .= '<ul class="shop-details"><li><a href="#" id="' . $counter . '" class="show-info-bubble" /><strong>' . $shop->company . '</strong></a></li>';
            $html .= '<li>' . $shop->street . " " . $shop->houseNo . '</li>';
            $html .= '<li>' . $shop->zipCode . ' ' . $shop->city . '</li></ul>';
            $html .= '<a id="shop' . $shop->parcelShopId . '" class="parcelshoplink" href="#">';
            $html .= '<img src="' . Mage::getDesign()->getSkinUrl('images/dpd/icon_route.png') . '" alt="route" width="16" height="16" style="margin-right: 5px; margin-bottom:10px;">';
            $html .= '<strong>' . Mage::helper('dpd')->__('Ship to this ParcelShop.') . '</strong>';
            $html .= '</a>';
            $html .= '</div>';
            $this->_configArray['shop' . $shop->parcelShopId]['company'] = trim($shop->company);
            $this->_configArray['shop' . $shop->parcelShopId]['houseno'] = $shop->street . " " . $shop->houseNo;
            $this->_configArray['shop' . $shop->parcelShopId]['zipcode'] = $shop->zipCode;
            $this->_configArray['shop' . $shop->parcelShopId]['city'] = $shop->city;
            $this->_configArray['shop' . $shop->parcelShopId]['country'] = $shop->isoAlpha2;
            $this->_configArray['shop' . $shop->parcelShopId]['parcelShopId'] = $shop->parcelShopId;
            $this->_configArray['shop' . $shop->parcelShopId]['gmapsCenterlat'] = $shop->latitude;
            $this->_configArray['shop' . $shop->parcelShopId]['gmapsCenterlng'] = $shop->longitude;
            $this->_configArray['shop' . $shop->parcelShopId]['special'] = false;
            $this->_configArray['shop' . $shop->parcelShopId]['extra_info'] = Mage::helper('core')->jsonEncode(array_filter(array(
                'Opening hours' => (isset($shop->openingHours) && $shop->openingHours != "" ? Mage::helper('core')->jsonEncode($shop->openingHours) : ''),
                'Telephone' => (isset($shop->phone) && $shop->phone != "" ? $shop->phone : ''),
                'Website' => (isset($shop->homepage) && $shop->homepage != "" ? '<a href="' . 'http://' . $shop->homepage . '" target="_blank">' . $shop->homepage . '</a>' : ''),
                'Extra info' => (isset($shop->extraInfo) && $shop->extraInfo != "" ? $shop->extraInfo : ''))));
            $this->_configArray['shop' . $shop->parcelShopId]['gmapsMarkerContent'] = $this->_getMarkerHtml($shop, false);
            $counter++;
        }
        if ($html) {
            return $this->_getSpecialShopsHtml($html, $counter);
        }
        return $html;
    }

    /**
     * Render the html for openinghours. (to keep template files clean from to much functional php)
     *
     * @param $dpdExtraInfo
     * @return string
     */
    public function getOpeningHoursHtml($dpdExtraInfo)
    {
        $html = "";
        if (is_array(json_decode($dpdExtraInfo))) {
            foreach (json_decode($dpdExtraInfo) as $hours) {
                $html .= '<ul class="daywrapper left">';
                $html .= '<li class="day">' . Mage::helper('dpd')->__($hours->weekday) . '</li>';
                if ($hours->openMorning != "") {
                    $html .= '<li class="hour">' . $hours->openMorning . ' - ' . $hours->closeMorning . '</li>';
                } else {
                    $html .= '<li class="hour">' . Mage::helper('dpd')->__('closed') . '</li>';
                }
                if ($hours->openAfternoon != "") {
                    $html .= '<li class="hour">' . $hours->openAfternoon . ' - ' . $hours->closeAfternoon . '</li>';
                } else {
                    $html .= '<li class="hour">' . Mage::helper('dpd')->__('closed') . '</li>';
                }
                $html .= '</ul>';
            }
        } else {
            $html .= $dpdExtraInfo;
        }
        return $html;
    }

    /**
     * Gets html for the custom shops if available.
     *
     * @param $html
     * @param $counter
     * @return bool|string
     */
    protected function _getSpecialShopsHtml($html, $counter)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $countryCode = $billingAddress->getCountryId();
        $websiteId = Mage::app()->getWebsite()->getId();
        $specialShopsCollection = Mage::getModel('dpd/specialparcelshops')->getCollection()->addFieldToFilter('parcelshop_country', array('eq' => $countryCode))->addFieldToFilter('parcelshop_website_id', array('eq' => $websiteId));

        foreach ($specialShopsCollection as $specialShop) {
            $html .= '<div class="shop-data" id="shop' . $specialShop->getParcelshopDelicomId() . '">';
            if (Mage::getStoreConfig('carriers/dpdparcelshops/special_parcelshop_icon') != "") {
                $html .= '<img class="specialparcelshopImage" height="50" width="50" src="' . Mage::getBaseUrl('media') . "dpd/" . Mage::getStoreConfig('carriers/dpdparcelshops/special_parcelshop_icon') . '"/>';
            }
            $html .= '<ul class="shop-details"><li><a href="#" id="' . $counter . '" class="show-info-bubble" /><strong>' . $specialShop->getParcelshopPudoName() . '</strong></a></li>';
            $html .= '<li>' . $specialShop->getData('parcelshop_address_1') . '</li>';
            $html .= '<li>' . $specialShop->getParcelshopPostCode() . ' ' . $specialShop->getParcelshopTown() . '</li></ul>';
            $html .= '<a id="shop' . $specialShop->getParcelshopDelicomId() . '" class="parcelshoplink" href="#">';
            $html .= '<img src="' . Mage::getDesign()->getSkinUrl('images/dpd/icon_route.png') . '" alt="route" width="16" height="16" style="margin-right: 5px; margin-bottom:10px;">';
            $html .= '<strong>' . Mage::helper('dpd')->__('Ship to this ParcelShop.') . '</strong>';
            $html .= '</a>';
            $html .= '</div>';
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['company'] = trim($specialShop->getParcelshopPudoName());
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['houseno'] = $specialShop->getData('parcelshop_address_1');
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['zipcode'] = $specialShop->getParcelshopPostCode();
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['city'] = $specialShop->getParcelshopTown();
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['country'] = $specialShop->getParcelshopCountry();
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['parcelShopId'] = $specialShop->getParcelshopDelicomId();
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['gmapsCenterlat'] = $specialShop->getParcelshopLatitude();
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['gmapsCenterlng'] = $specialShop->getParcelshopLongitude();
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['specialImage'] = (Mage::getStoreConfig('carriers/dpdparcelshops/special_parcelshop_icon') != "" ? Mage::getBaseUrl('media') . "dpd/" . Mage::getStoreConfig('carriers/dpdparcelshops/special_parcelshop_icon') : "");
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['special'] = true;
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['extra_info'] = Mage::helper('core')->jsonEncode(array_filter(array(
                'Opening hours' => ($specialShop->getParcelshopOpeninghours() && $specialShop->getParcelshopOpeninghours() != "" ? $specialShop->getParcelshopOpeninghours() : ''),
                'Telephone' => "",
                'Website' => "",
                'Extra info' => "")));
            $this->_configArray['shop' . $specialShop->getParcelshopDelicomId()]['gmapsMarkerContent'] = $this->_getMarkerHtml($specialShop, true);
            $counter++;
        }
        return ($html == "" ? false : $html);
    }

    /**
     * Gets html for the marker info bubbles.
     *
     * @param $shop
     * @param $special
     * @return string
     */
    protected function _getMarkerHtml($shop, $special)
    {
        if ($special && Mage::getStoreConfig('carriers/dpdparcelshops/special_parcelshop_icon')) {
            $image = Mage::getBaseUrl('media') . "dpd/" . Mage::getStoreConfig('carriers/dpdparcelshops/special_parcelshop_icon');
        } else {
            $image = Mage::getDesign()->getSkinUrl('images/dpd/dpd_parcelshop_logo.png');
        }
        $html = '<div class="content">
            <table style="min-width:250px" cellpadding="3" cellspacing="3" border="0">
                <tbody>
                    <tr>
                        <td rowspan="3" width="90"><img class="parcelshoplogo bubble" style="width:80px; height:62px;" src="' . $image . '" alt="extrainfo"/></td>
                        <td><strong>' . ($special ? $shop->getParcelshopPudoName() : $shop->company) . '</strong></td>
                    </tr>
                    <tr>
                        <td>' . ($special ? $shop->getData('parcelshop_address_1') : $shop->street . " " . $shop->houseNo) . '</td>
                    </tr>
                    <tr>
                        <td>' . ($special ? $shop->getParcelshopPostCode() . ' ' . $shop->getParcelshopTown() : $shop->zipCode . ' ' . $shop->city) . '</td>
                    </tr>
                </tbody>
            </table>
            <div class="dpdclear"></div>
            ';


        if (!$special && isset($shop->openingHours) && $shop->openingHours != "") {
            $html .= '<div class="dotted-line">
            <table>
            <tbody>';
            foreach ($shop->openingHours as $openinghours) {
                $html .= '<tr><td style="padding-right:10px;"><strong>' . $openinghours->weekday . '</strong></td><td style="padding-right:10px;">' . $openinghours->openMorning . ' - ' . $openinghours->closeMorning . '
            </td><td>' . $openinghours->openAfternoon . ' - ' . $openinghours->closeAfternoon . '</td></tr>';
            }
            $html .= '</tbody>
            </table>
            </div><div class="dpdclear"></div>';
        }
        elseif($special && $shop->getParcelshopOpeninghours() && $shop->getParcelshopOpeninghours()!=""){

            $html .= '<div class="dotted-line">
            <table>
            <tbody>';
            foreach (Mage::helper('core')->jsonDecode($shop->getParcelshopOpeninghours()) as $openinghours) {

                $html .= '<tr><td style="padding-right:10px;"><strong>' . $openinghours['weekday'] . '</strong></td><td style="padding-right:10px;">' . $openinghours['openMorning'] . ' - ' . $openinghours['closeMorning'] . '
            </td><td>' . $openinghours['openAfternoon'] . ' - ' . $openinghours['closeAfternoon'] . '</td></tr>';
            }
            $html .= '</tbody>
            </table>
            </div><div class="dpdclear"></div>';
        }


        $html .= '<div class="dotted-line">
                    <table>
                        <tbody>
                            <tr class="pointer">
                                <td id="' . 'shop' . ($special ? $shop->getParcelshopDelicomId() : $shop->parcelShopId) . '" class="parcelshoplink" onclick="window.dpdShipping.saveParcelShop(event);" style="width: 25px;"><img src="' . Mage::getDesign()->getSkinUrl('images/dpd/icon_route.png') . '" alt="route" width="16" height="16" ></td>
                                <td id="' . 'shop' . ($special ? $shop->getParcelshopDelicomId() : $shop->parcelShopId) . '" class="parcelshoplink" onclick="window.dpdShipping.saveParcelShop(event);"><strong>' . Mage::helper('dpd')->__('Ship to this ParcelShop.') . '</strong></td>
                            </tr>
                        </tbody>
                    </table>
                  </div></div><div class="dpdclear"></div>';
        return $html;
    }

    /**
     * Returns quote object.
     *
     * @return mixed
     */
    public function getQuote()
    {
        return Mage::getModel('checkout/cart')->getQuote();
    }

    /**
     * Returns shipping cost.
     *
     * @return string
     */
    public function getShippingAmount() {
        $cost = $this->getQuote()->getShippingAddress()->getShippingAmount();

        return number_format((float)$cost, 2, '.', '');
    }



}