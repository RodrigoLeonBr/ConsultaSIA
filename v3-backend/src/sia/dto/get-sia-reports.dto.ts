import { IsOptional, IsString, IsInt, IsNotEmpty, Min, Max, Length } from 'class-validator';
import { Type } from 'class-transformer';

export class GetSiaReportsDto {
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
    limit?: number = 50;

    // Competência obrigatória — sem ela a query faz full table scan em 6M+ rows
    @IsString()
    @IsNotEmpty()
    @Length(6, 6)
    competence: string;

    // CNES do prestador (s_prd.prd_uid = prestador.re_cunid, varchar 7) — opcional
    @IsOptional()
    @IsString()
    @Length(1, 7)
    providerId?: string;
}
