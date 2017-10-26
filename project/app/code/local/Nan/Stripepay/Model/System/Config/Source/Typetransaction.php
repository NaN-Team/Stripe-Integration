<?php
/**
 * Nan_Stripepay
 */

/**
 * Class Nan_Stripepay_Model_System_Config_Source_Typetransaction
 * Source for Auth or Auth and Capture payment.
 * @author  NaN Team
 * @version 0.1.0
 * @package StripePay
 */



/**
 * Class Nan_Stripepay_Model_System_Config_Source_Typetransaction
 */
class Nan_Stripepay_Model_System_Config_Source_Typetransaction
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'authorizeonly', 'label'=>Mage::helper('nan_stripepay')->__('Authorize only')),
            array('value' => 'authorizecapture', 'label'=>Mage::helper('nan_stripepay')->__('Authorize and Capture')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'authorizeonly' => Mage::helper('nan_stripepay')->__('Authorize only'),
            'authorizecapture' => Mage::helper('nan_stripepay')->__('Authorize and Capture'),
        );
    }
}