<?php

/**
 * Interface for login/logout in StudOn
 */
class sspmod_studon_Interface
{
    /**
     * @var sspmod_studon_Interface
     */
    private static $instance = null;

    /**
     * @var null|SimpleSAML_Auth_Simple
     */
    private $samlAuth = null;


    /**
     * Get Singleton Instance
     *
     * @return sspmod_studon_Interface
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->samlAuth = new SimpleSAML_Auth_Simple('default-sp');
    }

    /**
     * Wrapper for SimpleSAML_Auth_Simple::requireAuth()
     */
    public function requireAuth()
    {
        $this->samlAuth->requireAuth();
    }

    /**
     * Wrapper for SimpleSAML_Auth_Simple::getAttributes()
     */
    public function getAttributes()
    {
        return $this->samlAuth->getAttributes();
    }

    /**
     * Wrapper for SimpleSAML_Auth_Simple::getLogoutUrl()
     * @param  string    $returnUrl
     * @return string
     */
    public function getLogoutUrl($returnUrl)
    {
        return $this->samlAuth->getLogoutURL($returnUrl);
    }

    /**
     * Get the Id of the simpleSAML session
     * @return null|string
     */
    public function getSamlSessionId()
    {
        $samlSession = SimpleSAML_Session::getSessionFromRequest();
        return $samlSession->getSessionId();
    }

    /**
     * Register the logout handler for StudOn
     * @param   string  $studonLogoutUrl    URL of saml_logout.php
     * @param   string  $studonSessionId    ID of the StudOn php session
     */
    public function registerLogoutService($studonLogoutUrl, $studonSessionId)
    {
        // SimpleSAML_Session is found by the autoloader of SimpleSAMLphp
        $samlSession = SimpleSAML_Session::getSessionFromRequest();
        $samlSession->setData('string', 'studonLogoutUrl', $studonLogoutUrl);
        $samlSession->setData('string', 'studonSessionId', $studonSessionId);
        $samlSession->registerLogoutHandler('default-sp','sspmod_studon_Interface','logoutHandler');
    }

    /**
     * Logout handler that can be registered (must be static)
     */
    public static function logoutHandler()
    {
        self::getInstance()->doLogout();
    }

    /**
     * Calls the SAML logout service of StudOn to end the StudOn session
     */
    private function doLogout()
    {
        $samlSession = SimpleSAML_Session::getSessionFromRequest();

        $studonLogoutUrl = $samlSession->getData('string', 'studonLogoutUrl');
        $studonSessionId = $samlSession->getData('string', 'studonSessionId');
        $samlSessionId = $samlSession->getSessionId();

        $post = "studonSessionId=$studonSessionId&samlSessionId=$samlSessionId";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $studonLogoutUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_exec($curl);
        curl_close($curl);
    }
}