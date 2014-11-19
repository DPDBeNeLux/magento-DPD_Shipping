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
 * Class DPD_Shipping_Block_Adminhtml_Sales_Order_Grid_Renderer_Shippingmethod
 */
class DPD_Shipping_Block_Adminhtml_Sales_Order_Grid_Renderer_Shippingmethod extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders select between DPD parcelshop or DPD classic.
     *
     * @param Varien_Object $row
     * @return string 
     */
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        if ($value == "dpdparcelshops_dpdparcelshops") {
            return 'DPD parcelshop';
        } elseif ($value == "dpdclassic_dpdclassic") {
            return 'DPD classic';
        }
        return $this;
    }
}