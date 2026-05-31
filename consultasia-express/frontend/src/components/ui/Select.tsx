import React from 'react';

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  error?: string;
  wrapperClassName?: string;
}

export function Select({ label, error, wrapperClassName = '', className = '', id, children, ...props }: SelectProps) {
  const selectId = id ?? label?.toLowerCase().replace(/\s+/g, '-');
  return (
    <div className={`flex flex-col gap-1 ${wrapperClassName}`}>
      {label && (
        <label htmlFor={selectId} className="text-xs font-medium text-slate-600 uppercase tracking-wide">
          {label}
        </label>
      )}
      <select
        id={selectId}
        className={`border border-slate-300 rounded-md px-3 py-2 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-[#0F52BA] focus:border-transparent transition-shadow ${error ? 'border-red-400' : ''} ${className}`}
        {...props}
      >
        {children}
      </select>
      {error && <span className="text-xs text-[#dc2626]">{error}</span>}
    </div>
  );
}
