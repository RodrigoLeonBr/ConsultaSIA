import React from 'react';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  wrapperClassName?: string;
}

export function Input({ label, error, wrapperClassName = '', className = '', id, ...props }: InputProps) {
  const inputId = id ?? label?.toLowerCase().replace(/\s+/g, '-');
  return (
    <div className={`flex flex-col gap-1 ${wrapperClassName}`}>
      {label && (
        <label htmlFor={inputId} className="text-xs font-medium text-slate-600 uppercase tracking-wide">
          {label}
        </label>
      )}
      <input
        id={inputId}
        className={`border rounded-md px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0F52BA] focus:border-transparent transition-shadow ${error ? 'border-red-400' : 'border-slate-300'} ${className}`}
        {...props}
      />
      {error && <span className="text-xs text-[#dc2626]">{error}</span>}
    </div>
  );
}
