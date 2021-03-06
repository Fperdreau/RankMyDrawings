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

/**
 * Class SessionInstance
 */
class SessionInstance {
    protected static $_instance;
    private static $timeout = 3600;

    private function __construct() {
        session_start();
        session_set_cookie_params(self::$timeout);
    }

    function __destruct() {
        session_write_close();
    }

    public static function initsession() {
        if(self::$_instance === null)
            self::$_instance = new self();
    }

    public function set($key,$value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        return $_SESSION[$key];
    }
}