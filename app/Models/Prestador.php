<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|Prestador query()
 * @method static Prestador updateOrCreate(array $attributes, array $values = [])
 * @method static Prestador firstOrCreate(array $attributes, array $values = [])
 * @method static Prestador create(array $attributes = [])
 * @method static Prestador find(mixed $id, array $columns = ['*'])
 * @method static Prestador findOrFail(mixed $id, array $columns = ['*'])
 * @method static Prestador first(array $columns = ['*'])
 * @method static Prestador where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
class Prestador extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'prestador';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 're_cunid';

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
        're_cunid',
        're_cnome',
        're_tipo',
        'cnpj',
        'area',
        'tipouni',
        'relatorio',
        'ativo',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'area' => 'integer',
        ];
    }

    /**
     * Get the s_prd records for this prestador.
     */
    public function sPrds(): HasMany
    {
        return $this->hasMany(SPrd::class, 'prd_uid', 're_cunid');
    }

    /**
     * Scope for active prestadores.
     */
    public function scopeActive($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope for private/unique prestadores.
     */
    public function scopePrivado($query)
    {
        return $query->where('re_tipo', 'P');
    }

    /**
     * Scope for basic unit prestadores.
     */
    public function scopeUnidadeBasica($query)
    {
        return $query->where('re_tipo', 'U');
    }

    /**
     * Scope for municipal hospital prestadores.
     */
    public function scopeHospitalMunicipal($query)
    {
        return $query->where('re_tipo', 'M');
    }

    /**
     * Check if prestador is municipal.
     */
    public function isMunicipal()
    {
        return $this->tipouni === 'M';
    }

    /**
     * Check if prestador is philanthropic.
     */
    public function isFilantropico()
    {
        return $this->tipouni === 'F';
    }

    /**
     * Check if prestador is particular.
     */
    public function isParticular()
    {
        return $this->tipouni === 'P';
    }

    /**
     * Check if prestador is state-owned.
     */
    public function isEstadual()
    {
        return $this->tipouni === 'E';
    }
}