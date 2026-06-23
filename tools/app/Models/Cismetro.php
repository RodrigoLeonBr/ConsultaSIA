<?php
// app/Models/Cismetro.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cismetro extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cismetro';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'codigo',
        'credenciamento',
        'grupo',
        'descricao',
        'valor',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    /**
     * Get the s_prd records for this cismetro.
     */
    public function sPrds(): HasMany
    {
        return $this->hasMany(SPrd::class, 'prd_pa', 'codigo');
    }

    /**
     * Get the s_pap records for this cismetro.
     */
    public function sPaps(): HasMany
    {
        return $this->hasMany(SPap::class, 'PAP_PA', 'codigo');
    }

    /**
     * Get the formatted value.
     */
    public function getFormattedValorAttribute()
    {
        return 'R$ ' . number_format($this->valor, 2, ',', '.');
    }

    /**
     * Calculate total value based on quantity.
     */
    public function calculateTotal($quantidade)
    {
        return $this->valor * $quantidade;
    }

    /**
     * Get formatted total value based on quantity.
     */
    public function getFormattedTotal($quantidade)
    {
        $total = $this->calculateTotal($quantidade);
        return 'R$ ' . number_format($total, 2, ',', '.');
    }
}