<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Maps;

use function array_key_exists;
use function ctype_alpha;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MapInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use PDO;

/**
 * Preferences for a user
 */
class UserPreferences implements MapInterface
{
    private Db $Db;

    private int $limit = 15;

    private string $displaySize = 'lg';

    // Can have two values: 'it' for item list (the default mode)
    // and 'tb' for tabular view
    private string $displayMode = 'it';

    private string $orderby = 'date';

    private int $singleColumnLayout = 0;

    private int $uploadsLayout = 1;

    /** @var array<string, string> $shortcuts */
    private array $shortcuts = array(
        'create' => 'c',
        'edit' => 'e',
        'submit' => 's',
        'todo' => 't',
    );

    private string $sort = 'desc';

    private int $showTeam = 0;

    private int $showTeamTemplates = 0;

    private int $showPublic = 0;

    private int $cjkFonts = 0;

    private int $pdfa = 1;

    private string $pdfFormat = 'A4';

    private int $useMarkdown = 0;

    private int $useIsodate = 0;

    private int $incFilesPdf = 1;

    private int $appendPdfs = 0;

    private int $chemEditor = 0;

    private string $lang = 'en_GB';

    private string $defaultRead = 'team';

    private string $defaultWrite = 'user';

    public function __construct(private int $id)
    {
        $this->Db = Db::getConnection();
        $this->hydrate($this->read());
    }

