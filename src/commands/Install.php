<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use const DB_NAME;
use function dirname;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Sql;
use Elabftw\Services\DatabaseInstaller;
use const SITE_URL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import database structure
 */
class Install extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'start';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Install eLabFTW in a MySQL database')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Ask information to connect to the MySQL database, create the config file and load the database structure.')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Delete and recreate the database before installing the structure.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        require_once dirname(__DIR__, 2) . '/config.php';
        $Db = Db::getConnection();

        $req = $Db->q('SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = "' . DB_NAME . '"');
        $res = $req->fetch();
        if ((int) $res['cnt'] > 1 && !$input->getOption('reset')) {
            $output->writeln('<info>→ Database structure already present. Skipping initialization.</info>');
            return 0;
        }

        $output->writeln(array(
            '',
            '      _          _     _____ _______        __',
            "  ___| |    __ _| |__ |  ___|_   _\ \      / /",
            " / _ \ |   / _| | '_ \| |_    | |  \ \ /\ / / ",
            "|  __/ |__| (_| | |_) |  _|   | |   \ V  V /  ",
            " \___|_____\__,_|_.__/|_|     |_|    \_/\_/   ",
            '                                              ',
            '',
        ));

        $output->writeln(array(
            '<info>Welcome to the install of eLabFTW!</info>',
            '<info>This program will install the MySQL structure.</info>',
            '<info>Before proceeding, make sure you have an empty MySQL database for eLabFTW with a user+password to access it.</info>',
            '',
        ));

        if ($input->getOption('reset')) {
            $output->writeln('<info>→ Resetting MySQL database...</info>');
            $Db->q('DROP DATABASE ' . DB_NAME);
            $Db->q('CREATE DATABASE ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
            $Db->q('USE ' . DB_NAME);
        }

        $output->writeln('<info>→ Initializing MySQL database...</info>');
        $sqlFs = FsTools::getFs(dirname(__DIR__) . '/sql');
        $Installer = new DatabaseInstaller(new Sql($sqlFs));
        $Installer->install();
        $output->writeln('<info>✓ Installation successful! You can now start using your eLabFTW instance.</info>');
        $output->writeln('<info>→ Register your Sysadmin account here: ' . SITE_URL . '/register.php</info>');
        $output->writeln('<info>→ Subscribe to the low volume newsletter to stay informed about new releases: http://eepurl.com/bTjcMj</info>');
        return 0;
    }
}
