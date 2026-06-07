"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const core_1 = require("@nestjs/core");
const app_module_1 = require("./app.module");
const common_1 = require("@nestjs/common");
const logging_interceptor_1 = require("./common/interceptors/logging.interceptor");
async function bootstrap() {
    const app = await core_1.NestFactory.create(app_module_1.AppModule);
    app.useGlobalPipes(new common_1.ValidationPipe({ transform: true, whitelist: true, forbidNonWhitelisted: true }));
    app.enableCors({
        origin: process.env.CORS_ORIGIN || 'http://localhost:5173',
        methods: ['GET', 'POST'],
    });
    app.useGlobalInterceptors(new logging_interceptor_1.LoggingInterceptor());
    if (process.env.RUN_WORKER !== 'true') {
        await app.listen(process.env.PORT ?? 3000);
    }
    else {
        await app.init();
    }
}
bootstrap();
//# sourceMappingURL=main.js.map