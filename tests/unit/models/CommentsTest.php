<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

class CommentsTest extends \PHPUnit\Framework\TestCase
{
    private Experiments $Entity;

    private Comments $Comments;

    protected function setUp(): void
    {
        $this->Entity = new Experiments(new Users(1, 1), 1);

        $this->Comments = new Comments($this->Entity);
    }

    public function testCreate(): void
    {
        $this->assertIsInt($this->Comments->create(new ContentParams('Ohai')));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->Comments->read(new ContentParams()));
    }

    public function testUpdate(): void
    {
        $this->Comments->setId(1);
        $this->Comments->Update(new ContentParams('Updated'));
        // too short comment
        $this->expectException(ImproperActionException::class);
        $this->Comments->Update(new ContentParams(''));
    }

    public function testDestroy(): void
    {
        $this->Comments->setId(1);
        $this->Comments->destroy();
    }

    public function testSetWrongId(): void
    {
        $this->expectException(IllegalActionException::class);
        $this->Comments->setId(0);
    }
}
