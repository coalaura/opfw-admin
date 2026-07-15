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

            $sharedKeys = false;

            if (PermissionHelper::hasPermission(PermissionHelper::PERM_REALTY_EDIT)) {
                $keys = explode(';', $property->shared_keys ?? '');
                $keys = array_values(array_filter($keys));

                $sharedKeys = array_map(function ($key) {
                    $key = explode('-', $key);

                    return [
                        'cid'   => intval($key[2]),
                        'name'  => $key[0],
                        'level' => intval($key[1]),
                    ];
                }, $keys);
            }

            $companies[$companyId]['properties'][$propertyId] = [
                'type'       => $property->property_type,
                'address'    => $property->property_address,
                'income'     => $property->property_income,
                'renter_cid' => $property->property_renter_cid,
                'renter'     => $property->property_renter,
                'last_pay'   => $property->property_last_pay,
                'keys'       => $sharedKeys,
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

        $property = DB::table('stocks_company_properties')->where('property_id', $propertyId)->first();

        if (! $property) {
            return $this->json(false, null, "property not found");
        }

        $renter = $property->property_renter_cid;

        if (! $renter) {
            return $this->json(false, null, "property not rented");
        }

        $sharedKeys = explode(";", $property->shared_keys ?? "");

        $access = [$renter];
        $levels = [];

        foreach ($sharedKeys as $key) {
            $part = explode("-", $key);

            if (sizeof($part) !== 3) {
                continue;
            }

            $cid   = intval($part[2]);
            $level = intval($part[1]);

            if ($cid) {
                $levels[$cid] = $level;
                $access[]     = $cid;
            }
        }

        $access = Character::select(["player_name", DB::raw("CONCAT(first_name, ' ', last_name) as full_name"), "character_id", "characters.license_identifier"])
            ->leftJoin("users", "characters.license_identifier", "=", "users.license_identifier")
            ->whereIn("character_id", $access)
            ->orderBy("full_name")
            ->get()->toArray();

        $access = array_map(function ($entry) use ($levels) {
            $entry['level'] = $levels[$entry['character_id']] ?? -1;

            return $entry;
        }, $access);

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

        $character = Character::find($renter);

        if (! $character) {
            return backWith('error', 'Property Renter CID is invalid');
        }

        $sharedKeys = '';

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

            $keyCharacter = Character::find($cid);

            // Invalid character
            if (! $keyCharacter) {
                return backWith('error', 'Invalid shared key (character not found)');
            }

            $name = str_replace('-', ' ', $keyCharacter->name);

            $sharedKeys .= sprintf('%s-%s-%s;', $name, $level, $cid);
        }

        DB::table('stocks_company_properties')->where('property_id', $propertyId)->update([
            'property_renter'     => $character->name,
            'property_renter_cid' => $character->character_id,
            'property_income'     => $income,
            'property_last_pay'   => $lastPay,
            'shared_keys'         => $sharedKeys,
        ]);

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
