<?php
    /**
     * PHP Version 7
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
    $options = setOptions('index');
    $_SESSION['redirectUri'] = $options['redirectUri'];

    /**
     * Create the client with the provided options array
     */
    $client = new \Osuuspankki\ServiceProviderClient($options);

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

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        handleRedirect($client);
    }

    if ($_SERVER['REQUEST_URI'] == '/jwks') {
        getJwks();
    } else {

        /**
         * If we don´t have an authenticated user yet, lets begin
         * the authentication process by clicking the login link
         * which takes us to the view where the user gets to choose
         * an Identity Provider (such as a bank etc.).
         *
         * The link contains a given redirectUri as a query parameter
         * among other parameters from the options array, and the user
         * is redirected to that URI after the authentication.
         * Generally speaking, that URI is usually the place where the
         * user was before the authentication process started.
         */

        if (!isset($_SESSION['user'])) :
            $page = array(
                'type' => 'example',
                'title' => 'DSP Hosted identification UI'
            );

            $example = array(
                'id' => 1,
                'action' => 'index.php',
                'title' => 'OP\'s hosted identification UI',
                'embedded' => false,
                'name' => 'Hosted UI',
            );

            include 'partials/header.php';
            include 'partials/example.php';
            include 'partials/footer.php';
        else :
            header("Location:"."userInfo.php");
        endif;
    } ?>
