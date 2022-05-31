<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function is_array;
use Elabftw\Interfaces\FileMakerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Exceptions\ProcessFailedException;
use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use EasyRdf\Resource;

/**
 * Create a pdf from an Entity
 */
class MakeRdf extends AbstractMake implements FileMakerInterface {

    private array $related;
    private string $format;
    private string $contentType;
    private string $fileType;
    private Graph $graph;

    /**
     * Constructor
     * 
     * @param AbstractEntity $entity
     * @param array $related
     * @param string $format
     */
    public function __construct(AbstractEntity $entity, string $format) {
        parent::__construct($entity);
        $this->format = $format;
        $this->related = $entity->Links->readRelated();

        /* generate an empty graph */
        $this->graph = new Graph();

        $contentType = "text/text";
        $fileType = ".txt";

        switch ($this->format) {
            case "turtle":
                $contentType = "text/turtle";
                $fileType = ".ttl";
                break;
            case "jsonld":
                $contentType = "application/ld+json";
                $fileType = ".jsonld";
                break;
            default:
                throw new ProcessFailedException("ERROR Cannot export graph in given format");
        }

        $this->contentType = $contentType;
        $this->fileType = $fileType;
    }

    /**
     * Generate rdf and return it as string
     */
    public function getFileContent(): string {
        try {
            /* register used namespaces in the rdf engine */
            RdfNamespace::set('el-e', 'http://elab.zb.kfa-juelich.de/experiments.php#');
            RdfNamespace::set('eli', 'http://elab.zb.kfa-juelich.de/database.php#');
            RdfNamespace::set('so', 'http://schema.org/');
            RdfNamespace::set('sm', 'http://scimesh.org/');

            /* add metadata as properties and related experiments as resource nodes */
            $sample = $this->graph->resource("https://elab.zb.kfa-juelich.de/database.php?mode=view&id=" . $this->Entity->id, 'sm:Sample');
            $this->processSampleData($sample, $this->Entity->readAll());
            $this->processRelatedExperiments();

            /* serialise into requested format */
            return $this->graph->serialise($this->format);
        } catch (Exception) {
            /* Catch any exceptions and throw a general error to the parent */
            throw new ProcessFailedException("ERROR while creating RDF");
        }
    }

    /**
     * Return the Content-Type
     */
    public function getContentType(): string {
        return $this->contentType;
    }

    /**
     * Replace weird characters by underscores
     */
    public function getFileName(): string {
        $title = Filter::forFilesystem($this->Entity->entityData['title']);
        return $title . $this->fileType;
    }

    /**
     * 
     * @param Resource $sample the graph resource to add metadata to
     * @param array $data the data array (keys as property name, values as property value)
     * @return void
     */
    private function processSampleData(Resource $sample, array $data): void {
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $sample->add('eli:' . $key, $value);
            }
        }
    }

    /**
     * 
     * @param Graph $graph the graph to add the experiments to
     * @return void
     */
    private function processRelatedExperiments(): void {
        foreach ($this->getRelatedExperiments() as $relEx) {
            $exp = $this->graph->resource("https://elab.zb.kfa-juelich.de/experiments.php?mode=view&id=" . $relEx['entityid'], 'sm:Process');

            foreach ($relEx as $key => $value) {
                if ($key !== "linkid") {
                    $exp->add('el-e:' . $key, $value);
                }
            }
        }
    }

    /**
     * 
     * @return array related experiments or empty array
     */
    private function getRelatedExperiments(): array {
        if (isset($this->related['experiments']) && is_array($this->related['experiments'])) {
            return $this->related['experiments'];
        } else {
            return array();
        }
    }

}
