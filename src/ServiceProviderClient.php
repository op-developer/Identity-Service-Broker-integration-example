<?php
    /**
     * PHP Version 7
     * ServiceProviderClient File Doc Comment
     *
     * @category File
     * @package  ISBIntegrationExample
     * @author   OP-Palvelut Oy
     * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
     * @link     https://www.op.fi/
     */
namespace Osuuspankki;

use JOSE_JWT;
use JOSE_JWK;
require_once 'error.php'; // displays errors

/**
 * PHP Version 7
 * ServiceProviderClient Class Doc Comment
 *
 * @category Class
 * @package  ServiceProviderClient
 * @author   OP-Palvelut Oy
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://www.op.fi/
 */
class ServiceProviderClient extends \League\OAuth2\Client\Provider\GenericProvider
{
    protected $redirectUri = '';
    protected $privateKeyPath = '';
    protected $signingKeyPath = '';
    private $_authPurpose = '';
    private $_authPrompt = false;
    private $_authIdp = '';
    private $_tokenUri = '';
    private $_isbJwksUri = '';
    private $_clientId = '';
    private $_isbSigningKeyRefreshTime = 0;

    /**
     * ServiceProviderClient constructor
     *
     * @param object $opts options to be delivered to the ISP
     *
     * @return void
     */
    public function __construct($opts)
    {
        $this->redirectUri = $opts['redirectUri'];
        $this->privateKeyPath = $opts['privateKeyPath'];
        $this->signingKeyPath = $opts['signingKeyPath'];
        $this->clientId = $opts['clientId'];

        $opts['urlAuthorize'] = $opts['apiHost'].'/oauth/authorize';
        $this->_isbJwksUri = $opts['apiHost'].'/jwks/broker';
        $opts['urlAccessToken'] = $opts['apiHost'].'/oauth/token';
        $this->_tokenUri = $opts['urlAccessToken'];
        $opts['urlResourceOwnerDetails'] = $opts['apiHost'].'/oauth/profile';
        $opts['verify'] = false;
        $opts['scope'] = 'openid profile address email phone personal_identity_code';

        parent::__construct($opts);
        $this->init();
    }

