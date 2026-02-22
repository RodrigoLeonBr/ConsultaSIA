import { IsOptional, IsString, IsInt, Min, Max, Length } from 'class-validator';
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

    @IsOptional()
    @IsString()
    @Length(6, 6)
    competence?: string;

    @IsOptional()
    @IsString()
    @Length(14, 14)
    providerId?: string;
}
