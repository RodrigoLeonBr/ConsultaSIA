<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedimento extends Model
{
    protected $table = 'procedimento';
    protected $primaryKey = 'codigo';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'procedimento',
        'pa_total',
        'rub_total',
        'rub_dc',
        'pa_rub',
        'pa_id',
        'financiamento',
        'vl_sp',
        'vl_sh',
    ];

    private const COLUMN_MAP = [
        'codigo'       => 'codigo',
        'procedimento' => 'procedimento',
        'pa_total'     => 'PA_TOTAL',
        'rub_total'    => 'RUB_TOTAL',
        'rub_dc'       => 'RUB_DC',
        'pa_rub'       => 'PA_RUB',
        'pa_id'        => 'PA_ID',
        'financiamento'=> 'FINANCIAMENTO',
        'vl_sp'        => 'VL_SP',
        'vl_sh'        => 'VL_SH',
    ];

    public function getAttribute($key)
    {
        $dbKey = self::COLUMN_MAP[$key] ?? $key;
        return parent::getAttribute($dbKey);
    }

    public function setAttribute($key, $value)
    {
        $dbKey = self::COLUMN_MAP[$key] ?? $key;
        return parent::setAttribute($dbKey, $value);
    }

    protected function casts(): array
    {
        return [
            'pa_total' => 'decimal:2',
            'vl_sp'    => 'decimal:2',
            'vl_sh'    => 'decimal:2',
        ];
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format((float) $this->pa_total, 2, ',', '.');
    }

    public function getVlTotalAttribute(): float
    {
        return (float) $this->vl_sp + (float) $this->vl_sh;
    }

    public function getFormattedVlTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->vl_total, 2, ',', '.');
    }

    public function sPrds(): HasMany
    {
        return $this->hasMany(SPrd::class, 'prd_pa', 'codigo');
    }

    public function cismetros(): HasMany
    {
        return $this->hasMany(Cismetro::class, 'codigo', 'codigo');
    }
}
