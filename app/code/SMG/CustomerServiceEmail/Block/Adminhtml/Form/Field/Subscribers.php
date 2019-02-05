<?php
/**
 * @copyright Copyright (c) 2019 SMG, LLC
 */

namespace SMG\CustomerServiceEmail\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class Subscribers
 * @package SMG\CustomerServiceEmail\Block\Adminhtml\Form\Field
 */
class Subscribers extends AbstractFieldArray
{
    /**
     * @var string Field ID for subscriber emails
     */
    const SUBSCRIBER_EMAIL = 'subscriber_email';

    /**
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(self::SUBSCRIBER_EMAIL, ['label' => __('Subscriber Email')]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
