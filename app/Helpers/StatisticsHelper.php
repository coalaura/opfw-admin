<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class StatisticsHelper
{
    // PDM Purchase & PDM Finance
    public static function collectPDMStatistics(): array
    {
        return self::collectFinanceStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'paid $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action IN ('Vehicle Purchased', 'Vehicle Financed') GROUP BY date ORDER BY timestamp DESC");
    }

    // EDM Purchase
    public static function collectEDMStatistics(): array
    {
        return self::collectFinanceStatistics("SELECT COUNT(id) as count, SUM(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1), ',', '')) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'EDM Purchase' GROUP BY date ORDER BY timestamp DESC");
    }

    // Gemstone sales
    public static function collectGemSaleStatistics(): array
    {
        return self::collectFinanceStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Sold Gems' GROUP BY date ORDER BY timestamp DESC");
    }

    // Pawnshop sales
    public static function collectPawnshopStatistics(): array
    {
        return self::collectFinanceStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'received $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action = 'Used Pawn Shop' GROUP BY date ORDER BY timestamp DESC");
    }

    // Casino revenue
    public static function collectCasinoStatistics(): array
    {
        return self::collectFinanceStatistics("SELECT COUNT(id) as count, -SUM(money_won) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from casino_logs GROUP BY date ORDER BY timestamp DESC");
    }

    // Drug sales
    public static function collectDrugSaleStatistics(): array
    {
        return self::collectFinanceStatistics("SELECT COUNT(id) as count, SUM(SUBSTRING_INDEX(SUBSTRING_INDEX(details, 'for $', -1), '.', 1)) as amount, DATE_FORMAT(timestamp, '%c/%d/%Y') as date from user_logs WHERE action IN ('Sold Cocaine', 'Sold Weed', 'Sold Acid', 'Sold Lean', 'Sold Meth', 'Sold Moonshine', 'Sold Shrooms') GROUP BY date ORDER BY timestamp DESC");
    }

    private static function collectFinanceStatistics(string $query): array
    {
        $result = [];

        $data = DB::select($query);

        for ($i = 0; $i <= 30; $i++) {
            $time = strtotime("-$i days");
            $date = date('n/d/Y', $time);

            $entry = self::findEntry($data, $date);

            $amount = $entry && $entry->amount ? intval($entry->amount) : 0;
            $count = $entry && $entry->count ? intval($entry->count) : 0;

            $result[] = [
                'date'   => date('jS F Y', $time),
                'amount' => $amount,
                'count'  => $count,
            ];
        }

        array_reverse($result);

        return $result;
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
