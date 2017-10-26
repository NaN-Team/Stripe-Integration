<?php

/**
 * Class Nan_Stripepay_Helper_Data
 */

/**
 * Class Nan_Stripepay_Helper_Data
 * Main Helper
 * @author  NaN Team
 * @version 0.1.0
 * @package StripePay
 */

class Nan_Stripepay_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * getConfigData
     *
     * Gets the name of a param, returns it if set
     * @param $data
     * @return mixed
     */
    public function getConfigData($data)
    {
        return Mage::getStoreConfig('payment/nan_stipepay/'. $data);
    }

    /**
     * isSupportedCurrency
     *
     * Gets the code of currency, checks if it is avaible for the
     * module
     *
     * Returns true if it is avaible, false if not
     * @param $currencyCode
     * @return bool
     */
    public function isSupportedCurrency($currencyCode)
    {
        $supportedCurrencies = explode(',', $this->getConfigData('supported_currencies'));
        if (in_array($currencyCode, $supportedCurrencies)) {
            return true;
        }
        return false;
    }
}

