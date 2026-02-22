import { Test, TestingModule } from '@nestjs/testing';
import { ReportsService } from '../src/reports/reports.service';
import { getRepositoryToken } from '@nestjs/typeorm';
import { ReportJob } from '../src/reports/entities/report-job.entity';
import { ReportResultHeader } from '../src/reports/entities/report-result-header.entity';
import { ReportResultRow } from '../src/reports/entities/report-result-rows.entity';

describe('ReportsService', () => {
    let service: ReportsService;

    const mockReportJobRepository = {
        create: jest.fn().mockImplementation(dto => dto),
        save: jest.fn().mockImplementation(job => Promise.resolve({ id: Date.now(), ...job })),
    };

    const mockHeaderRepository = {};
    const mockRowsRepository = {};

    beforeEach(async () => {
        const module: TestingModule = await Test.createTestingModule({
            providers: [
                ReportsService,
                {
                    provide: getRepositoryToken(ReportJob),
                    useValue: mockReportJobRepository,
                },
                {
                    provide: getRepositoryToken(ReportResultHeader),
                    useValue: mockHeaderRepository,
                },
                {
                    provide: getRepositoryToken(ReportResultRow),
                    useValue: mockRowsRepository,
                },
            ],
        }).compile();

        service = module.get<ReportsService>(ReportsService);
    });

    it('should be defined', () => {
        expect(service).toBeDefined();
    });

    it('should create a job', async () => {
        const dto = { type: 'test-report', parameters: { foo: 'bar' } };
        expect(await service.createJob(dto)).toEqual({
            id: expect.any(Number),
            type: 'test-report',
            parameters: { foo: 'bar' },
            status: 'PENDING'
        });
    });
});
