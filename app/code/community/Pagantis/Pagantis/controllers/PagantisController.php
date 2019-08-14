<?php

/**
 * Pagantis Checkout Controller
 *
 * @package    Pagantis_Pagantis
 * @copyright  Copyright (c) 2015 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
 * @link	   http://www.yameveo.com
 */
class Pagantis_Pagantis_PagantisController extends Mage_Core_Controller_Front_Action
{
    /**
     * When a customer chooses Pagantis on Checkout/Payment page
     *
     */
     public function redirectAction()
     {
         $session = Mage::getSingleton('checkout/session');
         $order = Mage::getModel('sales/order')->load($session->getLastOrderId());
         //we came back from previous order
         if ($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
             && $order->getPagantisTransaction() == 'pmt_pending_order') {
               $this->_restore_cart($order);
               $this->loadLayout();
               $this->renderLayout();
         }
         // if order is not paid yet, redirect to payment page
         else if($order->getState() != Mage_Sales_Model_Order::STATE_COMPLETE &&
            $order->getState() !=Mage_Sales_Model_Order::STATE_PROCESSING) {
            $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
            $order->setState($state, $state, Mage::helper('pagantis_pagantis')->__('Redirected to Pagantis'), false);
            $order->setPagantisTransaction('pmt_pending_order');
            $order->save();
            $this->_restore_cart($order);
            $this->loadLayout();
            $this->renderLayout();
         } else {
             //order is paid
             $this->successAction();
         }
     }

    /**
     * When customer cancel payment from CECA (UrlKO)
     *
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
        if ( !$order->getId()) {
          $session->addError('Lo sentimos, se ha producido algún error en el pago, le agradeceríamos que volviera a intentarlo.');
          Mage::helper('pagantis_pagantis')->restoreQuote();
          $this->_redirect('checkout/cart');
        } elseif ($order->getId() &&
            $order->getState() != Mage_Sales_Model_Order::STATE_COMPLETE &&
            $order->getState() !=Mage_Sales_Model_Order::STATE_PROCESSING) {
            //Mage::getSingleton(‘core/session’)->addError(‘Error message’);
            $session->addError('Lo sentimos, se ha producido algún error en el pago, le agradeceríamos que volviera a intentarlo.');
            if ($session->getLastRealOrderId()) {
                $order->cancel()->save();
                Mage::helper('pagantis_pagantis')->restoreQuote();
            }
            $this->_redirect('checkout/cart');
        } else {
          //order is paid
          $this->successAction();
        }
    }

    public function notificationAction(){
        //$json = json_decode(file_get_contents(Mage::getBaseDir().'/charge.created.txt'),true);
        //Notification url mush be like http://mydomain.com/pagantis/pagantis/notification

        $json = file_get_contents('php://input');
        $temp = json_decode($json,true);
        //verify notification
        $conf = Mage::getStoreConfig('payment/pagantis');
        $environment = $conf['environment'];
        switch($environment){
            case Pagantis_Pagantis_Model_Webservice_Client::ENV_TESTING:
                $key=$conf['account_key_test'];
                break;
            case Pagantis_Pagantis_Model_Webservice_Client::ENV_PRODUCTION:
                $key=$conf['account_key_real'];
                break;
        }
        $signature_check = sha1($key.$temp['account_id'].$temp['api_version'].$temp['event'].$temp['data']['id']);
        $signature_check_sha512 = hash('sha512',$key.$temp['account_id'].$temp['api_version'].$temp['event'].$temp['data']['id']);
        if ($signature_check != $temp['signature'] && $signature_check_sha512 != $temp['signature']){
          //hack detected
          $this->cancelAction();
          return false;
        }
        $data = $temp['data'];
        $orderId = $data['order_id'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        Mage::log('Pagantis notification received for orderId '.$orderId);
        //Mage::log($data,null,'logfile.txt');
        if ($order->getId()) {
            switch ($temp['event']) {
                case 'charge.created':
                    $order->setPagantisTransaction($data['id']);
                    $order->save();
                    $this->_processOrder($order);
                    break;
                case 'charge.failed':
                    if ( $order->getState != Mage_Sales_Model_Order::STATE_PROCESSING &&
                         $order->getState != Mage_Sales_Model_Order::STATE_COMPLETE) {
                        $order->setPagantisTransaction($data['id']);
                        $order->setState(Mage_Sales_Model_Order::STATE_CANCELED,true);
                        $order->save();
                    }
                    break;
            }
        }
    }

    public function geturlAction(){
        //$json = json_decode(file_get_contents(Mage::getBaseDir().'/charge.created.txt'),true);
        //Notification url mush be like http://mydomain.com/pagantis/pagantis/notification

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://pmt.pagantis.com/v1/installments');
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $result = curl_exec($ch);
        $the_url = substr(
            $result,
            strpos($result, 'href') + 6,
            strpos($result, 'redirected') - strpos($result, 'href') - 6 - 2
        );
        echo $the_url;
    }

    /**
     * When customer returns from Pagantis (UrlOK)
     * The order information at this point is in POST
     * variables. Order processing is done on callbackAction
     */
    public function successAction()
    {
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }

