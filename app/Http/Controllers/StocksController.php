<?php
namespace App\Http\Controllers;

use App\AuditLog;
use App\Character;
use App\Helpers\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StocksController extends Controller
{
    /**
     * List all companies.
     *
     * @return Response
     */
    public function companies(Request $request): Response
    {
        $dbCompanies = DB::table('stocks_companies')
            ->select("stocks_companies.*", DB::raw("CONCAT(first_name, ' ', last_name) as owner_name"))
            ->leftJoin("characters", "character_id", "=", "owner_cid")
            ->orderBy('company_name')->get();

        $dbEmployees = DB::table('stocks_company_employees')
            ->select("stocks_company_employees.*", DB::raw("CONCAT(first_name, ' ', last_name) as employee_name"))
            ->leftJoin("characters", "character_id", "=", "employee_cid")
            ->orderByDesc('permissions')->get();

        $dbProperties = DB::table('stocks_company_properties')
            ->select("stocks_company_properties.*", DB::raw("CONCAT(first_name, ' ', last_name) as property_renter"))
            ->leftJoin("characters", "character_id", "=", "property_renter_cid")
            ->orderBy('property_address')->get();

        $propertyAccess = [];

        if (PermissionHelper::hasPermission(PermissionHelper::PERM_REALTY_EDIT)) {
            $dbPropertyAccess = DB::table('stocks_company_property_access')
                ->select(
                    'stocks_company_property_access.property_id',
                    'stocks_company_property_access.character_id as cid',
                    'stocks_company_property_access.access_level as level',
                    DB::raw("CONCAT(first_name, ' ', last_name) as name")
                )
                ->leftJoin('characters', 'characters.character_id', '=', 'stocks_company_property_access.character_id')
                ->get();

            foreach ($dbPropertyAccess as $access) {
                $propertyAccess[$access->property_id][] = [
                    'cid'   => intval($access->cid),
                    'name'  => $access->name,
                    'level' => intval($access->level),
                ];
            }
        }

        $companies = [];

        foreach ($dbCompanies as $company) {
            $companyId = $company->company_id;

            $companies[$companyId] = [
                'name'              => $company->company_name,
                'description'       => $company->company_description,
                'logo'              => $company->company_logo,
                'owner'             => $company->owner_name,
                'reg_timestamp'     => $company->company_reg_timestamp,
                'bankrupt'          => $company->bankrupt,
                'balance'           => $company->company_balance,
                'properties'        => [],
                'employees'         => [],
                'empty_properties'  => 0,
                'filled_properties' => 0,
            ];
        }

        foreach ($dbProperties as $property) {
            $companyId  = $property->company_id;
            $propertyId = $property->property_id;

            if (! isset($companies[$companyId])) {
                continue;
            }

            $companies[$companyId]['properties'][$propertyId] = [
                'type'       => $property->property_type,
                'address'    => $property->property_address,
                'income'     => $property->property_income,
                'renter_cid' => $property->property_renter_cid,
                'renter'     => $property->property_renter,
                'last_pay'   => $property->property_last_pay,
                'keys'       => $propertyAccess[$propertyId] ?? false,
            ];

            if ($property->property_renter) {
                $companies[$companyId]['filled_properties']++;
            } else {
                $companies[$companyId]['empty_properties']++;
            }
        }

        foreach ($dbEmployees as $employee) {
            $companyId = $employee->company_id;

            if (! isset($companies[$companyId])) {
                continue;
            }

            $companies[$companyId]['employees'][] = [
                'cid'         => $employee->employee_cid,
                'name'        => $employee->employee_name,
                'position'    => $employee->position,
                'salary'      => $employee->salary,
                'permissions' => $employee->permissions,
            ];
        }

        return Inertia::render('Stocks/Companies', [
            'companies' => $companies,
        ]);
    }

    public function property(Request $request, int $propertyId)
    {
        if (! $this->isSeniorStaff($request)) {
            abort(401);
        }

        $property = DB::table('stocks_company_properties')
            ->select(
                'stocks_company_properties.*',
                'users.player_name as renter_player_name',
                'characters.character_id as renter_character_id',
                'characters.license_identifier as renter_license_identifier',
                DB::raw("CONCAT(characters.first_name, ' ', characters.last_name) as renter_full_name")
            )
            ->leftJoin('characters', 'characters.character_id', '=', 'stocks_company_properties.property_renter_cid')
            ->leftJoin('users', 'users.license_identifier', '=', 'characters.license_identifier')
            ->where('stocks_company_properties.property_id', $propertyId)
            ->first();

        if (! $property) {
            return $this->json(false, null, "property not found");
        }

        $renter = $property->property_renter_cid;

        if (! $renter) {
            return $this->json(false, null, "property not rented");
        }

        $access = [];

        if ($property->renter_character_id) {
            $access[$property->renter_character_id] = [
                'player_name'         => $property->renter_player_name,
                'full_name'           => $property->renter_full_name,
                'character_id'        => $property->renter_character_id,
                'license_identifier'  => $property->renter_license_identifier,
                'level'               => -1,
            ];
        }

        $propertyAccess = DB::table('stocks_company_property_access as property_access')
            ->select(
                'users.player_name',
                'characters.character_id',
                'characters.license_identifier',
                'property_access.access_level as level',
                DB::raw("CONCAT(characters.first_name, ' ', characters.last_name) as full_name")
            )
            ->join('characters', 'characters.character_id', '=', 'property_access.character_id')
            ->leftJoin('users', 'users.license_identifier', '=', 'characters.license_identifier')
            ->where('property_access.property_id', $propertyId)
            ->get();

        foreach ($propertyAccess as $accessEntry) {
            $access[$accessEntry->character_id] = [
                'player_name'        => $accessEntry->player_name,
                'full_name'          => $accessEntry->full_name,
                'character_id'       => $accessEntry->character_id,
                'license_identifier' => $accessEntry->license_identifier,
                'level'              => intval($accessEntry->level),
            ];
        }

        $access = array_values($access);

        usort($access, function ($first, $second) {
            return strcmp($first['full_name'], $second['full_name']);
        });

        return $this->json(true, [
            'id'      => $property->property_id,
            'address' => $property->property_address,
            'renter'  => $renter,
            'access'  => $access,
        ]);
    }

    public function updateProperty(Request $request, int $propertyId)
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_REALTY_EDIT)) {
            abort(401);
        }

        $property = DB::table('stocks_company_properties')->where('property_id', $propertyId)->first();

        if (! $property) {
            return backWith('error', 'Property not found');
        }

        $propertyLastPay = $property->property_last_pay;

        $renter  = $request->input('renter');
        $income  = $request->input('income') ?? $property->property_cost;
        $lastPay = strtotime($request->input('last_pay'));
        $keys    = $request->input('keys');

        if (! $lastPay || $lastPay < $propertyLastPay) {
            return backWith('error', 'Invalid last pay date');
        }

        if ($income <= 0 || $income > 50000) {
            return backWith('error', 'Invalid rent amount');
        }

        if (! $keys || ! is_array($keys)) {
            return backWith('error', 'Invalid shared keys');
        }

        $propertyAccess = [];

        foreach ($keys as $key) {
            $cid   = intval($key['cid']);
            $level = intval($key['level']);

            // Invalid cid
            if (! $cid || $cid <= 0) {
                return backWith('error', 'Invalid shared key (cid)');
            }

            // Invalid level
            if (! $level || ! in_array($level, [1, 2, 3])) {
                return backWith('error', 'Invalid shared key (level)');
            }

            $propertyAccess[$cid] = [
                'property_id'  => $propertyId,
                'character_id' => $cid,
                'access_level' => $level,
            ];
        }

        $characters = Character::whereIn('character_id', array_merge([$renter], array_keys($propertyAccess)))
            ->get()
            ->keyBy('character_id');

        $character = $characters->get($renter);

        if (! $character) {
            return backWith('error', 'Property Renter CID is invalid');
        }

        if ($characters->only(array_keys($propertyAccess))->count() !== count($propertyAccess)) {
            return backWith('error', 'Invalid shared key (character not found)');
        }

        DB::transaction(function () use ($propertyId, $character, $income, $lastPay, $propertyAccess) {
            DB::table('stocks_company_properties')->where('property_id', $propertyId)->update([
                'property_renter'     => $character->name,
                'property_renter_cid' => $character->character_id,
                'property_income'     => $income,
                'property_last_pay'   => $lastPay,
            ]);

            DB::table('stocks_company_property_access')->where('property_id', $propertyId)->delete();

            if ($propertyAccess) {
                DB::table('stocks_company_property_access')->insert(array_values($propertyAccess));
            }
        });

        return backWith('success', 'Property updated');
    }

    public function editCompanyBalance(Request $request, int $id): \Illuminate\Http\Response
    {
        if (! PermissionHelper::hasPermission(PermissionHelper::PERM_EDIT_COMPANY_BALANCE)) {
            return self::json(false, null, 'You do not have permission to edit company balances.');
        }

        $company = DB::table('stocks_companies')
            ->select('company_id', 'company_name', 'company_balance')
            ->where('company_id', '=', $id)
            ->first();

        if (! $company) {
            return self::json(false, null, 'Invalid company ID.');
        }

        $balance = intval($request->post('balance'));
        $user    = user();

        DB::table('stocks_companies')
            ->where('company_id', '=', $id)
            ->update(['company_balance' => $balance]);

        AuditLog::log(
            $user->license_identifier,
            'balance.edit',
            'company',
            $id,
            sprintf(
                "%s edited company #%d (%s) balance: %d -> %d.",
                $user->consoleName(),
                $id,
                $company->company_name,
                $company->company_balance,
                $balance
            ),
            [
                'company_id'   => $id,
                'company_name' => $company->company_name,
                'before'       => $company->company_balance,
                'after'        => $balance,
            ]
        );

        return self::json(true);
    }
}
