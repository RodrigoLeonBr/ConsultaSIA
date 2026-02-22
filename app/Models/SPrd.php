<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SPrd extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 's_prd';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'prd_uid',
        'prd_cmp',
        'prd_flh',
        'prd_seq',
        'prd_pa',
        'prd_cbo',
        'prd_idade',
        'prd_qt_p',
        'prd_qt_a',
        'prd_vl_p',
        'prd_vl_a',
        'prd_mvm',
        'prd_org',
        'prd_flpa',
        'prd_flcbo',
        'prd_flca',
        'prd_flida',
        'prd_flqt',
        'prd_fler',
        'prd_apanum',
        'prd_cnsmed',
        'prd_rms',
        'prd_cnpj',
        'prd_nfis',
        'prd_resid',
        'prd_rub',
        'prd_cpx',
        'prd_tpfin',
        'prd_qtdatr',
        'prd_qtdatu',
        'prd_rc',
        'prd_cidpri',
        'prd_cidsec',
        'prd_cidcas',
        'prd_incout',
        'prd_incurg',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'prd_idade' => 'integer',
            'prd_qt_p' => 'integer',
            'prd_qt_a' => 'integer',
            'prd_vl_p' => 'decimal:2',
            'prd_vl_a' => 'decimal:2',
            'prd_qtdatr' => 'integer',
            'prd_qtdatu' => 'integer',
        ];
    }

    /**
     * Get the prestador that owns this production record.
     */
    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'prd_uid', 're_cunid');
    }

    /**
     * Get the procedimento that owns this production record.
     */
    public function procedimento(): BelongsTo
    {
        return $this->belongsTo(Procedimento::class, 'prd_pa', 'codigo');
    }

    /**
     * Get the CBO that owns this production record.
     */
    public function cbo(): BelongsTo
    {
        return $this->belongsTo(Cbo::class, 'prd_cbo', 'cbo');
    }

    /**
     * Get the cismetro that owns this production record.
     */
    public function cismetro(): BelongsTo
    {
        return $this->belongsTo(Cismetro::class, 'prd_pa', 'codigo');
    }

    /**
     * Get the formatted presented value.
     */
    public function getFormattedPresentedValueAttribute()
    {
        return 'R$ ' . number_format($this->prd_vl_p, 2, ',', '.');
    }

    /**
     * Get the formatted approved value.
     */
    public function getFormattedApprovedValueAttribute()
    {
        return 'R$ ' . number_format($this->prd_vl_a, 2, ',', '.');
    }

    /**
     * Scope for presented production.
     */
    public function scopePresented($query)
    {
        return $query->where('prd_qt_p', '>', 0);
    }

    /**
     * Scope for approved production.
     */
    public function scopeApproved($query)
    {
        return $query->where('prd_qt_a', '>', 0);
    }
}
