"use client";

import { useActionState } from "react";
import type { KaryawanFormState } from "./actions";
import {
  DIVISI_OPTIONS,
  STATUS_KEPEGAWAIAN_OPTIONS,
  TIPE_PETUGAS_OPTIONS,
  STATUS_PERNIKAHAN_OPTIONS,
} from "./hr-helpers";
import { FormField, TextInput, TextArea, SelectInput } from "@/components/ui/form-field";

export type KaryawanDefaults = Partial<Record<string, string>>;

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
          <FormField label="Nama Lengkap (sesuai KTP)" required error={err("nama")}>
            <TextInput name="nama" defaultValue={d.nama} />
          </FormField>
          <FormField label="NIK" required error={err("nik")}>
            <TextInput name="nik" defaultValue={d.nik} placeholder="16 digit" />
          </FormField>
          <FormField label="Nomor KK" required error={err("nomor_kk")}>
            <TextInput name="nomor_kk" defaultValue={d.nomor_kk} />
          </FormField>
          <FormField label="Nomor SIM (opsional)">
            <TextInput name="tipe_nomor_sim" defaultValue={d.tipe_nomor_sim} />
          </FormField>
          <FormField label="Jenis Kelamin" required error={err("jenis_kelamin")}>
            <SelectInput name="jenis_kelamin" defaultValue={d.jenis_kelamin ?? ""}>
              <option value="" disabled>Pilih...</option>
              <option value="Laki-laki">Laki-laki</option>
              <option value="Perempuan">Perempuan</option>
            </SelectInput>
          </FormField>
          <FormField label="Agama" required error={err("agama")}>
            <TextInput name="agama" defaultValue={d.agama} />
          </FormField>
          <FormField label="Tempat Lahir" required error={err("tempat_lahir")}>
            <TextInput name="tempat_lahir" defaultValue={d.tempat_lahir} />
          </FormField>
          <FormField label="Tanggal Lahir" required error={err("tanggal_lahir")}>
            <TextInput type="date" name="tanggal_lahir" defaultValue={d.tanggal_lahir} />
          </FormField>
          <FormField label="Status Pernikahan" required error={err("status_pernikahan")}>
            <SelectInput name="status_pernikahan" defaultValue={d.status_pernikahan ?? ""}>
              <option value="" disabled>Pilih...</option>
              {STATUS_PERNIKAHAN_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </SelectInput>
          </FormField>
          <FormField label="No. Telepon / WhatsApp">
            <TextInput name="no_telp" defaultValue={d.no_telp} />
          </FormField>
          <FormField label="Email">
            <TextInput type="email" name="email" defaultValue={d.email} />
          </FormField>
          <FormField label="Alamat" full>
            <TextArea name="alamat" defaultValue={d.alamat} rows={2} />
          </FormField>
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Kepegawaian</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField label="Divisi" required error={err("divisi")}>
            <SelectInput name="divisi" defaultValue={d.divisi ?? ""}>
              <option value="" disabled>Pilih...</option>
              {DIVISI_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </SelectInput>
          </FormField>
          <FormField label="Jabatan">
            <TextInput name="jabatan" defaultValue={d.jabatan} placeholder="Contoh: Koordinator Teknisi Rajeg" />
          </FormField>
          <FormField label="Tipe Petugas">
            <SelectInput name="tipe_petugas" defaultValue={d.tipe_petugas ?? "Lainnya"}>
              {TIPE_PETUGAS_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </SelectInput>
          </FormField>
          <FormField label="Status Kepegawaian" required error={err("status_kepegawaian")}>
            <SelectInput name="status_kepegawaian" defaultValue={d.status_kepegawaian ?? ""}>
              <option value="" disabled>Pilih...</option>
              {STATUS_KEPEGAWAIAN_OPTIONS.map((v) => (
                <option key={v} value={v}>{v}</option>
              ))}
            </SelectInput>
          </FormField>
          <FormField label="Tanggal Masuk">
            <TextInput type="date" name="tanggal_masuk" defaultValue={d.tanggal_masuk} />
          </FormField>
          <FormField label="Status Aktif">
            <SelectInput name="status_aktif" defaultValue={d.status_aktif ?? "1"}>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </SelectInput>
          </FormField>
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Gaji & Rekening</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField label="Gaji Pokok" required error={err("gaji_pokok")}>
            <TextInput type="number" min="0" name="gaji_pokok" defaultValue={d.gaji_pokok} />
          </FormField>
          <FormField label="Tunjangan Jabatan">
            <TextInput type="number" min="0" name="tunjangan_jabatan" defaultValue={d.tunjangan_jabatan ?? "0"} />
          </FormField>
          <FormField label="Tunjangan Operasional">
            <TextInput type="number" min="0" name="tunjangan_operasional" defaultValue={d.tunjangan_operasional ?? "0"} />
          </FormField>
          <FormField label="Bank">
            <TextInput name="bank" defaultValue={d.bank} placeholder="Contoh: BCA / BRI / Mandiri" />
          </FormField>
          <FormField label="Nomor Rekening">
            <TextInput name="rekening" defaultValue={d.rekening} />
          </FormField>
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Akun Login</h2>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField label="Username" required error={err("username")}>
            <TextInput name="username" defaultValue={d.username} />
          </FormField>
          <FormField label={isEdit ? "Password (kosongkan jika tidak ganti)" : "Password"} required={!isEdit} error={err("password")}>
            <TextInput name="password" defaultValue="" placeholder={isEdit ? "••••••••" : ""} />
          </FormField>
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
