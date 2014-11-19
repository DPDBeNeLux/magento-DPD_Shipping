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
$installer->run("
     CREATE TABLE IF NOT EXISTS {$this->getTable('dpd_shipping_returnlabels')} (
     `returnlabels_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `date_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     `label_number` varchar(255) NOT NULL DEFAULT '',
     `label_pdf_url` varchar(255) NOT NULL,
     `label_instructions_url` varchar(255) NOT NULL,
     `order_id` int(11) NOT NULL,
     PRIMARY KEY (`returnlabels_id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
   ");
$installer->getConnection()->addColumn($installer->getTable('sales/order'), 'dpd_label_exported', "int(11) null");
$installer->endSetup();