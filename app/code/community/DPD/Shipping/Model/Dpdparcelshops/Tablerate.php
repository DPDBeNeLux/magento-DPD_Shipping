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
 * Class DPD_Shipping_Model_Dpdparcelshops_Tablerate
 */
class DPD_Shipping_Model_Dpdparcelshops_Tablerate extends Mage_Core_Model_Abstract
{
    /**
     * Initialise the model.
     */
    protected function _construct()
    {
        $this->_init("dpd/dpdparcelshops_tablerate");
    }
}
