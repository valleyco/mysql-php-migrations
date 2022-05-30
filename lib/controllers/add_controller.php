<?php
/**
 * This file houses the MpmAddController class.
 *
 * @package    mysql_php_migrations
 * @subpackage Controllers
 * @license    http://www.opensource.org/licenses/bsd-license.php  The New BSD License
 * @link       http://code.google.com/p/mysql-php-migrations/
 */

/**
 * The MpmAddController is used to create a new migration script.
 *
 * @package    mysql_php_migrations
 * @subpackage Controllers
 */
class MpmAddController extends MpmController
{
    /**
     * Determines what action should be performed and takes that action.
     *
     * @uses MPM_DB_PATH
     * @uses MpmDbHelper::test()
     * @uses MpmListHelper::getFiles()
     * @uses MpmCommandLineWriter::getInstance()
     * @uses MpmCommandLineWriter::addText()
     * @uses MpmCommandLineWriter::write()
     * @uses MpmDbHelper::getMethod()
     * @uses MpmUpController::displayHelp()
     *
     * @return void
     */
    public function doAction()
    {
        // make sure system is init'ed
        MpmDbHelper::test();
        $migration_type = $this->arguments[0] ?? '--sql';

        if ( ! in_array($migration_type, ['--php', '--sql'])) {
            return $this->displayHelp();
        }
        // get date stamp for use in generating filename
        $date_stamp = date('Y_m_d_H_i_s');
        $filename = $date_stamp . '.php';
        $vars = ['timestamp' => $date_stamp];

        // get list of files
        $files = MpmListHelper::getFiles();

        // if filename is taken, throw error
        if (in_array($filename, $files)) {
            $obj = MpmCommandLineWriter::getInstance();
            $obj->addText('Unable to obtain a unique filename for your migration.  Please try again in a few seconds.');
            $obj->write();
        }

        // create file
        if (MpmDbHelper::getMethod() == MPM_METHOD_PDO) {
            $file = $migration_type =='--sql'
                ? MpmTemplateHelper::getTemplate('pdo_sql_migration.txt', $vars)
                : MpmTemplateHelper::getTemplate('pdo_migration.txt', $vars);
        } else {
            $file = $migration_type =='--sql'
                ? MpmTemplateHelper::getTemplate('mysqli_sql_migration.txt', $vars)
                : MpmTemplateHelper::getTemplate('mysqli_migration.txt', $vars);
        }

        // write the file
        $fp = fopen(MPM_DB_PATH . $filename, 'w');

        if ($fp == false) {
            $obj = MpmCommandLineWriter::getInstance();
            $obj->addText('Unable to write new migration file.');
            $obj->write();
        }
        $success = fwrite($fp, $file);

        if ($success == false) {
            $obj = MpmCommandLineWriter::getInstance();
            $obj->addText('Unable to write new migration file.');
            $obj->write();
        }
        fclose($fp);

        if ($migration_type == '--sql') {
            file_put_contents(MPM_DB_PATH ."${date_stamp}_up.sql", "-- put your up sql statements here\n");
            file_put_contents(MPM_DB_PATH ."${date_stamp}_down.sql", "-- put your down sql statements here\n");
        }
        // display success message
        $obj = MpmCommandLineWriter::getInstance();
        $obj->addText('New migration created: file '. MPM_DB_PATH . $filename);
        $obj->write();
    }

    /**
     * Displays the help page for this controller.
     *
     * @uses MpmCommandLineWriter::getInstance()
     * @uses MpmCommandLineWriter::addText()
     * @uses MpmCommandLineWriter::write()
     *
     * @return void
     */
    public function displayHelp()
    {
        $obj = MpmCommandLineWriter::getInstance();
        $obj->addText('./migrate.php add [--sql|--php]');
        $obj->addText(' ');
        $obj->addText('This command is used to create a new migration script.');
        $obj->addText('  --php - The script will be created and prepopulated with the up() and down() methods which you can then modify for the migration.');
        $obj->addText('  --sql - sql files will be generated for the up and down actions which you can then edit.');
        $obj->addText(' default is sql ');
        $obj->addText('Valid Example:');
        $obj->addText('./migrate.php add', 4);
        $obj->write();
    }
}
