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
 * Class DPD_Shipping_Model_Mysql4_Specialparcelshops
 */
class DPD_Shipping_Model_Mysql4_Specialparcelshops extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Store errors here for display in the session message.
     *
     * @var array
     */
    protected $_importErrors = array();
    /**
     * Website Id of the imported date.
     *
     * @var
     */
    protected $_importWebsiteId;
    /**
     * Csv Rows.
     *
     * @var
     */
    protected $_importedRows;

    /**
     * Sets model primary key.
     */
    protected function _construct()
    {
        $this->_init("dpd/specialparcelshops", "specialparselshops_id");
    }

    /**
     * Uploads the file and imports the data into the table.
     *
     * @param Varien_Object $object
     * @return $this
     */
    public function uploadAndImport(Varien_Object $object)
    {
        if (empty($_FILES['groups']['tmp_name']['dpdparcelshops']['fields']['custom_parcelshops_import']['value'])) {
            return $this;
        }
        $website = Mage::app()->getWebsite($object->getScopeId());
        $this->_importWebsiteId = (int)$website->getId();
        $csvFile = $_FILES['groups']['tmp_name']['dpdparcelshops']['fields']['custom_parcelshops_import']['value'];
        $io = new Varien_Io_File();
        $info = pathinfo($csvFile);
        $io->open(array('path' => $info['dirname']));
        $io->streamOpen($info['basename'], 'r');
        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();

        try {
            $rowNumber = 1;
            $importData = array();
            $parcelShopNumber = 0;
            $totalrows = count(file($csvFile));
            $condition = array(
                'parcelshop_website_id = ?' => $this->_importWebsiteId
            );
            $adapter->delete($this->getMainTable(), $condition);

            while (false !== ($csvLine = $io->streamReadCsv(";"))) {
                $rowNumber++;

                if (empty($csvLine)) {
                    continue;
                }

                $row = $this->_getImportRow($csvLine, $rowNumber, $totalrows);
                if ($row !== false) {
                    if ($csvLine[0] == 'GR') {
                        $parcelShopNumber++;
                    }
                    if (!isset($importData[$parcelShopNumber])) {
                        $importData[$parcelShopNumber] = array();
                    }
                    $importData[$parcelShopNumber] = array_merge($importData[$parcelShopNumber], $row);
                }
            }
            $this->_saveImportData($importData);
            $io->streamClose();

        } catch (Mage_Core_Exception $e) {
            if (count($rowNumber) < 5) {
                $this->_importErrors[] = Mage::helper('dpd')->__('Invalid ParcelShops format in the Row #%s', $rowNumber);
                return false;
            }
            $adapter->rollback();
            $io->streamClose();
            Mage::throwException($e->getMessage());
        } catch (Exception $e) {
            $adapter->rollback();
            $io->streamClose();
            Mage::helper('dpd')->log($e, Zend_Log::ERR);
            Mage::throwException(Mage::helper('dpd')->__('An error occurred while importing ParcelShops. Check the log for details.'));
        }

        if ($this->_importErrors) {
            $adapter->rollback();
            $error = Mage::helper('dpd')->__('File has not been imported. See the following list of errors: %s', implode(" \n", $this->_importErrors));
            Mage::throwException($error);
        }
        $adapter->commit();
        return $this;
    }

    /**
     * Gets the row and validates content.
     *
     * @param $row
     * @param int $rowNumber
     * @param $totalrows
     * @return array|bool
     */
    protected function _getImportRow($row, $rowNumber = 0, $totalrows)
    {
        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        // validation
        if ($rowNumber == 2 && $row[0] != "AA") {
            $this->_importErrors[] = Mage::helper('dpd')->__('Your csv does not have the required AA header. Please refer to the manual.');
        } elseif ($row[0] == "ZZ" && $rowNumber != $totalrows + 1) {
            $this->_importErrors[] = Mage::helper('dpd')->__('Your csv closed too soon with the ZZ ending. Please refer to the manual.');
        } elseif ($rowNumber == $totalrows + 1 && $row[0] != "ZZ" && empty($this->_importErrors)) {
            $this->_importErrors[] = Mage::helper('dpd')->__('Your csv does not have the required ZZ ending. Please refer to the manual.');
        }

        //normalize data
        if ($row[0] == "GR") {
            $requiredAttributes = array("PUDO Id" => $row[1], "Pudo Name" => $row[4], "Pudo Language" => $row[5], "Pudo Country" => $row[6], "Latitude" => $row[7], "Longitude", $row[8]);
            if($missingAttributes = $this->_checkRequired($requiredAttributes)){
                $this->_importErrors[] = Mage::helper('dpd')->__('Your csv does not have the following required attributes %s on row %s.  Please refer to the manual.', $missingAttributes, $rowNumber);
            }
            $fillarray = array($this->_importWebsiteId, $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8]);
            return $fillarray;
        } elseif ($row[0] == "AD") {
            $requiredAttributes = array("Address 1" => $row[4], "Post Code" => $row[7], "Town" => $row[8]);
            if($missingAttributes = $this->_checkRequired($requiredAttributes)){
                $this->_importErrors[] = Mage::helper('dpd')->__('Your csv does not have the following required attributes %s on row %s.  Please refer to the manual.', $missingAttributes, $rowNumber);
            }
            $fillarray = array($row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8], $row[9]);
            return $fillarray;
        } elseif ($row[0] == "HO") {
            $daysArray = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
            $hoursarray = array();
            $count = 2;
            for ($i = 0; $i < 7; $i++) {
                $timetable = new stdClass;
                $timetable->weekday = $daysArray[$i];
                $timetable->openMorning = substr_replace($row[$count++], ":", 2, 0);
                $timetable->closeMorning = substr_replace($row[$count++], ":", 2, 0);
                $timetable->openAfternoon = substr_replace($row[$count++], ":", 2, 0);
                $timetable->closeAfternoon = substr_replace($row[$count++], ":", 2, 0);
                $hoursarray[] = $timetable;
            }
            $fillarray = array(Mage::helper('core')->jsonEncode($hoursarray));
            return $fillarray;
        }

        return false;
    }

    /**
     * Save all data.
     *
     * @param array $data
     * @return $this
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = array('parcelshop_website_id', 'parcelshop_delicom_id', 'parcelshop_carrier_pudo_id', 'parcelshop_manager',
                'parcelshop_pudo_name', 'parcelshop_pudo_language', 'parcelshop_country', 'parcelshop_latitude',
                'parcelshop_longitude', 'parcelshop_city_code', 'parcelshop_langue_1', 'parcelshop_address_1', 'parcelshop_address_2',
                'parcelshop_location_information', 'parcelshop_post_code', 'parcelshop_town', 'parcelshop_region', 'parcelshop_openinghours');
            $values = $data;
            $this->_getWriteAdapter()->insertArray($this->getMainTable(), $columns, $values);
        }
        return $this;
    }

    /**
     * Check required data row per row.
     *
     * @param array $requiredAttributes
     * @return bool|string
     */
    protected function _checkRequired(array $requiredAttributes)
    {
        $missing = false;
        $missingattributes = array();
        foreach($requiredAttributes as $key => $requiredAttribute){
            if(!$requiredAttribute){
                $missingattributes[] =  $key;
                $missing = true;
            }
        }
        if(!$missing){
            return false;
        }

        return implode(', ',$missingattributes);
    }
}