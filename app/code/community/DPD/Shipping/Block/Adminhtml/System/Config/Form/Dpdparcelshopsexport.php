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
 * Class DPD_Shipping_Block_Adminhtml_System_Config_Form_Dpdparcelshopsexport
 */
class DPD_Shipping_Block_Adminhtml_System_Config_Form_Dpdparcelshopsexport extends Mage_Adminhtml_Block_System_Config_Form_Field implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return mixed
     */
    public function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $buttonBlock = Mage::app()->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'website' => $buttonBlock->getRequest()->getParam('website')
        );

        $data = array(
            'label' => Mage::helper('adminhtml')->__('Export CSV'),
            'onclick' => 'setLocation(\'' . Mage::helper('adminhtml')->getUrl("adminhtml/dpdconfig/exportDpdParcelshopsTablerates", $params) . 'conditionName/\' + $(\'carriers_dpdparcelshops_condition_name\').value + \'/dpdparcelshopstablerates.csv\' )',
            'class' => '',
            'id' => 'carriers_dpdparcelshops_export'
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }
}
