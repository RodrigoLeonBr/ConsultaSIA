"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.AppModule = void 0;
const common_1 = require("@nestjs/common");
const config_1 = require("@nestjs/config");
const typeorm_1 = require("@nestjs/typeorm");
const reports_module_1 = require("./reports/reports.module");
const worker_module_1 = require("./worker/worker.module");
const sia_module_1 = require("./sia/sia.module");
let AppModule = class AppModule {
};
exports.AppModule = AppModule;
exports.AppModule = AppModule = __decorate([
    (0, common_1.Module)({
        imports: [
            config_1.ConfigModule.forRoot({
                isGlobal: true,
                envFilePath: ['.env', '../.env'],
            }),
            typeorm_1.TypeOrmModule.forRootAsync({
                imports: [config_1.ConfigModule],
                useFactory: (configService) => ({
                    type: 'mysql',
                    host: configService.get('DB_HOST', '127.0.0.1'),
                    port: configService.get('DB_PORT', 3306),
                    username: configService.get('DB_USER') || configService.get('DB_USERNAME', 'hospital'),
                    password: configService.get('DB_PASSWORD') || '',
                    database: configService.get('DB_NAME') || configService.get('DB_DATABASE', 'producao'),
                    entities: [__dirname + '/**/*.entity{.ts,.js}'],
                    synchronize: false,
                    logging: true,
                }),
                inject: [config_1.ConfigService],
            }),
            reports_module_1.ReportsModule,
            worker_module_1.WorkerModule,
            sia_module_1.SiaModule,
        ],
    })
], AppModule);
//# sourceMappingURL=app.module.js.map