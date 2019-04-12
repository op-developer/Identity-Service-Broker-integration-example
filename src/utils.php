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
         *
         * @var apiHost          OP api host (sandbox/prod)
         * @var clientId         Personal id from the extranet
         * @var clientSecret     Personal secret from the extranet
         * @var redirectUri      Url to redirect the user to after authentication
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
            'redirectUri'     => getenv($redirect_uri),
            'privateKeyPath'  => getenv('PRIVATE_KEY_PATH'),
            'signingKeyPath'  => getenv('SIGNING_KEY_PATH')
        ];
        return $options;
    }

    /**
     * utils getJwks
     * returns JWKSet
     *
     *
     * @return string JWK set
     */
    function getJwks()
    {

        $private_enc_key_pem  = file_get_contents(getenv('PRIVATE_KEY_PATH'));
        $private_sig_key_pem  = file_get_contents(getenv('SIGNING_KEY_PATH'));

        // signalling key
        $signing_key = new phpseclib\Crypt\RSA();
        $signing_key->loadKey($private_sig_key_pem);
        $signing_key->loadKey($signing_key->getPublicKey());
        $sig_jwk = JOSE_JWK::encode($signing_key, array(
            'kid' => hash('sha1', 'signing'),
            'use' => 'sig'
        ));

        // encryption key
        $encryption_key = new phpseclib\Crypt\RSA();
        $encryption_key->loadKey($private_enc_key_pem);
        $encryption_key->loadKey($encryption_key->getPublicKey());
        $enc_jwk = JOSE_JWK::encode($encryption_key, array(
            'kid' => hash('sha1', 'encrypt'),
            'use' => 'enc'
        ));

        $keys = array($enc_jwk,$sig_jwk);

        $jwks = new JOSE_JWKSet($keys);
        header_remove();
        http_response_code(200);
        header('Content-Type: application/json');
        echo($jwks->toString());
    }
?>
