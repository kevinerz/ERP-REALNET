import {
  IconGrid,
  IconUsers,
  IconWifi,
  IconTicket,
  IconCheck,
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
//   Aktivasi -> aktivasi_pelanggan.php (dibangun ulang persis di "/pelanggan/aktivasi":
//     antrian status='belum diproses', proses aktivasi + kirim WA, atau simpan pending)
//   Proses PSB -> prosesaktivasi.php (dibangun persis di "/pelanggan/proses-psb":
//     monitoring status='aktivasi', read-only, tanpa aksi ubah data seperti versi lama)
//   Selesai PSB -> selesai_aktivasi.php (dibangun persis di "/pelanggan/selesai-psb":
//     monitoring status IN ('on','selesai'), filter+sort+paginasi, read-only)
//   Gangguan -> gangguan.php (dibangun persis di "/gangguan": list + stats
//     card (selalu global) + filter/sort/paginasi + create/edit/hapus, status
//     'selesai' otomatis isi tanggal_selesai, direvisi dari 'selesai' otomatis
//     dikosongkan lagi -- sama seperti edit_gangguan.php lama)
//   Cabut -> cabut.php (dibangun persis di "/cabut": form tambah tiket +
//     filter/cari + tabel TANPA paginasi, statistik & distribusi per-POP
//     dihitung dari hasil YANG SUDAH DIFILTER -- beda dari Gangguan yang
//     statistiknya selalu global. Tabel tickets_cabut_modem yang tadinya di
//     database terpisah (u272457353_cabut, belum dikonsolidasi) sekarang
//     dipindahkan ke database utama; cabut.php dialihkan ikut pakai
//     database ini juga lewat getErpDbPdo(), data lama tidak dimigrasikan)
//   Remote Modem -> https://remot.datarealsolution.net/ (subdomain terpisah, bukan modul)
//   HRIS -> dashkaryawan.php (menu utama sempat dialihkan ke halaman "maintenance",
//     tapi file aslinya ada dan berfungsi -- akan dipakai sebagai acuan)
//   BBM -> list_reimburse.php
//   Kasbon -> list_kasbon.php
//   Logistik > AMS (Aset) -> pengajuan_aset.php
//   Logistik > IMS (Inventory) -> dashims.php
//   Pengaturan > POP/ODP/PAKET -> pop.php, dashodp.php, dashpaketku.php
//   Master Data Pelanggan -> BARU (bukan migrasi 1:1) -- menggantikan cara
//     lama di tiket/index.php yang query langsung ke database billing
//     terpisah "u272457353_dapel" (tbl_customers) tiap kali halaman dibuka.
//     Diisi dari import sekali data billing lama MixRadius (3.749 pelanggan,
//     lihat import_pelanggan_master.sql) + otomatis bertambah tiap ada
//     pelanggan baru/diedit lewat form Pelanggan/Instalasi (PSB). Dipakai
//     sebagai sumber dropdown/autocomplete (components/pelanggan-picker.tsx)
//     di form Gangguan & Cabut.

export const NAV_GROUPS: NavGroup[] = [
  {
    title: "Utama",
    items: [{ label: "Dashboard", href: "/dashboard", icon: IconGrid }],
  },
  {
    title: "Pelanggan",
    items: [
      { label: "Pelanggan / Instalasi", href: "/pelanggan", icon: IconUsers },
      { label: "Master Data Pelanggan", href: "/pelanggan-master", icon: IconUsers },
      { label: "Antrian Aktivasi", href: "/pelanggan/aktivasi", icon: IconTicket },
      { label: "Proses PSB", href: "/pelanggan/proses-psb", icon: IconWifi },
      { label: "Selesai PSB", href: "/pelanggan/selesai-psb", icon: IconCheck },
    ],
  },
  {
    title: "Operasional",
    items: [
      { label: "Gangguan", href: "/gangguan", icon: IconTicket },
      { label: "Cabut", href: "/cabut", icon: IconWifi },
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
