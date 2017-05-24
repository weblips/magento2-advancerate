<?php
/**
 * CedCommerce
  *
  * NOTICE OF LICENSE
  *
  * This source file is subject to the End User License Agreement (EULA)
  * that is bundled with this package in the file LICENSE.txt.
  * It is also available through the world-wide-web at this URL:
  * http://cedcommerce.com/license-agreement.txt
  *
  * @category    Ced
  * @package     Ced_Advancerate
  * @author       CedCommerce Core Team <connect@cedcommerce.com >
  * @copyright   Copyright CEDCOMMERCE (http://cedcommerce.com/)
  * @license      http://cedcommerce.com/license-agreement.txt
  */
namespace Ced\Advancerate\Model\ResourceModel\Carrier;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;


class Advancerate extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    protected $_importWebsiteId = 0;

    protected $_importErrors = [];

    protected $_importedRows = 0;

 //   protected $_importUniqueHash = [];

    protected $_importIso2Countries;

    protected $_importIso3Countries;

    protected $_importRegions;

    protected $_importConditionName;

    protected $_conditionFullNames = [];

    protected $_coreConfig;

    protected $_logger;

    protected $_storeManager;

    protected $_carrierTablerate;

    protected $_countryCollectionFactory;

    protected $_regionCollectionFactory;

    protected $_filesystem;

    protected $_citySession;

    public function __construct(
            
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ced\Advancerate\Model\Carrier\Advancerate $carrierTablerate,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Framework\Filesystem $filesystem,
         \Magento\Catalog\Model\Session $citySession,
        $connectionName = null
    ) {
       
        $this->_coreConfig = $coreConfig;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_carrierTablerate = $carrierTablerate;
        $this->_countryCollectionFactory = $countryCollectionFactory;
        $this->_regionCollectionFactory = $regionCollectionFactory;
        $this->_filesystem = $filesystem;
        $this->_citySession = $citySession;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table and id field name
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('advance_rate', 'id');
    }
    
    
    public function getRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        
        $connection = $this->getConnection();
        $condition = $this->_coreConfig->getValue('carriers/advancerate/ratecondition', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $postcode = $request->getDestPostcode();
        $city     = $request->getDestCity();

        if (strlen($postcode)>6) {
            $data = explode('/', $postcode);
            $ndata = count($data);
            if($ndata==3){
                $postcode = $data[0];
                $city = $data[1].'/'.$data[2];
            }elseif($ndata==4){
                $postcode = $data[0];
                $city = $data[1].'/'.$data[2].'/'.$data[3];
            }   
        }else{
            $postcode = $request->getDestPostcode();
            $city     = $request->getDestCity();
            if(!$city){
                $city = $this->_citySession->getMyValue();
            }
        }

     
        $bind = [
                    ':website_id' => (int) $request->getWebsiteId(),
                    //':vendor_id' => $this->getVendorId(),
                   ':dest_country_id' => $request->getDestCountryId(),
                   ':dest_region_id' => (int) $request->getDestRegionId(),
                   ':city' => $city,
                   ':dest_zip' => $postcode
        ];
        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'website_id = :website_id'
        )/*->where(
            'vendor_id= :vendor_id'
       )*/;
      
    
        switch ($condition) {
            case 0: $bind[':weight'] = $request->getPackageWeight();
                
            $select->where('weight_from <= :weight')
            ->where('weight_to >= :weight')
            ->order(array('dest_country_id DESC', 'dest_region_id DESC','city DESC', 'dest_zip DESC'));
    
            break;
                
            case 1: $bind[':order_total'] = $request->getPackageValue();
    
            $select->where('price_from <= :order_total')
            ->where('price_to >= :order_total')
            ->order(array('dest_country_id DESC', 'dest_region_id DESC','city DESC', 'dest_zip DESC'))->limit(1);
    
            break;
    
            case 2: $bind[':qty'] = $request->getPackageQty();
    
            $select->where('qty_from <= :qty')
            ->where('qty_to >= :qty')
            ->order(array('dest_country_id DESC', 'dest_region_id DESC','city DESC', 'dest_zip DESC'));
            break;
        }
    
        $orWhere = '(' . implode(') OR (', array(
                "dest_country_id = :dest_country_id AND dest_region_id = :dest_region_id AND city = :city AND dest_zip = :dest_zip",
                "dest_country_id = :dest_country_id AND dest_region_id = :dest_region_id AND city = '*'   AND dest_zip = :dest_zip",
                "dest_country_id = :dest_country_id AND dest_region_id = 0       AND city = :city AND dest_zip = :dest_zip",
                "dest_country_id = :dest_country_id AND dest_region_id = 0       AND city = '*'   AND dest_zip = :dest_zip",
                    
                "dest_country_id = '0' AND dest_region_id = :dest_region_id AND city = :city AND dest_zip = :dest_zip",
                "dest_country_id = '0' AND dest_region_id = :dest_region_id AND city = '*'   AND dest_zip = :dest_zip",
                "dest_country_id = '0' AND dest_region_id = 0 AND city = :city AND dest_zip = :dest_zip",
                "dest_country_id = '0' AND dest_region_id = 0 AND city = '*' AND dest_zip = :dest_zip",
                    
                "dest_country_id = :dest_country_id AND dest_region_id = :dest_region_id AND city = :city AND dest_zip = '*'",
                "dest_country_id = :dest_country_id AND dest_region_id = :dest_region_id AND city = '*'   AND dest_zip = '*'",
                "dest_country_id = :dest_country_id AND dest_region_id = 0       AND city = :city AND dest_zip = '*'",
                "dest_country_id = :dest_country_id AND dest_region_id = 0       AND city = '*'   AND dest_zip = '*'",
                    
                "dest_country_id = '0' AND dest_region_id = :dest_region_id AND city = :city AND dest_zip = '*'",
                "dest_country_id = '0' AND dest_region_id = :dest_region_id AND city = '*'   AND dest_zip = '*'",
                "dest_country_id = '0' AND dest_region_id = 0 AND city = :city AND dest_zip = '*'",
                "dest_country_id = '0' AND dest_region_id = 0 AND city = '*' AND dest_zip = '*'",
                    
        )) . ')';


       

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/resulte.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        //$logger->info(print_r($bind, true));

        $select->where($orWhere);
       
        $result = $connection->fetchAll($select, $bind);
        //$logger->info(print_r($result, true));
        
        $methods = array();
        $rates = array();
        $weight_type = $this->_coreConfig->getValue('carriers/advancerate/weight_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        if ($weight_type==1) { //0 kilogram , 1 gram
             $shippingWeight = ceil($request->getPackageWeight()/1000);
        }else{
              $shippingWeight = ceil($request->getPackageWeight());
        }
       
        
        $dimensional_condition = $this->_coreConfig->getValue('carriers/advancerate/dimensional_calculation', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      

        if(!empty($result)){
            foreach ($result as $key => $value) {
                if (!in_array($value['shipping_method'] , $methods)) {
                    $items = $request->getAllItems();
                    $rate       = $value['price'];
                    $totalPrice = 0;
                    $totalWeight = 0;
                    if ($dimensional_condition==1) {
                        foreach ($items as $item) {
                            $product = $objectManager->create('Magento\Catalog\Model\Product')
                                                 ->load($item->getProductId());

                            //product dimension in cm
                            $height = $product->getData('dimension_package_height');
                            $length = $product->getData('dimension_package_length');
                            $width  = $product->getData('dimension_package_width'); 
                            $weight = $product->getData('weight');
                            $qty    = $item->getQty();
                            
                            $totalWeight = $weight * $qty;
                            if ($weight_type==1) {
                                 $totalWeight = $totalWeight / 1000; 
                            }

                             $totalVolume = ($length * $width * $height) * $qty;

                            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/jne.log');
                            $logger = new \Zend\Log\Logger();
                            $logger->addWriter($writer);
                            $logger->info("volume :".($totalVolume/6000)." weight:".$totalWeight." weighttype:".$weight_type); 
                           
                            ##dibandingkan berat dg volume/6000##
                            $max = max($totalWeight,($totalVolume/6000));                                       
                            // $max = $max + ($max*1/10);
                            $price = $this->round_up($max,0) * $rate;
                            $totalPrice += $price; 

                        }
                    }else{
                          $totalPrice = $rate*$shippingWeight;
                    }

                    $rates[] = array(
                                 'method' => $value['shipping_method'],
                                 'label' => $value['shipping_label'],
                                 'price' => $totalPrice,
                                 'etd'   => $value['etd']
                             );
                }
            }
        }else{
            $rates = null;
        }   
        return $rates;
    }
    
    

    protected function round_up($value, $places)
    {
        $mult = pow(10, abs($places)); 
        
        return $places < 0 ?
            ceil($value / $mult) * $mult :
            ceil($value * $mult) / $mult;
    }
    
    
    /**
     * Upload table rate file and import data from it
     *
     * @param \Magento\Framework\DataObject $object
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\OfflineShipping\Model\Resource\Carrier\Tablerate
     * @todo: this method should be refactored as soon as updated design will be provided
     * @see https://wiki.corp.x.com/display/MCOMS/Magento+Filesystem+Decisions
     */
    public function uploadAndImport(\Magento\Framework\DataObject $object)
    {
        
        if (empty($_FILES['groups']['tmp_name']['advancerate']['fields']['import']['value'])) {
            return $this;
        }
        $csvFile = $_FILES['groups']['tmp_name']['advancerate']['fields']['import']['value'];
        $website = $this->_storeManager->getWebsite($object->getScopeId());

        $this->_importWebsiteId = (int)$website->getId();
 //       $this->_importUniqueHash = [];
        $this->_importErrors = [];
        $this->_importedRows = 0;
        
        $tmpDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::SYS_TMP);

        $path = $tmpDirectory->getRelativePath($csvFile);
        
        $stream = $tmpDirectory->openFile($path);
        
        // check and skip headers
        $headers = $stream->readCsv();

        if ($headers === false || count($headers) < 12) {
            $stream->close();
            throw new \Magento\Framework\Exception\LocalizedException(__('Please correct Advance Rates File Format.'));
        }

        /* if ($object->getData('groups/tablerate/fields/condition_name/inherit') == '1') {
            $conditionName = (string)$this->_coreConfig->getValue('carriers/tablerate/condition_name', 'default');
        } else {
            $conditionName = $object->getData('groups/tablerate/fields/condition_name/value');
        }
        $this->_importConditionName = $conditionName;
 */
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $rowNumber = 1;
            $importData = [];

            $this->_loadDirectoryCountries();
            $this->_loadDirectoryRegions();

            // delete old data by website and condition name
            $condition = [
                'website_id = ?' => $this->_importWebsiteId,
        //        'condition_name = ?' => $this->_importConditionName,
            ];
            $connection->delete($this->getMainTable(), $condition);

            while (false !== ($csvLine = $stream->readCsv())) {
                $rowNumber++;

                if (empty($csvLine)) {
                    continue;
                }

                $row = $this->_getImportRow($csvLine, $rowNumber);
                if ($row !== false) {
                    $importData[] = $row;
                }

                if (count($importData) == 5000) {
                    $this->_saveImportData($importData);
                    $importData = [];
                }
            }
            $this->_saveImportData($importData);
            $stream->close();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $connection->rollback();
            $stream->close();
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $connection->rollback();
            $stream->close();
            $this->_logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while importing advance rates.')
            );
        }

        $connection->commit();

        if ($this->_importErrors) {
            $error = __(
                'We couldn\'t import this file because of these errors: %1',
                implode(" \n", $this->_importErrors)
            );
            throw new \Magento\Framework\Exception\LocalizedException($error);
        }

        return $this;
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
            $row[$k] = trim($v);
        }

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
        
        
        
        /* if ($row[11] == '') {
            $this->_importErrors[] = __('Shipping Method can not be empty "%1" in the Row #%2.', $row[11], $rowNumber);
            return false;
        }else{
             $shipping_method = $row[11];
        } */
        
        $shipping_method = preg_replace(array("/[^a-z0-9_]/","/\_+/"), '_', strtolower($row[11]));
        if ($shipping_method == '' || $shipping_method == '_') {
            $this->_importErrors[] = ___('Invalid Shipping Method Name "%s" in the Row #%s.', $row[11], $rowNumber);
            return false;
        }
        $shipping_label = $row[11];
        $etd = $row[12];
        $vendorId = $this->getVendorId();
        return [
            $this->_importWebsiteId,$vendorId, $countryId, $regionId, $city, $zipCode,                   
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
            $this->getConnection()->insertArray($this->getMainTable(), $columns, $data);
            $this->_importedRows += count($data);
        }
          
        return $this;
    }
    
    protected function _parseDecimalValue($value)
    {
        if (!is_numeric($value)) {
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
