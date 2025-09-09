import type { Express, Request } from "express";
import { createServer, type Server } from "http";

// Extend Express Request type to include user
interface AuthenticatedRequest extends Request {
  user: {
    id: string;
    username: string;
    role: string;
  };
}
import { storage } from "./storage";
import bcrypt from "bcrypt";
import jwt from "jsonwebtoken";
import { 
  insertUserSchema,
  insertCboSchema,
  insertPrestadorSchema,
  insertProcedimentoSchema,
  insertSRubSchema,
  insertConsultaProdSchema,
  loginSchema
} from "@shared/schema";

const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

// Authentication middleware
const authenticateToken = (req: AuthenticatedRequest, res: any, next: any) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ message: 'Access token required' });
  }

  jwt.verify(token, JWT_SECRET, (err: any, user: any) => {
    if (err) {
      return res.status(403).json({ message: 'Invalid token' });
    }
    req.user = user;
    next();
  });
};

// Admin role middleware
const requireAdmin = (req: AuthenticatedRequest, res: any, next: any) => {
  if (req.user.role !== 'admin') {
    return res.status(403).json({ message: 'Admin access required' });
  }
  next();
};

export async function registerRoutes(app: Express): Promise<Server> {
  // Authentication routes
  app.post('/api/auth/login', async (req, res) => {
    try {
      const { username, password } = loginSchema.parse(req.body);
      
      const user = await storage.getUserByUsername(username);
      if (!user || !user.active) {
        return res.status(401).json({ message: 'Invalid credentials' });
      }

      const isValidPassword = await bcrypt.compare(password, user.password);
      if (!isValidPassword) {
        return res.status(401).json({ message: 'Invalid credentials' });
      }

      const token = jwt.sign(
        { id: user.id, username: user.username, role: user.role },
        JWT_SECRET,
        { expiresIn: '24h' }
      );

      res.json({
        token,
        user: {
          id: user.id,
          username: user.username,
          email: user.email,
          firstName: user.firstName,
          lastName: user.lastName,
          role: user.role,
        }
      });
    } catch (error) {
      console.error('Login error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.get('/api/auth/me', authenticateToken, async (req: AuthenticatedRequest, res) => {
    try {
      const user = await storage.getUser(req.user.id);
      if (!user) {
        return res.status(404).json({ message: 'User not found' });
      }

      res.json({
        id: user.id,
        username: user.username,
        email: user.email,
        firstName: user.firstName,
        lastName: user.lastName,
        role: user.role,
      });
    } catch (error) {
      console.error('Auth me error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  // Dashboard routes
  app.get('/api/dashboard/stats', authenticateToken, async (req, res) => {
    try {
      const stats = await storage.getDashboardStats();
      res.json(stats);
    } catch (error) {
      console.error('Dashboard stats error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  // CBO routes
  app.get('/api/cbo', authenticateToken, async (req, res) => {
    try {
      const page = parseInt(req.query.page as string) || 1;
      const limit = parseInt(req.query.limit as string) || 10;
      const search = req.query.search as string;
      
      const result = await storage.getCBOs(page, limit, search);
      res.json(result);
    } catch (error) {
      console.error('Get CBOs error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  app.get('/api/cbo/:id', authenticateToken, async (req, res) => {
    try {
      const cbo = await storage.getCBO(req.params.id);
      if (!cbo) {
        return res.status(404).json({ message: 'CBO not found' });
      }
      res.json(cbo);
    } catch (error) {
      console.error('Get CBO error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  app.post('/api/cbo', authenticateToken, async (req, res) => {
    try {
      const cboData = insertCboSchema.parse(req.body);
      const cbo = await storage.createCBO(cboData);
      
      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'create',
        tableName: 'cbo',
        recordId: cbo.id,
        newValues: JSON.stringify(cbo),
      });
      
      res.status(201).json(cbo);
    } catch (error) {
      console.error('Create CBO error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.put('/api/cbo/:id', authenticateToken, async (req, res) => {
    try {
      const cboData = insertCboSchema.partial().parse(req.body);
      const oldCbo = await storage.getCBO(req.params.id);
      const cbo = await storage.updateCBO(req.params.id, cboData);
      
      if (!cbo) {
        return res.status(404).json({ message: 'CBO not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'update',
        tableName: 'cbo',
        recordId: cbo.id,
        oldValues: JSON.stringify(oldCbo),
        newValues: JSON.stringify(cbo),
      });
      
      res.json(cbo);
    } catch (error) {
      console.error('Update CBO error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.delete('/api/cbo/:id', authenticateToken, async (req, res) => {
    try {
      const oldCbo = await storage.getCBO(req.params.id);
      const success = await storage.deleteCBO(req.params.id);
      
      if (!success) {
        return res.status(404).json({ message: 'CBO not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'delete',
        tableName: 'cbo',
        recordId: req.params.id,
        oldValues: JSON.stringify(oldCbo),
      });
      
      res.status(204).send();
    } catch (error) {
      console.error('Delete CBO error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  // Prestador routes
  app.get('/api/prestador', authenticateToken, async (req, res) => {
    try {
      const page = parseInt(req.query.page as string) || 1;
      const limit = parseInt(req.query.limit as string) || 10;
      const search = req.query.search as string;
      
      const result = await storage.getPrestadores(page, limit, search);
      res.json(result);
    } catch (error) {
      console.error('Get Prestadores error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  app.post('/api/prestador', authenticateToken, async (req, res) => {
    try {
      const prestadorData = insertPrestadorSchema.parse(req.body);
      const prestador = await storage.createPrestador(prestadorData);
      
      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'create',
        tableName: 'prestador',
        recordId: prestador.id,
        newValues: JSON.stringify(prestador),
      });
      
      res.status(201).json(prestador);
    } catch (error) {
      console.error('Create Prestador error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.put('/api/prestador/:id', authenticateToken, async (req, res) => {
    try {
      const prestadorData = insertPrestadorSchema.partial().parse(req.body);
      const oldPrestador = await storage.getPrestador(req.params.id);
      const prestador = await storage.updatePrestador(req.params.id, prestadorData);
      
      if (!prestador) {
        return res.status(404).json({ message: 'Prestador not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'update',
        tableName: 'prestador',
        recordId: prestador.id,
        oldValues: JSON.stringify(oldPrestador),
        newValues: JSON.stringify(prestador),
      });
      
      res.json(prestador);
    } catch (error) {
      console.error('Update Prestador error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.delete('/api/prestador/:id', authenticateToken, async (req, res) => {
    try {
      const oldPrestador = await storage.getPrestador(req.params.id);
      const success = await storage.deletePrestador(req.params.id);
      
      if (!success) {
        return res.status(404).json({ message: 'Prestador not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'delete',
        tableName: 'prestador',
        recordId: req.params.id,
        oldValues: JSON.stringify(oldPrestador),
      });
      
      res.status(204).send();
    } catch (error) {
      console.error('Delete Prestador error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  // Procedimento routes
  app.get('/api/procedimento', authenticateToken, async (req, res) => {
    try {
      const page = parseInt(req.query.page as string) || 1;
      const limit = parseInt(req.query.limit as string) || 10;
      const search = req.query.search as string;
      
      const result = await storage.getProcedimentos(page, limit, search);
      res.json(result);
    } catch (error) {
      console.error('Get Procedimentos error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  app.post('/api/procedimento', authenticateToken, async (req, res) => {
    try {
      const procedimentoData = insertProcedimentoSchema.parse(req.body);
      const procedimento = await storage.createProcedimento(procedimentoData);
      
      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'create',
        tableName: 'procedimento',
        recordId: procedimento.id,
        newValues: JSON.stringify(procedimento),
      });
      
      res.status(201).json(procedimento);
    } catch (error) {
      console.error('Create Procedimento error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.put('/api/procedimento/:id', authenticateToken, async (req, res) => {
    try {
      const procedimentoData = insertProcedimentoSchema.partial().parse(req.body);
      const oldProcedimento = await storage.getProcedimento(req.params.id);
      const procedimento = await storage.updateProcedimento(req.params.id, procedimentoData);
      
      if (!procedimento) {
        return res.status(404).json({ message: 'Procedimento not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'update',
        tableName: 'procedimento',
        recordId: procedimento.id,
        oldValues: JSON.stringify(oldProcedimento),
        newValues: JSON.stringify(procedimento),
      });
      
      res.json(procedimento);
    } catch (error) {
      console.error('Update Procedimento error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.delete('/api/procedimento/:id', authenticateToken, async (req, res) => {
    try {
      const oldProcedimento = await storage.getProcedimento(req.params.id);
      const success = await storage.deleteProcedimento(req.params.id);
      
      if (!success) {
        return res.status(404).json({ message: 'Procedimento not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'delete',
        tableName: 'procedimento',
        recordId: req.params.id,
        oldValues: JSON.stringify(oldProcedimento),
      });
      
      res.status(204).send();
    } catch (error) {
      console.error('Delete Procedimento error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  // S_RUB routes
  app.get('/api/srub', authenticateToken, async (req, res) => {
    try {
      const page = parseInt(req.query.page as string) || 1;
      const limit = parseInt(req.query.limit as string) || 10;
      const search = req.query.search as string;
      
      const result = await storage.getSRubs(page, limit, search);
      res.json(result);
    } catch (error) {
      console.error('Get SRubs error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  app.post('/api/srub', authenticateToken, async (req, res) => {
    try {
      const srubData = insertSRubSchema.parse(req.body);
      const srub = await storage.createSRub(srubData);
      
      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'create',
        tableName: 's_rub',
        recordId: srub.id,
        newValues: JSON.stringify(srub),
      });
      
      res.status(201).json(srub);
    } catch (error) {
      console.error('Create SRub error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.put('/api/srub/:id', authenticateToken, async (req, res) => {
    try {
      const srubData = insertSRubSchema.partial().parse(req.body);
      const oldSRub = await storage.getSRub(req.params.id);
      const srub = await storage.updateSRub(req.params.id, srubData);
      
      if (!srub) {
        return res.status(404).json({ message: 'SRub not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'update',
        tableName: 's_rub',
        recordId: srub.id,
        oldValues: JSON.stringify(oldSRub),
        newValues: JSON.stringify(srub),
      });
      
      res.json(srub);
    } catch (error) {
      console.error('Update SRub error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  app.delete('/api/srub/:id', authenticateToken, async (req, res) => {
    try {
      const oldSRub = await storage.getSRub(req.params.id);
      const success = await storage.deleteSRub(req.params.id);
      
      if (!success) {
        return res.status(404).json({ message: 'SRub not found' });
      }

      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'delete',
        tableName: 's_rub',
        recordId: req.params.id,
        oldValues: JSON.stringify(oldSRub),
      });
      
      res.status(204).send();
    } catch (error) {
      console.error('Delete SRub error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  // Reports routes
  app.get('/api/reports/data', authenticateToken, async (req, res) => {
    try {
      const page = parseInt(req.query.page as string) || 1;
      const limit = parseInt(req.query.limit as string) || 50;
      const filters = req.query.filters ? JSON.parse(req.query.filters as string) : {};
      
      const result = await storage.getConsultaProdData(filters, page, limit);
      res.json(result);
    } catch (error) {
      console.error('Get reports data error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  app.post('/api/reports/export', authenticateToken, async (req, res) => {
    try {
      const { format, filters, fields } = req.body;
      
      // Get data for export
      const result = await storage.getConsultaProdData(filters, 1, 10000); // Large limit for export
      
      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'export',
        tableName: 'consulta_prod',
        newValues: JSON.stringify({ format, filters, fields, recordCount: result.total }),
      });

      // Here you would implement actual export logic for CSV, Excel, PDF
      // For now, return the data
      res.json({
        success: true,
        message: `Export in ${format} format initiated`,
        data: result.data.slice(0, 100), // Limit for demo
      });
    } catch (error) {
      console.error('Export error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  // User management routes (Admin only)
  app.get('/api/users', authenticateToken, requireAdmin, async (req, res) => {
    try {
      // This would implement user listing for admin
      res.json({ message: 'User management endpoint - to be implemented' });
    } catch (error) {
      console.error('Get users error:', error);
      res.status(500).json({ message: 'Internal server error' });
    }
  });

  app.post('/api/users', authenticateToken, requireAdmin, async (req, res) => {
    try {
      const userData = insertUserSchema.parse(req.body);
      const user = await storage.createUser(userData);
      
      // Audit log
      await storage.createAuditLog({
        userId: (req as AuthenticatedRequest).user.id,
        action: 'create',
        tableName: 'users',
        recordId: user.id,
        newValues: JSON.stringify({ ...user, password: '[HIDDEN]' }),
      });
      
      res.status(201).json({ ...user, password: undefined });
    } catch (error) {
      console.error('Create user error:', error);
      res.status(400).json({ message: 'Invalid request data' });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}
