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
exports.SiaProductionQueryDto = exports.SortDto = exports.FilterItemDto = exports.VALID_SIA_FIELD_IDS = exports.VALID_OPERATORS = void 0;
const class_validator_1 = require("class-validator");
const class_transformer_1 = require("class-transformer");
exports.VALID_OPERATORS = [
    '=', '>', '<', '>=', '<=',
    'like', 'starts_with', 'ends_with',
    'between', 'in',
];
exports.VALID_SIA_FIELD_IDS = [
    'prd_cmp', 'prd_uid', 'prd_cbo', 'prd_pa', 'procedimento_descricao',
    'PRD_QT_P', 'PRD_VL_P', 'PRD_QT_A', 'PRD_VL_A',
    'PRD_RUB', 'PRD_CIDPRI',
    'cismetro_valor', 'cismetro_total', 'cismetro_descricao',
];
class FilterItemDto {
    fieldId;
    operator;
    value;
}
exports.FilterItemDto = FilterItemDto;
__decorate([
    (0, class_validator_1.IsString)(),
    (0, class_validator_1.IsIn)(exports.VALID_SIA_FIELD_IDS),
    __metadata("design:type", String)
], FilterItemDto.prototype, "fieldId", void 0);
__decorate([
    (0, class_validator_1.IsString)(),
    (0, class_validator_1.IsIn)(exports.VALID_OPERATORS),
    __metadata("design:type", String)
], FilterItemDto.prototype, "operator", void 0);
__decorate([
    (0, class_validator_1.IsDefined)(),
    __metadata("design:type", Object)
], FilterItemDto.prototype, "value", void 0);
class SortDto {
    fieldId;
    direction;
}
exports.SortDto = SortDto;
__decorate([
    (0, class_validator_1.IsString)(),
    (0, class_validator_1.IsIn)(exports.VALID_SIA_FIELD_IDS),
    __metadata("design:type", String)
], SortDto.prototype, "fieldId", void 0);
__decorate([
    (0, class_validator_1.IsString)(),
    (0, class_validator_1.IsIn)(['ASC', 'DESC']),
    __metadata("design:type", String)
], SortDto.prototype, "direction", void 0);
class SiaProductionQueryDto {
    competence;
    select;
    filters;
    page = 1;
    pageSize = 50;
    sort;
}
exports.SiaProductionQueryDto = SiaProductionQueryDto;
__decorate([
    (0, class_validator_1.IsString)(),
    (0, class_validator_1.IsNotEmpty)(),
    (0, class_validator_1.Length)(6, 6),
    __metadata("design:type", String)
], SiaProductionQueryDto.prototype, "competence", void 0);
__decorate([
    (0, class_validator_1.IsArray)(),
    (0, class_validator_1.ArrayMaxSize)(20),
    (0, class_validator_1.IsString)({ each: true }),
    (0, class_validator_1.IsIn)(exports.VALID_SIA_FIELD_IDS, { each: true }),
    __metadata("design:type", Array)
], SiaProductionQueryDto.prototype, "select", void 0);
__decorate([
    (0, class_validator_1.IsOptional)(),
    (0, class_validator_1.IsArray)(),
    (0, class_validator_1.ArrayMaxSize)(20),
    (0, class_validator_1.ValidateNested)({ each: true }),
    (0, class_transformer_1.Type)(() => FilterItemDto),
    __metadata("design:type", Array)
], SiaProductionQueryDto.prototype, "filters", void 0);
__decorate([
    (0, class_validator_1.IsOptional)(),
    (0, class_transformer_1.Type)(() => Number),
    (0, class_validator_1.IsInt)(),
    (0, class_validator_1.Min)(1),
    __metadata("design:type", Number)
], SiaProductionQueryDto.prototype, "page", void 0);
__decorate([
    (0, class_validator_1.IsOptional)(),
    (0, class_transformer_1.Type)(() => Number),
    (0, class_validator_1.IsInt)(),
    (0, class_validator_1.Min)(1),
    (0, class_validator_1.Max)(500),
    __metadata("design:type", Number)
], SiaProductionQueryDto.prototype, "pageSize", void 0);
__decorate([
    (0, class_validator_1.IsOptional)(),
    (0, class_validator_1.ValidateNested)(),
    (0, class_transformer_1.Type)(() => SortDto),
    __metadata("design:type", SortDto)
], SiaProductionQueryDto.prototype, "sort", void 0);
//# sourceMappingURL=sia-production-query.dto.js.map