<?php
/**
 * Created by PHPro
 *
 * @package      DPD
 * @subpackage   Shipping
 * @category     Checkout
 * @author       PHPro (info@phpro.be)
 */
$installer = $this;
$installer->startSetup();
// add quote attributes

$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_special_point', "boolean default '0'");

$installer->endSetup();