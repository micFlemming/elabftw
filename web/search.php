<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function count;
use Elabftw\Controllers\SearchController;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use function filter_var;
use function rtrim;
use function trim;

/**
 * The search page
 * Here be dragons!
 *
 */
require_once 'app/init.inc.php';
$App->pageTitle = _('Search');

$Experiments = new Experiments($App->Users);
$Database = new Items($App->Users);
$Tags = new Tags($Experiments);
$tagsArr = $Tags->readAll();

$itemsTypesArr = (new ItemsTypes($App->Users))->read(new ContentParams('', 'all'));
$categoryArr = $statusArr = (new Status($App->Users->team))->read(new ContentParams());
if ($Request->query->get('type') !== 'experiments') {
    $categoryArr = $itemsTypesArr;
}

// TEAM GROUPS
$TeamGroups = new TeamGroups($App->Users);
$teamGroupsArr = $TeamGroups->read(new ContentParams());
$visibilityArr = $TeamGroups->getVisibilityList();

$usersArr = $App->Users->readAllFromTeam();

// ANDOR
$andor = ' AND ';
if ($Request->query->has('andor') && $Request->query->get('andor') === 'or') {
    $andor = ' OR ';
}

// WHERE do we search?
if ($Request->query->get('type') === 'experiments') {
    $Entity = $Experiments;
} else {
    $Entity = $Database;
}

// ARE WE STRICT?
// strict mode means we don't add wildcard characters around the query
$isStrict = false;
if ($Request->query->get('strict') === 'on') {
    $isStrict = true;
}

// TITLE
$title = '';
if ($Request->query->has('title') && !empty($Request->query->get('title'))) {
    $title = filter_var(trim($Request->query->get('title')), FILTER_SANITIZE_STRING);
    if ($title !== false) {
        $Entity->titleFilter = Tools::getSearchSql($title, $andor, 'title', $isStrict);
    }
}

// BODY
$body = '';
if ($Request->query->has('body') && !empty($Request->query->get('body'))) {
    $body = filter_var(trim($Request->query->get('body')), FILTER_SANITIZE_STRING);
    if ($body !== false) {
        $Entity->bodyFilter = Tools::getSearchSql($body, $andor, 'body', $isStrict);
    }
}

// VISIBILITY
$vis = '';
if ($Request->query->has('vis') && !empty($Request->query->get('vis'))) {
    $vis = Check::visibility($Request->query->get('vis'));
}

// FROM
$from = '';
if ($Request->query->has('from') && !empty($Request->query->get('from'))) {
    $from = Filter::kdate($Request->query->get('from'));
}

// TO
$to = '';
if ($Request->query->has('to') && !empty($Request->query->get('to'))) {
    $to = Filter::kdate($Request->query->get('to'));
}

// RENDER THE FIRST PART OF THE PAGE (search form)
$renderArr = array(
    'Request' => $Request,
    'Experiments' => $Experiments,
    'Database' => $Database,
    'andor' => $andor,
    'body' => $body,
    'categoryArr' => $categoryArr,
    'itemsTypesArr' => $itemsTypesArr,
    'tagsArr' => $tagsArr,
    'teamGroupsArr' => $teamGroupsArr,
    'title' => $title,
    'statusArr' => $statusArr,
    'usersArr' => $usersArr,
    'visibilityArr' => $visibilityArr,
);
echo $App->render('search.html', $renderArr);

/**
 * Here the search begins
 * If there is a search, there will be get parameters, so this is our main switch
 */
if ($Request->query->count() > 0) {

    // STATUS
    $status = '';
    if (Check::id((int) $Request->query->get('status')) !== false) {
        $status = $Request->query->get('status');
    }

    // RATING
    $rating = null;
    $allowedRatings = array('null', '1', '2', '3', '4', '5');
    if (in_array($Request->query->get('rating'), $allowedRatings, true)) {
        $rating = $Request->query->get('rating');
    }

    // PREPARE SQL query

    /////////////////////////////////////////////////////////////////
    if ($Request->query->has('type')) {
        // Tag search
        if (!empty($Request->query->get('tags'))) {
            // get all the ids with that tag
            $ids = $Entity->Tags->getIdFromTags($Request->query->get('tags'), (int) $App->Users->userData['team']);
            if (count($ids) > 0) {
                $idFilter = ' AND (';
                foreach ($ids as $id) {
                    $idFilter .= 'entity.id = ' . $id . ' OR ';
                }
                $idFilter = rtrim($idFilter, ' OR ');
                $idFilter .= ')';
                $Entity->idFilter = $idFilter;
            }
        }

        // Visibility search
        if (!empty($vis)) {
            $Entity->addFilter('entity.canread', $vis);
        }

        // Date search
        if (!empty($from) && !empty($to)) {
            $Entity->dateFilter = " AND entity.date BETWEEN '$from' AND '$to'";
        } elseif (!empty($from) && empty($to)) {
            $Entity->dateFilter = " AND entity.date BETWEEN '$from' AND '99991212'";
        } elseif (empty($from) && !empty($to)) {
            $Entity->dateFilter = " AND entity.date BETWEEN '00000101' AND '$to'";
        }

        // Rating search
        if (!empty($rating)) {
            // rating is whitelisted here
            $Entity->addFilter('entity.rating', $rating);
        }


        if ($Request->query->get('type') === 'experiments') {

            // USERID FILTER
            if ($Request->query->has('owner')) {
                $owner = $App->Users->userData['userid'];
                if (Check::id((int) $Request->query->get('owner')) !== false) {
                    $owner = $Request->query->get('owner');
                }
                // all the team is 0 as userid
                if ($Request->query->get('owner') !== '0') {
                    $Entity->addFilter('entity.userid', $owner);
                }
            }

            // Status search
            if (!empty($status)) {
                $Entity->addFilter('entity.category', $status);
            }
        } else {
            // FILTER ON DATABASE ITEMS TYPES
            if (Check::id((int) $Request->query->get('type')) !== false) {
                $Entity->addFilter('categoryt.id', $Request->query->get('type'));
            }
        }


        try {
            $Controller = new SearchController($App, $Entity);
            echo $Controller->show(true)->getContent();
        } catch (ImproperActionException $e) {
            echo Tools::displayMessage($e->getMessage(), 'ko', false);
        }
    }
} else {
    // no search
    echo $App->render('todolist.html', array());
    echo $App->render('footer.html', array());
}
