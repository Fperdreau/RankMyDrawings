<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
=======
This file is part of RankMyDrawings.

RankMyDrawings is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

RankMyDrawings is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with RankMyDrawings.  If not, see <http://www.gnu.org/licenses/>.

*/
class FullBackup extends AppCron {
    /**
     * Assign chairmen for the next n sessions
     * @return bool
     */

    public $name = 'FullBackup';
    public $path;
    public $status = 'Off';
    public $installed = False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options;

    /**
     * Constructor
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        $this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

    /**
     * Register the plugin in the db
     * @return bool|mysqli_result
     */
    public function install() {
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Run job
     * @return string
     */
    public function run() {
        // db backup
        $backupfile = backup_db(); // backup database
        mail_backup($backupfile); // Send backup file to admins

        // file backup
        $zipfile = file_backup(); // Backup site files (archive)
        $filelink = $zipfile;
        // Write log only if server request
        $result = "Full Backup successfully done.";
        $this->logger("$this->name.txt",$result);
        return $filelink;
    }
}