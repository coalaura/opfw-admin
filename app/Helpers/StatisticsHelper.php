<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class StatisticsHelper
{
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
        $count = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'sold ', -1), ' `', 1)");
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)");

        return self::collectStatistics("SELECT SUM($count) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Used Pawn Shop' GROUP BY date ORDER BY timestamp DESC");
    }

    // Material Vendor sales
    public static function collectMaterialVendorStatistics(): array
    {
        $count = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'sold ', -1), 'x', 1)");
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
        $count = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'purchased ', -1), 'x', 1)");
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

    // Bus Driver revenue
    public static function collectBusDriverStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE details IN ('bus_driver_mission', 'bus-driver-mission') GROUP BY date ORDER BY timestamp DESC");
    }

    // LS Customs revenue
    public static function collectLSCustomsStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE details 'ls-customs-purchase' GROUP BY date ORDER BY timestamp DESC");
    }

    // Found items revenue
    public static function collectFoundItemsStatistics(): array
    {
        $count = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'sold ', -1), ' `', 1)");
        $amount = self::number("SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)");
        $items = implode(' OR ', array_map(function($name) {
            return "SUBSTRING_INDEX(SUBSTRING_INDEX(details, '`', -2), '`', 1) = '$name'";
        }, [
            'Small Frog',
            'Lucky Penny',
            'Caterpillar',
            '4 Leaf Clover',
            'Small Frog MK2',
            'Seashell'
        ]));

        return self::collectStatistics("SELECT SUM($count) as count, SUM($amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Used Pawn Shop' AND ($items) GROUP BY date ORDER BY timestamp DESC");
    }

    // Generic Economy Statistics
    public static function collectGenericEconomyStatistics(): array
    {
        $start = microtime(true);

        $data = DB::select("SELECT details, SUM(amount) as amount, COUNT(id) as count FROM money_logs WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY) AND details IS NOT NULL GROUP BY details ORDER BY amount DESC");

        return [
            'data' => $data,
            'time' => round((microtime(true) - $start) * 1000),
        ];
    }

    // Specific Money Statistics
    public static function collectSpecificMoneyStatistics(array $types): array
    {
        $cleanTypes = implode(', ', array_filter(array_map(function($type) {
            return '"' . preg_replace('/[^\w-]/', '', $type) . '"';
        }, $types)));

        return DB::select("SELECT details, COUNT(id) as count, SUM(amount) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from money_logs WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY) AND details IN ({$cleanTypes}) GROUP BY date, details ORDER BY timestamp DESC");
    }

    public static function collectUserLogsCountStatistics(string ...$action): array
    {
        if (sizeof($action) === 1) {
            return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = '{$action[0]}' GROUP BY date ORDER BY timestamp DESC");
        }

        $action = implode("', '", $action);

        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action IN ('{$action}') GROUP BY date ORDER BY timestamp DESC");
    }

    public static function collectStatistics(string $query): array
    {
        $start = microtime(true);

        $result = [];

        $data = DB::select($query);

        for ($i = 0; $i <= 30; $i++) {
            $time = strtotime("-$i days");
            $date = date('n/d/Y', $time);

            $entry = self::findEntry($data, $date);

            $amount = $entry && $entry->amount ? intval($entry->amount) : 0;
            $count  = $entry && $entry->count ? intval($entry->count) : 0;

            $result[] = [
                'date'   => date('jS F Y', $time),
                'amount' => $amount,
                'count'  => $count,
            ];
        }

        array_reverse($result);

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
