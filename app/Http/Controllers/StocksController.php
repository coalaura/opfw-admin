<?php

namespace App\Http\Controllers;

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
    public function companies(): Response
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

            if (!isset($companies[$companyId])) {
                continue;
            }

            $companies[$companyId]['properties'][$propertyId] = [
                'type'     => $property->property_type,
                'address'  => $property->property_address,
                'income'   => $property->property_income,
                'renter'   => $property->property_renter,
                'last_pay' => $property->property_last_pay,
            ];

            if ($property->property_renter) {
                $companies[$companyId]['filled_properties']++;
            } else {
                $companies[$companyId]['empty_properties']++;
            }
        }

        foreach ($dbEmployees as $employee) {
            $companyId = $employee->company_id;

            if (!isset($companies[$companyId])) {
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
}
