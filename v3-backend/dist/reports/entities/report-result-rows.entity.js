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
exports.ReportResultRow = void 0;
const typeorm_1 = require("typeorm");
const report_result_header_entity_1 = require("./report-result-header.entity");
let ReportResultRow = class ReportResultRow {
    id;
    header;
    rowIndex;
    rowJson;
};
exports.ReportResultRow = ReportResultRow;
__decorate([
    (0, typeorm_1.PrimaryColumn)({ name: 'result_id', type: 'bigint' }),
    __metadata("design:type", String)
], ReportResultRow.prototype, "id", void 0);
__decorate([
    (0, typeorm_1.ManyToOne)(() => report_result_header_entity_1.ReportResultHeader, { onDelete: 'CASCADE' }),
    (0, typeorm_1.JoinColumn)({ name: 'result_id', referencedColumnName: 'id' }),
    __metadata("design:type", report_result_header_entity_1.ReportResultHeader)
], ReportResultRow.prototype, "header", void 0);
__decorate([
    (0, typeorm_1.PrimaryColumn)({ name: 'row_index' }),
    __metadata("design:type", Number)
], ReportResultRow.prototype, "rowIndex", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'longtext', name: 'row_json' }),
    __metadata("design:type", String)
], ReportResultRow.prototype, "rowJson", void 0);
exports.ReportResultRow = ReportResultRow = __decorate([
    (0, typeorm_1.Entity)('report_result_rows', { database: 'producao' }),
    (0, typeorm_1.Index)(['header', 'rowIndex'])
], ReportResultRow);
//# sourceMappingURL=report-result-rows.entity.js.map