<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class StatisticsHelper
{
    // not weaponData.isMelee and not weaponData.throwable and not weaponData.isMisc
    // and its not weapon_addon_stungun, weapon_stungun or weapon_stungun_mp
    const Guns = [
        1752584910, -1660422300, 2100324592, -1045183535, 1627465347, 1119849093, 964555122, 1593441988, -924350237, -1355376991, 584646201, -771403250, 137902532, 1432025498, -340621788, 984333226, 100416529, 1198879012, 1305664598, -1746263880, -275439685, 687914362, 205991906, 453432689, -1238556825, -266763809, 727643628, -1716589765, 2138347493, -862975727, -947031628, 177293209, -1027401503, 1052850250, -1568386805, -1094502964, -86904375, -608341376, 62870901, -952879014, -810431678, 465894841, -1357824103, -270015777, -1466123874, -1923845809, -1312131151, 1834241177, 819155540, -618237638, 1198256469, 748372090, 1853742572, 731779237, -441697337, 1785463520, -1658906650, 826063196, -2084633992, -1840517646, 1460239560, 1045507099, -1021085081, -1121678507, -566293128, -18093114, 859191078, -977611140, -564480041, -496173278, -879347409, -624163738, 324215364, -1075685676, 1053051806, 1470379660, 125959754, -774507221, 2132975508, -1768145561, -1946516017, -598887786, 350597077, 317205821, 2144741730, -2066285827, -2009644972, 961495388, -1853920116, 487013001, 1649403952, -807467678, -619010992, 1924557585, -2115075845, 435594297, 171789620, 736523883, 94989220, -1122711209, -1654528753, 1672152130, 2017895192, -22923932, 2024373456, -1063057011, -494615257, -1076751822, -1074790547,
    ];

    private static function number(string $query): string
    {
        return "REPLACE(REPLACE($query, ',', ''), '.', '')";
    }

    // PDM Purchase & PDM Finance
    public static function collectPDMStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'paid $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action IN ('Vehicle Purchased', 'Vehicle Financed') GROUP BY date ORDER BY timestamp DESC");
    }

    // EDM Purchase
    public static function collectEDMStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'EDM Purchase' GROUP BY date ORDER BY timestamp DESC");
    }

    // Special Imports Purchase
    public static function collectSpecialImportsStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), ' (', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Special Imports' GROUP BY date ORDER BY timestamp DESC");
    }

    // Tunershop Purchase
    public static function collectTunerStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Tunershop Purchase' GROUP BY date ORDER BY timestamp DESC");
    }

    // Gemstone sales
    public static function collectGemSaleStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Sold Gems' GROUP BY date ORDER BY timestamp DESC");
    }

    // Pawnshop sales
    public static function collectPawnshopStatistics(): array
    {
        $count  = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'sold ', -1), ' `', 1)");
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)");

        return self::collectStatistics("SELECT SUM($count) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Used Pawn Shop' GROUP BY date ORDER BY timestamp DESC");
    }

    // Material Vendor sales
    public static function collectMaterialVendorStatistics(): array
    {
        $count  = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'sold ', -1), 'x', 1)");
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)");

        return self::collectStatistics("SELECT SUM($count) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Sold Materials' GROUP BY date ORDER BY timestamp DESC");
    }

    // Casino revenue
    public static function collectCasinoStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, -SUM(money_won) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from casino_logs GROUP BY date ORDER BY timestamp DESC");
    }

    // Drug sales
    public static function collectDrugSaleStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action IN ('Sold Cocaine', 'Sold Weed', 'Sold Acid', 'Sold Lean', 'Sold Meth', 'Sold Moonshine', 'Sold Shrooms') GROUP BY date ORDER BY timestamp DESC");
    }

    // Store sales
    public static function collectStoreSaleStatistics(): array
    {
        $count  = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'purchased ', -1), 'x', 1)");
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), 'with', 1)");

        return self::collectStatistics("SELECT SUM($count) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Item(s) Purchased' GROUP BY date ORDER BY timestamp DESC");
    }

    // Hourly paychecks
    public static function collectPaycheckStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM money_logs WHERE details = 'hourly-salary' GROUP BY date ORDER BY timestamp DESC");
    }

    // Robberies (count)
    public static function collectRobberiesStatistics(): array
    {
        return self::collectUserLogsCountStatistics("Store Robbery", "Bank Robbery", "Jewelry Store");
    }

    // Joins (count)
    public static function collectJoinsStatistics(): array
    {
        return self::collectUserLogsCountStatistics("User Joined");
    }

    // OOC Messages (count)
    public static function collectOOCMessagesStatistics(): array
    {
        return self::collectUserLogsCountStatistics("Global OOC message", "Local OOC message");
    }

    // Reports (count)
    public static function collectReportsStatistics(): array
    {
        return self::collectUserLogsCountStatistics("Report");
    }

    // Impounds
    public static function collectImpoundsStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'got $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Impound' GROUP BY date ORDER BY timestamp DESC");
    }

    // Robbed Peds
    public static function collectRobbedPedsStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Robbed Ped' GROUP BY date ORDER BY timestamp DESC");
    }

    // Daily Tasks (count)
    public static function collectDailyTasksStatistics(): array
    {
        return self::collectUserLogsCountStatistics("Daily Task Completed");
    }

    // Deaths (count)
    public static function collectDeathsStatistics(): array
    {
        return self::collectUserLogsCountStatistics("Player Killed", "Player Died");
    }

    // Crafted guns (count)
    public static function collectGunCraftingStatistics(): array
    {
        return self::collectUserLogsCountStatistics("Crafted Gun");
    }

    // Game crashed hourly (count)
    public static function collectGameCrashHourlyStatistics(): array
    {
        return self::collectStatistics("SELECT 0 as count, SUM(IF(details LIKE '%`Server->client%', 1, 0)) as amount, SUM(IF(details LIKE '%`Game crashed:%', 1, 0)) as amount2, DATE_FORMAT(timestamp, '%c/%d/%Y %H') as date FROM user_logs WHERE action = 'User Disconnected' AND (details LIKE '%`Server->client%' OR details LIKE '%`Game crashed:%') GROUP BY date ORDER BY timestamp DESC", 3, false, ["amount2"], false, true);
    }

    // Game crashed daily (count)
    public static function collectGameCrashDailyStatistics(): array
    {
        return self::collectStatistics("SELECT 0 as count, SUM(IF(details LIKE '%`Server->client%', 1, 0)) as amount, SUM(IF(details LIKE '%`Game crashed:%', 1, 0)) as amount2, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'User Disconnected' AND (details LIKE '%`Server->client%' OR details LIKE '%`Game crashed:%') GROUP BY date ORDER BY timestamp DESC", 30, false, ["amount2"]);
    }

    // Airlifts (count)
    public static function collectAirliftsStatistics(): array
    {
        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'Hospitalization' AND details LIKE '%airlifted%' GROUP BY date ORDER BY timestamp DESC");
    }

    // Mining Explosions (count)
    public static function collectMiningExplosionStatistics(): array
    {
        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = 'Mining Explosion' GROUP BY date ORDER BY timestamp DESC");
    }

    // Lucky Wheel Spins (count)
    public static function collectLuckyWheelStatistics(): array
    {
        $before = time() - 30 * 24 * 60 * 60;

        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(FROM_UNIXTIME(timestamp), '%c/%d/%Y') as date FROM lucky_wheel_spins WHERE timestamp > $before GROUP BY date ORDER BY timestamp DESC");
    }

    // Items found in Dumpsters (count)
    public static function collectDumpsterStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'moved ', -1), 'x', 1)");

        return self::collectStatistics("SELECT 0 as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Item Moved' AND details LIKE '%dumpster-%' GROUP BY date ORDER BY timestamp DESC");
    }

    // Scratched Tickets
    public static function collectScratchTicketStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'and won $', -1), '.', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Scratched Ticket' AND details NOT LIKE '%\$amount%' GROUP BY date ORDER BY timestamp DESC");
    }

    // Bills paid
    public static function collectBillsStatistics(): array
    {
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'paid the $', -1), ' (', 1)");

        return self::collectStatistics("SELECT COUNT(id) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Paid Bill' GROUP BY date ORDER BY timestamp DESC");
    }

    // Daily Activities paid refresh
    public static function collectDailyRefreshStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(-amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE details = 'daily-activities-refresh-task' GROUP BY date ORDER BY timestamp DESC");
    }

    // ATM Withdraw fees
    public static function collectATMWithdrawFeesStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(-amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE details = 'atm-withdraw-fee' GROUP BY date ORDER BY timestamp DESC");
    }

    // Bus Driver revenue
    public static function collectBusDriverStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE details IN ('bus_driver_mission', 'bus-driver-mission') GROUP BY date ORDER BY timestamp DESC");
    }

    // LS Customs revenue
    public static function collectLSCustomsStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(-amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE details = 'ls-customs-purchase' GROUP BY date ORDER BY timestamp DESC");
    }

    // Blackjack win chance
    public static function collectBlackjackWinStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(IF(money_won > 0, 1, 0)) / COUNT(id) * 100 as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from casino_logs WHERE game = 'blackjack' GROUP BY date ORDER BY timestamp DESC", 30, false, [], true);
    }

    // Shots fired (by guns damage dealt)
    public static function collectShotsFiredStatistics(): array
    {
        $unsignedGuns = array_map(function($hash) {
            if ($hash < 0) {
                return $hash + 2 ** 32; // Convert negative to unsigned 32-bit equivalent
            }

            return $hash;
        }, self::Guns);

        $whereIn = implode(', ', $unsignedGuns);

        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(FROM_UNIXTIME(ROUND(`timestamp` / 1000)), '%c/%d/%Y') as date FROM weapon_damage_events WHERE weapon_type IN ($whereIn) GROUP BY date ORDER BY `timestamp` DESC");
    }

    // Found items revenue
    public static function collectFoundItemsStatistics(): array
    {
        $count  = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'sold ', -1), ' `', 1)");
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)");
        $items  = implode(' OR ', array_map(function ($name) {
            return "SUBSTRING_INDEX(SUBSTRING_INDEX(details, '`', -2), '`', 1) = '$name'";
        }, [
            'Small Frog',
            'Lucky Penny',
            'Caterpillar',
            '4 Leaf Clover',
            'Small Frog MK2',
            'Seashell',
        ]));

        return self::collectStatistics("SELECT SUM($count) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Used Pawn Shop' AND ($items) GROUP BY date ORDER BY timestamp DESC");
    }

    // General economy statistics
    public static function collectEconomyStatistics(): array
    {
        return DB::select("SELECT DATE_FORMAT(STR_TO_DATE(date, '%d.%m.%Y %H:%i'), '%d.%m.%Y') as date, SUM(cash) / SUM(1) as cash, SUM(bank) / SUM(1) as bank, SUM(stocks) / SUM(1) as stocks, SUM(savings) / SUM(1) as savings, SUM(shared) / SUM(1) as shared, SUM(bonds) / SUM(1) as bonds, MAX(richest) as richest, MAX(poorest) as poorest FROM economy_statistics GROUP BY date ORDER BY STR_TO_DATE(date, '%d.%m.%Y %H:%i') ASC");
    }

    // General user statistics
    public static function collectUserStatistics(): array
    {
        return DB::select("SELECT date, total_joins, max_joined, max_queue, JSON_LENGTH(joined_users) as joined_users FROM user_statistics ORDER BY STR_TO_DATE(date, '%d.%m.%Y') ASC");
    }

    // General fps statistics
    public static function collectFPSStatistics(): array
    {
        return DB::select("SELECT date, minimum, maximum, average, average_1_percent FROM fps_statistics WHERE STR_TO_DATE(date, '%d.%m.%Y %H:%i') >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY STR_TO_DATE(date, '%d.%m.%Y %H:%i') ASC");
    }

    // Anti-cheat statistics
    public static function collectAntiCheatStatistics(array $ignoreTypes): array
    {
        $whereNot = implode(' AND ', array_map(function ($type) {
            return "type != '$type'";
        }, $ignoreTypes));

        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(FROM_UNIXTIME(timestamp), '%c/%d/%Y') as date, timestamp FROM anti_cheat_events WHERE {$whereNot} GROUP BY date", 30, true);
    }

    // Specific Money Statistics
    public static function collectSpecificMoneyStatistics(array $types): array
    {
        $cleanTypes = implode(', ', array_filter(array_map(function ($type) {
            return '"' . preg_replace('/[^\w-]/', '', $type) . '"';
        }, $types)));

        return DB::select("SELECT details, SUM(amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY) AND details IN ({$cleanTypes}) GROUP BY date, details ORDER BY timestamp DESC");
    }

    public static function collectUserLogsCountStatistics(string ...$action): array
    {
        if (sizeof($action) === 1) {
            return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = '{$action[0]}' GROUP BY date ORDER BY timestamp DESC");
        }

        $action = implode("', '", $action);

        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action IN ('{$action}') GROUP BY date ORDER BY timestamp DESC");
    }

    public static function collectStatistics(string $query, int $days = 30, bool $showAll = false, $secondary = [], bool $decimals = false, bool $hourly = false): array
    {
        $start = microtime(true);

        $result = [];

        $data = DB::select($query);

        if ($showAll && !empty($data)) {
            $time = min(array_map(function ($entry) {
                return $entry->timestamp;
            }, $data));

            $days = ceil((time() - $time) / 86400);
        }

        if ($hourly) {
            $days *= 24;
        }

        $convert = $decimals ? 'floatval' : 'intval';

        for ($i = 0; $i <= $days; $i++) {
            $time = strtotime("-$i " . ($hourly ? "hours" : "days"));
            $date = date('n/d/Y' . ($hourly ? ' H' : ''), $time);

            $entry = self::findEntry($data, $date);

            $amount = $entry && $entry->amount ? call_user_func($convert, $entry->amount) : 0;
            $count  = $entry && $entry->count ? call_user_func($convert, $entry->count) : 0;

            $res = [
                'date'   => date('jS F Y' . ($hourly ? ' - H:00' : ''), $time),
                'amount' => $amount,
                'count'  => $count,
            ];

            foreach ($secondary as $second) {
                $val = $entry && isset($entry->{$second}) ? call_user_func($convert, $entry->{$second}) : 0;

                $res[$second] = $val;
            }

            $result[] = $res;
        }

        // array_reverse($result);

        return [
            'data' => $result,
            'time' => round((microtime(true) - $start) * 1000),
        ];
    }

    private static function findEntry(array $data, string $date)
    {
        foreach ($data as $entry) {
            if ($entry->date === $date) {
                return $entry;
            }
        }

        return null;
    }
}
