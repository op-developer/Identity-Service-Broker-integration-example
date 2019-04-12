<?php
    /**
     * PHP Version 7
     * Error File Doc Comment
     *
     * @category File
     * @package  ISBIntegrationExample
     * @author   OP-Palvelut Oy
     * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
     * @link     https://www.op.fi/
     */

/**
 * Displays error with the template
 *
 * @param string $error       short error decription
 * @param string $description more detailed error description. optional
 *
 * @return void
 */
function displayErrorWithTemplate($error, $description=null)
{
    $page = array(
        'type' => 'response',
        'title' => 'Error during identification'
    );

    $redirectUri = $_SESSION['redirectUri'];
    include 'partials/header.php'; ?>
        <h2>Error during identification</h2>

        <h3>Error during identification</h3>
        <pre><?= $error ?></pre>

        <?php if ($description) : ?>
            <h3>Error description</h3>
            <pre><?= $description ?></pre>
        <?php endif; ?>

        <p><a href="<?= $redirectUri ?>">&laquo; Retry</a></p>
    <?php
    include 'partials/footer.php';
    exit();
}
?>
