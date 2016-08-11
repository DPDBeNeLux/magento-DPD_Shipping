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
 * Class DPD_Shipping_Model_Adminhtml_DpdGrid
 */
class DPD_Shipping_Model_Adminhtml_Dpdgrid extends Mage_Core_Model_Abstract
{

    /**
     * Generates and completes an order, reference: generateAndCompleteAction.
     *
     * @param $orderId
     * @return $this
     */
    public function generateAndCompleteOrder($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $shipmentCollection = $order->getShipmentsCollection();
        if ($shipmentCollection->count() > 0 && !$order->getDpdLabelExists()) {
            $dpdused = false;
            foreach ($shipmentCollection as $shipment) {
                foreach ($shipment->getAllTracks() as $tracker) {
                    if (strpos($tracker->getCarrierCode(), 'dpd') !== false) {
                        $labelName = $this->_generateLabelAndReturnLabel($order, $shipment);
                        if (!$labelName) {
                            $message = Mage::helper('dpd')->__("Something went wrong while processing order %s, please check your error logs.", $order->getIncrementId());
                            Mage::getSingleton('core/session')->addError($message);
                            continue;
                        } else {
                            $dpdused = true;
                            $locale = Mage::app()->getStore($order->getStoreId())->getConfig('general/locale/code');
                            $localeCode = explode('_', $locale);
                            $labelNameCode = explode('-', $labelName);
                            $shipment->setDpdLabelPath($labelName . ".pdf");
                            $shipment->setDpdTrackingUrl('<a target="_blank" href="' . "http://tracking.dpd.de/cgi-bin/delistrack?typ=32&lang=" . $localeCode[0] . "&pknr=" . $labelNameCode[1] . "&var=" . Mage::getStoreConfig('shipping/dpd_classic/userid') . '">' . Mage::helper('dpd')->__('Track this shipment') . '</a>');
                            $tracker->setData('number', $labelName);
                            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($shipment)
                                ->addObject($tracker)
                                ->save();
                        }
                    }
                }
            }
            if ($dpdused) {
                $order->addStatusHistoryComment(Mage::helper('dpd')->__('Shipped with DPD generateLabelAndComplete'), true);
                $order->setDpdLabelExists(1);
                $order->save();
                return true;
            } else {
                $message = Mage::helper('dpd')->__("The order with id %s has only none DPD shipments.", $order->getIncrementId());
                Mage::getSingleton('core/session')->addNotice($message);
                return false;
            }
        } elseif (!$order->getDpdLabelExists()) {
            $shipment = $order->prepareShipment();
            $shipment->register();
            $weight = Mage::helper('dpd')->calculateTotalShippingWeight($shipment);
            $shipment->setTotalWeight($weight);
            $labelName = $this->_generateLabelAndReturnLabel($order, $shipment);
            if (!$labelName) {
                $message = Mage::helper('dpd')->__("Something went wrong while processing order %s, please check your error logs.", $order->getIncrementId());
                Mage::getSingleton('core/session')->addError($message);
                return false;
            } else {
                $explodeForCarrier = explode('_', $order->getShippingMethod(), 3);
                $locale = Mage::app()->getStore($order->getStoreId())->getConfig('general/locale/code');
                $localeCode = explode('_', $locale);
                $labelNameCode = explode('-', $labelName);
                $shipment->setDpdLabelPath($labelName . ".pdf");
                $shipment->setDpdTrackingUrl('<a target="_blank" href="' . "http://tracking.dpd.de/cgi-bin/delistrack?typ=32&lang=" . $localeCode[0] . "&pknr=" . $labelNameCode[1] . "&var=" . Mage::getStoreConfig('shipping/dpd_classic/userid') . '">' . Mage::helper('dpd')->__('Track this shipment') . '</a>');
                $order->setIsInProcess(true);
                $order->addStatusHistoryComment(Mage::helper('dpd')->__('Shipped with DPD generateLabelAndComplete'), true);
                $order->setDpdLabelExists(1);
                $tracker = Mage::getModel('sales/order_shipment_track')
                    ->setShipment($shipment)
                    ->setData('title', 'DPD')
                    ->setData('number', $labelName)
                    ->setData('carrier_code', $explodeForCarrier[0])
                    ->setData('order_id', $shipment->getData('order_id'));
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->addObject($tracker)
                    ->save();
                return true;
            }
        } else {
            $message = Mage::helper('dpd')->__("The order with id %s is not ready to be shipped or has already been shipped.", $order->getIncrementId());
            Mage::getSingleton('core/session')->addNotice($message);
            return false;
        }
        return $this;
    }

