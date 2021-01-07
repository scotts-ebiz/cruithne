<?php

namespace SMG\GuestCart\Plugin;

use \Magento\Quote\Model\Quote\ItemFactory;
use \Magento\Quote\Api\Data\TotalsItemExtensionFactory;
use \Magento\Quote\Api\CartTotalRepositoryInterface;
use \Magento\Quote\Api\Data\TotalsInterface;

class CartTotalRepositoryPlugin
{

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var TotalsItemExtensionFactory
     */
    private $totalItemExtension;

    /**
     * CartTotalRepositoryPlugin constructor.
     * @param ItemFactory $itemFactory
     * @param TotalsItemExtensionFactory $totalItemExtensionFactory
     */
    public function __construct(
        ItemFactory $itemFactory,
        TotalsItemExtensionFactory $totalItemExtensionFactory
    ) {
        $this->itemFactory = $itemFactory;
        $this->totalItemExtension = $totalItemExtensionFactory;

    }

    /**
     * @param CartTotalRepositoryInterface $subject
     * @param TotalsInterface $totals
     * @return TotalsInterface
     */
    public function afterGet(
        CartTotalRepositoryInterface $subject,
        TotalsInterface $totals
    ) {
        foreach ($totals->getItems() as $item) {
            $quoteItem = $this->itemFactory->create()->load($item->getItemId());
            $extensionAttributes = $item->getExtensionAttributes();
            if ($extensionAttributes === null) {
                $extensionAttributes = $this->totalItemExtension->create();
            }
            $extensionAttributes->setSku($quoteItem->getSku());
            $item->setExtensionAttributes($extensionAttributes);
        }

        return $totals;
    }

}