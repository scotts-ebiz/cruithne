<?php
/**
 * User: cnixon
 * Date: 6/17/21
 * Time: 9:20 AM
 */

namespace SMG\Api\Helper;

use Psr\Log\LoggerInterface;
use SMG\Mirasvit\Cron\ScoreUpdateCron;
use Exception;

class FraudHelper
{

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ScoreUpdateCron
     */
    private $_fraudCron;


    /**
     * FraudHelper constructor.
     * @param LoggerInterface $logger
     * @param ScoreUpdateCron $fraudCron
     */
    public function __construct(LoggerInterface $logger,
                                ScoreUpdateCron $fraudCron
    )
    {
        $this->_logger = $logger;
        $this->_fraudCron = $fraudCron;
    }

    /**
     * Run fraud cron check manually.
     *
     * @return boolean
     */
    public function fraudCheck()
    {
        try {
            $this->_fraudCron->execute();
            return true;
        } catch (Exception $e) {
            $this->_logger->error("There was an error while running the fraud check cron: " . $e->getMessage());
            return false;
        }
    }

}