    public function save(): bool
    {
        $sql = 'UPDATE users SET
            limit_nb = :new_limit,
            display_size = :new_display_size,
            display_mode = :new_display_mode,
            orderby = :new_orderby,
            sort = :new_sort,
            sc_create = :new_sc_create,
            sc_edit = :new_sc_edit,
            sc_submit = :new_sc_submit,
            sc_todo = :new_sc_todo,
            show_team = :new_show_team,
            show_team_templates = :new_show_team_templates,
            show_public = :new_show_public,
            chem_editor = :new_chem_editor,
            lang = :new_lang,
            default_read = :new_default_read,
            default_write = :new_default_write,
            single_column_layout = :new_layout,
            uploads_layout = :new_uploads_layout,
            cjk_fonts = :new_cjk_fonts,
            pdfa = :new_pdfa,
            pdf_format = :new_pdf_format,
            use_markdown = :new_use_markdown,
            use_isodate = :new_use_isodate,
            inc_files_pdf = :new_inc_files_pdf,
            append_pdfs = :new_append_pdfs
            WHERE userid = :userid;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':new_limit', $this->limit);
        $req->bindParam(':new_display_size', $this->displaySize);
        $req->bindParam(':new_display_mode', $this->displayMode);
        $req->bindParam(':new_orderby', $this->orderby);
        $req->bindParam(':new_sort', $this->sort);
        $req->bindParam(':new_sc_create', $this->shortcuts['create']);
        $req->bindParam(':new_sc_edit', $this->shortcuts['edit']);
        $req->bindParam(':new_sc_submit', $this->shortcuts['submit']);
        $req->bindParam(':new_sc_todo', $this->shortcuts['todo']);
        $req->bindParam(':new_show_team', $this->showTeam);
        $req->bindParam(':new_show_team_templates', $this->showTeamTemplates);
        $req->bindParam(':new_show_public', $this->showPublic);
        $req->bindParam(':new_chem_editor', $this->chemEditor);
        $req->bindParam(':new_lang', $this->lang);
        $req->bindParam(':new_default_read', $this->defaultRead);
        $req->bindParam(':new_default_write', $this->defaultWrite);
        $req->bindParam(':new_layout', $this->singleColumnLayout);
        $req->bindParam(':new_uploads_layout', $this->uploadsLayout);
        $req->bindParam(':new_cjk_fonts', $this->cjkFonts);
        $req->bindParam(':new_pdfa', $this->pdfa);
        $req->bindParam(':new_pdf_format', $this->pdfFormat);
        $req->bindParam(':new_use_markdown', $this->useMarkdown);
        $req->bindParam(':new_use_isodate', $this->useIsodate);
        $req->bindParam(':new_inc_files_pdf', $this->incFilesPdf);
        $req->bindParam(':new_append_pdfs', $this->appendPdfs);
        $req->bindParam(':userid', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    final public function setLimit(string $setting): void
    {
        $this->limit = Check::limit((int) $setting);
    }

    final public function setDisplaySize(string $setting): void
    {
        $this->displaySize = Check::displaySize($setting);
    }

    final public function setDisplayMode(string $setting): void
    {
        $this->displayMode = Check::displayMode($setting);
    }

    final public function setSort(string $setting): void
    {
        $this->sort = Check::sort($setting);
    }

    final public function setOrderby(string $setting): void
    {
        $this->orderby = Check::orderby($setting);
    }

    final public function setSingleColumnLayout(string $setting): void
    {
        $this->singleColumnLayout = Filter::toBinary($setting);
    }

    final public function setUploadsLayout(string $setting): void
    {
        $this->uploadsLayout = Filter::toBinary($setting);
    }

    final public function setShortcut(string $shortcut, string $setting): void
    {
        // take the first letter only
        $key = $setting[0];
        if (ctype_alpha($key)) {
            $this->shortcuts[$shortcut] = $key;
        }
    }

    final public function setShowTeam(string $setting): void
    {
        $this->showTeam = Filter::toBinary($setting);
    }

    final public function setShowTeamTemplates(string $setting): void
    {
        $this->showTeamTemplates = Filter::toBinary($setting);
    }

    final public function setShowPublic(string $setting): void
    {
        $this->showPublic = Filter::toBinary($setting);
    }

    final public function setCjkFonts(string $setting): void
    {
        $this->cjkFonts = Filter::toBinary($setting);
    }

    final public function setPdfa(string $setting): void
    {
        $this->pdfa = Filter::toBinary($setting);
    }

    final public function setPdfFormat(string $setting): void
    {
        $allowed = array('A4', 'LETTER', 'ROYAL');
        if (in_array($setting, $allowed, true)) {
            $this->pdfFormat = $setting;
        }
    }

    final public function setUseMarkdown(string $setting): void
    {
        $this->useMarkdown = Filter::toBinary($setting);
    }

    final public function setUseIsodate(string $setting): void
    {
        $this->useIsodate = Filter::toBinary($setting);
    }

    final public function setIncFilesPdf(string $setting): void
    {
        $this->incFilesPdf = Filter::toBinary($setting);
    }

    final public function setAppendPdfs(string $setting): void
    {
        $this->appendPdfs = Filter::toBinary($setting);
    }

    final public function setChemEditor(string $setting): void
    {
        $this->chemEditor = Filter::toBinary($setting);
    }

    final public function setLang(string $setting): void
    {
        if (array_key_exists($setting, Tools::getLangsArr())) {
            $this->lang = $setting;
        }
    }

    final public function setDefaultRead(string $setting): void
    {
        $this->defaultRead = Check::visibility($setting);
    }

    final public function setDefaultWrite(string $setting): void
    {
        $this->defaultWrite = Check::visibility($setting);
    }

    /**
     * Fill this object's properties from the source
     * Source can be sql query or post data
     *
     * @param array<string, mixed> $source
     */
    public function hydrate(array $source): void
    {
        $this->setLimit($source['limit_nb'] ?? $this->limit);
        $this->setLang($source['lang'] ?? $this->lang);
        $this->setDisplaySize($source['display_size'] ?? $this->displaySize);
        $this->setDisplayMode($source['display_mode'] ?? $this->displayMode);
        $this->setSort($source['sort'] ?? $this->sort);
        $this->setOrderby($source['orderby'] ?? $this->orderby);
        $this->setSingleColumnLayout($source['single_column_layout'] ?? '0');
        $this->setUploadsLayout($source['uploads_layout'] ?? '0');
        $this->setShortcut('create', $source['sc_create'] ?? $this->shortcuts['create']);
        $this->setShortcut('edit', $source['sc_edit'] ?? $this->shortcuts['edit']);
        $this->setShortcut('submit', $source['sc_submit'] ?? $this->shortcuts['submit']);
        $this->setShortcut('todo', $source['sc_todo'] ?? $this->shortcuts['todo']);
        $this->setShowTeam($source['show_team'] ?? '0');
        $this->setShowTeamTemplates($source['show_team_templates'] ?? '0');
        $this->setShowPublic($source['show_public'] ?? '0');
        $this->setCjkFonts($source['cjk_fonts'] ?? '0');
        $this->setPdfa($source['pdfa'] ?? '0');
        $this->setPdfFormat($source['pdf_format'] ?? $this->pdfFormat);
        $this->setUseMarkdown($source['use_markdown'] ?? '0');
        $this->setUseIsodate($source['use_isodate'] ?? '0');
        $this->setIncFilesPdf($source['inc_files_pdf'] ?? '0');
        $this->setAppendPdfs($source['append_pdfs'] ?? '0');
        $this->setChemEditor($source['chem_editor'] ?? '0');
        $this->setDefaultRead($source['default_read'] ?? $this->defaultRead);
        $this->setDefaultWrite($source['default_write'] ?? $this->defaultWrite);
    }

    /**
     * Read from the current team
     */
    private function read(): array
    {
        $sql = 'SELECT limit_nb,
            display_size,
            display_mode,
            sort,
            orderby,
            single_column_layout,
            uploads_layout,
            sc_create,
            sc_edit,
            sc_submit,
            sc_todo,
            show_team,
            show_team_templates,
            show_public,
            cjk_fonts,
            pdfa,
            pdf_format,
            use_markdown,
            use_isodate,
            inc_files_pdf,
            append_pdfs,
            chem_editor,
            default_read,
            default_write
            FROM users WHERE userid = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch();
        if ($res === false) {
            throw new ImproperActionException('Could not find a user with that id!');
        }

        return $res;
    }
}