    public function callbackAction()
    {
        $params = $this->getRequest()->getPost();
        $response = Mage::getModel('pagantis_pagantis/webservice_response', $params);
        $request = Mage::helper('pagantis_pagantis')->getRequest();

        if ($request->checkResponseSignature($response)) {
            // Process order
            $this->_processOrder($response->getOrder(), $response);
        } else {
            // Log invalid signature and redirect to home
            Mage::log('Pagantis: invalid signature on callback', Zend_Log::WARN);
            $this->_redirect('');
        }
    }

    /**
     * Process order
     *
     * @param $order
     */
    private function _processOrder($order)
    {
        try {
            $sendMail = (int) Mage::getStoreConfig('payment/pagantis/sendmail');
            $createInvoice = (int) Mage::getStoreConfig('payment/pagantis/invoice');
            if ($order->getId()) {
                //if ($response->isDsValid()) {
                    $orderStatus = Mage_Sales_Model_Order::STATE_PROCESSING;
                    if ($order->canInvoice() && $createInvoice) {
                        $invoice = $this->_createInvoice($order);
                        $comment = Mage::helper('pagantis_pagantis')->__('Transacción authorizada. Creada factura %s', $invoice->getIncrementId());
                    } else {
                        $comment = Mage::helper('pagantis_pagantis')->__('Transacción authorizada, factura no creada');
                    }
//                } else {
//                    $orderStatus = Mage_Sales_Model_Order::STATE_CANCELED;
//                    $comment = Mage::helper('yameveo_ceca')->__($response->getErrorMessage());
//                }
                $order->setState($orderStatus, $orderStatus, $comment, true);
                $order->save();
                if ($sendMail) {
                    if ($orderStatus == Mage_Sales_Model_Order::STATE_PROCESSING) {
                        $order->sendNewOrderEmail();
                    } else {
                        $order->sendOrderUpdateEmail(true);
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Create an invoice for the order and send an email
     *
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order_Invoice
     */
    private function _createInvoice($order)
    {
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        if (!$invoice->getTotalQty()) {
            Mage::throwException(Mage::helper('core')->__('No se puede crear una factura sin productos.'));
        }

        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

        $transactionSave->save();
        $invoice->sendEmail();
        return $invoice;
    }

    private function _restore_cart($order) {
        $cart = Mage::getSingleton('checkout/cart');
        $items = $order->getItemsCollection();
        if ($cart->getItemsCount()<=0){
          foreach ($items as $item) {
              try {
                  $cart->addOrderItem($item);
              } catch (Mage_Core_Exception $e) {
                  $session->addError($this->__($e->getMessage()));
                  Mage::logException($e);
                  continue;
              }
          }
          $cart->save();
        }
    }
}
