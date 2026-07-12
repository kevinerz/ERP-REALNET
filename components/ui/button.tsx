import { forwardRef } from "react";
import { Spinner } from "./spinner";

// Tombol bersama -- dipakai lintas modul supaya gaya tombol (warna, radius,
// ukuran, state loading) konsisten. Tidak wajib dipakai di modul lama
// (masih banyak <button> polos di form-form yang sudah ada), tapi
// direkomendasikan untuk modul baru -- lihat docs/MODULE_GUIDE.md.

type ButtonVariant = "primary" | "secondary" | "danger" | "ghost";
type ButtonSize = "sm" | "md";

const VARIANT_CLASSES: Record<ButtonVariant, string> = {
  primary: "bg-brand-600 text-white shadow-elevated hover:bg-brand-700 disabled:bg-brand-300",
  secondary: "border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50",
  danger: "border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-50",
  ghost: "text-gray-600 hover:bg-gray-100 disabled:opacity-50",
};

const SIZE_CLASSES: Record<ButtonSize, string> = {
  sm: "px-3 py-1.5 text-xs",
  md: "px-5 py-2.5 text-sm",
};

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: ButtonVariant;
  size?: ButtonSize;
  isLoading?: boolean;
  loadingText?: string;
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant = "primary", size = "md", isLoading, loadingText, disabled, children, className = "", ...props },
  ref
) {
  return (
    <button
      ref={ref}
      disabled={disabled || isLoading}
      className={`inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition disabled:cursor-not-allowed ${VARIANT_CLASSES[variant]} ${SIZE_CLASSES[size]} ${className}`}
      {...props}
    >
      {isLoading && <Spinner className="h-4 w-4" />}
      {isLoading && loadingText ? loadingText : children}
    </button>
  );
});
