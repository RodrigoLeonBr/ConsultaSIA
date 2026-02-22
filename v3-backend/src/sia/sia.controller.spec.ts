import { Test, TestingModule } from '@nestjs/testing';
import { SiaController } from './sia.controller';
import { SiaService } from './sia.service';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';

describe('SiaController', () => {
    let controller: SiaController;
    let service: SiaService;

    const mockSiaService = {
        getReports: jest.fn((dto: GetSiaReportsDto) => {
            return {
                data: [],
                meta: {
                    totalRows: 0,
                    page: dto.page || 1,
                    pageSize: dto.limit || 50,
                    totalPages: 0,
                    queryTimeMs: 10,
                },
            };
        }),
    };

    beforeEach(async () => {
        const module: TestingModule = await Test.createTestingModule({
            controllers: [SiaController],
            providers: [
                {
                    provide: SiaService,
                    useValue: mockSiaService,
                },
            ],
        }).compile();

        controller = module.get<SiaController>(SiaController);
        service = module.get<SiaService>(SiaService);
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    it('should be defined', () => {
        expect(controller).toBeDefined();
    });

    it('should call service with default pagination when query is empty', async () => {
        const dto = new GetSiaReportsDto();

        const result = await controller.getReports(dto);

        expect(service.getReports).toHaveBeenCalledWith(dto);
        expect(result.meta.page).toBe(1);
        expect(result.meta.pageSize).toBe(50);
    });

    it('should call service with competence and provider filters', async () => {
        const dto: GetSiaReportsDto = {
            page: 2,
            limit: 100,
            competence: '202607',
            providerId: '12345678000100',
        };

        const result = await controller.getReports(dto);

        expect(service.getReports).toHaveBeenCalledWith(dto);
        expect(result.meta.page).toBe(2);
        expect(result.meta.pageSize).toBe(100);
    });

    // Since validation is handled by Global ValidationPipe at runtime, we mock the DTO payload
    // that would eventually reach the controller. NestJS Validation Pipe throws HTTP 400 for errors like (limit=1000).
});
