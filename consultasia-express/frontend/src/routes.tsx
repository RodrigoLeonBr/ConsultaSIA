import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Shell } from './components/layout/Shell';
import { DashboardPage }      from './pages/DashboardPage';
import { PrestadoresPage }    from './pages/PrestadoresPage';
import { ProcedimentosPage }  from './pages/ProcedimentosPage';
import { CboPage }            from './pages/CboPage';
import { RubricasPage }       from './pages/RubricasPage';
import { SiaDynamicPage }     from './pages/SiaDynamicPage';
import { FaturamentoPage }    from './pages/FaturamentoPage';
import { AsyncReportsPage }   from './pages/AsyncReportsPage';
import { JobResultsPage }     from './pages/JobResultsPage';

export function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Shell />}>
          <Route index element={<DashboardPage />} />
          <Route path="prestadores"   element={<PrestadoresPage />} />
          <Route path="procedimentos" element={<ProcedimentosPage />} />
          <Route path="cbos"          element={<CboPage />} />
          <Route path="rubricas"      element={<RubricasPage />} />
          <Route path="sia-dinamico"  element={<SiaDynamicPage />} />
          <Route path="faturamento"   element={<FaturamentoPage />} />
          <Route path="async-reports" element={<AsyncReportsPage />} />
          <Route path="job-results/:jobId" element={<JobResultsPage />} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
