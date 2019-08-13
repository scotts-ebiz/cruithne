<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace SMG\Checkout\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * Cart source
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Cart extends \Magento\Checkout\CustomerData\Cart
	{
		
		public function getSectionData()
		{
			$totals = $this->getQuote()->getTotals();
			$subtotalAmount = $totals['subtotal']->getValue();
			return [
				'summary_count' => $this->getSummaryCount(),
				'subtotalAmount' => $subtotalAmount,
				'subtotal' => isset($totals['subtotal'])
					? $this->checkoutHelper->formatPrice($subtotalAmount)
					: 0,
				'grand_total' => isset($totals['grand_total']) ? $this->checkoutHelper->formatPrice($totals['grand_total']->getValue()) : 0,
				'possible_onepage_checkout' => $this->isPossibleOnepageCheckout(),
				'items' => $this->getRecentItems(),
				'extra_actions' => $this->layout->createBlock(\Magento\Catalog\Block\ShortcutButtons::class)->toHtml(),
				'isGuestCheckoutAllowed' => $this->isGuestCheckoutAllowed(),
				'website_id' => $this->getQuote()->getStore()->getWebsiteId(),
				'storeId' => $this->getQuote()->getStore()->getStoreId()
			];
		}
	}
