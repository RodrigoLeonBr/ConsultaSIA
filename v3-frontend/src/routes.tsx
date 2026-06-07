import { Routes, Route, Link } from 'react-router-dom';
import { SiaReportsPage } from './pages/SiaReportsPage';
import { SiaBillingProviderPage } from './pages/SiaBillingProviderPage';
import { SiaDynamicPage } from './pages/SiaDynamicPage';
import { AsyncReportsPage } from './pages/AsyncReportsPage';
import { JobResultsPage } from './pages/JobResultsPage';

const DummyPage = ({ title }: { title: string }) => (
    <div style={{ padding: '20px' }}>
        <h2>{title}</h2>
        <p>Em construção...</p>
    </div>
);

export function AppRoutes() {
    return (
        <>
            <nav style={{ padding: '15px', background: '#333', color: 'white' }}>
                <ul style={{ display: 'flex', gap: '20px', listStyle: 'none', margin: 0 }}>
                    <li><Link to="/" style={{ color: 'white' }}>Dashboard</Link></li>
                    <li><Link to="/reports/sync" style={{ color: 'white' }}>SIA — Produção</Link></li>
                    <li><Link to="/reports/sia/billing-provider" style={{ color: 'white' }}>SIA — Faturamento</Link></li>
                    <li><Link to="/reports/sia/dynamic" style={{ color: 'white' }}>SIA — Dinâmico</Link></li>
                    <li><Link to="/reports/async" style={{ color: 'white' }}>Relatório Pesado</Link></li>
                </ul>
            </nav>

            <Routes>
                <Route path="/" element={<DummyPage title="Dashboard Home" />} />

                {/* SIA — consulta síncrona de produção (s_prd raw) */}
                <Route path="/reports/sync" element={<SiaReportsPage />} />

                {/* SIA — Faturamento por Prestador (GROUP BY hierárquico) */}
                <Route path="/reports/sia/billing-provider" element={<SiaBillingProviderPage />} />

                {/* SIA — Relatório dinâmico com seleção de colunas e filtros compostos */}
                <Route path="/reports/sia/dynamic" element={<SiaDynamicPage />} />

                {/* Relatório pesado assíncrono via job */}
                <Route path="/reports/async" element={<AsyncReportsPage />} />
                <Route path="/reports/results/:resultId" element={<JobResultsPage />} />
            </Routes>
        </>
    );
}
