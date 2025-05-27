<?php
namespace App\Http\Controllers;

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
        $dbCompanies  = DB::table('stocks_companies')->orderBy('company_name')->get();
        $dbEmployees  = DB::table('stocks_company_employees')->orderByDesc('permissions')->get();
        $dbProperties = DB::table('stocks_company_properties')->orderBy('property_address')->get();

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
}
