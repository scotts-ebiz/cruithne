<?php
 namespace SMG\Customfields\Model\Config\Source;
 
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\DB\Ddl\Table;
 
/**
 * Custom Attribute Renderer
 *
 * @author      Webkul Core Team <support@webkul.com>
 */
class RegionOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var OptionFactory
     */
    protected $optionFactory;
 
    /**
     * @param OptionFactory $optionFactory
     */
    /*public function __construct(OptionFactory $optionFactory)
    {
        $this->optionFactory = $optionFactory;  
        //you can use this if you want to prepare options dynamically  
    }*/
 
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $regionFactory = $objectManager->create('Magento\Directory\Model\ResourceModel\Region\CollectionFactory')->create();
        $regionFactory->addFieldToFilter('country_id', array('eq' => 'US'));
        /* your Attribute options list*/
         $optionsc=$regionFactory->toOptionArray();
        $this->_options= $optionsc;
        return $this->_options;
    }
    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment' => 'Custom Attribute Options  ' . $attributeCode . ' column',
            ],
        ];
    }
}

