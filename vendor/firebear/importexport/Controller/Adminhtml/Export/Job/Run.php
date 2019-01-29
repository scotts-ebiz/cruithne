<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\JobFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;

class Run extends JobController
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $url;
    /** @var ExportJobRepositoryInterface */
    protected $exportJobRepository;
    /** @var \Magento\Framework\Json\EncoderInterface */
    protected $jsonEncoder;

    /**
     * Run constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonoEncoder
     * @param ExportJobRepositoryInterface $exportJobRepository
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        ExportJobRepositoryInterface $exportJobRepository,
        Data $helper
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->exportJobRepository = $exportJobRepository;
        $this->url = $context->getBackendUrl();
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $result[0] = true;
        $exportFile = '';
        $lastEntityId = '';
        if ($this->getRequest()->isAjax()
            && $this->getRequest()->getParam('file')
            && $this->getRequest()->getParam('id')
        ) {
            try {
                session_write_close();
                ignore_user_abort(true);
                set_time_limit(0);
                ob_implicit_flush();
                $id = $this->getRequest()->getParam('id');
                $file = $this->getRequest()->getParam('file');
                $lastEntityId = $this->getRequest()->getParam('last_entity_value');

                if ($lastEntityId) {
                    $this->updateLastEntityId($id, $lastEntityId);
                }
                $exportFile = $this->helper->runExport($id, $file);
                $result = $this->helper->getResultProcessor();

                if (isset($result[1])
                    && $result[1] > $lastEntityId
                ) {
                    $lastEntityId = $result[1];
                }
            } catch (\Exception $e) {
                $result[0] = false;
            }

            return $resultJson->setData([
                'result' => $result[0],
                'file' => $this->url->getUrl(
                    'import/export_job/download',
                    ['file' => str_replace("/", "|", $exportFile)]
                ),
                'last_entity_id' => $lastEntityId,
            ]);
        }
    }

    /**
     * @param $jobId
     * @param $lastEntityId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateLastEntityId($jobId, $lastEntityId)
    {
        $exportJob = $this->exportJobRepository->getById($jobId);
        $sourceData = $this->jsonDecoder->decode($exportJob->getExportSource());
        $sourceData = array_merge(
            $sourceData,
            [
                'last_entity_id' => $lastEntityId,
            ]
        );
        $sourceData = $this->jsonEncoder->encode($sourceData);
        $exportJob->setExportSource($sourceData);
        $this->exportJobRepository->save($exportJob);
    }
}
