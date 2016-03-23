<?php
namespace Pecee\Model\User;

use Pecee\Date;
use Pecee\Model\Model;

class UserBadLogin extends Model {

    protected $columns = [
        'id',
        'username',
        'created_date',
        'ip',
        'active'
    ];

	public function __construct() {

		parent::__construct();

        $this->ip = request()->getIp();
        $this->created_date = Date::toDateTime();
	}

    public static function track($username) {
        $login = new static ();
        $login->username = $username;
        $login->save();
    }

	public static function checkBadLogin() {

        $trackQuery = self::fetchOne('SELECT `created_date`, COUNT(`ip`) AS `request_count` FROM {table} WHERE `ip` = %s AND `active` = 1 GROUP BY `ip` ORDER BY `request_count` DESC', request()->getIp());

        if($trackQuery->hasRow()) {
            $lastLoginTimeStamp = $trackQuery->created_date;
            $countRequestsFromIP = $trackQuery->request_count;
            $lastLoginMinutesAgo = round((time()-strtotime($lastLoginTimeStamp))/60);
            return ($lastLoginMinutesAgo < 30 && $countRequestsFromIP > 20);
        }
        return false;
	}

	public static function reset() {
        self::nonQuery('UPDATE {table} SET `active` = 0 WHERE `ip` = %s', request()->getIp());
	}
}