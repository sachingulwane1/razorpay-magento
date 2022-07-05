<?php

namespace Razorpay\Magento\Model;

use Razorpay\Api\Api;
use Magento\Framework\Module\ModuleListInterface;
use Razorpay\Api\Errors;
use Razorpay\Magento\Model\PaymentMethod;

use function PHPSTORM_META\type;

class TrackPluginInstrumentation 
{
    const MODULE_NAME = 'Razorpay_Magento';

    protected $api;

    private String $keyId;

    private String $keySecret;

    protected ModuleListInterface $moduleList;

    public function __construct(
        String $keyId, 
        String $keySecret,
        ModuleListInterface $moduleList,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->keyId        = $keyId;
        $this->keySecret    = $keySecret; 

        $this->api          = $this->setAndGetRzpApiInstance();
        $this->moduleList   = $moduleList;
        $this->logger       = $logger;
    }

    public function setAndGetRzpApiInstance()
    {
        $apiInstance = new Api($this->keyId, $this->keySecret);

        return $apiInstance;
    }

    public function rzpTrackSegment($event, $properties)
    {
        try
        {
            if (empty($event) === true or is_string($event) === false)
            {
                throw new \Exception("Empty field passed for event name payload to Segment");
            }
            if (empty($properties) === true)
            {
                throw new \Exception("Empty field passed for event properties payload to Segment");
            }

            if (is_string($properties))
            {
                $properties = json_decode($properties);
            }

            $objectManager      = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata    = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $magentoVersion     = $productMetadata->getVersion();
    
            $razorpayPluginVersion = $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
            
            $defaultProperties = array();

            $defaultProperties['platform']          = 'Magento';
            $defaultProperties['platform_version']  = $magentoVersion;
            $defaultProperties['plugin']            = 'Razorpay';
            $defaultProperties['plugin_version']    = $razorpayPluginVersion;

            $properties = array_merge($properties, $defaultProperties);
            
            $data = [
                'event'         => $event,
                'properties'    => $properties
            ];

            $this->logger->info('Event: '. $event .'. Properties: '. json_encode($properties));

            $response = $this->api->request->request('POST', 'plugins/segment', $data);

            return $response;
        }
        catch (\Razorpay\Api\Errors\Error $e)
        {
            $this->logger->critical("Error:" . $e->getMessage());
            $response = ['status' => 'error', 'message' => $e->getMessage()];
            return $response;
        }
        catch (\Exception $e)
        {
            $this->logger->critical("Error:" . $e->getMessage());
            $response = ['status' => 'error', 'message' => $e->getMessage()];
            return $response;
        }
    }

    public function rzpTrackDataLake($properties)
    {
        try
        {

        }
        catch (\Razorpay\Api\Errors\Error $e)
        {
            $this->logger->info($e->getMessage());
        }
        catch (\Exception $e)
        {
            $this->logger->info($e->getMessage());
        }
    }
}

?>