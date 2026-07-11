"use client";

import { useActionState } from "react";
import type { KaryawanFormState } from "./actions";
import {
  DIVISI_OPTIONS,
  STATUS_KEPEGAWAIAN_OPTIONS,
  TIPE_PETUGAS_OPTIONS,
  STATUS_PERNIKAHAN_OPTIONS,
} from "./hr-helpers";

export type KaryawanDefaults = Partial<Record<string, string>>;

function Field({
  label,
  required,
  error,
  children,
  full,
}: {
  label: string;
  required?: boolean;
  error?: string;
  children: React.ReactNode;
  full?: boolean;
}) {
  return (
    <div className={full ? "sm:col-span-2" : ""}>
      <label className="mb-1 block text-sm font-medium text-gray-700">
        {label}
        {required && <span className="text-red-500"> *</span>}
      </label>
      {children}
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}

const inputClass =
  "w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none";

export default function KaryawanForm({
  action,
  defaultValues,
  submitLabel,
  isEdit,
}: {
  action: (prevState: KaryawanFormState, formData: FormData) => Promise<KaryawanFormState>;
  defaultValues?: KaryawanDefaults;
  submitLabel: string;
  isEdit?: boolean;
}) {
  const [state, formAction, isPending] = useActionState(action, {});
  const d = defaultValues ?? {};
  const err = (name: string) => state?.fieldErrors?.[name];

  return (
    <form action={formAction} className="space-y-6">
      {state?.error && (
        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
          {state.error}
        </div>
      )}

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Data Pribadi</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Field label="Nama Lengkap (sesuai KTP)" required error={err("nama")}>
            <input name="nama" defaultValue={d.nama} className={inputClass} />
          </Field>
          <Field label="NIK" required error={err("nik")}>
            <input name="nik" defaultValue={d.nik} placeholder="16 digit" className={inputClass} />
          </Field>
          <Field label="Nomor KK" required error={err("nomor_kk")}>
            <input name="nomor_kk" defaultValue={d.nomor_kk} className={inputClass} />
          </Field>
          <Field label="Nomor SIM (opsional)">
            <input name="tipe_nomor_sim" defaultValue={d.tipe_nomor_sim} className={inputClass} />
          </Field>
          <Field label="Jenis Kelamin" required error={err("jenis_kelamin")}>
            <select name="jenis_kelamin" defaultValue={d.jenis_kelamin ?? ""} className={inputClass}>
              <option value="" disabled>Pilih...</option>
              <option value="Laki-laki">Laki-laki</option>
              <option value="Perempuan">Perempuan</option>
            </select>
          </Field>
          <Field label="Agama" required error={err("agama")}>
            <input name="agama" defaultValue={d.agama} className={inputClass} />
          </Field>
          <Field label="Tempat Lahir" required error={err("tempat_lahir")}>
            <input name="tempat_lahir" defaultValue={d.tempat_lahir} className={inputClass} />
          </Field>
          <Field label="Tanggal Lahir" required error={err("tanggal_lahir")}>
            <input type="date" name="tanggal_lahir" defaultValue={d.tanggal_lahir} className={inputClass} />
          </Field>
          <Field label="Status Pernikahan" required error={err("status_pernikahan")}>
            <select name="status_pernikahan" defaultValue={d.status_pernikahan ?? ""} className={inputClass}>
              <option value="" disabled>Pilih...</option>
              {STATUS_PERNIKAHAN_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </select>
          </Field>
          <Field label="No. Telepon / WhatsApp">
            <input name="no_telp" defaultValue={d.no_telp} className={inputClass} />
          </Field>
          <Field label="Email">
            <input type="email" name="email" defaultValue={d.email} className={inputClass} />
          </Field>
          <Field label="Alamat" full>
            <textarea name="alamat" defaultValue={d.alamat} rows={2} className={inputClass} />
          </Field>
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Kepegawaian</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Field label="Divisi" required error={err("divisi")}>
            <select name="divisi" defaultValue={d.divisi ?? ""} className={inputClass}>
              <option value="" disabled>Pilih...</option>
              {DIVISI_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </select>
          </Field>
          <Field label="Jabatan">
            <input name="jabatan" defaultValue={d.jabatan} placeholder="Contoh: Koordinator Teknisi Rajeg" className={inputClass} />
          </Field>
          <Field label="Tipe Petugas">
            <select name="tipe_petugas" defaultValue={d.tipe_petugas ?? "Lainnya"} className={inputClass}>
              {TIPE_PETUGAS_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </select>
          </Field>
          <Field label="Status Kepegawaian" required error={err("status_kepegawaian")}>
            <select name="status_kepegawaian" defaultValue={d.status_kepegawaian ?? ""} className={inputClass}>
              <option value="" disabled>Pilih...</option>
              {STATUS_KEPEGAWAIAN_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </select>
          </Field>
          <Field label="Tanggal Masuk">
            <input type="date" name="tanggal_masuk" defaultValue={d.tanggal_masuk} className={inputClass} />
          </Field>
          <Field label="Status Aktif">
            <select name="status_aktif" defaultValue={d.status_aktif ?? "1"} className={inputClass}>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </Field>
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Gaji & Rekening</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Field label="Gaji Pokok" required error={err("gaji_pokok")}>
            <input type="number" min="0" name="gaji_pokok" defaultValue={d.gaji_pokok} className={inputClass} />
          </Field>
          <Field label="Tunjangan Jabatan">
            <input type="number" min="0" name="tunjangan_jabatan" defaultValue={d.tunjangan_jabatan ?? "0"} className={inputClass} />
          </Field>
          <Field label="Tunjangan Operasional">
            <input type="number" min="0" name="tunjangan_operasional" defaultValue={d.tunjangan_operasional ?? "0"} className={inputClass} />
          </Field>
          <Field label="Bank">
            <input name="bank" defaultValue={d.bank} placeholder="Contoh: BCA / BRI / Mandiri" className={inputClass} />
          </Field>
          <Field label="Nomor Rekening">
            <input name="rekening" defaultValue={d.rekening} className={inputClass} />
          </Field>
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Akun Login</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Field label="Username" required error={err("username")}>
            <input name="username" defaultValue={d.username} className={inputClass} />
          </Field>
          <Field label={isEdit ? "Password (kosongkan jika tidak ganti)" : "Password"} required={!isEdit} error={err("password")}>
            <input name="password" defaultValue="" placeholder={isEdit ? "••••••••" : ""} className={inputClass} />
          </Field>
        </div>
      </section>

      <button
        type="submit"
        disabled={isPending}
        className="rounded-md bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
      >
        {isPending ? "Menyimpan..." : submitLabel}
      </button>
    </form>
  );
}
