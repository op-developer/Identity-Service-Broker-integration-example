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
     * Set the default timezone to Helsinki-time
     */
    date_default_timezone_set('Europe/Helsinki');

    $page = array(
        'type' => 'response',
        'title' => 'Identification Service example'
    );

    include 'partials/header.php';

    if (isset($_SESSION['user'])) : ?>
        <p>
            The information received depends on the scope of identification request and on what attributes are
            available. Do note that not all sources of information have given name and family name available as
            separate attributes.
        </p>
        <h2>Identification information</h2>

        <table>
            <tr>
                <th>Name</th>
                <td><?= $_SESSION['user']['name'] ?></td>
            </tr>
            <tr>
                <th>Identity code</th>
                <td><?= $_SESSION['user']['personal_identity_code'] ?></td>
            </tr>
            <tr>
                <th>Time of authentication</th>
                <td><?= date("M d H:i:s", $_SESSION['user']['auth_time']) ?></td>
            </tr>
        </table>

        <h3>Raw data</h3>
        <pre><?php print_r($_SESSION['user']) ?></pre>
    <?php endif; ?>
        <p><a href="logout.php">&laquo; Log out</a></p>
    <?php include 'partials/footer.php'; ?>
