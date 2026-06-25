<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SusPaulista extends Model
{
    protected $table = 'sus_paulista';

    protected $fillable = [
        'codigo',
        'modalidade',
        'competencia_inicial',
        'competencia_final',
        'descricao',
        'tab_paulista',
        'complementacao_tsp',
    ];

    protected function casts(): array
    {
        return [
            'tab_paulista' => 'decimal:2',
            'complementacao_tsp' => 'decimal:2',
        ];
    }

    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'codigo', 'codigo');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('competencia_final', '999999');
    }

    public function scopeForCompetencia(Builder $query, string $competencia): Builder
    {
        return $query
            ->where('competencia_inicial', '<=', $competencia)
            ->where('competencia_final', '>=', $competencia);
    }

    public function getFormattedTabPaulistaAttribute(): string
    {
        return 'R$ ' . number_format((float) $this->tab_paulista, 2, ',', '.');
    }

    public function getFormattedComplementacaoTspAttribute(): string
    {
        return 'R$ ' . number_format((float) $this->complementacao_tsp, 2, ',', '.');
    }
}
