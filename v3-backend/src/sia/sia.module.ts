import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { SiaController } from './sia.controller';
import { SiaService } from './sia.service';
import { SPrd } from './entities/s-prd.entity';

@Module({
    imports: [TypeOrmModule.forFeature([SPrd])],
    controllers: [SiaController],
    providers: [SiaService],
})
export class SiaModule { }
