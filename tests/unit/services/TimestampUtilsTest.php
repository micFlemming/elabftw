<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Filesystem;

class TimestampUtilsTest extends \PHPUnit\Framework\TestCase
{
    private Filesystem $fixturesFs;

    protected function setUp(): void
    {
        $this->fixturesFs = (new StorageFactory(StorageFactory::FIXTURES))->getStorage()->getFs();
    }

    public function testTimestamp(): void
    {
        $mockResponse = $this->fixturesFs->read('dfn.asn1');
        $client = $this->getClient($mockResponse);

        $Maker = new MakeDfnTimestamp(array(), $this->getFreshTimestampableEntity());
        $pdfBlob = $this->fixturesFs->read('dfn.pdf');
        $tsUtils = new TimestampUtils($client, $pdfBlob, $Maker->getTimestampParameters(), new TimestampResponse());
        $this->assertInstanceOf(TimestampResponse::class, $tsUtils->timestamp());
    }

    private function getClient(string $mockResponse): Client
    {
        // don't use the real guzzle client, but use a mock
        // https://docs.guzzlephp.org/en/stable/testing.html
        $mock = new MockHandler(array(
            new Response(200, array(), $mockResponse),
            new RequestException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        return new Client(array('handler' => $handlerStack));
    }

    private function getFreshTimestampableEntity(): Experiments
    {
        $Entity = new Experiments(new Users(1, 1));
        // create a new experiment for timestamping tests
        $Entity->setId($Entity->create(new EntityParams('ts test')));
        // set it to a status that is timestampable
        $Entity->updateCategory(2);
        return $Entity;
    }
}
