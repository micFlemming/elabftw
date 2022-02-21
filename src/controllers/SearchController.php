<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\DisplayParams;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;

/**
 * For search.php
 */
class SearchController extends AbstractEntityController
{
    public function __construct(App $app, AbstractEntity $entity)
    {
        parent::__construct($app, $entity);

        // on search page, the categories can be status or itemstypes depending on where one searches
        if ($this->App->Request->query->get('type') === 'experiments') {
            $Category = new Status($this->App->Users->team);
        } else {
            $Category = new ItemsTypes($this->App->Users);
        }
        $this->categoryArr = $Category->readAll();
    }

    /**
     * Get the results from main sql query with items to display
     */
    protected function getItemsArr(): array
    {
        $DisplayParams = new DisplayParams();
        $DisplayParams->adjust($this->App);
        return $this->Entity->readShow($DisplayParams);
    }
}
