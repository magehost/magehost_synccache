<?php
namespace MageHost\SyncCache\Model\RestClient;

class Local
{
    /** @var \Psr\Log\LoggerInterface */
    protected $_logger;
    /** @var \Magento\Integration\Model\IntegrationService */
    protected $_integrationService;
    /** @var \Magento\Integration\Model\Integration */
    protected $_integration;
    /** @var \Zend_Oauth_Http_Utility */
    protected $_oauthHttpUtility;

    /**
     * Local constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Integration\Model\IntegrationService $integrationService
     * @param \Zend_Oauth_Http_Utility $oauthHttpUtility
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Integration\Model\IntegrationService $integrationService,
        \Zend_Oauth_Http_Utility $oauthHttpUtility
    ) {
        $this->_logger = $logger;
        $this->_integrationService = $integrationService;
        $this->_oauthHttpUtility = $oauthHttpUtility;
    }

    /**
     * @param int $integrationId
     * @return bool - True if succesful.
     */
    public function setIntegrationId($integrationId) {
        if (empty($integrationId)) {
            $this->_logger->error(sprintf('%s: Integration ID not set',__CLASS__));
            return false;
        }
        try {
            $this->_integration = $this->_integrationService->get($integrationId);
        } catch (\Magento\Framework\Exception\IntegrationException $e) {
            $this->_logger->error(sprintf('%s: Error using integration %d: %s',
                __CLASS__, $integrationId, $e->getMessage()));
            return false;
        }
        return true;
    }

    /**
     * @param string $url
     * @param array $extraHeaders
     * @return bool|mixed
     */
    public function get($url,$extraHeaders=array()) {
        if (empty($this->_integration)) {
            $this->_logger->error(sprintf('%s: No integration selected',__CLASS__));
            return false;
        }

        $data = [
            'oauth_consumer_key' => $this->_integration->getConsumerKey(),
            'oauth_nonce' => md5(uniqid(rand(), true)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->_integration->getToken(),
            'oauth_version' => '1.0'
        ];
        $data['oauth_signature'] = $this->_oauthHttpUtility->sign( $data,
            $data['oauth_signature_method'],
            $this->_integration->getConsumerSecret(),
            $this->_integration->getTokenSecret(),
            'GET',
            $url );

        $curl = curl_init();

        $headers = $extraHeaders;
        $headers[] = 'Authorization: OAuth ' . http_build_query($data, '', ',');

        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $this->_logger->info(sprintf("%s: Calling %s",__CLASS__,$url));
        $result = curl_exec($curl);
        curl_close($curl);
        $this->_logger->info(sprintf("%s: Result: %s", __CLASS__, var_export($result,1)));
        return $result;
    }
}