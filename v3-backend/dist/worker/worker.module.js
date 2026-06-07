"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.WorkerModule = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const worker_service_1 = require("./worker.service");
const report_job_entity_1 = require("../reports/entities/report-job.entity");
const report_result_header_entity_1 = require("../reports/entities/report-result-header.entity");
const report_result_rows_entity_1 = require("../reports/entities/report-result-rows.entity");
const s_prd_entity_1 = require("../sia/entities/s-prd.entity");
let WorkerModule = class WorkerModule {
};
exports.WorkerModule = WorkerModule;
exports.WorkerModule = WorkerModule = __decorate([
    (0, common_1.Module)({
        imports: [typeorm_1.TypeOrmModule.forFeature([report_job_entity_1.ReportJob, report_result_header_entity_1.ReportResultHeader, report_result_rows_entity_1.ReportResultRow, s_prd_entity_1.SPrd])],
        providers: [worker_service_1.WorkerService],
    })
], WorkerModule);
//# sourceMappingURL=worker.module.js.map