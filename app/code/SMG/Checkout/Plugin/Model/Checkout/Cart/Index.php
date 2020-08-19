<?php
/**
 * User: cnixon
 * Date: 8/11/20
 * Time: 11:36 AM
 */

namespace SMG\Checkout\Plugin\Model\Checkout\Cart;

use Magento\Framework\Exception\LocalizedException;

class Index {

    protected $_mulchSkus = array(
        "8865918010",
        "8855918010",
        "8845918010",
        "8865918020",
        "8845918020",
        "8855918020",
        "8865918030",
        "8845918030",
        "8855918030",
        "8865918040",
        "8845918040",
        "8855918040",
        "8865918050",
        "8845918050",
        "8855918050"
    );

    public function beforeAddProduct($subject, $productInfo, $requestInfo = null) {
        if (!empty($subject) && !empty($subject->getQuote()) && !empty($subject->getQuote()->getItems())) {
            $skuInCart = $subject->getQuote()->getItems()[0]->getData('sku');
            $skuIncoming = $productInfo->getData('sku');
            if(in_array($skuInCart, $this->_mulchSkus) && $skuInCart != $skuIncoming) {
                throw new LocalizedException(__('You can only have one mulch product per cart.'));
            }
        }

        return [$productInfo, $requestInfo];
    }
}
