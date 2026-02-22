<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Forma extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'forma';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id_registro';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'grupo',
        'subgrupo',
        'forma',
        'descricao',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id_registro' => 'integer',
        ];
    }

    /**
     * Scope for grupo level (2 digits)
     */
    public function scopeGrupo($query, $grupo)
    {
        return $query->where('grupo', $grupo)
                    ->where('subgrupo', $grupo . '00')
                    ->where('forma', $grupo . '0000');
    }

    /**
     * Scope for subgrupo level (4 digits)
     */
    public function scopeSubgrupo($query, $subgrupo)
    {
        return $query->where('subgrupo', $subgrupo)
                    ->where('forma', $subgrupo . '00');
    }

    /**
     * Scope for forma level (6 digits)
     */
    public function scopeForma($query, $forma)
    {
        return $query->where('forma', $forma);
    }

    /**
     * Get description for grupo level
     */
    public static function getGrupoDescricao($grupo)
    {
        $forma = self::grupo($grupo)->first();
        return $forma ? $forma->descricao : "Grupo $grupo";
    }

    /**
     * Get description for subgrupo level
     */
    public static function getSubgrupoDescricao($subgrupo)
    {
        $forma = self::subgrupo($subgrupo)->first();
        return $forma ? $forma->descricao : "Sub-grupo $subgrupo";
    }

    /**
     * Get description for forma level
     */
    public static function getFormaDescricao($forma)
    {
        $forma = self::forma($forma)->first();
        return $forma ? $forma->descricao : "Forma $forma";
    }
}
