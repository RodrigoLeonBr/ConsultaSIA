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
exports.SPrd = void 0;
const typeorm_1 = require("typeorm");
let SPrd = class SPrd {
    id;
    prestadorCnes;
    competence;
    procedureCode;
    cbo;
    quantityPresented;
    quantityApproved;
    valuePresented;
    valueApproved;
    financingType;
    grupo;
    subgrupo;
    forma;
    cnpj;
};
exports.SPrd = SPrd;
__decorate([
    (0, typeorm_1.PrimaryGeneratedColumn)({ name: 'id', type: 'bigint', unsigned: true }),
    __metadata("design:type", Number)
], SPrd.prototype, "id", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'prd_uid', type: 'varchar', length: 7 }),
    __metadata("design:type", String)
], SPrd.prototype, "prestadorCnes", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'prd_cmp', type: 'varchar', length: 6 }),
    __metadata("design:type", String)
], SPrd.prototype, "competence", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'prd_pa', type: 'varchar', length: 10 }),
    __metadata("design:type", String)
], SPrd.prototype, "procedureCode", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'prd_cbo', type: 'varchar', length: 8, nullable: true }),
    __metadata("design:type", String)
], SPrd.prototype, "cbo", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PRD_QT_P', type: 'int', nullable: true }),
    __metadata("design:type", Number)
], SPrd.prototype, "quantityPresented", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PRD_QT_A', type: 'int', nullable: true }),
    __metadata("design:type", Number)
], SPrd.prototype, "quantityApproved", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PRD_VL_P', type: 'decimal', precision: 15, scale: 2, nullable: true }),
    __metadata("design:type", String)
], SPrd.prototype, "valuePresented", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PRD_VL_A', type: 'decimal', precision: 15, scale: 2, nullable: true }),
    __metadata("design:type", String)
], SPrd.prototype, "valueApproved", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'prd_rub', type: 'varchar', length: 6, nullable: true }),
    __metadata("design:type", String)
], SPrd.prototype, "financingType", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'grupo', type: 'varchar', length: 2, nullable: true, insert: false, update: false }),
    __metadata("design:type", String)
], SPrd.prototype, "grupo", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'subgrupo', type: 'varchar', length: 4, nullable: true, insert: false, update: false }),
    __metadata("design:type", String)
], SPrd.prototype, "subgrupo", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'forma', type: 'varchar', length: 6, nullable: true, insert: false, update: false }),
    __metadata("design:type", String)
], SPrd.prototype, "forma", void 0);
__decorate([
    (0, typeorm_1.Column)({ name: 'PRD_CNPJ', type: 'varchar', length: 14, nullable: true }),
    __metadata("design:type", String)
], SPrd.prototype, "cnpj", void 0);
exports.SPrd = SPrd = __decorate([
    (0, typeorm_1.Entity)('s_prd', { database: 'producao' })
], SPrd);
//# sourceMappingURL=s-prd.entity.js.map