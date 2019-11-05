<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace SMG\Checkout\Block\Checkout;

use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Cart source
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AttributeMerger
{
    protected function getMultilineFieldConfig($attributeCode, array $attributeConfig, $providerName, $dataScopePrefix)
    {
        $lines = [];
        unset($attributeConfig['validation']['required-entry']);
        for ($lineIndex = 0; $lineIndex < (int)$attributeConfig['size']; $lineIndex++) {
          $isFirstLine = $lineIndex === 0;
          $line = [
            'label' => __("%1: Lineasdf %2", $attributeConfig['label'], $lineIndex + 1),
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
              // customScope is used to group elements within a single form e.g. they can be validated separately
              'customScope' => $dataScopePrefix,
              'template' => 'ui/form/field',
              'elementTmpl' => 'ui/form/element/input'
            ],
            'dataScope' => $lineIndex,
            'provider' => $providerName,
            'validation' => $isFirstLine
              ? array_merge(
                ['required-entry' => (bool)$attributeConfig['required']],
                $attributeConfig['validation']
              )
              : $attributeConfig['validation'],
            'additionalClasses' => $isFirstLine ? 'field' : 'additional'

          ];
          if ($isFirstLine && isset($attributeConfig['default']) && $attributeConfig['default'] != null) {
            $line['value'] = $attributeConfig['default'];
          }
          $lines[] = $line;
        }
        return [
          'component' => 'Magento_Ui/js/form/components/group',
          'label' => $attributeConfig['label'],
          'required' => (bool)$attributeConfig['required'],
          'dataScope' => $dataScopePrefix . '.' . $attributeCode,
          'provider' => $providerName,
          'sortOrder' => $attributeConfig['sortOrder'],
          'type' => 'group',
          'config' => [
            'template' => 'ui/group/group',
            'additionalClasses' => $attributeCode
          ],
          'children' => $lines,
        ];
    }

}