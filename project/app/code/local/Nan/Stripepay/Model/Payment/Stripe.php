<?php
//Adrenaline Circuit
/**
 * Class Nan_Stripepay_Helper_Data
 */

require_once(Mage::getBaseDir('lib') . DS . 'stripe-php' . DS . 'init.php');


/**
 * Class Nan_Stripepay_Model_Payment_Stripe
 * Payment model for Stripe
 * @author  NaN Team (Adrenaline)
 * @version 0.1.0
 * @package StripePay
 */
class Nan_Stripepay_Model_Payment_Stripe extends Mage_Payment_Model_Method_Cc
{
    /**
     * Payment method unique code
     * @var string
     */
    protected $_code = 'nan_stripepay';

    /**
     * Is there a gateway to connect or is it an offline payment?
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * A capture should be done?
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Nan_Stripepay_Model_Payment_Stripe constructor.
     */
    public function __construct()
    {
        \Stripe\Stripe::setApiKey(Mage::helper('nan_stripepay')->getConfigData('api_key'));
    }

    /**
     * authorize
     * @param Varien_Object $payment
     * @param float         $amount
     * @return $this
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        /** @var bool | array $result */
        $result = $this->callStripe($payment, $amount, false);
        if ($result === false) {
            Mage::throwException($this->_getHelper()->__('Error Processing the request'));
        } else {
            //transaction correct
            $payment->setTransactionId($result['transaction_id']);
            $addInformation = array(
                'charge_id' => $result['charge_id'],
            );
            $payment->setAdditionalInformation($addInformation);
        }
        return $this;
    }

    /**
     * capture
     * @param Varien_Object $payment
     * @param float         $amount
     * @return $this|bool
     */
    public function capture(Varien_Object $payment, $amount)
    {
        try {
            if ($payment->getTransactionId() != null) {
                $ch = \Stripe\Charge::retrieve($payment->getAdditionalInformation('charge_id'));
                $ch->capture(); //captured transaction
            } else {
                //authorize and capture
                $result = $this->callStripe($payment, $amount, true);
                $payment->setTransactionId($result['transaction_id']);
            }
            $payment->setIsTransactionClosed(true);
            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            //$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE);   //FIXME: FIXIT! DO IT!
        } catch (Exception $e) {
            $order = $payment->getOrder();
            Mage::log(__CLASS__ . ': there was an error when processing the payment against Stripe. Order Id: ' . $order->getId() . '. Amount: ' . $amount . '. Message: ' . $e);
            return false;
        }
        return $this;
    }

    /**
     * callStripe
     * @param Varien_Object $payment
     * @param               $amount
     * @param               $auth_cap
     * @return array|bool|\Stripe\Charge
     */
    private function callStripe(Varien_Object $payment, $amount, $auth_cap)
    {
        //set API KEY
        \Stripe\Stripe::setApiKey($this->getConfigData('api_key'));

        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();
        // process charge without capture
        try {
            //create token for card and customer data
            $token = \Stripe\Token::create(
                array(
                    'card' => array(
                        'number'          => $payment->getCcNumber(),
                        'exp_month'       => sprintf('%02d', $payment->getCcExpMonth()),
                        'exp_year'        => $payment->getCcExpYear(),
                        'cvc'             => $payment->getCcCid(),
                        'name'            => $billingAddress->getName(),
                        'address_line1'   => $billingAddress->getStreet(1),
                        'address_line2'   => $billingAddress->getStreet(2),
                        'address_zip'     => $billingAddress->getPostcode(),
                        'address_state'   => $billingAddress->getRegion(),
                        'address_country' => $billingAddress->getCountry(),
                    ),
                )
            );
            //charge
            $charge = \Stripe\Charge::create(
                array(
                    'amount'      => $amount * 100,
                    'currency'    => strtolower($order->getBaseCurrencyCode()),
                    'capture'     => $auth_cap,
                    'source'      => $token->id,
                    'description' => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                )
            );
        } catch (Exception $e) {
            Mage::log(__CLASS__ . ': there was an error when processing the payment against Stripe. Order Id: ' . $order->getId() . '. Amount: ' . $amount . '. Message: ' . $e);
            return false;
        }
        return $auth_cap ? $charge : array('transaction_id' => time(), 'charge_id' => $charge->id);
    }

    /**
     * isAvailable
     * Is this method available?
     * @param null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        // check order total
        if ($quote && $quote->getBaseGrandTotal() < Mage::helper('nan_stripepay')->getConfigData('min_order_amount')) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    /**
     * canUseForCurrency
     * Is available for this currency?
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!Mage::helper('nan_stripepay')->isSupportedCurrency($currencyCode)) {
            return false;
        }
        return parent::canUseForCurrency($currencyCode);
    }
}