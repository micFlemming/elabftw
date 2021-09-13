<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Items;
use Elabftw\Models\Scheduler;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the scheduler
 *
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    $Items = new Items($App->Users);
    $Scheduler = new Scheduler($Items);

    // CREATE
    if ($Request->request->has('create')) {
        $Items->setId((int) $Request->request->get('item'));
        $Scheduler->create(
            $Request->request->get('start'),
            $Request->request->get('end'),
            $Request->request->get('title'),
        );
    }

    // GET EVENTS
    if ($Request->query->has('start') && $Request->query->has('end')) {
        if (empty($Request->query->get('item'))) {
            $Response->setData($Scheduler->readAllFromTeam($Request->query->get('start'), $Request->query->get('end')));
        } else {
            $Items->setId((int) $Request->query->get('item'));
            $Response->setData($Scheduler->read($Request->query->get('start'), $Request->query->get('end')));
        }
    }

    // UPDATE START
    if ($Request->request->has('updateStart')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $Scheduler->updateStart($Request->request->get('delta'));
    }
    // UPDATE END
    if ($Request->request->has('updateEnd')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $Scheduler->updateEnd($Request->request->get('end'));
    }

    // BIND
    if ($Request->request->has('bind')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $Scheduler->bind((int) $Request->request->get('entityid'), $Request->request->get('type'));
    }

    // UNBIND
    if ($Request->request->has('unbind')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $Scheduler->unbind($Request->request->get('type'));
    }

    // DESTROY
    if ($Request->request->has('destroy')) {
        $Scheduler->setId((int) $Request->request->get('id'));
        $Scheduler->destroy();
    }
} catch (ImproperActionException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true),
    ));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
} finally {
    $Response->send();
}
