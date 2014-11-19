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
$installer->getConnection()->addColumn($installer->getTable('sales/quote'), 'dpd_parcelshop_id', "varchar(255) null");
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'dpd_parcelshop_id', "varchar(255) null");
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'dpd_label_exported', "bool null default 0");
$installer->endSetup();