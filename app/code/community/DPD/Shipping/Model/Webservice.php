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
 * Class DPD_Shipping_Model_Webservice
 */
class DPD_Shipping_Model_Webservice extends Mage_Core_Model_Abstract
{

    /**
     * Max number of times a login should be retried when the authentication token is expired.
     */
    CONST MAX_LOGIN_RETRY = 3;

    /**
     * The message communication language, should be en_US as instructed by DPD.
     */
    CONST MESSAGE_LANGUAGE = 'en_US';

    /**
     * Athentication namespace for the authentication header in soap requests.
     */
    CONST AUTHENTICATION_NAMESPACE = 'http://dpd.com/common/service/types/Authentication/2.0';

    /**
     * Path to login webservice wsdl.
     */
    CONST WEBSERVICE_LOGIN = 'LoginService/V2_0/?wsdl';

    /**
     * Path to ParcelShopFinder webservice wsdl.
     */
    CONST WEBSERVICE_PARCELSHOP = 'ParcelShopFinderService/V3_0/?wsdl';

    /**
     * Path to Shipment webservice wsdl.
     */
    CONST WEBSERVICE_SHIPMENT = 'ShipmentService/V2_0/?wsdl';

    /**
     * Product type for shipmentservice, should be always 'CL' as instructed by DPD.
     */
    CONST SHIPMENTSERVICE_PRODUCT = 'CL';

    /**
     * Ordertype for shipmentservice, should be always 'consigment' as instructed by DPD.
     */
    CONST SHIPMENTSERVICE_ORDERTYPE = 'consignment';

    /**
     * Paperformat for Return labels, should always be 'A6' as instructed by DPD.
     */
    CONST SHIPMENTSERVICE_RETURN_PAPERFORMAT = 'A6';

    /**
     * XML path to configuration setting for webserviceurl.
     */
    CONST XML_PATH_DPD_URL = 'shipping/dpdclassic/webserviceurl';

    /**
     * XML path to configuration setting for userid.
     */
    CONST XML_PATH_DPD_USERID = 'shipping/dpdclassic/userid';

    /**
     * XML path to configuration setting for password.
     */
    CONST XML_PATH_DPD_PASSWORD = 'shipping/dpdclassic/password';

    /**
     * XML path to configuration setting for sender name.
     */
    CONST XML_PATH_DPD_SENDER_NAME = 'shipping/dpdclassic/sender_name';

    /**
     * XML path to configuration setting for sender street.
     */
    CONST XML_PATH_DPD_SENDER_STREET = 'shipping/dpdclassic/sender_street';

    /**
     * XML path to configuration setting for sender streetnumber.
     */
    CONST XML_PATH_DPD_SENDER_STREETNUMBER = 'shipping/dpdclassic/sender_streetnumber';

    /**
     * XML path to configuration setting for sender country.
     */
    CONST XML_PATH_DPD_SENDER_COUNTRY = 'shipping/dpdclassic/sender_country';

    /**
     * XML path to configuration setting for sender zipcode.
     */
    CONST XML_PATH_DPD_SENDER_ZIPCODE = 'shipping/dpdclassic/sender_zipcode';

    /**
     * XML path to configuration setting for sender city.
     */
    CONST XML_PATH_DPD_SENDER_CITY = 'shipping/dpdclassic/sender_city';

    /**
     * XML path to configuration setting for paperformat of shipping labels.
     */
    CONST XML_PATH_DPD_PAPERFORMAT = 'shipping/dpdclassic/paperformat';

    /**
     * XML path to configuration setting for weight unit to send to webservice;
     */
    CONST XML_PATH_DPD_WEIGHTUNIT = 'shipping/dpdclassic/weight_unit';

    /**
     * XML path to configuration setting for the maximum number of parcelshops that should be returned by the webservice.
     */
    CONST XML_PATH_PARCELSHOP_MAXPOINTERS = 'carriers/dpdparcelshops/google_maps_maxpointers';