    /**
     * ServiceProviderClient init
     * Inits and checks if errors have occurred
     *
     * @return void
     */
    public function init()
    {
        if (isset($_GET['error'])) {
            if (isset($_GET['error_description'])) {
                /* if end user has cancelled identification in embedded mode
                    do not show error but the embedded ID wall */
                if (strpos($_SESSION['redirectUri'], 'embedded') !== false &&
                    $_GET['error'] !== 'cancel') {
                    displayErrorWithTemplate($_GET['error'], $_GET['error_description']);
                }
            } else {
                displayErrorWithTemplate($_GET['error']);
            }
        } elseif (isset($_GET['code']) && $this->isStateFail()) {
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }
            displayErrorWithTemplate('Invalid state');
        }
    }

    /**
     * ServiceProviderClient httpGetJson
     *
     * @param $endpointUrl the endpoint url
     * @param array $headers request parameters
     *
     * @return Json
     */
    public function httpGetJson($endpointUrl, array $headers)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpointUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $headers,
        ));

        try {
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err || $httpcode != 200) {
                displayErrorWithTemplate('http get failed for url '.$endpointUrl, $err.$httpcode);
                exit;
            }
            return $response;
        }
        catch (Exception $e) {
            displayErrorWithTemplate('http get failed', $e->getMessage());
        }
    }

    /**
     * ServiceProviderClient validateEmbeddedUIJson
     *
     * @param $jsonToCheck json data from embedded-ui
     *
     * @return void
     */
    public function validateEmbeddedUIJson($jsonToCheck)
    {
        try {
                if (!isset($jsonToCheck['identityProviders']) || !isset($jsonToCheck['isbProviderInfo']) ||
                    !isset($jsonToCheck['isbConsent'])) {
                        displayErrorWithTemplate('embedded UI data validation failed');
                }
        }
        catch (Exception $e) {
            displayErrorWithTemplate('data validation failed', $e->getMessage());
        }
        return;
    }

    /**
     * ServiceProviderClient isStateFail
     * Check and validates state
     *
     * @return bool
     */
    protected function isStateFail()
    {
        return empty($_GET['state'])
          || (isset($_SESSION['oauth2state'])
          && $_GET['state'] !== $_SESSION['oauth2state']);
    }

    /**
     * ServiceProviderClient getRedirectUri
     *
     * @return string
     */
    protected function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * ServiceProviderClient getPrivateKeyPath
     *
     * @return string
     */
    protected function getPrivateKeyPath()
    {
        return $this->privateKeyPath;
    }

    /**
     * ServiceProviderClient getSigningKeyPath
     *
     * @return string
     */
    protected function getSigningKeyPath()
    {
        return $this->signingKeyPath;
    }

    /**
     * ServiceProviderClient setAuthPurpose
     *
     * Sets the authorization purpose parameter value
     *
     * @param string $purpose the purpose of the authorization
     *
     * @return void
     */
    public function setAuthPurpose($purpose)
    {
        $this->_authPurpose = $purpose;
    }

    /**
     * ServiceProviderClient setAuthPrompt
     *
     * Sets the authorization prompt parameter value
     *
     * @param string $prompt the prompt value for the authorization
     *
     * @return void
     */
    public function setAuthPrompt($prompt)
    {
        $this->_authPrompt = $prompt;
    }

    /**
     * ServiceProviderClient setAuthIdp
     *
     * Sets the authorization IDP parameter value
     *
     * @param string $idp the idp value for the authorization
     *
     * @return void
     */
    public function setAuthIdp($idp)
    {
        $this->_authIdp = $idp;
    }

    /**
     * ServiceProviderClient getAccessTokenRequest
     *
     * Returns a prepared request for requesting an access token.
     *
     * @param array $params Query string parameters
     * @return RequestInterface
     */
    protected function getAccessTokenRequest(array $params)
    {
        $method  = $this->getAccessTokenMethod();
        $url     = $this->getAccessTokenUrl($params);

        $params = $this->_makeCustomParams($params);
        $options = [];
        $options['headers'] = $this->_setHeaders();
        $options['body'] = $this->buildQueryString($params);

        return $this->getRequest($method, $url, $options);
    }

    /**
     * ServiceProviderClient authenticate
     *
     * To obtain the user attributes from the identity token
     * you need to first decrypt the JWE token received from
     * the OIDC token endpoint. Decryption is done using your
     * private RSA key. The decrypted JWS token is signed using
     * OP's RSA certificate to prevent tampering. You need
     * to verify that the signature is valid using the JWT library
     * of your choice. The payload of the JWS token embedded in
     * the JWE token contains user information.
     *
     * @param string $code        authorization code
     * @param string $redirectUri redirectUri from $options. E.g. localhost
     *
     * @return void
     */
    public function authenticate($code, $redirectUri = null)
    {
        try {
            $accessToken  = $this->getAccessToken(
                'authorization_code',
                ['code' => $code]
            );
        }
        catch (\Throwable | \Error | \Exception $e) {
            displayErrorWithTemplate(
                'Something went wrong.
                It might have been wrong clientSecret or
                some other problem when getting the accessToken',
                $e->getMessage()
            );
        }
        $private_key  = file_get_contents($this->getPrivateKeyPath());

        // Get JWE token from the OIDC token endpoint using the accessToken
        $jwt_string = $accessToken->getValues()["id_token"];

        // Decode the JWE token
        $jwe = JOSE_JWT::decode($jwt_string);

        // Decrypt the token with the private key
        $jwe->decrypt($private_key);

        // Decode the JWS token from inside the JWE token
        $jws = JOSE_JWT::decode($jwe->plain_text);

        $header = $jws->header;  
        $kid = $header['kid'];        

        // Calculate cache expiry time based on creation of cache file. In this example it is 10 minutes
        // Of cource check that there is cache file at all 
        if (file_exists("/tmp/isbcache.json")) {
            $this->_isbSigningKeyRefreshTime = filemtime("/tmp/isbcache.json") + getenv('CACHE_REFRESH_RATE');
        }

        // Check if there is needs for cache update. In case cache does not exist keys are always fetched
        if (time() > $this->_isbSigningKeyRefreshTime) {
            // Get new keys to cache (to local file) from JWKS endpoint
            $this->_storeIsbSigningKeysToCache();
        } 

        $public_key = $this->_getIsbSigningKeyFromCache($kid);

        // Verify signature. If this fails, lets try to retrive new keys to cache and verify again. 
        // This is because there could be a possibility that keys has been changed and key refresh is needed

        $verifyok = "";

        try {
            $jws->verify($public_key);
            $verifyok = "Yes";
        }
        catch (\Throwable | \Error | \Exception $e) {
            $verifyok = "No";
        }

        if ($verifyok!="yes") {
            $this->_storeIsbSigningKeysToCache();
            $public_key = $this->_getIsbSigningKeyFromCache($kid);
            $jws->verify($public_key);            
        }

        $_SESSION['user'] = $jws->claims;

        if (!$redirectUri) {
            $redirectUri = $this->getRedirectUri();
        }
        header('Location: '.$redirectUri);
    }

    /**
     * ServiceProviderClient getAllowedClientOptions
     *
     * @param array $options options for the OIDC auth
     *
     * @return array
     */
    protected function getAllowedClientOptions(array $options)
    {
        return ['timeout', 'proxy', 'verify'];
    }

    /**
     * ServiceProviderClient getAuthorizationParameters
     *
     * @param array $options options for the OIDC auth
     *
     * @return object
     */
    protected function getAuthorizationParameters(array $options)
    {
        $options = parent::getAuthorizationParameters($options);
        $options['scope'] = 'openid profile personal_identity_code';
        if ($this->_authPurpose != 'normal') {
            $options['scope'] .= ' '.$this->_authPurpose;
        }
        if ($this->_authPrompt) {
            $options['prompt'] = 'consent';
        }
        if ($this->_authIdp != '') {
            $options['ftn_idp_id'] = $this->_authIdp;
        }
        $options['nonce'] = $this->getRandomState(22);
        $_SESSION['oauth2state'] = $options['state'];

        return $options;
    }

    /**
     * ServiceProviderClient getAuthorizationQuery
     *
     * Builds the authorization URL's query string.
     *
     * @param  array $params Query parameters
     * @return string Query string
     */
    protected function getAuthorizationQuery(array $params)
    {
        try {
            // add query parameters as claims in JWS token
            $jwt = new JOSE_JWT($params);
            $signing_key  = file_get_contents($this->getSigningKeyPath());
            $jws = $jwt->sign($signing_key, 'RS256');
            $query_param = [];
            $query_param['request'] = $jws->toString();
            return $this->buildQueryString($query_param);
        } catch (\Throwable | \Error | \Exception $e) {
            displayErrorWithTemplate(
                'Something went wrong in making signed JWT request parameter for /authorize',
                $e->getMessage()
            );
        }
    }

    /**
     * ServiceProviderClient makePrivateKeyJwt
     *
     * Sets the authorization IDP parameter value
     *
     *
     * @return string
     */
    public function makePrivateKeyJwt()
    {
        try {
            $jwt = new JOSE_JWT(array(
                'iss' => $this->clientId,
                'sub' => $this->clientId,
                'aud' => $this->_tokenUri,
                'jti' => $this->getRandomState(22),
                'exp' => time() + 10 * 60
            ));
            $signing_key  = file_get_contents($this->getSigningKeyPath());
            $jws = $jwt->sign($signing_key, 'RS256');
            return $jws->toString();
        } catch (\Throwable | \Error | \Exception $e) {
            displayErrorWithTemplate(
                'Something went wrong in making signed JWT key',
                $e->getMessage()
            );
        }
    }

    /**
     * ServiceProviderClient _makeCustomParams
     *
     * Builds custom queryParams for /oauth/token endpoint
     *
     * @param  array $params Query parameters
     * @return array Query parameters
     */
    private function _makeCustomParams($params)
    {
        // Remove client_id and client_secret from parameters as we use JWT token for authentication
        // These are set in the getAccessToken method of the oauth2-client library
        unset($params['client_id']);
        unset($params['client_secret']);
        // add client_assertion_type and client_assertion
        $params['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
        $params['client_assertion'] = $this->makePrivateKeyJwt();
        return $params;
    }

    /**
     * ServiceProviderClient _setHeaders
     *
     * sets request headers for the /oauth/token endpoint
     *
     * @return array request headers
     */
    private function _setHeaders()
    {
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];
        return $headers;
    }

    /**
     * ServiceProviderClient _storeIsbSigningKeysToCache()
     * 
     * gets JWKS keys from ISB JWKS endpoint and store keys to the local JWKS cache.  (file /tmp/isbcache.json)
     * 
     */
    private function _storeIsbSigningKeysToCache() {

        try {
            $isbJwkSetCacheTmp = json_decode($this->httpGetJson($this->_isbJwksUri, []), true); 
            $file = '/tmp/isbcache.json';
            $content = json_encode($isbJwkSetCacheTmp);
            file_put_contents($file, $content);

        } catch (\Throwable | \Error | \Exception $e) {
            displayErrorWithTemplate(
                'something went wrong during fetcing keys from JWKS URI',
                $e->getMessage()
            );
        }       
    }

    /**
     * ServiceProviderClient _getIsbSigningKeyFromCache($kid)
     * 
     * return signing key from cache by kid
     * 
     */
    private function _getIsbSigningKeyFromCache($kid) {

        try {
            $file = '/tmp/isbcache.json';
            $content = file_get_contents($file);
            $isbJwkSetCache = json_decode($content, true);

            for ($i = 0; $i < sizeof($isbJwkSetCache); $i++) {
                if ($isbJwkSetCache['keys'][$i]['kid']==$kid) {
                    $key = new JOSE_JWK($isbJwkSetCache['keys'][$i]);
                }
            }
    
            return $key;
        } catch (\Throwable | \Error | \Exception $e) {
            displayErrorWithTemplate(
                'something went wrong with the ISB signing key fetch from cache',
                $e->getMessage()
            );
        }
    }
}
