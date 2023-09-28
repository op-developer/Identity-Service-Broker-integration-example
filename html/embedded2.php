<?php
    /**
     * PHP Version 8
     * Index File Doc Comment
     *
     * @category File
     * @package  ISBIntegrationExample
     * @author   OP-Palvelut Oy
     * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
     * @link     https://www.op.fi/
     */
    session_start();

    require_once dirname(__DIR__) . '/vendor/autoload.php';

    /**
     * Set options
     */
    $options = setOptions("embedded2");
    $_SESSION['redirectUri'] = $options['redirectUri'];

    /**
     * Create the client with the provided options array
     */
    $client = new \Osuuspankki\ServiceProviderClient($options);

    /**
     *  get and validate identification wall data from ISB
     */
    $requestParams = [];
    $json_data = $client->httpGetJson($options['apiHost']."/api/embedded-ui/".$options['clientId'].'?lang=en', $requestParams);
    $json_data = strip_tags($json_data); // remove possible html
    $json_data = str_replace('\r\n', '<br><br>', $json_data); // handle multi-line disturbance notifications properly
    $embeddedInfo = json_decode($json_data, true);
    $client->validateEmbeddedUIJson($embeddedInfo);

    /**
     * We got the code from the authorizationUrl endpoint as query parameter,
     * so now we can authenticate the user and get user authentication information.
     *
     * You can redirect the user to a different URI by giving a second parameter of
     * type string to the authenticate() -function.
     * By default the url is the provided redirectUri.
     */
    if (isset($_GET['code'])) {
        handleToken($client);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        handleRedirect($client);
    }

    if (!isset($_SESSION['user'])) :
        $page = array(
            'type' => 'example',
            'title' => 'DSP Embedded identification UI with dropdown'
        );

        $example = array(
            'id' => 3,
            'action' => 'embedded2.php',
            'title' => 'Self-hosted UI (dropdown) embedded into your service',
            'embedded' => true,
            'name' => 'Embedded dropdown',
        );

        include 'partials/header.php';
        include 'partials/example.php';
        include 'partials/footer.php';
    else :
        header("Location:"."userInfo.php");
    endif; ?>
