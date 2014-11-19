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
 * Class DPD_Shipping_AjaxController
 */
class DPD_Shipping_AjaxController extends Mage_Core_Controller_Front_Action {
    /**
     * Load indexpage of this controller.
     */
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Return window for overlay mode of the shipping method.
     */
    public function windowindexAction(){
        $this->loadLayout();
        if($this->getRequest()->getParam('windowed')){
            $this->getLayout()->getBlock('dpd')->setIsAjax(true);
        }
        $this->renderLayout();
    }

    /**
     * Saves the parcel and returns the selected parcelshop template.
     */
    public function saveparcelAction(){
        $parcelData =  $this->getRequest()->getPost();
        if($parcelData['special'] === 'false') {
            $parcelData['special'] = false;
        }

        $quote = Mage::getModel('checkout/cart')->getQuote();

        $quote
            ->setDpdSelected(1)
            ->setDpdParcelshopId($parcelData['parcelShopId'])
            ->setDpdCompany($parcelData['company'])
            ->setDpdStreet($parcelData['houseno'])
            ->setDpdZipcode($parcelData['zipcode'])
            ->setDpdCity($parcelData['city'])
            ->setDpdCountry($parcelData['country'])
            ->setDpdExtraInfo($parcelData['extra_info'])
            ->setDpdSpecialPoint(Mage::getStoreConfig('carriers/dpdparcelshops/custom_parcelshops_free_shipping') && $parcelData['special'])
            ->save();

        $quote->getShippingAddress()
            ->setShippingMethod('dpdparcelshops_dpdparcelshops')
            ->setCollectShippingRates(true)
            ->requestShippingRates();

        $quote->save();

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Unsets selected parcelshop and returns select parcelshop template.
     */
    public function invalidateparcelAction(){
        Mage::getModel('checkout/cart')->getQuote()->setDpdSelected(0)->save();
        $this->loadLayout();
        $this->renderLayout();
    }
}