    /**
     * Generates a shipment label and saves it on the harddisk.
     *
     * @param $order
     * @param $shipment
     * @return mixed
     */
    protected function _generateLabelAndReturnLabel($order, $shipment)
    {
        $parcelshop = false;
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if (strpos($order->getShippingMethod(), 'parcelshop') !== false) {
            $parcelshop = true;
        }
        if ($parcelshop) {
            $recipient = array(
                'name1' => $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
                'name2' => $billingAddress->getCompany(),
                'street' => $billingAddress->getStreet(1) . " " . $billingAddress->getStreet(2),
                'country' => $billingAddress->getCountry(),
                'zipCode' => $billingAddress->getPostcode(),
                'city' => $billingAddress->getCity()
            );
        }
        else{
            $recipient = array(
                'name1' => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
                'name2' => $shippingAddress->getCompany(),
                'street' => $shippingAddress->getStreet(1) . " " . $shippingAddress->getStreet(2),
                'country' => $shippingAddress->getCountry(),
                'zipCode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity()
            );
        }
        $labelWebserviceCallback = Mage::getSingleton('dpd/webservice')->getShippingLabel($recipient, $order, $shipment, $parcelshop);

        if ($labelWebserviceCallback) {
            Mage::helper('dpd')->generatePdfAndSave($labelWebserviceCallback->parcellabelsPDF, 'orderlabels', $order->getIncrementId() . "-" . $labelWebserviceCallback->shipmentResponses->parcelInformation->parcelLabelNumber);
            return $order->getIncrementId() . "-" . $labelWebserviceCallback->shipmentResponses->parcelInformation->parcelLabelNumber;
        } else {
            return false;
        }
    }

    /**
     * Processes the undownloadable labels. (set mark and zip)
     *
     * @param $orderIds
     * @return bool|string
     */
    public function processUndownloadedLabels($orderIds)
    {
        $labelPdfArray = array();
        $i = 0;
        $err = false;
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $exported = false;
            if (!$order->getDpdLabelExported()) {
                $shippingCollection = Mage::getResourceModel('sales/order_shipment_collection')
                    ->setOrderFilter($order)
                    ->load();
                if (count($shippingCollection)) {
                    foreach ($shippingCollection as $shipment) {
                        if ($shipment->getDpdLabelPath() != "" && file_exists(Mage::getBaseDir('media') . "/dpd/orderlabels/" . $shipment->getDpdLabelPath()) && $shipment->getDpdLabelPath() != ".pdf") {
                            $labelPdfArray[] = Mage::getBaseDir('media') . "/dpd/orderlabels/" . $shipment->getDpdLabelPath();
                            $exported = true;
                        }
                    }
                    if ($exported) {
                        $order->setDpdLabelExported(1)->save();
                    }
                }
            } else {
                $i++;
            }
        }
        if (!count($labelPdfArray)) {
            return false;
        }
        if ($i > 0) {
            $message = Mage::helper('dpd')->__('%s orders already had downloaded labels.', $i);
            Mage::getSingleton('core/session')->addNotice($message);
        } else {
            $message = Mage::helper('dpd')->__('All labels have been downloaded.');
            Mage::getSingleton('core/session')->addSuccess($message);
        }
        return $this->_zipLabelPdfArray($labelPdfArray, Mage::getBaseDir('media') . "/dpd/orderlabels/undownloaded.zip", true);
    }

    /**
     * Zips the labels.
     *
     * @param array $files
     * @param string $destination
     * @param bool $overwrite
     * @return bool|string
     */
    protected function _zipLabelPdfArray($files = array(), $destination = '', $overwrite = false)
    {
        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        $valid_files = array();
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        if (count($valid_files)) {
            $zip = new ZipArchive();
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE | ZIPARCHIVE::CREATE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            foreach ($valid_files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            return $destination;
        } else {
            return false;
        }
    }

}