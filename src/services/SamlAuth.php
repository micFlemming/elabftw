<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Saml;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Config;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Models\ValidatedUser;
use function is_array;
use OneLogin\Saml2\Auth as SamlAuthLib;

/**
 * SAML auth service
 */
class SamlAuth implements AuthInterface
{
    private AuthResponse $AuthResponse;

    public function __construct(private SamlAuthLib $SamlAuthLib, private array $configArr, private array $settings)
    {
        $this->AuthResponse = new AuthResponse('saml');
    }

    public function tryAuth(): AuthResponse
    {
        $returnUrl = $this->settings['baseurl'] . '/index.php?acs';
        $this->SamlAuthLib->login($returnUrl);
        return $this->AuthResponse;
    }

    public function assertIdpResponse(): AuthResponse
    {
        $this->SamlAuthLib->processResponse();
        $errors = $this->SamlAuthLib->getErrors();

        // Display the errors if we are in debug mode
        if (!empty($errors)) {
            $error = Tools::error();
            // get more verbose if debug mode is active
            if ($this->configArr['debug']) {
                $error = implode(', ', $errors);
            }
            throw new UnauthorizedException($error);
        }

        if (!$this->SamlAuthLib->isAuthenticated()) {
            throw new UnauthorizedException('Authentication with IDP failed!');
        }

        // get the user information sent by IDP
        $samlUserdata = $this->SamlAuthLib->getAttributes();

        // GET EMAIL
        $email = $this->getEmail($samlUserdata);

        // GET POPULATED USERS OBJECT
        $Users = $this->getUsers($email, $samlUserdata);
        $userid = (int) $Users->userData['userid'];

        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];

        // synchronize the teams from the IDP
        // because teams can change since the time the user was created
        if ($this->configArr['saml_sync_teams']) {
            $Teams = new Teams($Users);
            $Teams->synchronize($userid, $this->getTeamsFromIdpResponse($samlUserdata));
        }

        // load the teams from db
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }

    private function getEmail(array $samlUserdata): string
    {
        $email = $samlUserdata[$this->settings['idp']['emailAttr']];

        if (is_array($email)) {
            $email = $email[0];
        }

        if ($email === null) {
            throw new ImproperActionException('Could not find email in response from IDP! Aborting.');
        }
        return $email;
    }

    private function getTeamsFromIdpResponse(array $samlUserdata): array
    {
        if (empty($this->settings['idp']['teamAttr'])) {
            throw new ImproperActionException('Cannot synchronize team(s) from IDP if no value is set for looking up team(s) in IDP response!');
        }
        $teams = $samlUserdata[$this->settings['idp']['teamAttr']];
        if (empty($teams)) {
            throw new ImproperActionException('Could not find team(s) in IDP response!');
        }

        $Teams = new Teams(new Users());
        if (is_array($teams)) {
            return $Teams->getTeamsFromIdOrNameOrOrgidArray($teams);
        }

        if (is_string($teams)) {
            // maybe it's a string containing several teams separated by spaces
            return $Teams->getTeamsFromIdOrNameOrOrgidArray(explode(',', $teams));
        }
        throw new ImproperActionException('Could not find team ID to assign user!');
    }

    private function getTeams(array $samlUserdata): array
    {
        $teams = $samlUserdata[$this->settings['idp']['teamAttr'] ?? 'Nope'] ?? array();

        // if no team attribute is sent by the IDP, use the default team
        if (empty($teams)) {
            // we directly get the id from the stored config
            $teamId = (int) $this->configArr['saml_team_default'];
            if ($teamId === 0) {
                throw new ImproperActionException('Could not find team ID to assign user!');
            }
            return array($teamId);
        }

        if (is_array($teams)) {
            return ($teams);
        }

        if (is_string($teams)) {
            // maybe it's a string containing several teams separated by commas
            return explode(',', $teams);
        }
        throw new ImproperActionException('Could not find team ID to assign user!');
    }

    private function getUsers(string $email, array $samlUserdata): Users
    {
        try {
            $Users = ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            // the user doesn't exist yet in the db
            // what do we do? Lookup the config setting for that case
            if ($this->configArr['saml_user_default'] === '0') {
                throw new ImproperActionException('Could not find an existing user. Ask a Sysadmin to create your account.');
            }

            // GET FIRSTNAME AND LASTNAME
            $firstname = $samlUserdata[$this->settings['idp']['fnameAttr'] ?? 'Unknown'] ?? 'Unknown';
            if (is_array($firstname)) {
                $firstname = $firstname[0];
            }
            $lastname = $samlUserdata[$this->settings['idp']['lnameAttr'] ?? 'Unknown'] ?? 'Unknown';
            if (is_array($lastname)) {
                $lastname = $lastname[0];
            }

            // now try and get the teams
            $teams = $this->getTeams($samlUserdata);

            // CREATE USER (and force validation of user, with user permissions)
            $Users = ValidatedUser::fromExternal($email, $teams, $firstname, $lastname);
        }
        return $Users;
    }
}
