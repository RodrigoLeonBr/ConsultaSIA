import type React from 'react';
import { BrowserRouter, Routes, Route, Link, useLocation } from 'react-router-dom';
import { SiaDynamicPage } from './pages/SiaDynamicPage';
import { AsyncReportsPage } from './pages/AsyncReportsPage';
import { JobResultsPage } from './pages/JobResultsPage';

function Nav() {
  const { pathname } = useLocation();
  const linkStyle = (path: string): React.CSSProperties => ({
    color: pathname === path ? '#2980b9' : '#555',
    textDecoration: 'none',
    fontWeight: pathname === path ? 'bold' : 'normal',
    padding: '4px 0',
    borderBottom: pathname === path ? '2px solid #2980b9' : '2px solid transparent',
  });

  return (
    <nav style={{ padding: '10px 24px', background: '#f8f9fa', borderBottom: '1px solid #e0e0e0', display: 'flex', gap: 20 }}>
      <Link to="/" style={linkStyle('/')}>SIA Dinâmico</Link>
      <Link to="/async" style={linkStyle('/async')}>Relatórios Assíncronos</Link>
    </nav>
  );
}

export function AppRoutes() {
  return (
    <BrowserRouter>
      <Nav />
      <Routes>
        <Route path="/" element={<SiaDynamicPage />} />
        <Route path="/async" element={<AsyncReportsPage />} />
        <Route path="/job-results/:jobId" element={<JobResultsPage />} />
      </Routes>
    </BrowserRouter>
  );
}
