<?php

/**
 * Class Pagantis_Pagantis_Model_Webservice_Request
 * @package    Pagantis_Pagantis
 * @copyright  Copyright (c) 2015 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
 * @link	   http://www.yameveo.com
 */

class Pagantis_Pagantis_Model_Webservice_Request
{
    const BASE = 'pagantis/pagantis';

    /**
     * @var string $_urlPagantis Redirect url for payment
     */
    protected $_urlPagantis;

    /**
     * @var string $_amount Order Amount
     */
    protected $_amount;

    /**
     * @var string $_currency Order Amount
     */
    protected $_currency;

    /**
     * @var string $_orderId Increment order Id
     */
    protected $_orderId;
    /**
     * @var string $_languagePagantis Force language on bank page
     */
    protected $_languagePagantis;

    /**
     * @var string $_authMethod Authentication method for payment redirect.
     */
    protected $_authMethod;

    /**
     * @var string $_accountCode
     */
    protected $_accountCode;

    /**
     * @var string $accountKey
     */
    protected $_accountKey;

    /**
     * @var string $_accountApiKey
     */
    protected $_accountApiKey;

    /**
     * @var string $_urlOk Requerido 500 URL completa.
     */
    protected $_urlOk;

    /**
     *
     * @var string $_urlKo Requerido 500 URL completa.
     */
    protected $_urlKo;

    /**
     *
     * @var string $_callback_url Requerido 500 URL completa.
     */
    protected $_callback_url;

    /**
     * @var string $_firma created by clave_de_firma + account_id + order_id + amount + currency + auth_method + ok_url + nok_url
     */
    protected $_firma;

    /**
     * @var discount
     */
    protected $_discount;

    /**
     * @var end_of_month
     */
    protected $_end_of_month;


    public function __construct()
    {
        $this->_languagePagantis = $this->setLanguagePagantis(); //Por defecto español
        $this->_currency = $this->setCurrency();
    }

    /**
     * Return the array version of the Request to be sent through the form
     *
     * @return string
     */
    public function toArray()
    {
        $array = array();
        $array['order_id'] = $this->_orderId;
        $array['amount'] = $this->_amount;
        $array['currency'] = $this->_currency;

        $array['ok_url'] = $this->_urlOk;
        $array['nok_url'] = $this->_urlKo;
        $array['callback_url'] = $this->_callback_url;
        $array['cancelled_url'] = $this->_urlCancel;
        $array['iframe'] = $this->_iframe;
        //$array['urlPagantis'] = $this->_urlPagantis;
        $array['discount[full]'] = $this->_discount;
        $array['end_of_month'] = $this->_end_of_month;

        $array['locale'] = $this->_languagePagantis;
        $array['mobile_phone'] = $this->_userData['mobile_phone'];
        $array['full_name'] = $this->_userData['full_name'];
        $array['email'] = $this->_userData['email'];
        $array['address[street]'] = $this->_userData['Bstreet'];
        $array['address[city]'] = $this->_userData['Bcity'];
        $array['address[province]'] = $this->_userData['Bprovince'];
        $array['address[zipcode]'] = $this->_userData['Bzipcode'];
        $array['shipping[street]'] = $this->_userData['street'];
        $array['shipping[city]'] = $this->_userData['city'];
        $array['shipping[province]'] = $this->_userData['province'];
        $array['shipping[zipcode]'] = $this->_userData['zipcode'];
        $array['dni'] = $this->_userData['dni'];
        $array['dob'] = $this->_userData['dob'];
        $array['metadata[member_since]'] = $this->_userData['sign_up_date'];
        $array['metadata[num_orders]'] = $this->_userData['num_prev_orders'];
        $array['metadata[amount_orders]'] = $this->_userData['total_paid'];
        $array['metadata[num_full_refunds]'] = $this->_userData['num_full_refunds'];
        $array['metadata[num_partial_refunds]'] = $this->_userData['num_partial_refunds'];
        $array['metadata[amount_refunds]'] = $this->_userData['amount_refunded'];
        $array['metadata[module_version]'] = '3.3.1';
        $array['metadata[platform]'] = 'magento '. Mage::getVersion();

        foreach ($this->_items as $key => $value) {
            $array['items[' . $key . '][description]'] = $value['description'];
            $array['items[' . $key . '][quantity]'] = $value['quantity'];
            $array['items[' . $key . '][amount]'] = $value['amount'];
        }
        $array['account_id'] = $this->_accountCode;
        $array['signature'] = $this->_firma;

        return $array;
    }

    /**
     * Assign url for redirect
     * @param string $urlPagantis
     * @throws Exception
     */
    public function setUrlPagantis($urlPagantis = '')
    {
        if (strlen(trim($urlPagantis)) > 0) {
            $this->_urlPagantis = $urlPagantis;
        } else {
            throw new \Exception('Missing url for redirect to page bank');
        }
    }

