<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use Mpdf\Mpdf;

/**
 * For pdf producing classes
 */
trait PdfTrait
{
    private Mpdf $mpdf;

    public function getContentType(): string
    {
        return 'application/pdf';
    }
}
