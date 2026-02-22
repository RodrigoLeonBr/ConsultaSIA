"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.SiaModule = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const sia_controller_1 = require("./sia.controller");
const sia_service_1 = require("./sia.service");
const s_pap_entity_1 = require("./entities/s-pap.entity");
let SiaModule = class SiaModule {
};
exports.SiaModule = SiaModule;
exports.SiaModule = SiaModule = __decorate([
    (0, common_1.Module)({
        imports: [typeorm_1.TypeOrmModule.forFeature([s_pap_entity_1.SPap])],
        controllers: [sia_controller_1.SiaController],
        providers: [sia_service_1.SiaService],
    })
], SiaModule);
//# sourceMappingURL=sia.module.js.map