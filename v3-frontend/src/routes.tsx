import { Routes, Route, Link } from 'react-router-dom';
import { SiaReportsPage } from './pages/SiaReportsPage';

// Componente placeholder para simplificar a demonstração das rotas e não quebrar build
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
                    <li><Link to="/reports/sync" style={{ color: 'white' }}>SIA (Síncrono)</Link></li>
                    <li><Link to="/reports/async" style={{ color: 'white' }}>SIHD (Assíncrono)</Link></li>
                </ul>
            </nav>

            <Routes>
                <Route path="/" element={<DummyPage title="Dashboard Home" />} />

                {/* Rota Oficial do MVP Slice 1 */}
                <Route path="/reports/sync" element={<SiaReportsPage />} />

                <Route path="/reports/async" element={<DummyPage title="Jobs Assíncronos" />} />
                <Route path="/reports/results/:resultId" element={<DummyPage title="DataGrid de Resultados (Worker)" />} />
            </Routes>
        </>
    );
}
