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
 * Class DPD_Shipping_Block_Adminhtml_Sales_Order_View_Tab_Returnlabels
 */
class DPD_Shipping_Block_Adminhtml_Sales_Order_View_Tab_Returnlabels
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface{

    /**
     * Constructs the block and sets template on it.
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('dpd/order/view/tab/returnlabels.phtml');
    }

    /**
     * Returns tab label.
     *
     * @return string
     */
    public function getTabLabel() {
        return Mage::helper('dpd')->__('DPD Return Labels');
    }

    /**
     * Returns tab title.
     *
     * @return string
     */
    public function getTabTitle() {
        return Mage::helper('dpd')->__('DPD Return Labels');
    }

    /**
     * Checks if tab can be shown.
     *
     * @return bool
     */
    public function canShowTab() {
        return true;
    }

    /**
     * Checks if the tab has to be hidden.
     *
     * @return bool
     */
    public function isHidden() {
        return false;
    }

    /**
     * Returns the order object.
     *
     * @return mixed
     */
    public function getOrder(){
        return Mage::registry('current_order');
    }

    /**
     * Returns return labels for current order.
     *
     * @return mixed
     */
    public function getReturnLabels(){
        $returnlabels = Mage::getModel('dpd/returnlabels')->
            getCollection()
            ->addFieldToFilter('order_id',array('eq' => $this->getOrder()->getId()));
        return $returnlabels;
    }
}