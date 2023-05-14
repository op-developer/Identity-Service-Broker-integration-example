<?php
    /**
     * PHP Version 7
     * Utils File Doc Comment
     *
     * @category File
     * @package  ISBIntegrationExample
     * @author   OP-Palvelut Oy
     * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
     * @link     https://www.op.fi/
     */

    /**
     * utils handleToken
     * handles the token received from the ISB
     *
     * @param className $client
     *
     * @return void
     */
    function handleToken($client)
    {
        try {
            $client->authenticate($_GET['code']);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            displayErrorWithTemplate($e->getMessage());
        }
    }

    /**
     * utils handleRedirect
     * handles the POST form data and makes redirect to ISB
     *
     * @param className $client
     *
     * @return void
     */
    function handleRedirect($client)
    {
        isset($_POST['purpose']) ? $client->setAuthPurpose($_POST['purpose']) : $client->setAuthPurpose('normal');

        $providerParams = '';
        if (isset($_POST['authenticate'])) {
            isset($_POST['promptBox']) ? $client->setAuthPrompt(true) : $client->setAuthPrompt(false);
            isset($_POST['selectedIdp']) ? $client->setAuthIdp($_POST['selectedIdp']) : $client->setAuthIdp('');
            header('Location:'.$client->getAuthorizationUrl().$providerParams);
            exit;
        }
    }

    /**
     * utils setOptions
     * Does initial settings
     *
     * @param string $page chosen redirectUri page
     *
     * @return array
     */
    function setOptions ($page)
    {

        /**
         * Options for the ServiceProviderClient
         */
        switch($page) {
            case 'embedded':
                $redirect_uri = 'REDIRECT_URI_EMBEDDED';
                break;
            case 'embedded2':
                $redirect_uri = 'REDIRECT_URI_EMBEDDED2';
                break;
            default:
                $redirect_uri = 'REDIRECT_URI';
                break;
        }
        $options = [
            'apiHost'         => getenv('API_HOST'),
            'clientId'        => getenv('CLIENT_ID'),
            'ftnSpname'       => getenv('FTN_SPNAME'),
            'redirectUri'     => getenv($redirect_uri),
            'privateKeyPath'  => getenv('PRIVATE_KEY_PATH'),
            'signingKeyPath'  => getenv('SIGNING_KEY_PATH'),
            'cacheAge'        => getenv('CACHE_REFRESH_RATE')
        ];
        return $options;
    }

    /**
     * utils getSignedJwks
     * returns JWS containing the JWKSet
     *
     *
     * @return string JWK set
     */
    function getSignedJwks()
    {

        $private_enc_key_pem  = file_get_contents(getenv('PRIVATE_KEY_PATH'));
        $private_sig_key_pem  = file_get_contents(getenv('SIGNING_KEY_PATH'));

        // signing key
        $signing_key = new phpseclib\Crypt\RSA();
        $signing_key->loadKey($private_sig_key_pem);
        $signing_key->loadKey($signing_key->getPublicKey());
        $sig_jwk = JOSE_JWK::encode($signing_key, array(
            'use' => 'sig'
        ));

        // encryption key
        $encryption_key = new phpseclib\Crypt\RSA();
        $encryption_key->loadKey($private_enc_key_pem);
        $encryption_key->loadKey($encryption_key->getPublicKey());
        $enc_jwk = JOSE_JWK::encode($encryption_key, array(
            'use' => 'enc'
        ));

        // make JWKSet
        $keys = array($enc_jwk,$sig_jwk);
        $jwks = new JOSE_JWKSet($keys);

        // create JSON web token
        $jwt = new JOSE_JWT(array(
            'keys' => json_decode($jwks->toString(), true)['keys'],
            'iss' => getenv('REDIRECT_URI'),
            'sub' => getenv('REDIRECT_URI'),
            'iat' => time(),
            'exp' => time() + 25 * 60 * 60 // 25 hours
        ));

        // create entity signing key
        $entity_signing_key_pem = file_get_contents(getenv('ENTITY_SIGNING_KEY_PATH'));
        $entity_signing_key = new phpseclib\Crypt\RSA();
        $entity_signing_key->loadKey($entity_signing_key_pem);
        $entity_sig_jwk = JOSE_JWK::encode($entity_signing_key);

        // add kid manually
        $jws = new JOSE_JWS($jwt);
        $jws->header['kid'] = $entity_sig_jwk->thumbprint();

        // sign the JSON web token
        $jws = $jws->sign($entity_signing_key_pem, 'RS256');

        header_remove();
        http_response_code(200);
        header('Content-Type: application/jwk-set+jwt');
        echo($jws->toString());
    }

    /**
     * utils getEntityStatement
     * returns Entity Statement of the Service Provider
     *
     *
     * @return string Entity Statement
     */
    function getEntityStatement()
    {
        // create keyset
        $entity_signing_key_pem = file_get_contents(getenv('ENTITY_SIGNING_KEY_PATH'));
        $entity_signing_key = new phpseclib\Crypt\RSA();
        $entity_signing_key->loadKey($entity_signing_key_pem);

        // Public key
        $entity_signing_key_pub = new phpseclib\Crypt\RSA();
        $entity_signing_key_pub->loadKey($entity_signing_key->getPublicKey());
        $entity_sig_jwk = JOSE_JWK::encode($entity_signing_key_pub,array(
            'use' => 'sig'
        ));

        // make JWKSet
        $keys = array($entity_sig_jwk);
        $jwks = new JOSE_JWKSet($keys);

        // create Entity Statement JSON web token
        $openid_relying_party = array(
            'redirect_uris' => array(getenv('REDIRECT_URI').'/oauth/code'),
            'application_type' => 'web',
            'id_token_signed_response_alg' => 'RS256',
            'id_token_encrypted_response_alg' => 'RSA-OAEP',
            'id_token_encrypted_response_enc' => 'A128CBC-HS256',
            'request_object_signing_alg' => 'RS256',
            'token_endpoint_auth_method' => 'private_key_jwt',
            'token_endpoint_auth_signing_alg' => 'RS256',
            'client_registration_types' => array(),
            'organization_name' => 'Saippuakauppias',
            'signed_jwks_uri' => getenv('REDIRECT_URI').'/signed-jwks'
        );
        $jwt = new JOSE_JWT(array(
            'iss' => getenv('REDIRECT_URI'),
            'sub' => getenv('REDIRECT_URI'),
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24 * 365 * 10, // 10 years
            'jwks' => json_decode($jwks->toString(), true),
            'metadata' => array('openid_relying_party' => $openid_relying_party)
        ));


         // add kid manually and define typ
         $jws = new JOSE_JWS($jwt);
         $jws->header['kid'] = $entity_sig_jwk->thumbprint();
         $jws->header['typ'] = 'entity-statement+jwt';

         // sign the Entity Statement
         $jws = $jws->sign($entity_signing_key_pem, 'RS256');


         header_remove();
         http_response_code(200);
         header('Content-Type: application/entity-statement+jwt');
         echo($jws->toString());
    }
?>
