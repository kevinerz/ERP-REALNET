import {
  IconGrid,
  IconUsers,
  IconWifi,
  IconTicket,
  IconBriefcase,
  IconWallet,
  IconHandshake,
  IconArchive,
} from "./icons";

export type NavItem = {
  label: string;
  href: string;
  icon: (props: { className?: string }) => React.ReactNode;
  /** true = modul belum dibangun, tampil abu-abu dengan badge "Segera" */
  comingSoon?: boolean;
};

export type NavGroup = {
  title: string;
  items: NavItem[];
};

export const NAV_GROUPS: NavGroup[] = [
  {
    title: "Utama",
    items: [{ label: "Dashboard", href: "/dashboard", icon: IconGrid }],
  },
  {
    title: "Pelanggan",
    items: [{ label: "Pelanggan / Instalasi", href: "/pelanggan", icon: IconUsers }],
  },
  {
    title: "Operasional",
    items: [
      { label: "Jaringan", href: "/jaringan", icon: IconWifi, comingSoon: true },
      { label: "Tiket Gangguan", href: "/tiket", icon: IconTicket, comingSoon: true },
    ],
  },
  {
    title: "SDM & Keuangan",
    items: [
      { label: "Karyawan (HR)", href: "/hr", icon: IconBriefcase, comingSoon: true },
      { label: "Keuangan", href: "/keuangan", icon: IconWallet, comingSoon: true },
    ],
  },
  {
    title: "Mitra & Aset",
    items: [
      { label: "Mitra Resmi", href: "/mitra", icon: IconHandshake, comingSoon: true },
      { label: "Aset", href: "/aset", icon: IconArchive, comingSoon: true },
    ],
  },
];

/** Cari label halaman aktif berdasarkan pathname, dipakai topbar untuk judul halaman. */
export function findActiveLabel(pathname: string): string {
  for (const group of NAV_GROUPS) {
    for (const item of group.items) {
      if (pathname === item.href || pathname.startsWith(item.href + "/")) {
        return item.label;
      }
    }
  }
  return "ERP REALNET";
}
