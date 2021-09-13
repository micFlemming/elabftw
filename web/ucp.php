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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Revisions;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * User Control Panel
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('User Control Panel');

$Response = new Response();
$Response->prepare($Request);

try {
    $ApiKeys = new ApiKeys($App->Users);
    $apiKeysArr = $ApiKeys->read(new ContentParams());

    $TeamGroups = new TeamGroups($App->Users);
    $teamGroupsArr = $TeamGroups->read(new ContentParams());

    $Templates = new Templates($App->Users);
    $templatesArr = $Templates->getWriteableTemplatesList();
    $templateData = array();
    $stepsArr = array();
    $linksArr = array();

    if ($Request->query->has('templateid')) {
        $Templates->setId((int) $Request->query->get('templateid'));
        $templateData = $Templates->read(new ContentParams());
        $permissions = $Templates->getPermissions($templateData);
        if ($permissions['write'] === false) {
            throw new IllegalActionException('User tried to access a template without write permissions');
        }
        $Revisions = new Revisions(
            $Templates,
            (int) $App->Config->configArr['max_revisions'],
            (int) $App->Config->configArr['min_delta_revisions'],
            (int) $App->Config->configArr['min_days_revisions'],
        );
        $stepsArr = $Templates->Steps->read(new ContentParams());
        $linksArr = $Templates->Links->read(new ContentParams());
    }

    // TEAM GROUPS
    $TeamGroups = new TeamGroups($App->Users);
    $visibilityArr = $TeamGroups->getVisibilityList();

    $template = 'ucp.html';
    $renderArr = array(
        'Entity' => $Templates,
        'apiKeysArr' => $apiKeysArr,
        'langsArr' => Tools::getLangsArr(),
        'stepsArr' => $stepsArr,
        'linksArr' => $linksArr,
        'teamGroupsArr' => $teamGroupsArr,
        'templateData' => $templateData,
        'templatesArr' => $templatesArr,
        'visibilityArr' => $visibilityArr,
        'revNum' => isset($Revisions) ? $Revisions->readCount() : 0,
    );
} catch (ImproperActionException $e) {
    // show message to user
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (IllegalActionException $e) {
    // log notice and show message
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    // log error and show message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response->setContent($App->render($template, $renderArr));
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Exception' => $e)));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response->setContent($App->render($template, $renderArr));
}
$Response->setContent($App->render($template, $renderArr));
$Response->send();
