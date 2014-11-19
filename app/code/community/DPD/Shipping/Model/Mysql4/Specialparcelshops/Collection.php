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
 * Class DPD_Shipping_Model_Mysql4_Specialparcelshops_Collection
 */
class DPD_Shipping_Model_Mysql4_Specialparcelshops_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Initialises the model, the abstract file will render a collection from it.
     */
    public function _construct()
    {
        $this->_init("dpd/specialparcelshops");
    }
}
	 