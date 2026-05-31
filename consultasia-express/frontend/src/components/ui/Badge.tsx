import React from 'react';

type BadgeVariant = 'queued' | 'running' | 'done' | 'failed' | 'default';

interface BadgeProps {
  variant?: BadgeVariant;
  children: React.ReactNode;
  className?: string;
}

const BADGE_CLASSES: Record<BadgeVariant, string> = {
  queued:  'bg-slate-100 text-slate-600',
  running: 'bg-blue-100 text-blue-700',
  done:    'bg-green-100 text-[#16a34a]',
  failed:  'bg-red-100 text-[#dc2626]',
  default: 'bg-slate-100 text-slate-700',
};

export function Badge({ variant = 'default', children, className = '' }: BadgeProps) {
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${BADGE_CLASSES[variant]} ${className}`}>
      {children}
    </span>
  );
}
