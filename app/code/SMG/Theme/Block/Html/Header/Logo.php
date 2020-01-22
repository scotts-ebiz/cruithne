<?php

namespace SMG\Theme\Block\Html\Header;

/**
 * Logo page header block
 *
 * @api
 * @since 100.0.2
 */
class Logo extends \Magento\Theme\Block\Html\Header\Logo
{

    /** @var  */
    protected $_session;

    /**
     * Logo constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper
     * @param \Magento\Customer\Model\Session $session
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper,
        \Magento\Customer\Model\Session $session,
        array $data = []
    ) {
        $this->_session = $session;
        parent::__construct($context, $fileStorageHelper, $data);
    }

    /**
     * Is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        $visitorData = $this->_session->getData("visitor_data");
        return isset($visitorData["do_customer_login"]) && $visitorData["do_customer_login"];
    }
}