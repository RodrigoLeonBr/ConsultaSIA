import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { SiaController } from './sia.controller';
import { SiaService } from './sia.service';
import { SPap } from './entities/s-pap.entity';

@Module({
    imports: [TypeOrmModule.forFeature([SPap])],
    controllers: [SiaController],
    providers: [SiaService],
})
export class SiaModule { }
