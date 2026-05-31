import React from 'react';
import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Building2,
  Stethoscope,
  Users,
  Wallet,
  BarChart3,
  FileText,
  Clock,
} from 'lucide-react';

interface NavItem {
  to: string;
  label: string;
  icon: React.ReactNode;
}

const NAV_ITEMS: NavItem[] = [
  { to: '/',              label: 'Dashboard',          icon: <LayoutDashboard className="w-4 h-4" /> },
  { to: '/prestadores',   label: 'Prestadores',         icon: <Building2 className="w-4 h-4" /> },
  { to: '/procedimentos', label: 'Procedimentos',        icon: <Stethoscope className="w-4 h-4" /> },
  { to: '/cbos',          label: 'CBOs',                icon: <Users className="w-4 h-4" /> },
  { to: '/rubricas',      label: 'Rubricas',            icon: <Wallet className="w-4 h-4" /> },
  { to: '/sia-dinamico',  label: 'SIA Dinâmico',        icon: <BarChart3 className="w-4 h-4" /> },
  { to: '/faturamento',   label: 'Faturamento',         icon: <FileText className="w-4 h-4" /> },
  { to: '/async-reports', label: 'Relatórios Async',    icon: <Clock className="w-4 h-4" /> },
];

export function Sidebar() {
  return (
    <aside className="w-60 h-screen bg-slate-900 flex flex-col shrink-0">
      {/* Logo/Brand */}
      <div className="px-5 py-4 border-b border-slate-700">
        <h1 className="text-base font-bold text-white tracking-tight">ConsultAsia</h1>
        <p className="text-xs text-slate-400 mt-0.5">Produção SIA</p>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-3 py-3 overflow-y-auto">
        {NAV_ITEMS.map(item => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.to === '/'}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium mb-0.5 transition-colors ${
                isActive
                  ? 'bg-slate-700 text-white'
                  : 'text-slate-400 hover:text-white hover:bg-slate-800'
              }`
            }
          >
            {item.icon}
            {item.label}
          </NavLink>
        ))}
      </nav>

      {/* Footer */}
      <div className="px-5 py-3 border-t border-slate-700">
        <p className="text-xs text-slate-500">v3 · Express + Drizzle</p>
      </div>
    </aside>
  );
}
