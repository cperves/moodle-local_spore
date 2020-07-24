<?php

class Spore_Middleware_Authentication {

    protected $_authorization;
    protected $_signatureString;
    protected $_signatureSha1;

    /**
     * Construct the authentication object
     *
     * @param array   $args
     */
    public function __construct($args) {
        if (isset($args['authorization']))
            $this->setAuthorizationKey($args['authorization']);
        else
            $this->setAuthorizationKey('');
    }


    /*
     * Set authorization key
     */

    /**
     *
     *
     * @param unknown $applicationKey
     */
    public function setAuthorizationKey($authorization) {
        $this->_authorization = $authorization;
    }


    /**
     * Add the authorization into the client's headers
     *
     * @param unknown $spore (reference)
     */
    public function execute(&$spore) {
        // set signature string
        #$this->setSignatureString($spore);
        #$this->_signatureSha1 = sha1($this->_signatureString);

        // modify the request headers
        $client = RESTHttpClient :: getHttpClient();
        $client->createOrUpdateHeader('Authorization', $this->_authorization);
        $client->createOrUpdateHeader('Signature', $this->_signatureSha1);
    }


    /*
     * Generate signature string
     */

    /**
     *
     *
     * @param unknown $spore
     */
    public function setSignatureString($spore) {
        // add request method and path
        $this->_signatureString = strtolower($spore->getRequestMethod()) . $spore->getRequestUrlPath() ;

        // add request params
        $string_params = '';
        $params = $spore->getRequestParams();
        if (isset($params) && !empty($params)) {
            ksort($params);
            foreach ($params as $key => $val) {
                $string_params .= "$key=$val";
            }
        }
        $this->_signatureString .= $string_params;

        // add private key
        $this->_signatureString .= $this->_privateKey;
    }



    /**
     *
     *
     * @return unknown
     */
    public function getSignatureString() {
        return $this->_signatureString;
    }

    /**
     * Get the _Http_Client object used for communication
     *
     * @return _Http_Client
     */
    public function getHttpClient()
    {
        return $this->_httpClient;
    }


}
