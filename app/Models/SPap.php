<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SPap extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 's_pap';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'PAP_UID',
        'PAP_CMP',
        'PAP_NUM',
        'PAP_PA',
        'PAP_SEQ',
        'PAP_CBO',
        'PAP_IDADE',
        'PAP_QT_P',
        'PAP_QT_A',
        'PAP_MVM',
        'PAP_ORG',
        'PAP_FLPA',
        'PAP_FLEMA',
        'PAP_FLCBO',
        'PAP_FLQT',
        'PAP_FLER',
        'PAP_CNPJ',
        'PAP_NFISC',
        'PAP_CIDPRI',
        'PAP_CIDSEC',
        'PAP_EQUIPE',
        'PAP_VL_FED',
        'PAP_VL_LOC',
        'PAP_VL_INC',
        'PAP_INCOUT',
        'PAP_INCURG',
        'PAP_RUB',
        'PAP_TPFIN',
        'PAP_CPX',
        'PAP_RC',
        'PAP_UNTERC',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'PAP_IDADE' => 'integer',
            'PAP_QT_P' => 'decimal:2',
            'PAP_QT_A' => 'decimal:2',
            'PAP_VL_FED' => 'decimal:2',
            'PAP_VL_LOC' => 'decimal:2',
            'PAP_VL_INC' => 'decimal:2',
        ];
    }

    /**
     * Get the prestador that owns this APAC record.
     */
    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'PAP_UID', 're_cunid');
    }

    /**
     * Get the procedimento that owns this APAC record.
     */
    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'PAP_PA', 'codigo');
    }

    /**
     * Get the CBO that owns this APAC record.
     */
    public function cbo(): BelongsTo
    {
        return $this->belongsTo(Cbo::class, 'PAP_CBO', 'cbo');
    }

    /**
     * Get the APAC data related to this production record.
     */
    public function sApa(): BelongsTo
    {
        return $this->belongsTo(SApa::class, 'PAP_NUM', 'APA_NUM');
    }

    /**
     * Get the formatted federal value.
     */
    public function getFormattedFederalValueAttribute()
    {
        return 'R$ ' . number_format($this->PAP_VL_FED ?? 0, 2, ',', '.');
    }

    /**
     * Get the formatted local value.
     */
    public function getFormattedLocalValueAttribute()
    {
        return 'R$ ' . number_format($this->PAP_VL_LOC ?? 0, 2, ',', '.');
    }

    /**
     * Get the formatted incentive value.
     */
    public function getFormattedIncentiveValueAttribute()
    {
        return 'R$ ' . number_format($this->PAP_VL_INC ?? 0, 2, ',', '.');
    }

    /**
     * Get the total value (federal + local + incentive).
     */
    public function getTotalValueAttribute()
    {
        return ($this->PAP_VL_FED ?? 0) + ($this->PAP_VL_LOC ?? 0) + ($this->PAP_VL_INC ?? 0);
    }

    /**
     * Get the formatted total value.
     */
    public function getFormattedTotalValueAttribute()
    {
        return 'R$ ' . number_format($this->total_value, 2, ',', '.');
    }

    /**
     * Scope for APAC records with production quantity.
     */
    public function scopeWithProduction($query)
    {
        return $query->where('PAP_QT_P', '>', 0);
    }

    /**
     * Scope for APAC records with approved quantity.
     */
    public function scopeWithApproved($query)
    {
        return $query->where('PAP_QT_A', '>', 0);
    }

    /**
     * Scope for specific prestador.
     */
    public function scopeForPrestador($query, $prestadorId)
    {
        return $query->where('PAP_UID', $prestadorId);
    }

    /**
     * Scope for specific competencia.
     */
    public function scopeForCompetencia($query, $competencia)
    {
        return $query->where('PAP_MVM', $competencia);
    }

    /**
     * Scope for specific procedimento.
     */
    public function scopeForProcedimento($query, $procedimento)
    {
        return $query->where('PAP_PA', $procedimento);
    }

    /**
     * Scope for OCI procedures (procedimentos that start with '09').
     */
    public function scopeOci($query)
    {
        return $query->whereHas('sApa', function ($q) {
            $q->where('APA_PRIPAL', 'like', '09%');
        });
    }
}
