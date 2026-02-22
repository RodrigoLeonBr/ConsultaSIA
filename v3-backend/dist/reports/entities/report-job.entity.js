"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.ReportJob = void 0;
const typeorm_1 = require("typeorm");
let ReportJob = class ReportJob {
    id;
    status;
    type;
    parameters;
    errorMessage;
    createdAt;
    startedAt;
    completedAt;
};
exports.ReportJob = ReportJob;
__decorate([
    (0, typeorm_1.PrimaryGeneratedColumn)(),
    __metadata("design:type", Number)
], ReportJob.prototype, "id", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'enum', enum: ['queued', 'running', 'done', 'failed'], default: 'queued' }),
    __metadata("design:type", String)
], ReportJob.prototype, "status", void 0);
__decorate([
    (0, typeorm_1.Column)({ length: 100 }),
    __metadata("design:type", String)
], ReportJob.prototype, "type", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'json', nullable: true, name: 'payload_json' }),
    __metadata("design:type", Object)
], ReportJob.prototype, "parameters", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'text', nullable: true, name: 'error_message' }),
    __metadata("design:type", String)
], ReportJob.prototype, "errorMessage", void 0);
__decorate([
    (0, typeorm_1.CreateDateColumn)({ name: 'created_at' }),
    __metadata("design:type", Date)
], ReportJob.prototype, "createdAt", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'timestamp', nullable: true, name: 'started_at' }),
    __metadata("design:type", Date)
], ReportJob.prototype, "startedAt", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'timestamp', nullable: true, name: 'finished_at' }),
    __metadata("design:type", Date)
], ReportJob.prototype, "completedAt", void 0);
exports.ReportJob = ReportJob = __decorate([
    (0, typeorm_1.Entity)('report_job', { database: 'producao' })
], ReportJob);
//# sourceMappingURL=report-job.entity.js.map