    /**
     * Add trailing slash to url if not exists.
     *
     * @param $path
     * @return mixed|string
     */
    protected function _getWebserviceUrl($path)
    {
        $url = Mage::getStoreConfig($path);
        if (substr($url, -1) != '/') {
            $url = $url . DS;
        }

        return $url;
    }

    /**
     * Login webservice with ParcelShop credentials.
     *
     * @return mixed
     */
    protected function _login()
    {
        $webserviceUrl = $this->_getWebserviceUrl(self::XML_PATH_DPD_URL) . self::WEBSERVICE_LOGIN;
        $delisId = Mage::getStoreConfig(self::XML_PATH_DPD_USERID);
        $password = Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_DPD_PASSWORD));

        try {
            $client = new SoapClient($webserviceUrl);

            $result = $client->getAuth(array(
                    'delisId' => $delisId,
                    'password' => $password,
                    'messageLanguage' => self::MESSAGE_LANGUAGE)
            );

            Mage::helper('dpd')->log('Login for webservice succeeded', Zend_Log::INFO);
            Mage::helper('dpd')->log($result, Zend_Log::DEBUG);
        } catch (SoapFault $soapE) {
            Mage::helper('dpd')->log('Webservice Login failed:', Zend_Log::ERR);
            Mage::helper('dpd')->log($soapE->detail, Zend_Log::ERR);
            Mage::getSingleton('adminhtml/session')->addError('A problem occured with the ParcelShop webservice, please contact the store owner.');
            return false;
        } catch (Exception $e) {
            Mage::helper('dpd')->log($e->getMessage(), Zend_Log::ERR);
            return false;
        }

        $this->_setDepot($result->return->depot);
        $this->_setAuthToken($result->return->authToken);
        return $result->return;
    }

    /**
     * Set depot in core/session
     *
     * @param $depot
     */
    protected function _setDepot($depot)
    {
        Mage::getSingleton('core/session')->setDpdDepot($depot);
    }

    /**
     * Set authToken in core/session
     *
     * @param $authToken
     */
    protected function _setAuthToken($authToken)
    {
        Mage::getSingleton('core/session')->setDpdAuthToken($authToken);

    }

    /**
     * Get depot from core/session
     *
     * @return mixed
     */
    protected function _getDepot()
    {
        if(!Mage::getSingleton('core/session')->getDpdDepot()){
            $this->_login();
        }

        return Mage::getSingleton('core/session')->getDpdDepot();
    }

    /**
     * Get authToken from core/session
     *
     * @return mixed
     */
    protected function _getAuthToken()
    {
        if(!Mage::getSingleton('core/session')->getDpdAuthToken()){
            $this->_login();
        }
        return Mage::getSingleton('core/session')->getDpdAuthToken();
    }

    /**
     * Generates an authentication header that needs to be used for parcelshops and labels.
     *
     * @return SOAPHeader
     */
    protected function _getSoapHeader()
    {
        $delisId = Mage::getStoreConfig(self::XML_PATH_DPD_USERID);

        $soapHeaderBody = array(
            'delisId' => $delisId,
            'authToken' => $this->_getAuthToken(),
            'messageLanguage' => self::MESSAGE_LANGUAGE
        );

        return new SOAPHeader(self::AUTHENTICATION_NAMESPACE, 'authentication', $soapHeaderBody, false);
    }

    /**
     * Returns the sender information filled in the backend, is used for the generation of labels.
     *
     * @return array
     */
    protected function _getSenderInformation()
    {
        return array(
            'name1' => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_NAME),
            'street' => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_STREET),
            'houseNo' => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_STREETNUMBER),
            'country' => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_COUNTRY),
            'zipCode' => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_ZIPCODE),
            'city' => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_CITY)
        );
    }

    /**
     * Does the webservice call towards dpd for functions with authentication.
     * If the authentication token is expired a new token will be asked MAX_LOGIN_RETRY times.
     *
     * @param $webserviceUrl
     * @param $method
     * @param $parameters
     * @return mixed
     */
    protected function _webserviceCall($webserviceUrl, $method, $parameters)
    {
        $stop = false;
        $count = 0;

        while (!$stop && $count++ < self::MAX_LOGIN_RETRY) {
            try {
                $client = new SoapClient($webserviceUrl);
                $soapHeader = $this->_getSoapHeader();
                $client->__setSoapHeaders($soapHeader);

                $result = $client->__soapCall($method, array($parameters));
                $stop = true;

                if(isset($result->orderResult->shipmentResponses->faults) || isset($result->orderResult->shipmentResponses->faultString)) {
                    Mage::helper('dpd')->log('Webservice ' . $method . ' failed:', Zend_Log::ERR);
                    if(isset($result->orderResult->shipmentResponses->faults)){
                        Mage::helper('dpd')->log($result->orderResult->shipmentResponses->faults, Zend_Log::ERR);
                    }
                    if(isset($result->orderResult->shipmentResponses->faultString)){
                        Mage::helper('dpd')->log($result->orderResult->shipmentResponses->faultString, Zend_Log::ERR);
                    }
                    return false;
                }


                Mage::helper('dpd')->log('Webservice ' . $method . ' succeeded', Zend_Log::INFO);
                Mage::helper('dpd')->log($result, Zend_Log::DEBUG);

            } catch (SoapFault $soapE) {
                if (isset($soapE->detail)) {
                    if ($soapE->detail->authenticationFault->errorCode == 'LOGIN_5') {
                        Mage::helper('dpd')->log('Athentication token expired, retrying...', Zend_Log::INFO);
                        $this->_login();
                    } else {
                        Mage::helper('dpd')->log('Webservice ' . $method . ' failed:', Zend_Log::ERR);
                        Mage::helper('dpd')->log($soapE->detail, Zend_Log::ERR);
                        Mage::getSingleton('adminhtml/session')->addError($soapE->detail->authenticationFault->errorMessage);
                        return false;
                    }
                } else {
                    Mage::helper('dpd')->log($soapE->getMessage(), Zend_Log::ERR);
                    Mage::getSingleton('adminhtml/session')->addError($soapE->getMessage());
                    return false;
                }
            } catch (Exception $e) {
                Mage::helper('dpd')->log($e->getMessage(), Zend_Log::ERR);
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong with the webservice, please check the log files.');
                return false;
            }
        }

        if ($stop == false) {
            Mage::helper('dpd')->log('Athentication went wrong!', Zend_Log::ERR);
            return false;
        } else {
            return $result;
        }
    }

    /**
     * Returns if the login webservice works.
     *
     * @return bool
     */
    public function getLoginResult()
    {
        if ($this->_getAuthToken() && Mage::getStoreConfig(self::XML_PATH_DPD_USERID) && Mage::getStoreConfig(self::XML_PATH_DPD_PASSWORD)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Get parcelshops from webservice findParcelShopsByGeoData.
     *
     * @param $longitude
     * @param $latitude
     * @return mixed
     */
    public function getParcelShops($longitude, $latitude)
    {
        $webserviceUrl = $this->_getWebserviceUrl(self::XML_PATH_DPD_URL) . self::WEBSERVICE_PARCELSHOP;
        $limit = Mage::getStoreConfig(self::XML_PATH_PARCELSHOP_MAXPOINTERS);
        $parameters = array(
            'longitude' => $longitude,
            'latitude' => $latitude,
            'limit' => $limit,
            'consigneePickupAllowed' => 'true'
        );

        $result = $this->_webserviceCall($webserviceUrl, 'findParcelShopsByGeoData', $parameters);
        return $result;

    }

    /**
     * Get a returnlabel from webservice storeOrders.
     * $recipient = array('name1' => '', 'street' => '', 'country' => '', 'zipCode' => '', 'city' => '');
     *
     * @param $recipient
     * @return mixed
     */
    public function getReturnLabel($recipient)
    {
        $webserviceUrl = $this->_getWebserviceUrl(self::XML_PATH_DPD_URL) . self::WEBSERVICE_SHIPMENT;
        $sendingDepot = $this->_getDepot();
        $sender = $this->_getSenderInformation();

        $parameters = array(
            'paperFormat' => self::SHIPMENTSERVICE_RETURN_PAPERFORMAT,
            'order' => array(
                'generalShipmentData' => array(
                    'sendingDepot' => $sendingDepot,
                    'product' => self::SHIPMENTSERVICE_PRODUCT,
                    'sender' => $sender,
                    'recipient' => $recipient
                ),
                'parcels' => array(
                    'returns' => 1
                ),
                'productAndServiceData' => array(
                    'orderType' => self::SHIPMENTSERVICE_ORDERTYPE
                )
            ));

        $result = $this->_webserviceCall($webserviceUrl, 'storeOrders', $parameters);
        return $result->orderResult;
    }

    /**
     * Get a shippinglabel from webservice storeOrders.
     * $recipient = array('name1' => '', 'street' => '', 'country' => '', 'zipCode' => '', 'city' => '');
     *
     * @param $recipient
     * @param $order
     * @param $shipment
     * @param bool $parcelshop
     * @return mixed
     */
    public function getShippingLabel($recipient, Mage_Sales_Model_Order $order, $shipment, $parcelshop = false)
    {
        $webserviceUrl = $this->_getWebserviceUrl(self::XML_PATH_DPD_URL) . self::WEBSERVICE_SHIPMENT;
        $sendingDepot = $this->_getDepot();
        $sender = $this->_getSenderInformation();

        $paperFormatSource = Mage::getModel('dpd/system_config_source_paperformat')->toArray();
        $paperFormat = $paperFormatSource[Mage::getStoreConfig(self::XML_PATH_DPD_PAPERFORMAT)];

        $language = Mage::helper('dpd')->getLanguageFromStore($order->getStoreId());

        if ($parcelshop) {
            $productAndServiceData = array(
                'orderType' => self::SHIPMENTSERVICE_ORDERTYPE,
                'parcelShopDelivery' => array(
                    'parcelShopId' => $order->getDpdParcelshopId(),
                    'parcelShopNotification' => array(
                        'channel' => 1, //email
                        'value' => $order->getCustomerEmail(),
                        'language' => $language
                    )
                ));
        } else {
            $productAndServiceData = array(
                'orderType' => self::SHIPMENTSERVICE_ORDERTYPE,
                'predict' => array(
                    'channel' => 1, //email
                    'value' => $order->getCustomerEmail(),
                    'language' => $language
                ));
        }
        if(Mage::getStoreConfig(self::XML_PATH_DPD_WEIGHTUNIT) == ""){
            $weight = $shipment->getTotalWeight() * 100;
        }
        else{
            $weight = $shipment->getTotalWeight() * Mage::getStoreConfig(self::XML_PATH_DPD_WEIGHTUNIT);
        }
        $parameters = array(
            'paperFormat' => $paperFormat,
            'order' => array(
                'generalShipmentData' => array(
                    'mpsCustomerReferenceNumber1' => $order->getIncrementId(),
                    'sendingDepot' => $sendingDepot,
                    'product' => self::SHIPMENTSERVICE_PRODUCT,
                    'sender' => $sender,
                    'recipient' => $recipient
                ),
                'parcels' => array(
                    'customerReferenceNumber1' => $shipment->getIncrementId(),
                    'weight' => round($weight,0)
                ),
                'productAndServiceData' => $productAndServiceData
            ));

        $result = $this->_webserviceCall($webserviceUrl, 'storeOrders', $parameters);
        return $result->orderResult;
    }
}