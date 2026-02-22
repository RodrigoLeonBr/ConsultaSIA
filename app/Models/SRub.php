<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|SRub query()
 * @method static SRub updateOrCreate(array $attributes, array $values = [])
 * @method static SRub firstOrCreate(array $attributes, array $values = [])
 * @method static SRub create(array $attributes = [])
 * @method static SRub find(mixed $id, array $columns = ['*'])
 * @method static SRub findOrFail(mixed $id, array $columns = ['*'])
 * @method static SRub first(array $columns = ['*'])
 * @method static SRub where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
class SRub extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 's_rub';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'rub_id';

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
        'rub_id',
        'rub_dc',
        'rub_total',
    ];

    /**
     * Mapeamento de atributos (minúscula → maiúscula no banco)
     */
    public function getAttribute($key)
    {
        // Mapear 'rub_id' para 'RUB_ID', 'rub_dc' para 'RUB_DC', etc.
        $mapped = [
            'rub_id' => 'RUB_ID',
            'rub_dc' => 'RUB_DC',
            'rub_total' => 'RUB_TOTAL',
        ];

        $dbKey = $mapped[$key] ?? $key;
        return parent::getAttribute($dbKey);
    }

    public function setAttribute($key, $value)
    {
        $mapped = [
            'rub_id' => 'RUB_ID',
            'rub_dc' => 'RUB_DC',
            'rub_total' => 'RUB_TOTAL',
        ];

        $dbKey = $mapped[$key] ?? $key;
        return parent::setAttribute($dbKey, $value);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'rub_id';
    }
}