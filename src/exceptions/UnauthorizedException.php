<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

namespace Elabftw\Exceptions;

use Exception;

/**
 * If user is not authorized to access this resource
 */
final class UnauthorizedException extends Exception
{
    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        if ($message === null) {
            $message = _('Authentication required');
        }
        parent::__construct($message, $code, $previous);
    }
}
