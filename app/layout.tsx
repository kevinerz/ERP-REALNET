import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "ERP REALNET",
  description: "Sistem manajemen internal ERP REALNET",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body className="min-h-screen bg-gray-50 text-gray-900 antialiased">{children}</body>
    </html>
  );
}
