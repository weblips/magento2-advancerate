<?php
namespace Ced\Advancerate\Model;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Magento\Framework\Module\Dir\Reader;
use \Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Rateimportfile extends Command
{
    private $fileCsv;
    private $moduleReader;
    protected $commands;
    protected $customerRepositoryInterface;
    protected $objectManager;
    protected $storeManager;
    protected $scopeConfig;
    protected $message = '';
    protected $_importIso2Countries;
    protected $_importIso3Countries;
    protected $_countryCollectionFactory;
	protected $_regionCollectionFactory;
	protected $_importRegions;
	protected $_importedRows = 0;

      public function __construct(
     \Magento\Framework\Module\Dir\Reader $moduleReader,
     \Magento\Framework\File\Csv $fileCsv,
     DirectoryList $directory_list,
     \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
     \Magento\Framework\ObjectManagerInterface $objectmanager,
     \Magento\Store\Model\StoreManagerInterface $storeManager,
     \Magento\Framework\Filesystem $fileSystem,
     \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
     \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
     \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
     array $commands = []
    ) {
      $this->moduleReader = $moduleReader;
      $this->fileCsv = $fileCsv;
      $this->fileSystem = $fileSystem;
      $this->commands = $commands;
      $this->objectManager = $objectmanager;
      $this->customerRepositoryInterface = $customerRepositoryInterface;
      $this->storeManager = $storeManager;
      $this->_countryCollectionFactory = $countryCollectionFactory;
      $this->_regionCollectionFactory = $regionCollectionFactory;
      $this->scopeConfig = $scopeConfig;
      parent::__construct();
    }

    protected function configure()
    {
        $this->setName('ced:importrate')
             ->setDescription('Import Rate from csv');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/rateimport.log');
      $logger = new \Zend\Log\Logger();
      $logger->addWriter($writer);
      $logger->info('--Import Order Start--');
      $output->writeln('<info>Lets Start Import!</info>');
      $filePath = $this->fileSystem->getDirectoryRead(DirectoryList::PUB)->getAbsolutePath('rateimport/');
      $files = glob($filePath."*csv*");
      $this->_importedRows = 0;
     
      if(count($files)>0){
        foreach($files as $file){
	          $info = pathinfo($file);
	          $importHeader = $info['basename'];

	          $orderItem = str_replace('Header','Item',$importHeader);
	          //check if any item.csv match with header.csv
	          $data = $this->fileCsv->getData($filePath.$importHeader);

	          try {
	            $rowNumber = 1;
	            $importData = [];

	            $this->_loadDirectoryCountries();
	            $this->_loadDirectoryRegions();

	            for($rowNumber=1; $rowNumber < count($data); $rowNumber++) {

	            	$row = $this->_getImportRow($data[$rowNumber], $rowNumber);

	            	if ($row !== false) {
	                    $importData[] = $row;
	                }
	            }

	            $this->_saveImportData($importData);
	            $output->writeln('<info>Done!</info>');

	        } catch (\Magento\Framework\Exception\LocalizedException $e) {
	            
	            $error = \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
	            $output->writeln($error);
	        } 

	     }
       }
    }


    /**
     * Load directory countries
     */
    protected function _loadDirectoryCountries()
    {
        if ($this->_importIso2Countries !== null && $this->_importIso3Countries !== null) {
            return $this;
        }

        $this->_importIso2Countries = [];
        $this->_importIso3Countries = [];

        /** @var $collection \Magento\Directory\Model\Resource\Country\Collection */
        $collection = $this->_countryCollectionFactory->create();

        foreach ($collection->getData() as $row) {
            $this->_importIso2Countries[$row['iso2_code']] = $row['country_id'];
            $this->_importIso3Countries[$row['iso3_code']] = $row['country_id'];
        }

        return $this;
    }

    /**
     * Load directory regions
     */
    protected function _loadDirectoryRegions()
    {
        if ($this->_importRegions !== null) {
            return $this;
        }
        $this->_importRegions = [];

        /** @var $collection \Magento\Directory\Model\Resource\Region\Collection */
        $collection = $this->_regionCollectionFactory->create();
        foreach ($collection->getData() as $row) {
            $this->_importRegions[$row['country_id']][$row['code']] = (int)$row['region_id'];
        }
        return $this;
    }
    
    
     /**
     * Validate row for import and return table rate array or false
     * Error will be add to _importErrors array
     *
     * @param array $row
     * @param int $rowNumber
     * @return array|false
     */
    protected function _getImportRow($row, $rowNumber = 0)
    {
        // validate row
        if (count($row) < 12) {
            $this->_importErrors[] = __('Please correct Table Rates format in the Row #%1.', $rowNumber);
            return false;
        }

        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = $v;
        }

        $WebsiteId = 1;

        // validate country
        if (isset($this->_importIso2Countries[$row[0]])) {
            $countryId = $this->_importIso2Countries[$row[0]];
        } elseif (isset($this->_importIso3Countries[$row[0]])) {
            $countryId = $this->_importIso3Countries[$row[0]];
        } elseif ($row[0] == '*' || $row[0] == '') {
            $countryId = '0';
        } else {
            $this->_importErrors[] = __('Please correct Country "%1" in the Row #%2.', $row[0], $rowNumber);
            return false;
        }
        // validate region
        if ($countryId != '0' && isset($this->_importRegions[$countryId][$row[1]])) {
            $regionId = $this->_importRegions[$countryId][$row[1]];
        } elseif ($row[1] == '*' || $row[1] == '') {
            $regionId = 0;
        } else {
            $this->_importErrors[] = __('Please correct Region/State "%1" in the Row #%2.', $row[1], $rowNumber);
            return false;
        }
        // detect city
        if ($row[2] == '*' || $row[2] == '') {
            $city = '*';
        } else {
            $city = $row[2];
        }
        
        // detect zip code
        if ($row[3] == '*' || $row[3] == '') {
            $zipCode = '*';
        } else {
            $zipCode = $row[3];
        }
        
        // detect weight From
        
        if ($row[4] == '*' || $row[4] == '') {
            $weight_from = '0.0000';
        } else {
            $weight_from = $this->_parseDecimalValue($row[4]);
            if ($weight_from === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Weight From', $row[4], $rowNumber
                );
                return false;
            }
        }
        
        // detect weight to
        if ($row[5] == '*' || $row[5] == '') {
            $weight_to = '0.0000';
        } else {
            $weight_to = $this->_parseDecimalValue($row[5]);
            if ($weight_to === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Weight To', $row[5], $rowNumber
                );
                return false;
            }
        }
        
        // detect price from
        if ($row[6] == '*' || $row[6] == '') {
            $price_from = '0.0000';
        } else {
            $price_from = $this->_parseDecimalValue($row[6]);
            if ($price_from === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Price From', $row[6], $rowNumber
                );
                return false;
            }
        }
        
        // detect price to
        if ($row[7] == '*' || $row[7] == '') {
            $price_to = '0.0000';
        } else {
            $price_to = $this->_parseDecimalValue($row[7]);
            if ($price_to === false) {
                $this->_importErrors[] = __('Please correct %1 "%2" in the Row #%3.',
                    'Price To', $row[7], $rowNumber
                );
                return false;
            }
        }
        
        // detect Qty from
        if ($row[8] == '*' || $row[8] == '') {
            $qty_from = '0';
        } else {
            $qty_from = $row[8];
        }
        
        // detect Qty to
        if ($row[9] == '*' || $row[9] == '') {
            $qty_to = '0';
        } else {
            $qty_to = $row[9];
        }
        
        // validate Shipping price
        $shipping_price = $this->_parseDecimalValue($row[10]);
        if ($shipping_price === false) {
            $this->_importErrors[] = __('Please correct Shipping Price "%1" in the Row #%2.', $row[10], $rowNumber);
            return false;
        }
        
        $shipping_method = preg_replace(array("/[^a-z0-9_]/","/\_+/"), '_', strtolower($row[11]));
        if ($shipping_method == '' || $shipping_method == '_') {
            $this->_importErrors[] = ___('Invalid Shipping Method Name "%s" in the Row #%s.', $row[11], $rowNumber);
            return false;
        }
        $shipping_label = $row[11];
        $etd = $row[12];
        $vendorId = $this->getVendorId();
        return [
            $WebsiteId,$vendorId, $countryId, $regionId, $city, $zipCode,                   
            $weight_from, $weight_to, $price_from, $price_to, $qty_from,
            $qty_to, $shipping_price, $shipping_method,$shipping_label, $etd          
        ];
    }
    
    /**
     * Save import data batch
     * @param array $data
     * @return \Ced\Advancerate\Model\Resource\Carrier\Advancerate
     */
    protected function _saveImportData(array $data)
    {
        if (!empty($data)) {
            $columns = [
                'website_id','vendor_id','dest_country_id','dest_region_id','city','dest_zip',
                'weight_from','weight_to','price_from','price_to','qty_from',
                'qty_to','price','shipping_method','shipping_label', 'etd'
            ];
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        	$resource = $objectManager->create('Magento\Framework\App\ResourceConnection');
        	$connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        	$themeTable = $resource->getTableName('advance_rate');
            $connection->insertArray($themeTable, $columns, $data);
            $this->_importedRows += count($data);
        }
          
        return $this;
    }
    
    protected function _parseDecimalValue($value)
    {
        if (!is_numeric(floatval($value))) {
            return false;
        }
        $value = (double)sprintf('%.4F', $value);
        if ($value < 0.0000) {
            return false;
        }
        return $value;
    }
    
    public function getVendorId()
    {
        return 'admin';
    }
}
?>