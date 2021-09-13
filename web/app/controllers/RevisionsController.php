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
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Revisions;
use Elabftw\Models\Templates;
use Elabftw\Services\Check;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Revisions controller
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new RedirectResponse('../../experiments.php');

try {
    if ($Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users);
    } elseif ($Request->query->get('type') === 'experiments_templates') {
        $Entity = new Templates($App->Users);
    } elseif ($Request->query->get('type') === 'items') {
        $Entity = new Items($App->Users);
    } else {
        throw new IllegalActionException('Bad type!');
    }

    $Entity->setId((int) $Request->query->get('item_id'));
    $Entity->canOrExplode('write');
    $Revisions = new Revisions(
        $Entity,
        (int) $App->Config->configArr['max_revisions'],
        (int) $App->Config->configArr['min_delta_revisions'],
        (int) $App->Config->configArr['min_days_revisions'],
    );

    if ($Request->query->get('action') === 'restore') {
        $revId = Check::id((int) $Request->query->get('rev_id'));
        if ($revId === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }

        $Revisions->restore($revId);
        $App->Session->getFlashBag()->add('ok', _('Saved'));
    }

    if ($Entity->type == 'experiments_templates') {
        $Response = new RedirectResponse('../../ucp.php?tab=3&templateid=' . $Entity->id);
    } else {
        $Response = new RedirectResponse('../../' . $Entity->page . '.php?mode=view&id=' . $Entity->id);
    }
} catch (ImproperActionException $e) {
    // show message to user
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error(true));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $App->Session->getFlashBag()->add('ko', $e->getMessage());
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $App->Session->getFlashBag()->add('ko', Tools::error());
} finally {
    $Response->send();
}
