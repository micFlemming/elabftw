<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Aws\Credentials\Credentials;
use Elabftw\Models\Config;
use League\Flysystem\Filesystem;

class S3StorageTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFs(): void
    {
        $credentials = new Credentials('access-key', 'secret-key');
        $Storage = new S3Storage(Config::getConfig(), $credentials);
        $this->assertInstanceOf(Filesystem::class, $Storage->getFs());
    }
}
