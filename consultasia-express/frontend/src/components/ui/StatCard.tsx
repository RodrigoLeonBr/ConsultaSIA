import React from 'react';

type StatColor = 'primary' | 'success' | 'warning' | 'danger';

interface StatCardProps {
  label: string;
  value: string | number;
  icon: React.ReactNode;
  color?: StatColor;
  className?: string;
}

const COLOR_CLASSES: Record<StatColor, { icon: string; bg: string }> = {
  primary: { icon: 'text-[#0F52BA]', bg: 'bg-blue-50' },
  success: { icon: 'text-[#16a34a]', bg: 'bg-green-50' },
  warning: { icon: 'text-[#ca8a04]', bg: 'bg-amber-50' },
  danger:  { icon: 'text-[#dc2626]', bg: 'bg-red-50' },
};

export function StatCard({ label, value, icon, color = 'primary', className = '' }: StatCardProps) {
  const { icon: iconClass, bg } = COLOR_CLASSES[color];
  return (
    <div className={`bg-white rounded-lg border border-slate-200 shadow-sm p-5 flex items-center gap-4 ${className}`}>
      <div className={`p-3 rounded-lg ${bg}`}>
        <div className={`w-6 h-6 ${iconClass}`}>{icon}</div>
      </div>
      <div>
        <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{label}</p>
        <p className="text-2xl font-bold text-slate-800 font-mono">{typeof value === 'number' ? value.toLocaleString('pt-BR') : value}</p>
      </div>
    </div>
  );
}
