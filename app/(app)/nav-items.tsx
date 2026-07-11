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
  /** true = modul belum dibangun di Next.js, tampil abu-abu dengan badge "Segera" */
  comingSoon?: boolean;
  /** true = link keluar (subdomain lain), buka tab baru, bukan modul internal */
  external?: boolean;
};

export type NavGroup = {
  title: string;
  items: NavItem[];
};

// Struktur menu ini dibuat SAMA PERSIS dengan navbar.php lama (root), supaya
// urutan pengerjaan modul di Next.js jelas mengikuti menu yang sudah dipakai
// sehari-hari. Pemetaan file lama -> di sini untuk referensi kalau perlu cek balik:
//   Dashboard -> dashboard.php
//   Aktivasi/Proses PSB/Selesai PSB -> aktivasi_pelanggan.php, prosesaktivasi.php,
//     selesai_aktivasi.php (di Next.js digabung jadi 1 modul "/pelanggan" dengan
//     filter status, bukan 3 halaman terpisah -- lebih simpel tanpa kehilangan fungsi)
//   Gangguan -> gangguan.php
//   CABUT -> cabut.php (pakai database "cabut" yang belum ikut dikonsolidasi)
//   Remote Modem -> https://remot.datarealsolution.net/ (subdomain terpisah, bukan modul)
//   HRIS -> dashkaryawan.php (menu utama sempat dialihkan ke halaman "maintenance",
//     tapi file aslinya ada dan berfungsi -- akan dipakai sebagai acuan)
//   BBM -> list_reimburse.php
//   Kasbon -> list_kasbon.php
//   Logistik > AMS (Aset) -> pengajuan_aset.php
//   Logistik > IMS (Inventory) -> dashims.php
//   Pengaturan > POP/ODP/PAKET -> pop.php, dashodp.php, dashpaketku.php

export const NAV_GROUPS: NavGroup[] = [
  {
    title: "Utama",
    items: [{ label: "Dashboard", href: "/dashboard", icon: IconGrid }],
  },
  {
    title: "Pelanggan",
    items: [
      { label: "Pelanggan / Instalasi", href: "/pelanggan", icon: IconUsers },
    ],
  },
  {
    title: "Operasional",
    items: [
      { label: "Gangguan", href: "/gangguan", icon: IconTicket, comingSoon: true },
      { label: "Cabut", href: "/cabut", icon: IconWifi, comingSoon: true },
      {
        label: "Remote Modem",
        href: "https://remot.datarealsolution.net/",
        icon: IconWifi,
        external: true,
      },
    ],
  },
  {
    title: "SDM & Keuangan",
    items: [
      { label: "HRIS", href: "/hr", icon: IconBriefcase },
      { label: "BBM", href: "/bbm", icon: IconWallet, comingSoon: true },
      { label: "Kasbon", href: "/kasbon", icon: IconWallet, comingSoon: true },
    ],
  },
  {
    title: "Logistik",
    items: [
      { label: "AMS (Aset)", href: "/logistik/aset", icon: IconArchive, comingSoon: true },
      { label: "IMS (Inventory)", href: "/logistik/inventory", icon: IconArchive, comingSoon: true },
    ],
  },
  {
    title: "Pengaturan",
    items: [
      { label: "POP", href: "/pengaturan/pop", icon: IconHandshake, comingSoon: true },
      { label: "ODP", href: "/pengaturan/odp", icon: IconHandshake, comingSoon: true },
      { label: "PAKET", href: "/pengaturan/paket", icon: IconWifi, comingSoon: true },
    ],
  },
];

/** Cari label halaman aktif berdasarkan pathname, dipakai topbar untuk judul halaman. */
export function findActiveLabel(pathname: string): string {
  for (const group of NAV_GROUPS) {
    for (const item of group.items) {
      if (item.external) continue;
      if (pathname === item.href || pathname.startsWith(item.href + "/")) {
        return item.label;
      }
    }
  }
  return "ERP REALNET";
}
