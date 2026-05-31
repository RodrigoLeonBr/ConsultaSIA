import React from 'react';
import { AlertTriangle, CheckCircle, Info, XCircle } from 'lucide-react';

type AlertVariant = 'error' | 'warning' | 'success' | 'info';

interface AlertProps {
  variant?: AlertVariant;
  title?: string;
  children: React.ReactNode;
  className?: string;
}

const ALERT_CONFIG: Record<AlertVariant, { icon: React.ReactNode; classes: string }> = {
  error:   { icon: <XCircle className="w-4 h-4 shrink-0" />,       classes: 'bg-red-50 border-red-200 text-red-800' },
  warning: { icon: <AlertTriangle className="w-4 h-4 shrink-0" />, classes: 'bg-amber-50 border-amber-200 text-amber-800' },
  success: { icon: <CheckCircle className="w-4 h-4 shrink-0" />,   classes: 'bg-green-50 border-green-200 text-green-800' },
  info:    { icon: <Info className="w-4 h-4 shrink-0" />,          classes: 'bg-blue-50 border-blue-200 text-blue-800' },
};

export function Alert({ variant = 'info', title, children, className = '' }: AlertProps) {
  const { icon, classes } = ALERT_CONFIG[variant];
  return (
    <div className={`flex gap-3 items-start border rounded-md px-4 py-3 text-sm ${classes} ${className}`}>
      {icon}
      <div>
        {title && <p className="font-semibold mb-0.5">{title}</p>}
        <p>{children}</p>
      </div>
    </div>
  );
}
