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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Idps;
use Elabftw\Services\LoginHelper;
use Elabftw\Services\SamlAuth;
use Exception;
use OneLogin\Saml2\Auth as SamlAuthLib;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once 'app/init.inc.php';

$location = '../../experiments.php';
$Response = new RedirectResponse($location);

try {
    // SAML: IDP will redirect to this page after user login on IDP website
    if ($App->Request->query->has('acs')) {
        $Saml = new Saml($App->Config, new Idps());
        $settings = $Saml->getSettings((int) $App->Request->cookies->get('idp_id'));
        $AuthService = new SamlAuth(new SamlAuthLib($settings), $App->Config->configArr, $settings);

        $AuthResponse = $AuthService->assertIdpResponse();

        // no team was found so user must select one
        if ($AuthResponse->initTeamRequired) {
            $App->Session->set('initial_team_selection_required', true);
            $App->Session->set('teaminit_email', $AuthResponse->initTeamUserInfo['email']);
            $App->Session->set('teaminit_firstname', $AuthResponse->initTeamUserInfo['firstname']);
            $App->Session->set('teaminit_lastname', $AuthResponse->initTeamUserInfo['lastname']);
            $location = '../../login.php';

        // if the user is in several teams, we need to redirect to the team selection
        } elseif ($AuthResponse->isInSeveralTeams) {
            $App->Session->set('team_selection_required', true);
            $App->Session->set('team_selection', $AuthResponse->selectableTeams);
            $App->Session->set('auth_userid', $AuthResponse->userid);
            $location = '../../login.php';
        } else {
            $LoginHelper = new LoginHelper($AuthResponse, $App->Session);
            $LoginHelper->login((bool) $App->Request->cookies->get('icanhazcookies'));
        }
        // the redirect cookie is ignored for saml auth. See #2438.
        // we don't use a RedirectResponse but show a temporary redirection page or it will not work properly
        echo "<html><head><meta http-equiv='refresh' content='1;url=$location' /><title>You are being redirected...</title></head><body>You are being redirected...</body></html>";
        exit;
    }

    $Response = new RedirectResponse($location);
    $Response->send();
} catch (ImproperActionException $e) {
    $template = 'error.html';
    $renderArr = array('error' => $e->getMessage());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
} catch (Exception $e) {
    // log error and show general error message
    $App->Log->error('', array('Exception' => $e));
    $template = 'error.html';
    $renderArr = array('error' => Tools::error());
    $Response = new Response();
    $Response->prepare($Request);
    $Response->setContent($App->render($template, $renderArr));
    $Response->send();
}
