<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedimento extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'procedimento';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'codigo';

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'codigo',
        'procedimento',
        'pa_total',
        'rub_total',
        'rub_dc',
        'pa_rub',
        'pa_id',
        'financiamento',
    ];

    /**
     * Get the attribute value.
     */
    public function getAttribute($key)
    {
        $mapped = [
            'codigo' => 'codigo',
            'procedimento' => 'procedimento',
            'pa_total' => 'PA_TOTAL',
            'rub_total' => 'RUB_TOTAL',
            'rub_dc' => 'RUB_DC',
            'pa_rub' => 'PA_RUB',
            'pa_id' => 'PA_ID',
            'financiamento' => 'FINANCIAMENTO',
        ];
        $dbKey = $mapped[$key] ?? $key;
        return parent::getAttribute($dbKey);
    }

    /**
     * Set the attribute value.
     */
    public function setAttribute($key, $value)
    {
        $mapped = [
            'codigo' => 'codigo',
            'procedimento' => 'procedimento',
            'pa_total' => 'PA_TOTAL',
            'rub_total' => 'RUB_TOTAL',
            'rub_dc' => 'RUB_DC',
            'pa_rub' => 'PA_RUB',
            'pa_id' => 'PA_ID',
            'financiamento' => 'FINANCIAMENTO',
        ];
        $dbKey = $mapped[$key] ?? $key;
        return parent::setAttribute($dbKey, $value);
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'pa_total' => 'decimal:2',
        ];
    }

    /**
     * Get the s_prd records for this procedimento.
     */
    public function sPrds(): HasMany
    {
        return $this->hasMany(SPrd::class, 'prd_pa', 'codigo');
    }

    /**
     * Get the cismetro records for this procedimento.
     */
    public function cismetros(): HasMany
    {
        return $this->hasMany(Cismetro::class, 'codigo', 'codigo');
    }

    /**
     * Get the formatted total value.
     */
    public function getFormattedTotalAttribute()
    {
        return 'R$ ' . number_format($this->pa_total, 2, ',', '.');
    }
}
