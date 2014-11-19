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
 * Class DPD_Shipping_Adminhtml_DpdorderController
 */
class DPD_Shipping_Adminhtml_DpdorderController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Load indexpage of this controller.
     */
    public function indexAction()
    {
        Mage::getModel('core/session')->setDpdReturn(0);
        $this->_title($this->__('dpd'))->_title($this->__('DPD Orders'));
        $this->loadLayout();
        $this->_setActiveMenu('sales/sales');
        $this->_addContent($this->getLayout()->createBlock('dpd/adminhtml_sales_order'));
        $this->renderLayout();
    }

    /**
     * Load the grid.
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('dpd/adminhtml_sales_order_grid')->toHtml()
        );
    }

    /**
     * Export csvs, this fetches all current gridentries.
     */
    public function exportDPDOrdersCsvAction()
    {
        $fileName = 'dpd_orders.csv';
        $grid = $this->getLayout()->createBlock('dpd/adminhtml_sales_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }

    /**
     * Export excel (xml), this fetches all current gridentries.
     */
    public function exportDPDOrdersExcelAction()
    {
        $fileName = 'dpd_orders.xml';
        $grid = $this->getLayout()->createBlock('dpd/adminhtml_sales_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

    /**
     * Generate the returnlabel and the instructions pdf, this fetches the label from dpd webservices.
     * On fail this method will remove all db entries / generated pdfs to prevent outputting wrong entries.
     * Logs all errors in try catch.
     */
    public function generateRetourLabelAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        try {
            $returnId = Mage::getModel('dpd/returnlabels')->generateLabelAndSave($orderId);
            if($returnId){
            Mage::getModel('dpd/returnlabels')->generateInstructionsPdf($orderId, $returnId);
            $message = Mage::helper('dpd')->__("Your return label and instructions file have been generated and is available under 'DPD Return Labels' in this order.");
            Mage::getSingleton('core/session')->addSuccess($message);
            }
        } catch (Exception $e) {
            Mage::getModel('dpd/returnlabels')->deleteEntryAndAttachments($returnId);
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order/view/order_id/' . $orderId);
            return $this;
        }
        $this->_redirect('adminhtml/sales_order/view/order_id/' . $orderId);
        return $this;
    }

    /**
     * Call this to send an email with the dpd template.
     * This calls the model to handle emails the magento way.
     * Logs all errors in try catch.
     */
    public function sendEmailAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $returnId = $this->getRequest()->getParam('return_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        try {
            Mage::getModel('dpd/returnlabels')->sendEmail($order, $returnId);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/sales_order/view/order_id/' . $orderId);
            return $this;
        }
        $message = Mage::helper('dpd')->__("The email with return label and instructions has been sent to %s.", $order->getShippingAddress()->getEmail());
        Mage::getSingleton('core/session')->addSuccess($message);
        $this->_redirect('adminhtml/sales_order/view/order_id/' . $orderId);
        return $this;
    }

    /**
     * Generates a label and completes the shipment.
     * This is called by the action in the order grid dropdown.
     */
    public function generateAndCompleteAction()
    {
        ini_set('max_execution_time', 120);
        $orderIds = $this->getRequest()->getParam('entity_id');
        $maxOrderCount = 10;
        if(count($orderIds) > $maxOrderCount){
            $message = Mage::helper('dpd')->__("The maximum number of orders to process is %s. You selected %s. Please deselect some orders and try again.",$maxOrderCount, count($orderIds));
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/*/index');
            return $this;
        }
        if (!is_array($orderIds)) {
            try {
                Mage::getModel('dpd/adminhtml_dpdgrid')->generateAndCompleteOrder($orderIds);
                if(!is_object(Mage::getSingleton('core/session')->getMessages()->getLastAddedMessage())){
                    $message = Mage::helper('dpd')->__("Your label has been generated and statuses have been changed.");
                    Mage::getSingleton('core/session')->addSuccess($message);
                }
            } catch (Exception $e) {
                Mage::helper('dpd')->log($e->getMessage(), Zend_Log::ERR);
                $message = Mage::helper('dpd')->__("Your selected order is not ready to be shipped or has already been shipped, operation canceled.");
                Mage::getSingleton('core/session')->addError($message);
            }
        } else {
            try {
                $counter = 0;
                foreach ($orderIds as $orderId) {
                    // sleep(0.300) // uncomment this line if you encounter web service load problems - ONLY WHEN INSTRUCTED BY DPD !
                    try {
                        $result = Mage::getModel('dpd/adminhtml_dpdgrid')->generateAndCompleteOrder($orderId);
                        if($result){
                                 $counter ++;
                        }
                    } catch (Exception $e) {
                        Mage::helper('dpd')->log($e->getMessage(), Zend_Log::ERR);
                        $order = Mage::getResourceModel('sales/order_collection')->addAttributeToSelect('increment_id')->addAttributeToFilter('entity_id', array('eq' => $orderId))->getFirstItem();
                        $message = Mage::helper('dpd')->__("The order with id %s is not ready to be shipped or has already been shipped.", $order->getIncrementId());
                        Mage::getSingleton('core/session')->addNotice($message);
                    }
                }
                if($counter > 0){
                    $message = Mage::helper('dpd')->__("%s label(s) have been generated and statuses have been changed.", $counter);
                    Mage::getSingleton('core/session')->addSuccess($message);
                }
            } catch (Exception $e) {
                Mage::helper('dpd')->log($e->getMessage(), Zend_Log::ERR);
                $message = Mage::helper('dpd')->__("Some of the selected orders are not ready to be shipped or have already been shipped, operation canceled.");
                Mage::getSingleton('core/session')->addError($message);
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Zips all undownloaded labels and gives downloadresponse.
     */
    public function dowloadAllUndownloadedAction()
    {
        $orderIds = $this->getRequest()->getParam('entity_id');
        try {
            $path = Mage::getModel('dpd/adminhtml_dpdgrid')->processUndownloadedLabels($orderIds);
            if (!$path) {
                $message = Mage::helper('dpd')->__('No undownloaded labels found.');
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('*/*/index');
            } else {
                $this->_prepareDownloadResponse('dpd_undownloaded.zip', file_get_contents($path));
            }
        } catch (Exception $e) {
            Mage::helper('dpd')->log($e->getMessage(), Zend_Log::ERR);
            $message = Mage::helper('dpd')->__("The file(s) could not be downloaded, please check your DPD logs.");
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/*/index');
        }
    }

    /**
     * Fetches the label and puts it in a download response.
     */
    public function downloadDpdLabelAction()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        if ($shipment->getDpdLabelPath() == "") {
            $message = Mage::helper('dpd')->__("No label generated yet - please perform the ‘Generate Label and Complete’ action from the overview.");
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/sales_order_shipment/view/shipment_id/' . $shipmentId);
        } else {
            $pdf_Path = Mage::getBaseDir('media') . "/dpd/orderlabels/" . $shipment->getDpdLabelPath();
            $pdf = Zend_Pdf::load($pdf_Path);
            $this->_prepareDownloadResponse($shipment->getDpdLabelPath(), $pdf->render(), 'application/pdf');
            $shipment->setDpdLabelExported(1)->save();
        }
    }

    /**
     * Returns deleted message (used for customparcelshopdelete in sysconfig).
     *
     * @return void
     */
    public function checkAction()
    {
        $websiteId = $this->getRequest()->getPost('id');
        $collection = Mage::getModel('dpd/specialparcelshops')->getCollection()->addFieldToFilter('parcelshop_website_id', array('eq' => $websiteId));
        foreach ($collection->getItems() as $_item) {
            $_item->delete();
        }
        $message = Mage::helper('dpd')->__("The ParcelShops for this website have been deleted.");
        Mage::getSingleton('core/session')->addSuccess($message);
        Mage::app()->getResponse()->setBody($websiteId);
    }
}