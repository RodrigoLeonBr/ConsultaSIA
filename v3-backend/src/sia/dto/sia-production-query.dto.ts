import {
    IsArray,
    IsIn,
    IsInt,
    IsNotEmpty,
    IsOptional,
    IsString,
    Length,
    Max,
    Min,
    ArrayMaxSize,
    ValidateNested,
    IsDefined,
} from 'class-validator';
import { Type } from 'class-transformer';

// Operadores aceitos — subconjunto do catálogo
export const VALID_OPERATORS = [
    '=', '>', '<', '>=', '<=',
    'like', 'starts_with', 'ends_with',
    'between', 'in',
] as const;

export type FilterOperator = (typeof VALID_OPERATORS)[number];

// IDs válidos — importados no DTO para @IsIn; a lista canônica está no catálogo
// Mantida aqui para evitar dependência circular no contexto de validação
export const VALID_SIA_FIELD_IDS = [
    'prd_cmp', 'prd_uid', 'prd_cbo', 'prd_pa', 'procedimento_descricao',
    'PRD_QT_P', 'PRD_VL_P', 'PRD_QT_A', 'PRD_VL_A',
    'PRD_RUB', 'PRD_CIDPRI',
    'cismetro_valor', 'cismetro_total', 'cismetro_descricao',
] as const;

export type SiaFieldId = (typeof VALID_SIA_FIELD_IDS)[number];

// ─── Sub-DTOs ─────────────────────────────────────────────────────────────────

export class FilterItemDto {
    @IsString()
    @IsIn(VALID_SIA_FIELD_IDS)
    fieldId: string;

    @IsString()
    @IsIn(VALID_OPERATORS)
    operator: string;

    /**
     * Para operadores "between" e "in": array de strings.
     * Para os demais: string simples.
     * Validação do tipo (string vs array) é feita no service conforme o operador.
     */
    @IsDefined()
    value: unknown;
}

export class SortDto {
    @IsString()
    @IsIn(VALID_SIA_FIELD_IDS)
    fieldId: string;

    @IsString()
    @IsIn(['ASC', 'DESC'])
    direction: 'ASC' | 'DESC';
}

// ─── DTO principal ────────────────────────────────────────────────────────────

export class SiaProductionQueryDto {
    /** Competência obrigatória no formato AAAAMM (ex: 202301) */
    @IsString()
    @IsNotEmpty()
    @Length(6, 6)
    competence: string;

    /** Campos a exibir — ao menos 1, máximo 20 */
    @IsArray()
    @ArrayMaxSize(20)
    @IsString({ each: true })
    @IsIn(VALID_SIA_FIELD_IDS, { each: true })
    select: string[];

    /** Filtros dinâmicos — máximo 20 */
    @IsOptional()
    @IsArray()
    @ArrayMaxSize(20)
    @ValidateNested({ each: true })
    @Type(() => FilterItemDto)
    filters?: FilterItemDto[];

    @IsOptional()
    @Type(() => Number)
    @IsInt()
    @Min(1)
    page?: number = 1;

    @IsOptional()
    @Type(() => Number)
    @IsInt()
    @Min(1)
    @Max(500)
    pageSize?: number = 50;

    /** Ordenação — somente campos com sortable=true no catálogo */
    @IsOptional()
    @ValidateNested()
    @Type(() => SortDto)
    sort?: SortDto;
}
