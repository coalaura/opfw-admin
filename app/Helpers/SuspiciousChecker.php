<?php

namespace App\Helpers;

use App\Character;
use App\Helpers\GeneralHelper;
use App\Player;
use Illuminate\Support\Facades\DB;

class SuspiciousChecker
{
    const CacheTime = 10 * CacheHelper::MINUTE;

    const IgnoreUnusualMovement = [
        'Snowballs'      => 1500,
        'Water'          => 1500,
        'Weed 1oz'       => 800,
        'Weed 1q'        => 800,
        'Cocaine Bag'    => 800,
        'Acid'           => 800,
        'Fertilizer'     => 500,
        'Sub Ammo'       => 750,
        'Rifle Ammo'     => 750,
        'Shotgun Ammo'   => 500,
        'Cheeseburger'   => 900,
        'Hamburger'      => 900,
        'Thermite'       => 500,
        'Coke'           => 900,
        'Banana'         => 900,
        'First Aid Kit'  => 400,
        'Weed Seeds'     => 800,
        'Joint'          => 800,
        'Cigarette'      => 800,
        'Scrap Metal'    => 800,
        'Glass'          => 800,
        'Pistol Ammo'    => 600,
        'Aluminium'      => 600,
        'Steel'          => 600,
        'Rubber'         => 600,
        'Bucket'         => 800,
        'Silver Watches' => 350,
        'Gold Watches'   => 350,
        'Necklaces'      => 500,
    ];

    const IgnoreItems = [
        // Food
        'water'              => 800,
        'hamburger'          => 800,
        'belgian_fries'      => 800,
        'coke'               => 800,
        'wonder_waffle'      => 800,
        'cheeseburger'       => 800,
        'cheesecake'         => 800,
        'donut'              => 800,
        'green_apple'        => 800,
        'sandwich'           => 800,
        'taco'               => 800,
        'banana'             => 800,
        'smores'             => 800,

        // Drugs are irrelevant cause people hoard them
        'acid'               => 400,
        'cocaine_bag'        => 320,
        'weed_seeds'         => 250,
        'weed_1q'            => 250,
        'weed_1oz'           => 250,
        'oxy'                => 250,

        // Ammo same deal as with drugs
        'pistol_ammo'        => 340,
        'rifle_ammo'         => 340,
        'shotgun_ammo'       => 340,
        'sub_ammo'           => 340,

        // Materials cuz mechanics
        'scrap_metal'        => 350,
        'rubber'             => 350,
        'steel'              => 350,

        // Some general stuff people just like to hoard
        'evidence_bag_empty' => 500,
        'fertilizer'         => 250,
        'paper_bag'          => 500,
        'weapon_snowball'    => 900,
    ];

    private static function ignoreCharacterInventories(): array
    {
        $characters = Character::query()->select("character_id")->whereIn("license_identifier", self::getIgnorableLicenseIdentifiers())->get()->toArray();

        $inventories = [];

        foreach ($characters as $character) {
            $id = $character['character_id'];

            $inventories[] = 'character-' . $id;
            $inventories[] = 'locker-police-' . $id;
        }

        return $inventories;
    }

    /**
     * Finds item movements that have unusual amounts
     *
     * @return array
     */
    public static function findUnusualItems(): array
    {
        $key = 'unusual_item_movement_' . md5(json_encode(self::IgnoreUnusualMovement));

        if (CacheHelper::exists($key)) {
            return CacheHelper::read($key, []);
        }

        $sql = "SELECT * FROM (SELECT `identifier`, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(`details`, ' moved ', -1), ' to ', 1), 'x ', 1)) as `amount`, SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(`details`, ' moved ', -1), ' to ', 1), 'x ', -1) as `item`, `details`, CEIL(UNIX_TIMESTAMP(`timestamp`) / 600) * 600 as `time` FROM `user_logs` WHERE `action`='Item Moved' AND identifier NOT IN ('" . implode("', '", self::getIgnorableLicenseIdentifiers()) . "') GROUP BY CONCAT(`identifier`, '|', `time`, '|', `item`) ORDER BY `time` DESC) `logs` WHERE amount > 250";

        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $logs = DB::select($sql);

        $sus = [];
        foreach ($logs as $log) {
            if (isset(self::IgnoreUnusualMovement[$log->item]) && self::IgnoreUnusualMovement[$log->item] > intval($log->amount)) {
                continue;
            }

            $sus[] = [
                'identifier' => $log->identifier,
                'details'    => 'Moved ' . number_format(intval($log->amount)) . ' of ' . $log->item . ' in the span of 10 minutes',
                'timestamp'  => date('c', intval($log->time)),
            ];
        }

        CacheHelper::write($key, $sus, self::CacheTime);

        return $sus;
    }

