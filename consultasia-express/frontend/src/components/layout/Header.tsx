import React from 'react';
import { useLocation } from 'react-router-dom';

const PAGE_TITLES: Record<string, string> = {
  '/':              'Dashboard',
  '/prestadores':   'Prestadores',
  '/procedimentos': 'Procedimentos',
  '/cbos':          'CBOs',
  '/rubricas':      'Rubricas',
  '/sia-dinamico':  'Relatório SIA Dinâmico',
  '/faturamento':   'Faturamento por Prestador',
  '/async-reports': 'Relatórios Assíncronos',
};

function getTitle(pathname: string): string {
  if (pathname.startsWith('/job-results/')) return 'Resultados do Job';
  return PAGE_TITLES[pathname] ?? 'ConsultAsia';
}

export function Header() {
  const { pathname } = useLocation();
  return (
    <header className="h-12 bg-white border-b border-slate-200 flex items-center px-6 shrink-0">
      <h2 className="text-sm font-semibold text-slate-700">{getTitle(pathname)}</h2>
    </header>
  );
}
