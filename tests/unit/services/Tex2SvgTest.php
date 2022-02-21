<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use League\Flysystem\Filesystem;
use Mpdf\Mpdf;

class Tex2SvgTest extends \PHPUnit\Framework\TestCase
{
    private Mpdf $mpdf;

    private Filesystem $fixturesFs;

    protected function setUp(): void
    {
        $MpdfProvider = new MpdfProvider('Toto');
        $this->mpdf = $MpdfProvider->getInstance();
        $this->fixturesFs = (new StorageFactory(StorageFactory::FIXTURES))->getStorage()->getFs();
    }

    public function testNoMathJax(): void
    {
        $mathJaxHtml = '<html><head></head><body>No Tex here</body></html>';
        $Tex2Svg = new Tex2Svg($this->mpdf, $mathJaxHtml);
        $this->assertEquals($mathJaxHtml, $Tex2Svg->getContent());
    }

    public function testMathJax(): void
    {
        $mathJaxHtml = $this->fixturesFs->read('mathjax.html');
        $Tex2Svg = new Tex2Svg($this->mpdf, $mathJaxHtml);
        $mathJaxOut = $Tex2Svg->getContent();
        $mathJaxOutExpect = $this->fixturesFs->read('mathjax.out.html');
        $this->assertEquals($mathJaxOutExpect, $mathJaxOut);
    }

    public function testMathJaxFail(): void
    {
        $mathJaxHtml = $this->fixturesFs->read('mathjaxFail.html');
        $Tex2Svg = new Tex2Svg($this->mpdf, $mathJaxHtml);
        $mathJaxOut = $Tex2Svg->getContent();
        $this->assertEquals($mathJaxHtml, $mathJaxOut);
    }
}
