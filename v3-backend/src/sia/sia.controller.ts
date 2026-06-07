import { Controller, Get, Post, Query, Body } from '@nestjs/common';
import { SiaService } from './sia.service';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';
import { GetBillingProviderDto } from './dto/get-billing-provider.dto';
import { SiaProductionQueryDto } from './dto/sia-production-query.dto';

@Controller('reports/sia')
export class SiaController {
    constructor(private readonly siaService: SiaService) { }

    // ─── Existentes ───────────────────────────────────────────────────────────

    @Get()
    async getReports(@Query() query: GetSiaReportsDto) {
        return this.siaService.getReports(query);
    }

    @Get('faturamento-prestador')
    async getBillingProvider(@Query() query: GetBillingProviderDto) {
        return this.siaService.getBillingProvider(query);
    }

    // ─── Novos ────────────────────────────────────────────────────────────────

    /**
     * Metadados do catálogo de campos SIA.
     * Retorna a lista de campos disponíveis, operadores permitidos e limites.
     */
    @Get('metadata')
    getMetadata() {
        return this.siaService.getMetadata();
    }

    /**
     * Relatório dinâmico de Produção SIA.
     * Permite selecionar colunas, aplicar filtros compostos e ordenação.
     * Todos os campos e operadores são validados contra o Field Catalog (whitelist).
     */
    @Post('production')
    async getProduction(@Body() body: SiaProductionQueryDto) {
        return this.siaService.getDynamicProduction(body);
    }
}
