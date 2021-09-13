<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Controllers\DownloadController;
use Elabftw\Exceptions\IllegalActionException;
use function error_reporting;
use Exception;
use function set_time_limit;
use function strpos;
use Symfony\Component\HttpFoundation\Session\Session;

require_once 'init.inc.php';

try {
    // we disable errors to avoid having notice and warning polluting our file
    error_reporting(E_ERROR);

    // Make sure program execution doesn't time out
    // Set maximum script execution time in seconds (0 means no limit)
    set_time_limit(0);

    // Check for LONG_NAME
    $longName = (string) $Request->query->get('f');
    if (strpos($longName, "\0") !== false) {
        throw new IllegalActionException('Missing parameter for download');
    }

    $DownloadController = new DownloadController(
        $longName,
        (string) $Request->query->get('name'),
        $Request->query->has('forceDownload'),
    );
    $Response = $DownloadController->getResponse();
    $Response->send();
} catch (Exception $e) {
    $Session = new Session();
    $Session->start();
    $Session->getFlashBag()->add('ko', $e->getMessage());
    header('Location: ../experiments.php');
}
