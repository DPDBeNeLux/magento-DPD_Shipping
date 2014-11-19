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
     CREATE TABLE IF NOT EXISTS {$this->getTable('dpd_shipping_specialparcelshops')} (
     `specialparselshops_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `parcelshop_delicom_id` varchar(255) NOT NULL,
     `parcelshop_website_id` int(11) NULL,
     `parcelshop_carrier_pudo_id` int(11) NULL,
     `parcelshop_manager` varchar(255) NULL,
     `parcelshop_pudo_name`varchar(255) NOT NULL,
     `parcelshop_pudo_language`varchar(255) NOT NULL,
     `parcelshop_country`varchar(255) NOT NULL,
     `parcelshop_latitude`varchar(255) NOT NULL,
     `parcelshop_longitude`varchar(255) NOT NULL,
     `parcelshop_city_code`varchar(255) NULL,
     `parcelshop_langue_1`varchar(255) NULL,
     `parcelshop_address_1`varchar(255) NOT NULL,
     `parcelshop_address_2`varchar(255) NULL,
     `parcelshop_location_information`varchar(255) NULL,
     `parcelshop_post_code`varchar(255) NOT NULL,
     `parcelshop_town`varchar(255) NOT NULL,
     `parcelshop_region`varchar(255) NULL,
     `parcelshop_openinghours`text NULL,
     PRIMARY KEY (`specialparselshops_id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
   ");

$installer->endSetup();