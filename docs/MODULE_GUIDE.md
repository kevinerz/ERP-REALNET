# Panduan Modul ERP REALNET

Dokumen ini menjelaskan struktur standar setiap modul di aplikasi Next.js ini,
supaya menambah modul baru ("bongkar pasang") cepat dan konsisten dengan
modul yang sudah ada (Pelanggan, HRIS, Aktivasi Pelanggan).

## Struktur folder standar

Setiap modul domain hidup di `app/(app)/<nama-modul>/` dan biasanya berisi:

```
app/(app)/<modul>/
  page.tsx           # Halaman daftar (list) -- server component, fetch data langsung
  new/page.tsx        # Halaman tambah data baru
  [id]/page.tsx        # Halaman detail / edit satu baris data
  actions.ts           # Semua Server Action (create/update/delete) + tipe FormState
  <modul>-form.tsx     # Client component form, dipakai bersama oleh new/ dan [id]/
  <modul>-helpers.ts   # (opsional) fungsi murni: kalkulasi, opsi dropdown, format string
```

Untuk modul dengan sub-alur khusus (contoh: `pelanggan/aktivasi`), buat sebagai
sub-folder di dalam modul induknya, bukan modul terpisah, kalau datanya masih
satu tabel/domain yang sama.

## Konvensi `actions.ts`

- Selalu `"use server";` di baris pertama.
- Selalu panggil `await requireSession();` di awal setiap action (kecuali ada
  alasan eksplisit untuk publik).
- Tipe `XxxFormState = { error?: string; fieldErrors?: Record<string, string> }`
  diekspor supaya form client component bisa pakai `useActionState`.
- Validasi field wajib dilakukan manual di awal function (lihat pola
  `REQUIRED_FIELDS` di `hr/actions.ts`), kembalikan `fieldErrors` per field.
- Untuk error unique-constraint (P2002 Prisma), JANGAN cek manual --
  pakai helper bersama:

  ```ts
  import { formatUniqueConstraintError } from "@/lib/db-errors";

  const UNIQUE_FIELD_LABELS = { username: "Username", nik: "NIK" };
  // ...
  } catch (err) {
    const uniqueMsg = formatUniqueConstraintError(err, UNIQUE_FIELD_LABELS);
    if (uniqueMsg) return { error: uniqueMsg };
    console.error("createXxx error:", err);
    return { error: "Gagal menyimpan data. Coba lagi." };
  }
  ```

- Sukses create/update: `revalidatePath(...)` lalu `redirect(...)` ke halaman
  detail atau daftar.

## Konvensi form (client component)

Semua form pakai primitive bersama di `components/ui/form-field.tsx`, bukan
`<label>`/`<input>` manual atau `className` yang diulang-ulang:

```tsx
import { FormField, TextInput, TextArea, SelectInput } from "@/components/ui/form-field";

<FormField label="Nama" required error={err("nama")}>
  <TextInput name="nama" defaultValue={d.nama} />
</FormField>

<FormField label="Alamat" full>
  <TextArea name="alamat" defaultValue={d.alamat} rows={2} />
</FormField>

<FormField label="Status" required error={err("status")}>
  <SelectInput name="status" defaultValue={d.status ?? ""}>
    <option value="" disabled>Pilih...</option>
    {OPTIONS.map((v) => <option key={v} value={v}>{v}</option>)}
  </SelectInput>
</FormField>
```

`full` membuat field melebar 2 kolom grid (`sm:col-span-2`) -- pakai untuk
textarea/alamat.

## Konvensi tabel daftar (list page)

- Badge status (Aktif/Nonaktif/Segera dll) pakai `components/ui/badge.tsx`:
  `<Badge tone="green">Aktif</Badge>`.
- Baris kosong pakai `components/ui/empty-state.tsx`:
  `<EmptyTableRow colSpan={N} />` (atau `message="..."` custom).

## Notifikasi WhatsApp

Jangan panggil API Starsender langsung dari action modul. Pakai service di
`lib/notifications/`:

```ts
import { sendWhatsApp, sendWhatsAppToPop } from "@/lib/notifications";

await sendWhatsApp(target, message);          // ke satu nomor/grup manual
await sendWhatsAppToPop(pop, message);          // ke grup WA sesuai POP pelanggan
```

Template pesan dipisah dari mekanisme pengiriman -- taruh di
`lib/notifications/templates/<nama>.ts` sebagai fungsi
`build<Nama>Message(data)` yang mengembalikan string siap kirim. Kalau modul
baru butuh notifikasi jenis lain (bukan aktivasi), buat provider/template baru
di folder yang sama, jangan duplikasi logic fetch/token.

Daftar grup WhatsApp per-POP ada di `lib/notifications/pop-groups.ts` --
tambah/ubah grup di situ saja, satu tempat.

## Menambah modul baru: checklist singkat

1. Tambah entry menu di `app/(app)/nav-items.tsx` (ikon inline dari `icons.tsx`
   atau tambah ikon baru di sana, jangan pakai package ikon eksternal baru).
2. Buat folder `app/(app)/<modul>/` dengan struktur di atas.
3. Kalau ada dropdown/enum, taruh di `<modul>-helpers.ts` sebagai
   `const XXX_OPTIONS = [...] as const`.
4. Form pakai primitive `components/ui/form-field.tsx`.
5. Error unique constraint pakai `lib/db-errors.ts`.
6. Notifikasi (kalau ada) pakai `lib/notifications/`.
7. Verifikasi dengan `tsc --noEmit` sebelum commit (lihat catatan Prisma stub
   di bawah).

## Catatan verifikasi lokal (sandbox dev)

`prisma generate` tidak bisa jalan di sandbox development (jaringan ke
`binaries.prisma.sh` diblokir). Untuk cek tipe lokal, dipakai stub sementara
`generated/prisma/client.d.ts` (class `PrismaClient` dengan index signature
`[key: string]: any`), lalu dihapus lagi setelah `tsc --noEmit` sukses. Ini
tidak mempengaruhi build produksi (Hostinger) yang menjalankan
`prisma generate` sungguhan.
