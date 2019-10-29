<?php
 
namespace SMG\SPV2ContactWidget\Controller\Contact\Index;
 
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Contact\Model\ConfigInterface;
use Magento\Contact\Model\MailInterface;
use Psr\Log\LoggerInterface;
use \Magento\Framework\Translate\Inline\StateInterface;

class Post extends \Magento\Contact\Controller\Index\Post {

     /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var MailInterface
     */
    private $mail;

    protected $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected $inlineTranslation;
    public function __construct(
        Context $context,
        ConfigInterface $contactsConfig,
        MailInterface $mail,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger = null,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context, $contactsConfig,$mail,$dataPersistor);
        $this->inlineTranslation = $inlineTranslation;
        $this->context = $context;
        $this->mail = $mail;
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->resultJsonFactory = $resultJsonFactory;
    }

     
    public function execute()
    {

        $post = $this->getRequest()->getPostValue();
        $result = $this->resultJsonFactory->create();

        $result->setData(['status' => 200]);
        return $result; 

        
    }

     private function getDataPersistor()
    {
        if ($this->dataPersistor === null) {
            $this->dataPersistor = ObjectManager::getInstance()
                ->get(DataPersistorInterface::class);
        }

        return $this->dataPersistor;
    }

}  