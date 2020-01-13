<?php
 
namespace SMG\SPV2ContactWidget\Controller\Index;
 
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Contact\Model\ConfigInterface;
use Magento\Contact\Model\MailInterface;
use Psr\Log\LoggerInterface;
use \Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\DataObject;

class Post extends \Magento\Contact\Controller\Index\Post {

     /**
     * @var DataPersistorInterface
     */
    private $_dataPersistor;

    /**
     * @var Context
     */
    private $_context;

    /**
     * @var MailInterface
     */
    private $_mail;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    protected $_inlineTranslation;

    public function __construct(
        Context $context,
        ConfigInterface $contactsConfig,
        MailInterface $mail,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context, $contactsConfig, $mail, $dataPersistor);
        $this->_context = $context;
        $this->_mail = $mail;
        $this->_dataPersistor = $dataPersistor;
        $this->_logger = $logger;
        $this->_resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Post user data from the contact form
     * 
     * @return json $result
     */
    public function execute()
    {

        $result = $this->_resultJsonFactory->create();

        if( ! $this->getRequest()->isPost() ) {
            $result->setData( [ 'success' => false, 'message' => 'Not a POST request' ] );
            return $result;
        }

        try {
            $this->sendEmail($this->validatedParams());
            $result->setData( [ 'success' => true, 'message' => __('We received your email. Now we\'ll go to work as quickly as possible to respond. Look for a reply from us within a day or two.') ]);
        } catch(\Exception $e) {
            $this->_logger->critical($e);

            $result->setData( [ 'success' => false, 'message' => __('An error occurred while processing your form. Please try again later.') ]);
        }

        return $result;
    }

    /**
     * @param array $post Post data from contact form
     * @return void
     */
    private function sendEmail($post)
    {
        $this->_mail->send(
            $post['email'],
            ['data' => new DataObject($post)]
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function validatedParams()
    {
        $request = $this->getRequest();
        if (trim($request->getParam('name')) === '') {
            throw new LocalizedException(__('Enter the Name and try again.'));
        }
        if (trim($request->getParam('comment')) === '') {
            throw new LocalizedException(__('Enter the comment and try again.'));
        }
        if (false === \strpos($request->getParam('email'), '@')) {
            throw new LocalizedException(__('The email address is invalid. Verify the email address and try again.'));
        }
        if (trim($request->getParam('hideit')) !== '') {
            throw new \Exception();
        }

        return $request->getParams();
    }

}  