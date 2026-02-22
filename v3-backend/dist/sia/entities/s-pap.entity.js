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
exports.SPap = void 0;
const typeorm_1 = require("typeorm");
let SPap = class SPap {
    papNum;
    competence;
    providerId;
    procedureCode;
    quantityApproved;
    federalValue;
    cbo;
};
exports.SPap = SPap;
__decorate([
    (0, typeorm_1.PrimaryColumn)({ name: 'PAP_NUM', type: 'varchar', length: 13 }),
    __metadata("design:type", String)
], SPap.prototype, "papNum", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PAP_CMP', type: 'varchar', length: 6, nullable: true }),
    __metadata("design:type", String)
], SPap.prototype, "competence", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PAP_CNPJ', type: 'varchar', length: 14, nullable: true }),
    __metadata("design:type", String)
], SPap.prototype, "providerId", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PAP_PA', type: 'varchar', length: 10, nullable: true }),
    __metadata("design:type", String)
], SPap.prototype, "procedureCode", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PAP_QT_A', type: 'double', nullable: true }),
    __metadata("design:type", Number)
], SPap.prototype, "quantityApproved", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PAP_VL_FED', type: 'double', nullable: true }),
    __metadata("design:type", Number)
], SPap.prototype, "federalValue", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PAP_CBO', type: 'varchar', length: 6, nullable: true }),
    __metadata("design:type", String)
], SPap.prototype, "cbo", void 0);
exports.SPap = SPap = __decorate([
    (0, typeorm_1.Entity)('s_pap', { database: 'producao' })
], SPap);
//# sourceMappingURL=s-pap.entity.js.map