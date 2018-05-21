<?php
namespace MageHost\SyncCache\Model\RestClient;

class Local
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /** @var \Magento\Integration\Model\IntegrationService */
    private $integrationService;
    /** @var \Magento\Integration\Model\Integration */
    private $integration;
    /** @var \Zend_Oauth_Http_Utility */
    private $oauthHttpUtility;

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
        $this->logger = $logger;
        $this->integrationService = $integrationService;
        $this->oauthHttpUtility = $oauthHttpUtility;
    }

    /**
     * @param int $integrationId
     * @return bool - True if successful.
     */
    public function setIntegrationId($integrationId)
    {
        if (empty($integrationId)) {
            $this->logger->error(sprintf('%s: Integration ID not set', __CLASS__));
            return false;
        }
        try {
            $this->integration = $this->integrationService->get($integrationId);
        } catch (\Magento\Framework\Exception\IntegrationException $e) {
            $this->logger->error(sprintf(
                '%s: Error using integration %d: %s',
                __CLASS__,
                $integrationId,
                $e->getMessage()
            ));
            return false;
        }
        return true;
    }

    /**
     * @param $url
     * @param string $hostHeader
     * @param bool $sslOffloaded
     * @return bool|mixed
     */
    public function get($url, $hostHeader = '', $sslOffloaded = false)
    {
        if (empty($this->integration)) {
            $this->logger->error(sprintf('%s: No integration selected', __CLASS__));
            return false;
        }

        $data = [
            'oauth_consumer_key' => $this->integration->getConsumerKey(),
            'oauth_nonce' => $this->oauthHttpUtility->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->integration->getToken(),
            'oauth_version' => '1.0'
        ];
        $signUrl = $url;
        if (!empty($hostHeader) || $sslOffloaded) {
            $signUrlParts = parse_url($url);
            if (!empty($hostHeader)) {
                $signUrlParts['host'] = $hostHeader;
                unset($signUrlParts['port']);
            }
            if ($sslOffloaded) {
                $signUrlParts['scheme'] = 'https';
            }
            $signUrl = $this->unParseUrl($signUrlParts);
        }

        $data['oauth_signature'] = $this->oauthHttpUtility->sign(
            $data,
            $data['oauth_signature_method'],
            $this->integration->getConsumerSecret(),
            $this->integration->getTokenSecret(),
            'GET',
            $signUrl
        );

        $curl = curl_init();

        $headers = [ 'Authorization: OAuth ' . http_build_query($data, '', ',') ];
        if (!empty($hostHeader)) {
            $headers[] = 'Host: ' . $hostHeader;
        }
        if ($sslOffloaded) {
            $headers[] = 'X-Forwarded-Proto: https';
            $headers[] = 'Ssl-Offloaded: 1';
        }

        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $this->logger->info(sprintf("%s: Calling %s", __CLASS__, $url));
        $result = curl_exec($curl);
        curl_close($curl);
        $this->logger->info(sprintf("%s: Result: %s", __CLASS__, var_export($result, 1)));
        return $result;
    }

    /**
     * The opposite of the native PHP function parse_url(), simplified.
     * Required keys: scheme, host, path
     *
     * @param $parsed_url
     * @return string
     */
    private function unParseUrl($parsed_url)
    {
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return $parsed_url['scheme'] . "://" .
            $user . $pass . $parsed_url['host'] . $port .
            $parsed_url['path'] . $query . $fragment;
    }
}