    /**
     * Finds characters who have an unusual amount of money
     *
     * @return array
     */
    public static function findSuspiciousCharacters(): array
    {
        return DB::select("select license_identifier, characters.character_id, cash, bank, FLOOR(stocks_balance) as stocks_balance, first_name, last_name, COALESCE(SUM(savings_accounts.balance), 0) AS savings_balance, cash + bank + stocks_balance + COALESCE(SUM(savings_accounts.balance), 0) AS total_balance from characters left join savings_accounts on characters.character_id = savings_accounts.character_id where cash + bank + stocks_balance + COALESCE(savings_accounts.balance, 0) >= 1000000 group by characters.character_id order by total_balance desc");
    }

    /**
     * Finds entries where a lot of jewelry has been sold at a pawn shop
     *
     * @return array
     */
    public static function findSuspiciousPawnShopUsages(): array
    {
        $key = 'pawn_transactions';

        if (CacheHelper::exists($key)) {
            return CacheHelper::read($key, []);
        }

        $sql = "SELECT SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(`details`, '$', -1), '.', 1)) as `cash`, CEIL(UNIX_TIMESTAMP(`timestamp`) / 300) * 300 as `time`, `identifier` FROM `user_logs` WHERE action = 'Used Pawn Shop' GROUP BY CONCAT(`identifier`, '|', `time`) ORDER BY `time` DESC";

        $sus = self::getSaleLogEntries($sql, 100000, 'jewelry');

        CacheHelper::write($key, $sus, self::CacheTime);

        return $sus;
    }

    /**
     * Finds entries where a lot of materials have been sold at the warehouse
     *
     * @return array
     */
    public static function findSuspiciousWarehouseUsages(): array
    {
        $key = 'warehouse_transactions';

        if (CacheHelper::exists($key)) {
            return CacheHelper::read($key, []);
        }

        $sql = "SELECT SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(`details`, '$', -1), '.', 1)) as `cash`, CEIL(UNIX_TIMESTAMP(`timestamp`) / 300) * 300 as `time`, `identifier` FROM `user_logs` WHERE action = 'Sold materials' GROUP BY CONCAT(`identifier`, '|', `time`) ORDER BY `time` DESC";

        $sus = self::getSaleLogEntries($sql, 20000, 'materials');

        CacheHelper::write($key, $sus, self::CacheTime);

        return $sus;
    }

    /**
     * Parses [cash, identifier, time] arrays
     *
     * @param string $sql
     * @param int $threshold
     * @param string $name
     * @return array
     */
    private static function getSaleLogEntries(string $sql, int $threshold, string $name): array
    {
        DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        $logs = DB::select($sql);

        $sus = [];
        foreach ($logs as $log) {
            $cash = intval($log->cash);

            if ($cash > $threshold) {
                $sus[] = [
                    'identifier' => $log->identifier,
                    'details'    => 'Sold ' . $name . ' worth $' . number_format($cash) . ' in the span of 5 minutes',
                    'timestamp'  => date('c', intval($log->time)),
                ];
            }
        }

        return $sus;
    }

    private static function getIgnorableLicenseIdentifiers(): array
    {
        $ids = Player::query()->select(["license_identifier"])->where("is_super_admin", "=", 1)->orWhere("is_senior_staff", "=", 1)->get()->toArray();

        $ids = array_values(array_map(function ($entry) {
            return $entry["license_identifier"];
        }, $ids));

        $root = GeneralHelper::getRootUsers();
        foreach ($root as $user) {
            $ids[] = $user;
        }

        return $ids;
    }
}
