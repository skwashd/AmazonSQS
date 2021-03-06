<?php

/**
 * This file is part of the AmazonSQS package.
 *
 * (c) Christian Eikermann <christian@chrisdev.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AmazonSQS;

use apiTalk\Client as BaseClient;
use apiTalk\Request;
use apiTalk\Adapter\AdapterInterface;

/**
 * AmazonSQS apiTalk client for signed requets
 *
 * @author Christian Eikermann <christian@chrisdev.de>
 */
class Client extends BaseClient
{

    /**
     * AWS Access Key
     * 
     * @var string
     */
    private $awsAccessKey = null;
    
    /**
     * AWS Secret Key
     * 
     * @var string
     */
    private $awsSecretKey = null;

    /**
     * Constructor
     * 
     * @param string           $awsAccessKey
     * @param string           $awsSecretKey
     * @param AdapterInterface $adapter 
     */
    function __construct($awsAccessKey, $awsSecretKey, AdapterInterface $adapter = null)
    {
        $this->awsAccessKey = $awsAccessKey;
        $this->awsSecretKey = $awsSecretKey;

        parent::__construct($adapter);
    }

    /**
     * Send a raw HTTP request
     * 
     * @param \apiTalk\Request $request The request object
     * @param int              $expires (Optional) The expire timestamp - Default: time() + 5
     * 
     * @return \apiTalk\Response The response object  
     */
    public function send(Request $request, $expires = null)
    {
        $request = $this->signRequest($request, $expires);

        return parent::send($request);
    }

    /**
     * Signed the request with the HMAC SHA256 algorithm
     * 
     * @param \apiTalk\Request $request A request object
     * @param int              $expires (Optional) The expire timestamp - Default: time() + 5
     * 
     * @return \apiTalk\Request
     */
    protected function signRequest(Request $request, $expires = null)
    {
        // @codeCoverageIgnoreStart
        if (!$expires) {
            $expires = time() + 5;
        }
        // @codeCoverageIgnoreEnd
        
        $request->setParameter('AWSAccessKeyId', $this->awsAccessKey);
        $request->setParameter('Version', '2011-10-01');
        $request->setParameter('Expires', gmdate('Y-m-d\TH:i:s\Z', $expires));
        $request->setParameter('SignatureMethod', 'HmacSHA256');
        $request->setParameter('SignatureVersion', '2');
        
        if ($request->getMethod() == Request::METHOD_POST) {
            $request->setHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        
        // Remove old signature
        $parameters = $request->getParameters();
        if (isset($parameters['Signature'])) {
            unset($parameters['Signature']);
        }

        // Sort params by key 
        uksort($parameters, 'strcmp');
        $request->setParameters($parameters);
        
        // Parse URL
        $url = $request->getUri();
        $path = parse_url($url, PHP_URL_PATH);
        $host = parse_url($url, PHP_URL_HOST);
        
        // Generate raw request        
        $data = strtoupper($request->getMethod())."\n";
        $data .= strtolower($host)."\n";
        $data .= (!empty($path) ? $path : '/')."\n";
        $data .= http_build_query($request->getParameters());
        
        // Build the signature
        $hmac = hash_hmac('sha256', $data, $this->awsSecretKey, true);
        $request->setParameter('Signature', base64_encode($hmac));

        return $request;
    }
    
}