    /**
     * Assign url for redirect
     * @param string $urlPagantis
     * @throws Exception
     */
    public function setOrderId($orderId = '')
    {
        if (strlen(trim($orderId)) > 0) {
            $this->_orderId = $orderId;
        } else {
            throw new \Exception('Missing orderId');
        }
    }

    /**
     * Assign currency for redirect
     * @param string $currency
     * @throws Exception
     */
    public function setCurrency()
    {
        return Pagantis_Pagantis_Model_Currency::EUR;
    }

    /**
     * Assign url for redirect
     * @param string $urlPagantis
     * @throws Exception
     */
    public function setAmount($amount = '')
    {
        if (strlen(trim($amount)) > 0) {
            $this->_amount = $amount;
        } else {
            throw new \Exception('Missing amount');
        }
    }

    /**
     * Assign language for bank page
     * @param string $language Language for bank page
     */
    public function setLanguagePagantis($language = 'es')
    {
        return $language;
    }

    /**
     * Assign user data
     * @param string $addressId
     * @throws Exception
     */
    public function setUserData($addressId)
    {
        if ($addressId) {
            $address = Mage::getModel('sales/order_address')->load($addressId);
            $street = $address->getStreet();
            if (is_array($street)) {
                $this->_userData['street'] = $street[0];
            } else {
                $this->_userData['street'] = $street;
            }
            $this->_userData['city'] = $address->getCity();
            $this->_userData['province'] = $address->getCity();
            $this->_userData['zipcode'] = $address->getPostcode();
            $this->_userData['dni'] = $address->getVatId();
            $this->_userData['full_name'] = $address->getFirstname() . ' ' . $address->getLastname();
            $this->_userData['email'] = $address->getEmail();
            $this->_userData['mobile_phone'] = $address->getTelephone();
        } else {
            throw new \Exception('Missing user data info');
        }

        //fix to avoid empty fields
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if (empty($this->_userData['email'])) {
                $this->_userData['email'] = $customer->getEmail();
            }
            if (empty($this->_userData['full_name'])) {
                $this->_userData['full_name'] = $customer->getFirstname() . ' ' . $customer->getLastname();
            }
            if (empty($this->_userData['dni']) && $customer->getFirstname() == $address->getFirstname()
                && $customer->getLastname() == $address->getLastname()) {
                $this->_userData['dni'] = $customer->getData('taxvat');
            }
            if (empty($this->_userData['phone']) && $customer->getFirstname() == $address->getFirstname()
                && $customer->getLastname() == $address->getLastname()) {
                $this->_userData['phone'] = $customer->getPrimaryBillingAddress()->getTelephone();
            }
            if (empty($this->_userData['mobile_phone']) && $customer->getFirstname() == $address->getFirstname()
                && $customer->getLastname() == $address->getLastname()) {
                $this->_userData['mobile_phone'] = $customer->getPrimaryBillingAddress()->getTelephone();
            }
            if (empty($this->_userData['zipcode']) && $customer->getFirstname() == $address->getFirstname()
                && $customer->getLastname() == $address->getLastname()) {
                $this->_userData['zipcode'] = $customer->getPrimaryBillingAddress()->getPostcode();
            }
            if ($customer->getFirstname() == $address->getFirstname()
                && $customer->getLastname() == $address->getLastname()) {
                $this->_userData['dob'] =substr($customer->getDob(), 0, 10);
            }
        }
    }

    /**
     * Assign user extra data
     * @param string $addressId
     */
    public function setUserExtraData($addressId)
    {
        //set default values if we don't find the customer
        $this->_userData['sign_up_date']= '';
        $this->_userData['num_prev_orders'] = 0;
        $this->_userData['total_paid'] = 0;
        $this->_userData['amount_refunded'] = 0;
        $this->_userData['num_refunds'] = 0;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
           //if user is logged
            $customer = Mage::getSingleton('customer/session')->getCustomer();
        } else {
            //user not logged
            if ($addressId) {
                $address = Mage::getModel('sales/order_address')->load($addressId);
                $email = $address->getEmail();
                $customer = Mage::getModel("customer/customer");
                $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                $customer->loadByEmail($email);
            }
        }

        if ($customer->getId() != null) {
            $this->_userData['sign_up_date'] = date('Y/m/d', $customer->getCreatedAtTimestamp());
            $_orders = Mage::getModel('sales/order')->getCollection()->
                     addFieldToFilter('customer_id', $customer->getId())->
                     addFieldToFilter('status', array(
                       array('finset'=> array('complete')),
                       array('finset'=> array('processing')),
                     ));
            $this->_userData['num_prev_orders'] = $_orders->count();
            $total = 0;
            foreach ($_orders as $order) {
                $total += $order->getGrandTotal();
            }

            $total_memo_amt = 0;
            $total_partial_memos = 0;
            $total_full_memos = 0;
            $_orders = Mage::getModel('sales/order')->getCollection()->
                     addFieldToFilter('customer_id', $customer->getId());
            foreach ($_orders as $order) {
                $creditMemos = Mage::getResourceModel('sales/order_creditmemo_collection');
                $creditMemos->addFieldToFilter('order_id', $order->getId());
                $creditMemos->setOrder('created_at', 'DESC');
                foreach ($creditMemos as $memo) {
                    if ($order->getGrandTotal() == $memo->getGrandTotal()) {
                        $total_full_memos += 1;
                    } else {
                        $total_partial_memos += 1;
                    }
                    $total_memo_amt += $memo->getGrandTotal();
                }
                //$total_memos += $creditMemos->count();
            }
            $this->_userData['amount_refunded'] = ($total_memo_amt);
            $this->_userData['num_full_refunds'] = ($total_full_memos);
            $this->_userData['num_partial_refunds'] = ($total_partial_memos);
            $this->_userData['total_paid'] = $total;
        }
    }

    /**
     * Assign user data
     * @param string $addressId
     * @throws Exception
     */
    public function setUserBillData($addressId)
    {
        if ($addressId) {
            $address = Mage::getModel('sales/order_address')->load($addressId);
            $street = $address->getStreet();
            if ($street) {
                $this->_userData['Bstreet'] = $street[0];
            }
            $this->_userData['Bcity'] = $address->getCity();
            $this->_userData['Bprovince'] = $address->getCity();
            $this->_userData['Bzipcode'] = $address->getPostcode();
            $this->_userData['Bdni'] = $address->getVatId();
            $this->_userData['Bfull_name'] = $address->getFirstname() . ' ' . $address->getLastname();
            $this->_userData['Bemail'] = $address->getEmail();
            $this->_userData['Bphone'] = $address->getTelephone();
        } else {
            throw new \Exception('Missing user data info');
        }
    }

    /**
     * Assign user data
     * @param string $addressId
     * @throws Exception
     */
    public function setOrderItems($orderId)
    {
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $items = $order->getAllVisibleItems();
            $i = 0;
            foreach ($items as $item) {
                $amount = round($item->getPriceInclTax(), 2);
                $quantity = round($item->getQtyOrdered());
                $this->_items[$i]['description'] = $item->getName();
                $this->_items[$i]['quantity'] = $quantity;
                $this->_items[$i]['amount'] = round($amount*$quantity, 2);
                $i++;
            }
            $shippingAmount = round($order->getShippingInclTax(), 2);
            if ($shippingAmount) {
                $this->_items[$i]['description'] = "Gastos de envío";
                $this->_items[$i]['quantity'] = "1";
                $this->_items[$i]['amount'] = $shippingAmount;
                $i++;
            }
            $discountAmount = round($order->getBaseDiscountAmount(), 2);
            if ($discountAmount) {
                $this->_items[$i]['description'] = "Descuento";
                $this->_items[$i]['quantity'] = "1";
                $this->_items[$i]['amount'] = $discountAmount;
            }

        } else {
            throw new \Exception('Missing user data info');
        }
    }

    /**
     * Assign account code
     * @param string $accountCode
     * @throws Exception
     */
    public function setAccountCode($accountCode = '')
    {
        if (strlen(trim($accountCode)) > 0) {
            $this->_accountCode = $accountCode;
        } else {
            throw new \Exception('Missing account code');
        }
    }

    /**
     * Assign discount
     * @param string $discount
     * @throws Exception
     */
    public function setDiscount($discount = '')
    {
        if ($discount == 1) {
            $this->_discount = 'true';
        } else {
            $this->_discount = 'false';
        }
    }

    /**
     * Assign iframe
     * @param string $iframe
     * @throws Exception
     */
    public function setIframe($iframe=''){
        if ($iframe == 1) {
            $this->_iframe = 'true';
        } else {
            $this->_iframe = 'false';
          }
      }

    /**
     * Assign end_of_month
     * @param string end_of_month
     * @throws Exception
     */
    public function setEndOfMonth($end_of_month=''){
        if ($end_of_month == 'true') {
            $this->_end_of_month = 'true';
        } else {
            $this->_end_of_month = 'false';
        }
    }

    /**
     * Assign account key
     * @param string $accountKey
     * @throws Exception
     */
    public function setAccountKey($accountKey=''){
        if (strlen(trim($accountKey)) > 0) {
            $this->_accountKey = $accountKey;
        } else {
            throw new \Exception('Missing account key');
        }
    }

    /**
     * Assign account key
     * @param string $accountApiKey
     * @throws Exception
     */
    public function setAccountApiKey($accountApiKey=''){
        if (strlen(trim($accountApiKey)) > 0) {
            $this->_accountApiKey = $accountApiKey;
        } else {
            throw new \Exception('Missing account API key');
        }
    }

    /**
     * @param string $urlok
     * @throws Exception
     */
    public function setUrlOk()
    {
        $urlOk = $this->getUrl('success');
        if (strlen(trim($urlOk)) > 0) {
            $this->_urlOk = $urlOk;
        } else {
            throw new \Exception('UrlOk not defined');
        }

    }

    /**
     * @param string $urlnok
     * @throws Exception
     */
    public function setUrlKo($urlKo = '')
    {
        $urlKo = $this->getUrl('cancel');
        if (strlen(trim($urlKo)) > 0) {
            $this->_urlKo = $urlKo;
        } else {
            throw new \Exception('UrlKo not defined');
        }
    }

    /**
     * @param string $urlnok
     * @throws Exception
     */
     public function setUrlCancelled($urlKo = '')
     {
         $urlCancel = Mage::helper('checkout/url')->getCheckoutUrl();
         $have_params = strpos($urlCancel,'?');
         if ($have_params !== false){
           $urlCancel = substr($urlCancel,0,$have_params);
         }
         if (strlen(trim($urlCancel)) > 0) {
             $this->_urlCancel = $urlCancel;
         } else {
             throw new \Exception('UrlKo not defined');
         }
     }

    /**
     * @param string $urlnok
     * @throws Exception
     */
    public function setCacllbackUrl()
    {
        if (Mage::app()->getStore()->isFrontUrlSecure()){
            $this->_callback_url=Mage::getUrl('',array('_forced_secure'=>true))."pagantis/pagantis/notification";
        }else{
            $this->_callback_url=Mage::getUrl('',array('_forced_secure'=>false))."pagantis/pagantis/notification";
        }
        $this->_callback_url = Mage::getModel('core/url')->sessionUrlVar($this->_callback_url);
    }

    /**
     * Firm generation
     * Generated with SHA1 of _accountKey + _accountCode + _orderId + _amount + _currency + _urlOk + _urlKo
     * @throws Exception
     * @return string
     */
    public function setFirma()
    {
        $textToEncode = $this->_accountKey . $this->_accountCode . $this->_orderId . $this->_amount . $this->_currency  . $this->_urlOk . $this->_urlKo . $this->_callback_url . $this->_discount. $this->_urlCancel;
        //encoding is SHA1
        $this->_firma = sha1($textToEncode);
        //encoding is SHA512
        $this->_firma = hash('sha512',$textToEncode);
        /*
        if (strlen(trim($textToEncode)) > 0) {
            // Retrieve del SHA1
            $this->_firma = sha1($textToEncode);
        } else {
            throw new Exception('Missing SHA1');
        }
        */
    }

    //Utilities
    //http://stackoverflow.com/a/9111049/444225
    private function priceToSQL($price)
    {
        $price = preg_replace('/[^0-9\.,]*/i', '', $price);
        $price = str_replace(',', '.', $price);

        if (substr($price, -3, 1) == '.') {
            $price = explode('.', $price);
            $last = array_pop($price);
            $price = join($price, '') . '.' . $last;
        } else {
            $price = str_replace('.', '', $price);
        }

        return $price;
    }

    public function getUrl($path)
    {
        $url = Mage::getUrl(self::BASE . "/$path");
        return Mage::getModel('core/url')->sessionUrlVar($url);
    }

    /**
     * Converts field names for setters and getters
     *
     * @param string $name
     * @return string
     */
    private function underscore($name)
    {
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        return $result;
    }

    /**
     * Checks if received response is correct
     * Clave_encriptacion+MerchantID+AcquirerBIN+TerminalID+Num_operacion+Importe+TipoMoneda+ Exponente+Referencia
     * @param Yameveo_Ceca_Model_Webservice_Response $response
     * @return boolean
     */
    /*public function checkResponseSignature(Pagantis_Pagantis_Model_Webservice_Response $response)
    {
        $txt = $this->_clave_encriptacion;
        $txt .= $response->getMerchantId();
        $txt .= $response->getAcquirerBin();
        $txt .= $response->getTerminalId();
        $txt .= $response->getNumOperacion();
        $txt .= $response->getImporte();
        $txt .= $response->getTipoMoneda();
        $txt .= $response->getExponente();
        $txt .= $response->getReferencia();

        // Calculate signature
        $signature = sha1($txt);

        // Compare received signature with calculated
        return strtolower($signature) === strtolower($response->getFirma());
    }*/
}
