<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SEsus extends Model
{
    protected $table = 's_esus';

    protected $fillable = [
        'competencia',
        'cnes',
        'unidade',
        'tipo_relatorio',
        'bloco',
        'descricao_esus',
        'codigo_sigtap',
        'descricao_sigtap',
        'quantidade',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
        ];
    }

    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'codigo_sigtap', 'codigo');
    }

    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'cnes', 're_cunid');
    }

    public function scopeForCompetencia(Builder $query, string $competencia): Builder
    {
        return $query->where('competencia', $competencia);
    }
}
