<?php
    /**
     * PHP Version 7
     * Logout File Doc Comment
     *
     * @category File
     * @package  ISBIntegrationExample
     * @author   OP-Palvelut Oy
     * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
     * @link     https://www.op.fi/
     */
    session_start();
    $redirectUri = $_SESSION['redirectUri'];
    session_destroy();
    header('Location: ' . $redirectUri);
    exit();
