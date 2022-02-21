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

use function dirname;
use Elabftw\Elabftw\FsTools;
use Elabftw\Models\Config;
use Elabftw\Traits\TwigTrait;
use League\Flysystem\StorageAttributes;

/**
 * Generate Twig cache
 */
class CacheGenerator
{
    use TwigTrait;

    /**
     * Generate a twig cache file for all the templates in the template dir
     * @phan-suppress PhanAccessMethodInternal
     */
    public function generate(): void
    {
        $TwigEnvironment = $this->getTwig(Config::getConfig());
        $tplFs = FsTools::getFs(dirname(__DIR__, 2) . '/src/templates');
        $tplDir = dirname(__DIR__, 2) . '/src/templates';
        // iterate over all the templates
        $templates = $tplFs->listContents('.')->filter(function (StorageAttributes $attributes) {
            return $attributes->isFile();
        });

        foreach ($templates as $template) {
            // force compilation of the template into cache php file
            $TwigEnvironment->load($template->path());
        }
    }
}
