<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|Cbo query()
 * @method static Cbo updateOrCreate(array $attributes, array $values = [])
 * @method static Cbo firstOrCreate(array $attributes, array $values = [])
 * @method static Cbo create(array $attributes = [])
 * @method static Cbo find(mixed $id, array $columns = ['*'])
 * @method static Cbo findOrFail(mixed $id, array $columns = ['*'])
 * @method static Cbo first(array $columns = ['*'])
 * @method static Cbo where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
class Cbo extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'cbo';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'CBO';

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
        'cbo',
        'ds_cbo',
    ];

  /**
     * Mapeamento de atributos (minúscula → maiúscula no banco)
     */
    protected $attributes = [];

    public function getAttribute($key)
    {
        // Mapear campos minúsculos para maiúsculos no banco
        $mapped = [
            'cbo' => 'CBO',
            'ds_cbo' => 'DS_CBO',
        ];

        $dbKey = $mapped[$key] ?? $key;
        return parent::getAttribute($dbKey);
    }

    public function setAttribute($key, $value)
    {
        // Mapear campos minúsculos para maiúsculos no banco
        $mapped = [
            'cbo' => 'CBO',
            'ds_cbo' => 'DS_CBO',
        ];

        $dbKey = $mapped[$key] ?? $key;
        return parent::setAttribute($dbKey, $value);
    }    

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'CBO';
    }

    /**
     * Get the s_prd records for this CBO.
     */
    public function sPrds(): HasMany
    {
        return $this->hasMany(SPrd::class, 'prd_cbo', 'CBO');
    }
}