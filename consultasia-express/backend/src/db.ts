import { drizzle } from 'drizzle-orm/mysql2';
import mysql from 'mysql2/promise';

const pool = mysql.createPool({
  host: process.env.DB_HOST ?? 'localhost',
  port: Number(process.env.DB_PORT ?? 3306),
  user: process.env.DB_USER ?? 'root',
  password: process.env.DB_PASSWORD ?? '', // XAMPP default: root has no password
  database: process.env.DB_NAME ?? 'producao',
  connectionLimit: 10,
  timezone: 'Z',
});

pool.on('error', (err: Error) => {
  console.error('[DB] Pool connection error:', err.message);
});

export const db = drizzle(pool);
export { pool };
