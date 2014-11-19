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
 * Class DPD_Shipping_Block_Adminhtml_Sales_Order
 */
class DPD_Shipping_Block_Adminhtml_Sales_Order extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Sets blockgroup for our DPD Orders page.
     */
    public function __construct()
    {
        $this->_blockGroup = 'dpd';
        $this->_controller = 'adminhtml_sales_order';
        $this->_headerText = Mage::helper('dpd')->__('DPD Orders');
        parent::__construct();
        $this->_removeButton('add');
    }
}