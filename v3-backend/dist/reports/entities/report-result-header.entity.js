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
exports.ReportResultHeader = void 0;
const typeorm_1 = require("typeorm");
const report_job_entity_1 = require("./report-job.entity");
let ReportResultHeader = class ReportResultHeader {
    id;
    job;
    reportType;
    rowCount;
    competence;
    filtersHash;
    sourceTablesVersionsJson;
    createdAt;
    ttlExpiresAt;
};
exports.ReportResultHeader = ReportResultHeader;
__decorate([
    (0, typeorm_1.PrimaryGeneratedColumn)({ name: 'result_id' }),
    __metadata("design:type", Number)
], ReportResultHeader.prototype, "id", void 0);
__decorate([
    (0, typeorm_1.ManyToOne)(() => report_job_entity_1.ReportJob, { onDelete: 'CASCADE' }),
    (0, typeorm_1.JoinColumn)({ name: 'job_id', referencedColumnName: 'id' }),
    __metadata("design:type", report_job_entity_1.ReportJob)
], ReportResultHeader.prototype, "job", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'report_type', length: 100 }),
    __metadata("design:type", String)
], ReportResultHeader.prototype, "reportType", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'row_count', default: 0 }),
    __metadata("design:type", Number)
], ReportResultHeader.prototype, "rowCount", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'varchar', name: 'competence', length: 10, nullable: true }),
    __metadata("design:type", Object)
], ReportResultHeader.prototype, "competence", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'varchar', name: 'filters_hash', length: 64, nullable: true }),
    __metadata("design:type", Object)
], ReportResultHeader.prototype, "filtersHash", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'json', nullable: true, name: 'source_tables_versions_json' }),
    __metadata("design:type", Object)
], ReportResultHeader.prototype, "sourceTablesVersionsJson", void 0);
__decorate([
    (0, typeorm_1.CreateDateColumn)({ name: 'created_at' }),
    __metadata("design:type", Date)
], ReportResultHeader.prototype, "createdAt", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'timestamp', nullable: true, name: 'ttl_expires_at' }),
    __metadata("design:type", Object)
], ReportResultHeader.prototype, "ttlExpiresAt", void 0);
exports.ReportResultHeader = ReportResultHeader = __decorate([
    (0, typeorm_1.Entity)('report_result_header', { database: 'producao' })
], ReportResultHeader);
//# sourceMappingURL=report-result-header.entity.js.map