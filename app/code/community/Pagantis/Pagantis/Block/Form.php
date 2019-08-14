<?php

/**
 * Pagantis_Pagantis payment form
 *
 * @package    Pagantis_Pagantis
 * @copyright  Copyright (c) 2015 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
 * @link	   http://www.yameveo.com
 */
class Pagantis_Pagantis_Block_Form extends Mage_Payment_Block_Form
{

    protected function _construct()
    {

        $session = Mage::getSingleton('checkout/session');
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $quote = Mage::getModel('checkout/session')->getQuote();
        $quoteData= $quote->getData();
        $amount=$quoteData['grand_total'];
        $config = Mage::getStoreConfig('payment/pagantis');
        $this->setData('iframe',$config['iframe']);
        $discount = $config['discount'];
        if ($discount == 'true'){
          $this->setData('discount',1);
        }else{
          $this->setData('discount',0);
        }
        switch($config['environment']) {
            case Pagantis_Pagantis_Model_Webservice_Client::ENV_TESTING:
                $this->setData('public_key',$config['account_code_test']);
                break;
            case Pagantis_Pagantis_Model_Webservice_Client::ENV_PRODUCTION:
                $this->setData('public_key',$config['account_code_real']);
                break;
        }
        $end_of_month = $config['end_of_month'];
        $title=$config['title'];
        $this->setData('total',$amount);
        $mark->setTemplate('pagantis/form.phtml');
        $this->setTemplate('pagantis/pagantis.phtml')
            ->setMethodLabelAfterHtml($mark->toHtml())
            ->setMethodTitle($title)

        ;
        return parent::_construct();
    }

}
