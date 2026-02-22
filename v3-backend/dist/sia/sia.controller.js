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
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.SiaController = void 0;
const common_1 = require("@nestjs/common");
const sia_service_1 = require("./sia.service");
const get_sia_reports_dto_1 = require("./dto/get-sia-reports.dto");
let SiaController = class SiaController {
    siaService;
    constructor(siaService) {
        this.siaService = siaService;
    }
    async getReports(query) {
        return this.siaService.getReports(query);
    }
};
exports.SiaController = SiaController;
__decorate([
    (0, common_1.Get)(),
    __param(0, (0, common_1.Query)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [get_sia_reports_dto_1.GetSiaReportsDto]),
    __metadata("design:returntype", Promise)
], SiaController.prototype, "getReports", null);
exports.SiaController = SiaController = __decorate([
    (0, common_1.Controller)('reports/sia'),
    __metadata("design:paramtypes", [sia_service_1.SiaService])
], SiaController);
//# sourceMappingURL=sia.controller.js.map