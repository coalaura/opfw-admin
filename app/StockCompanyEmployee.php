<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCompanyEmployee extends Model
{
    protected $table = 'stocks_company_employees';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'employee_cid',
        'position',
        'salary',
        'permissions',
    ];

    protected $casts = [
        'company_id'   => 'integer',
        'employee_cid' => 'integer',
        'salary'       => 'integer',
        'permissions'  => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(StockCompany::class, 'company_id');
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'employee_cid');
    }
}
