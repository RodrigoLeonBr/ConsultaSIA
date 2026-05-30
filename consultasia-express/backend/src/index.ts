import 'dotenv/config';
import { db } from './db';
import { sql } from 'drizzle-orm';

async function main() {
  const result = await db.execute(sql`SELECT 1+1 AS result`);
  console.log('DB connection OK:', (result as any)[0][0]);
  process.exit(0);
}
main().catch(err => { console.error('DB FAILED:', err.message, err.cause ?? err); process.exit(1); });
