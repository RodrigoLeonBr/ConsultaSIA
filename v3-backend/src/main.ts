import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { ValidationPipe } from '@nestjs/common';
import { LoggingInterceptor } from './common/interceptors/logging.interceptor';

async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  // Habilita validação global (Transforma DTOs e impõe pipes)
  app.useGlobalPipes(new ValidationPipe({ transform: true, whitelist: true, forbidNonWhitelisted: true }));

  // Interceptador para medir Response Time / p95 do endpoint
  app.useGlobalInterceptors(new LoggingInterceptor());

  // Prevent starting the HTTP server logic if it's meant to purely parse Jobs (Worker Mode)
  if (process.env.RUN_WORKER !== 'true') {
    await app.listen(process.env.PORT ?? 3000);
  } else {
    await app.init(); // Just boot the IOC container and services (TypeORM/Cron)
  }
}
bootstrap();
