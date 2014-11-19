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
 * Class DPD_Shipping_Model_Adminhtml_System_Config_Backend_Shipping_Dpdclassic_Tablerate
 */
class DPD_Shipping_Model_Adminhtml_System_Config_Backend_Shipping_Dpdclassic_Tablerate extends Mage_Core_Model_Config_Data
{
    /**
     * Call the uploadAndImport function from the classic tablerate recourcemodel.
     */
    public function _afterSave()
    {
        Mage::getResourceModel('dpd/dpdclassic_tablerate')->uploadAndImport($this);
    }
}
