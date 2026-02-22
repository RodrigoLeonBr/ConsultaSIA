import { Module } from '@nestjs/common';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ReportsModule } from './reports/reports.module';
import { WorkerModule } from './worker/worker.module';
import { SiaModule } from './sia/sia.module';

@Module({
  imports: [
    ConfigModule.forRoot({
      isGlobal: true,
      envFilePath: ['.env', '../.env'],
    }),
    TypeOrmModule.forRootAsync({
      imports: [ConfigModule],
      useFactory: (configService: ConfigService) => ({
        type: 'mysql',
        host: configService.get<string>('DB_HOST', '127.0.0.1'),
        port: configService.get<number>('DB_PORT', 3306),
        username: configService.get<string>('DB_USER') || configService.get<string>('DB_USERNAME', 'hospital'),
        password: configService.get<string>('DB_PASSWORD') || '',
        database: configService.get<string>('DB_NAME') || configService.get<string>('DB_DATABASE', 'producao'),
        entities: [__dirname + '/**/*.entity{.ts,.js}'],
        // Migrations are disabled in production to not alter the legacy core schema.
        synchronize: false,
        logging: true,
      }),
      inject: [ConfigService],
    }),
    ReportsModule,
    WorkerModule,
    SiaModule,
  ],
})
export class AppModule { }
