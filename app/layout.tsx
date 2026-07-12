import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

// Font Inter -- dipilih supaya tipografi lebih tegas/rapi (sesuai brief
// "gaya mbanking, korporat") dibanding font sistem default sebelumnya.
const inter = Inter({
  subsets: ["latin"],
  variable: "--font-inter",
  display: "swap",
});

export const metadata: Metadata = {
  title: "ERP REALNET",
  description: "Sistem manajemen internal ERP REALNET",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id" className={inter.variable}>
      <body className="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">{children}</body>
    </html>
  );
}
