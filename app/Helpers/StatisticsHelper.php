<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class StatisticsHelper
{
    // PDM Purchase & PDM Finance
    public static function collectPDMStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'paid $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action IN ('Vehicle Purchased', 'Vehicle Financed') GROUP BY date ORDER BY timestamp DESC");
    }

    // EDM Purchase
    public static function collectEDMStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1), ',', '')) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'EDM Purchase' GROUP BY date ORDER BY timestamp DESC");
    }

    // Special Imports Purchase
    public static function collectSpecialImportsStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), ' (', 1), ',', '')) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Special Imports' GROUP BY date ORDER BY timestamp DESC");
    }

    // Tunershop Purchase
    public static function collectTunerStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1), ',', '')) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Tunershop Purchase' GROUP BY date ORDER BY timestamp DESC");
    }

    // Gemstone sales
    public static function collectGemSaleStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Sold Gems' GROUP BY date ORDER BY timestamp DESC");
    }

    // Pawnshop sales
    public static function collectPawnshopStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Used Pawn Shop' GROUP BY date ORDER BY timestamp DESC");
    }

    // Material Vendor sales
    public static function collectMaterialVendorStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Sold Materials' GROUP BY date ORDER BY timestamp DESC");
    }

    // Casino revenue
    public static function collectCasinoStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, -SUM(money_won) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from casino_logs GROUP BY date ORDER BY timestamp DESC");
    }

    // Drug sales
    public static function collectDrugSaleStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action IN ('Sold Cocaine', 'Sold Weed', 'Sold Acid', 'Sold Lean', 'Sold Meth', 'Sold Moonshine', 'Sold Shrooms') GROUP BY date ORDER BY timestamp DESC");
    }

    // Store sales
    public static function collectStoreSaleStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), 'with', 1) + SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'additional $', -1), 'due', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Item(s) Purchased' GROUP BY date ORDER BY timestamp DESC");
    }

    // Store paychecks
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

    // Impounds (count)
    public static function collectImpoundsStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'got $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Impound' GROUP BY date ORDER BY timestamp DESC");
    }

    // Robbed Peds (count)
    public static function collectRobbedPedsStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Robbed Ped' GROUP BY date ORDER BY timestamp DESC");
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

    // Scratched Tickets
    public static function collectScratchTicketStatistics(): array
    {
        return self::collectStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'and won $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Scratched Ticket' GROUP BY date ORDER BY timestamp DESC");
    }

    private static function collectUserLogsCountStatistics(string ...$action): array
    {
        if (sizeof($action) === 1) {
            return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action = '{$action[0]}' GROUP BY date ORDER BY timestamp DESC");
        }

        $action = implode("', '", $action);

        return self::collectStatistics("SELECT 0 as count, COUNT(id) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date FROM user_logs WHERE action IN ('{$action}') GROUP BY date ORDER BY timestamp DESC");
    }

    private static function collectStatistics(string $query): array
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
