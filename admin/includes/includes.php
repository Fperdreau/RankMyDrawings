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

@session_start();
// Includes required files (classes)
require_once($_SESSION['path_to_includes'].'includes.php');
require_once('functions.php');
date_default_timezone_set('Europe/Paris');

// Get site config
$config_file = $_SESSION['path_to_app']."admin/conf/config.php";
if (is_file($config_file)) {
    require_once($config_file);
}
