<?php
/**
 * Copyright © 2018 Vantiv, LLC. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Vantiv\Payment\Model\ResourceModel\Certification\Test\Result;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Exception\LocalizedException;

class Collection extends AbstractCollection
{
    /**
     * File name prefix
     *
     * @var string
     */
    const EXPORT_FILE_NAME = 'Certification_Test_Run';

    /**
     * File content type
     *
     * @var string
     */
    const CONTENT_TYPE = 'application/octet-stream';

    /**
     * Array of additional test result fields
     *
     * @var array
     */
    private $additionalFields = [
        'success_flag' => 'success_flag',
    ];

    /**
     * Define model and resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vantiv\Payment\Model\Certification\Test\Result',
            'Vantiv\Payment\Model\ResourceModel\Certification\Test\Result'
        );
    }

    /**
     * Get array of options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->_toOptionArray('test_id', 'name', $this->additionalFields);

        $res = [];
        foreach ($options as $option) {
            $option['label'] .= ', ' . (($option['success_flag'] == 1) ? __('Passed') : __('Failed'));
            $res[] = $option;
        }

        return $res;
    }

    /**
     * Return file name for downloading.
     *
     * @return string
     */
    public function getFileName()
    {
        return self::EXPORT_FILE_NAME . '_' . date('Ymd_His') . '.txt';
    }

    /**
     * MIME-type for 'Content-Type' header
     *
     * @return string
     */
    public function getContentType()
    {
        return self::CONTENT_TYPE;
    }

    /**
     * Export data.
     *
     * @return string
     * @throws LocalizedException
     */
    public function export()
    {
        $result = '';

        $this->load();
        foreach ($this->getItems() as $item) {
            $data = $item->toArray();

            $orderId = array_key_exists('order_id', $data)
                ? $data['order_id']
                : null;

            $litleTxnId = array_key_exists('litle_txn_id', $data)
                ? $data['litle_txn_id']
                : null;

            $line = sprintf("Test %s: %s\r\n", $orderId, $litleTxnId);
            $result .= $line;
        }

        if (empty($result)) {
            throw new LocalizedException(__('There is no data for the export.'));
        }

        return $result;
    }
}
