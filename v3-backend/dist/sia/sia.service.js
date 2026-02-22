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
exports.SiaService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const s_pap_entity_1 = require("./entities/s-pap.entity");
let SiaService = class SiaService {
    sPapRepository;
    constructor(sPapRepository) {
        this.sPapRepository = sPapRepository;
    }
    async getReports(queryDto) {
        const { page = 1, limit = 50, competence, providerId } = queryDto;
        const skip = (page - 1) * limit;
        const queryBuilder = this.sPapRepository.createQueryBuilder('s_pap');
        if (competence) {
            queryBuilder.andWhere('s_pap.PAP_CMP = :competence', { competence });
        }
        if (providerId) {
            queryBuilder.andWhere('s_pap.PAP_CNPJ = :providerId', { providerId });
        }
        const startTime = Date.now();
        const [rows, totalRows] = await queryBuilder
            .skip(skip)
            .take(limit)
            .getManyAndCount();
        const queryTimeMs = Date.now() - startTime;
        return {
            data: rows,
            meta: {
                totalRows,
                page,
                pageSize: limit,
                totalPages: Math.ceil(totalRows / limit),
                queryTimeMs,
            }
        };
    }
};
exports.SiaService = SiaService;
exports.SiaService = SiaService = __decorate([
    (0, common_1.Injectable)(),
    __param(0, (0, typeorm_1.InjectRepository)(s_pap_entity_1.SPap)),
    __metadata("design:paramtypes", [typeorm_2.Repository])
], SiaService);
//# sourceMappingURL=sia.service.js.map