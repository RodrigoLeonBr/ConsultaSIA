import { IsString, IsNotEmpty, IsOptional, IsObject } from 'class-validator';

export class CreateJobDto {
    @IsString()
    @IsNotEmpty()
    type: string;

    @IsObject()
    @IsOptional()
    parameters?: Record<string, any>;
}
