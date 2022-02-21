<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function dirname;

/**
 * RFC3161 timestamping with Digicert timestamping service
 * https://knowledge.digicert.com/generalinformation/INFO4231.html
 */
class MakeDigicertTimestamp extends MakeTimestamp
{
    protected const TS_URL = 'http://timestamp.digicert.com';

    protected const TS_CERT = 'digicert.pem';

    protected const TS_HASH = 'sha256';

    /**
     * Return the needed parameters to request/verify a timestamp
     *
     * @return array<string,string>
     */
    public function getTimestampParameters(): array
    {
        return array(
            'ts_login' => '',
            'ts_password' => '',
            'ts_url' => self::TS_URL,
            'ts_cert' => dirname(__DIR__) . '/certs/' . self::TS_CERT,
            'ts_hash' => self::TS_HASH,
            // digicert root cert is already in the trusted certs list provided by ca-certificates package
            'ts_chain' => '/etc/ssl/cert.pem',
            );
    }
}
