<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCompany extends Model
{
    protected $table = 'stocks_companies';

    protected $primaryKey = 'company_id';

    public $timestamps = false;

    protected $fillable = [
        'owner_cid',
        'company_name',
        'company_description',
        'company_logo',
        'company_balance',
        'bankrupt',
        'company_reg_timestamp',
    ];

    protected $casts = [
        'owner_cid'              => 'integer',
        'company_balance'        => 'integer',
        'bankrupt'               => 'boolean',
        'company_reg_timestamp' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'owner_cid');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(StockCompanyEmployee::class, 'company_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'company_id');
    }
}
