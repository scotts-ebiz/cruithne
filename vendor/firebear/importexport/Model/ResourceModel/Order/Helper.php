<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\ImportExport\Model\ResourceModel\Order;

/**
 * ImportExport MySQL resource helper model.
 * Extend default for split db functionality.
 *
 * @api
 * @since 100.0.2
 */
class Helper extends \Magento\ImportExport\Model\ResourceModel\Helper
{
    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param string $modulePrefix
     */
    public function __construct(\Magento\Framework\App\ResourceConnection $resource)
    {
        parent::__construct($resource, 'sales');
    }
}
