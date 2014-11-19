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
 * Class DPD_Shipping_Block_Adminhtml_System_Config_Form_Availability
 */
class DPD_Shipping_Block_Adminhtml_System_Config_Form_Availability extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Construct the block and set the corresponding template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('dpd/system/config/availability.phtml');
    }

    /**
     * Return element html.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button.
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/dpdorder/check');
    }

    /**
     * Check if there are any custom parcelshops for this website and return corresponding html.
     *
     * @return string
     */
    public function getAvailabilityHtml(){
        if($this->getParcelshopsAvailableForThisWebsite() != 0){
           return Mage::helper('dpd')->__("CSV Uploaded (%s shops). ", $this->getParcelshopsAvailableForThisWebsite());
        }
        else{
            return Mage::helper('dpd')->__("No CSV uploaded.");
        }
    }

    /**
     * Returns the size of the parcelshopcollection for this website.
     *
     * @return mixed
     */
    public function getParcelshopsAvailableForThisWebsite(){
       $websiteId = $this->getWebsiteScopeId();
       $collection =  Mage::getModel('dpd/specialparcelshops')->getCollection()->addFieldToFilter('parcelshop_website_id', array('eq' => $websiteId));
        return $collection->getSize();
    }

    /**
     * Generate button html.
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id' => 'dpd_button',
                'label' => $this->helper('adminhtml')->__('Delete'),
                'onclick' => 'javascript:check(); return false;'
            ));

        return $button->toHtml();
    }

    /**
     * Get the website id for the selected scope in sysconfig.
     *
     * @return mixed
     */
    public function getWebsiteScopeId(){
        return Mage::app()->getWebsite(Mage::app()->getRequest()->getParam('website'))->getId();
    }
}