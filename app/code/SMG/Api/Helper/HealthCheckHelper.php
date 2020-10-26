<?php
namespace SMG\Api\Helper;

use Psr\Log\LoggerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\DeploymentConfig;

class HealthCheckHelper
{

    /**
     * @var LoggerInterface
     */
    protected $_logger;
    
    /**
     * @var ObjectManagerInterface
     */
    private $_objectManager;
    
    /**
     * @var DeploymentConfig
     */
    protected $_deploymentconfig;

    
    public function __construct(LoggerInterface $logger,
    ObjectManagerInterface $objectmanager,
    DeploymentConfig $deploymentconfig
    )
    {
        $this->_logger = $logger;
        $this->_objectManager = $objectmanager;
        $this->_deploymentconfig = $deploymentconfig;
    }
    
    /**
     * 
     * @return boolean
     */
    public function getHealthCheck()
    {
        // check mysql connectivity
        foreach ($this->_deploymentconfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS) as $connectionData) {
            try {
                
                /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $dbAdapter */
                $dbAdapter = $this->_objectManager->create(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                ['config' => $connectionData]
                );
                
                $dbAdapter->getConnection();
                
                }  catch (\Magento\Framework\Exception\LocalizedException $e) {
                    
                    return false;
                    
                } catch (Zend_Db_Adapter_Exception $e) {
                    
                    return false;
                }
        }
        
        // check cache storage availability
        $cacheConfigs = $this->_deploymentconfig->get(ConfigOptionsListConstants::KEY_CACHE_FRONTEND);
        if ($cacheConfigs) {
            
            foreach ($cacheConfigs as $cacheConfig) {
                // allow config if only available "id_prefix"
                if (count($cacheConfig) === 1 && isset($cacheConfig['id_prefix'])) {
                    continue;
                    
                } elseif (!isset($cacheConfig[ConfigOptionsListConstants::CONFIG_PATH_BACKEND]) ||
                    !isset($cacheConfig[ConfigOptionsListConstants::CONFIG_PATH_BACKEND_OPTIONS])) {
                        
                     return false;
                     
                }
                
                $cacheBackendClass = $cacheConfig[ConfigOptionsListConstants::CONFIG_PATH_BACKEND];
                
                try {
                    
                    /** @var \Zend_Cache_Backend_Interface $backend */
                    $backend = new $cacheBackendClass($cacheConfig[ConfigOptionsListConstants::CONFIG_PATH_BACKEND_OPTIONS]);
                    $backend->test('test_cache_id');
                    
                } catch (\Exception $e) {
                    
                     return false;
                }
            }
        }
        
        return true;
    }
 
}

