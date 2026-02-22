<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder|Cbo query()
 * @method static Cbo updateOrCreate(array $attributes, array $values = [])
 * @method static Cbo firstOrCreate(array $attributes, array $values = [])
 * @method static Cbo create(array $attributes = [])
 * @method static Cbo find(mixed $id, array $columns = ['*'])
 * @method static Cbo findOrFail(mixed $id, array $columns = ['*'])
 * @method static Cbo first(array $columns = ['*'])
 * @method static Cbo where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @property string $cbo
 * @property string $ds_cbo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SPrd> $sPrds
 * @property-read int|null $s_prds_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cbo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cbo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cbo whereCbo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cbo whereDsCbo($value)
 */
	class Cbo extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder|Prestador query()
 * @method static Prestador updateOrCreate(array $attributes, array $values = [])
 * @method static Prestador firstOrCreate(array $attributes, array $values = [])
 * @method static Prestador create(array $attributes = [])
 * @method static Prestador find(mixed $id, array $columns = ['*'])
 * @method static Prestador findOrFail(mixed $id, array $columns = ['*'])
 * @method static Prestador first(array $columns = ['*'])
 * @method static Prestador where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @property string $re_cunid
 * @property string $re_cnome
 * @property string $re_tipo
 * @property string|null $cnpj
 * @property int $area
 * @property string $tipouni
 * @property string|null $relatorio
 * @property bool $ativo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SPrd> $sPrds
 * @property-read int|null $s_prds_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador fisica()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador juridica()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereArea($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereAtivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereCnpj($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereReCnome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereReCunid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereReTipo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereRelatorio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prestador whereTipouni($value)
 */
	class Prestador extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $codigo
 * @property string $procedimento
 * @property numeric $pa_total
 * @property string $rub_total
 * @property string $rub_dc
 * @property string $pa_rub
 * @property string $pa_id
 * @property string|null $financiamento
 * @property-read mixed $formatted_total
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SPrd> $sPrds
 * @property-read int|null $s_prds_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento whereFinanciamento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento wherePaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento wherePaRub($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento wherePaTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento whereProcedimento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento whereRubDc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Procedimento whereRubTotal($value)
 */
	class Procedimento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $prd_uid
 * @property string $prd_cmp
 * @property string $prd_flh
 * @property string $prd_seq
 * @property string $prd_pa
 * @property string $prd_cbo
 * @property int|null $prd_idade
 * @property int|null $prd_qt_p
 * @property int|null $prd_qt_a
 * @property numeric|null $prd_vl_p
 * @property numeric|null $prd_vl_a
 * @property string $prd_mvm
 * @property string $prd_org
 * @property string $prd_flpa
 * @property string $prd_flcbo
 * @property string $prd_flca
 * @property string $prd_flida
 * @property string $prd_flqt
 * @property string $prd_fler
 * @property string $prd_apanum
 * @property string|null $prd_cnsmed
 * @property string $prd_rms
 * @property string $prd_cnpj
 * @property string $prd_nfis
 * @property string $prd_resid
 * @property string $prd_rub
 * @property string $prd_cpx
 * @property string $prd_tpfin
 * @property int|null $prd_qtdatr
 * @property int|null $prd_qtdatu
 * @property string $prd_rc
 * @property string $prd_cidpri
 * @property string $prd_cidsec
 * @property string $prd_cidcas
 * @property string $prd_incout
 * @property string $prd_incurg
 * @property string|null $grupo
 * @property string|null $subgrupo
 * @property string|null $forma
 * @property-read \App\Models\Cbo|null $cbo
 * @property-read mixed $formatted_approved_value
 * @property-read mixed $formatted_presented_value
 * @property-read \App\Models\Prestador|null $prestador
 * @property-read \App\Models\Procedimento|null $procedimento
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd presented()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd whereForma($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd whereGrupo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdApanum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCbo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCidcas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCidpri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCidsec($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCmp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCnpj($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCnsmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdCpx($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdFlca($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdFlcbo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdFler($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdFlh($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdFlida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdFlpa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdFlqt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdIdade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdIncout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdIncurg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdMvm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdNfis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdOrg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdPa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdQtA($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdQtP($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdQtdatr($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdQtdatu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdRc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdResid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdRms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdRub($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdSeq($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdTpfin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdVlA($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd wherePrdVlP($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SPrd whereSubgrupo($value)
 */
	class SPrd extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder|SRub query()
 * @method static SRub updateOrCreate(array $attributes, array $values = [])
 * @method static SRub firstOrCreate(array $attributes, array $values = [])
 * @method static SRub create(array $attributes = [])
 * @method static SRub find(mixed $id, array $columns = ['*'])
 * @method static SRub findOrFail(mixed $id, array $columns = ['*'])
 * @method static SRub first(array $columns = ['*'])
 * @method static SRub where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @property string $rub_id
 * @property string $rub_dc
 * @property string $rub_total
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SRub newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SRub newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SRub whereRubDc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SRub whereRubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SRub whereRubTotal($value)
 */
	class SRub extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string|null $email
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $role
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $full_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User admins()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User operators()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 */
	class User extends \Eloquent {}
}

