import { IsOptional, IsString, IsInt, IsNotEmpty, Min, Max, Length } from 'class-validator';
import { Type } from 'class-transformer';

export class GetBillingProviderDto {
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

    // Competência obrigatória — JOIN com 6M+ rows sem filtro é inviável
    @IsString()
    @IsNotEmpty()
    @Length(6, 6)
    competence: string;

    // CNES do prestador — opcional para filtrar um único prestador
    @IsOptional()
    @IsString()
    @Length(1, 7)
    providerId?: string;
}
