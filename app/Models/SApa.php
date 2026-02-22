<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SApa extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 's_apa';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'APA_UID',
        'APA_NUM',
        'APA_EMISSA',
        'APA_DTINIC',
        'APA_DTFIM',
        'APA_TPATEN',
        'APA_TPAPAC',
        'APA_NMPCN',
        'APA_UFPCN',
        'APA_MAEPCN',
        'APA_LOGPCN',
        'APA_NUMPCN',
        'APA_CPLPCN',
        'APA_CEPPCN',
        'APA_MUNPCN',
        'APA_DTNASC',
        'APA_SEXPCN',
        'APA_VARIA',
        'APA_CPFRES',
        'APA_NMRES',
        'APA_MOTCOB',
        'APA_DTOBAL',
        'APA_CPFDIR',
        'APA_NMDIR',
        'APA_CMP',
        'APA_MVM',
        'APA_RMS',
        'APA_DTGER',
        'APA_FLER',
        'APA_INERPP',
        'APA_PRIPAL',
        'APA_CPFPCT',
        'APA_CNSPCT',
        'APA_CNSRES',
        'APA_CNSDIR',
        'APA_CIDCA',
        'APA_NPRONT',
        'APA_CODSOL',
        'APA_DTSOL',
        'APA_DTAUT',
        'APA_CODEMI',
        'APA_CATEND',
        'APA_APACAN',
        'APA_RACA',
        'APA_NOMERE',
        'APA_ETNIA',
        'APA_ADVLMC',
        'APA_ADVTZM',
        'APA_SRV',
        'APA_CSF',
        'APA_CDLOGR',
        'APA_BAIRRO',
        'APA_DDD',
        'APA_TEL',
        'APA_EMAIL',
        'APA_CNSEXE',
        'APA_INE',
        'APA_ADVSEX',
        'APA_EXPMAE',
        'APA_STRUA',
    ];

    /**
     * Get the prestador that owns this APAC record.
     */
    public function prestador(): BelongsTo
    {
        return $this->belongsTo(Prestador::class, 'APA_UID', 're_cunid');
    }

    /**
     * Get the SPap records for this APAC.
     */
    public function sPaps(): HasMany
    {
        return $this->hasMany(SPap::class, 'PAP_NUM', 'APA_NUM');
    }

    /**
     * Get the formatted birth date.
     */
    public function getFormattedBirthDateAttribute()
    {
        if (!$this->APA_DTNASC) {
            return '';
        }
        
        // Assuming format YYYYMMDD
        $date = $this->APA_DTNASC;
        if (strlen($date) === 8) {
            return substr($date, 6, 2) . '/' . substr($date, 4, 2) . '/' . substr($date, 0, 4);
        }
        
        return $date;
    }

    /**
     * Get the formatted start date.
     */
    public function getFormattedStartDateAttribute()
    {
        if (!$this->APA_DTINIC) {
            return '';
        }
        
        // Assuming format YYYYMMDD
        $date = $this->APA_DTINIC;
        if (strlen($date) === 8) {
            return substr($date, 6, 2) . '/' . substr($date, 4, 2) . '/' . substr($date, 0, 4);
        }
        
        return $date;
    }

    /**
     * Get the formatted end date.
     */
    public function getFormattedEndDateAttribute()
    {
        if (!$this->APA_DTFIM) {
            return '';
        }
        
        // Assuming format YYYYMMDD
        $date = $this->APA_DTFIM;
        if (strlen($date) === 8) {
            return substr($date, 6, 2) . '/' . substr($date, 4, 2) . '/' . substr($date, 0, 4);
        }
        
        return $date;
    }

    /**
     * Get the formatted competence.
     */
    public function getFormattedCompetenciaAttribute()
    {
        if (!$this->APA_MVM) {
            return '';
        }
        
        // Assuming format YYYYMM
        $date = $this->APA_MVM;
        if (strlen($date) === 6) {
            return substr($date, 4, 2) . '/' . substr($date, 0, 4);
        }
        
        return $date;
    }

    /**
     * Get the patient's age.
     */
    public function getPatientAgeAttribute()
    {
        if (!$this->APA_DTNASC) {
            return null;
        }
        
        $birthDate = $this->APA_DTNASC;
        if (strlen($birthDate) === 8) {
            $year = (int) substr($birthDate, 0, 4);
            $month = (int) substr($birthDate, 4, 2);
            $day = (int) substr($birthDate, 6, 2);
            
            $birth = \Carbon\Carbon::create($year, $month, $day);
            return $birth->age;
        }
        
        return null;
    }

    /**
     * Get the patient's gender description.
     */
    public function getPatientGenderDescriptionAttribute()
    {
        return match($this->APA_SEXPCN) {
            'M' => 'Masculino',
            'F' => 'Feminino',
            default => 'Não informado'
        };
    }

    /**
     * Scope for OCI procedures (procedimentos that start with '09').
     */
    public function scopeOci($query)
    {
        return $query->where('APA_PRIPAL', 'like', '09%');
    }

    /**
     * Scope for specific prestador.
     */
    public function scopeForPrestador($query, $prestadorId)
    {
        return $query->where('APA_UID', $prestadorId);
    }

    /**
     * Scope for specific competence.
     */
    public function scopeForCompetencia($query, $competencia)
    {
        return $query->where('APA_MVM', $competencia);
    }

    /**
     * Scope for specific procedure.
     */
    public function scopeForProcedimento($query, $procedimento)
    {
        return $query->where('APA_PRIPAL', $procedimento);
    }

    /**
     * Scope for active APACs (with start and end dates).
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('APA_DTINIC')
                    ->whereNotNull('APA_DTFIM');
    }

    /**
     * Scope for APACs by patient name.
     */
    public function scopeByPatientName($query, $name)
    {
        return $query->where('APA_NMPCN', 'like', '%' . $name . '%');
    }
}
