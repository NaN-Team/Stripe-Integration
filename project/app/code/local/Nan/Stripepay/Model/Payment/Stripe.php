<?php
/**
 * Class Nan_Stripepay_Helper_Data
 */

require_once(Mage::getBaseDir('lib') . DS . 'stripe-php' . DS . 'init.php');


/**
 * Class Nan_Stripepay_Model_Payment_Stripe
 * Payment model for Stripe
 * @author  NaN Team
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
     * Nan_Stripepay_Model_Payment_Stripe constructor.
     */
    public function __construct()
    {
        \Stripe\Stripe::setApiKey(Mage::helper('nan_stripepay')->getConfigData('api_key'));
    }

    /**
     * capture
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|bool
     */
    public function capture(Varien_Object $payment, $amount)
    {
        // get basic data
        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();

        // process charge
        try {
            $charge = \Stripe\Charge::create(
                array(
                    'amount' => $amount * 100,
                    'currency' => strtolower($order->getBaseCurrencyCode()),
                    'card' => array(
                        'number' => $payment->getCcNumber(),
                        'exp_month' => sprintf('%02d', $payment->getCcExpMonth()),
                        'exp_year' => $payment->getCcExpYear(),
                        'cvc' => $payment->getCcCid(),
                        'name' => $billingAddress->getName(),
                        'address_line1' => $billingAddress->getStreet(1),
                        'address_line2' => $billingAddress->getStreet(2),
                        'address_zip' => $billingAddress->getPostcode(),
                        'address_state' => $billingAddress->getRegion(),
                        'address_country' => $billingAddress->getCountry(),
                    ),
                    'description' => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                )
            );
            $control = true;
        } catch (Exception $e) {
            Mage::log('Nan_Stripepay_Model_Payment_Stripe: there was an error when processing the payment against Stripe. Order Id: ' . $order->getId() . '. Amount: ' . $amount . '. Message: ' . $e);
            return false;
        }

        // set Transaction Id
        $payment->setTransactionId($charge->id)->setIsTransactionClosed(0);
        return $this;
    }

    /**
     * isAvailable
     *
     * Is this method available?
     * @param null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        // check order total
        if ($quote && $quote->getBaseGrandTotal() < Mage::helper('nan_stripepay')->getConfigData('min_order_amount'))
        {
            return false;
        }
        return parent::isAvailable($quote);
    }

    /**
     * canUseForCurrency
     *
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