"use client";

import { useActionState, useRef } from "react";
import type { GangguanFormState } from "./actions";
import { FormField, TextInput, TextArea, SelectInput } from "@/components/ui/form-field";
import { STATUS_OPTIONS } from "./gangguan-helpers";
import PelangganPicker from "@/components/pelanggan-picker";

export type GangguanDefaults = Partial<Record<string, string>>;

export default function GangguanForm({
  action,
  defaultValues,
  submitLabel,
}: {
  action: (prevState: GangguanFormState, formData: FormData) => Promise<GangguanFormState>;
  defaultValues?: GangguanDefaults;
  submitLabel: string;
}) {
  const [state, formAction, isPending] = useActionState(action, {});
  const d = defaultValues ?? {};
  const err = (name: string) => state?.fieldErrors?.[name];

  const namaRef = useRef<HTMLInputElement>(null);
  const whatsappRef = useRef<HTMLInputElement>(null);
  const alamatRef = useRef<HTMLTextAreaElement>(null);

  return (
    <form action={formAction} className="space-y-5">
      {state?.error && (
        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
          {state.error}
        </div>
      )}

      <FormField label="Cari Pelanggan dari Master (opsional)">
        <PelangganPicker
          onPick={(hit) => {
            if (namaRef.current) namaRef.current.value = hit.nama;
            if (whatsappRef.current) whatsappRef.current.value = hit.telp;
            if (alamatRef.current && hit.alamat) alamatRef.current.value = hit.alamat;
          }}
        />
      </FormField>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormField label="Nama Pelanggan" required error={err("nama_pelanggan")}>
          <TextInput ref={namaRef} name="nama_pelanggan" defaultValue={d.nama_pelanggan} />
        </FormField>
        <FormField label="POP" required error={err("pop")}>
          <TextInput name="pop" defaultValue={d.pop} />
        </FormField>
        <FormField label="No. WhatsApp">
          <TextInput ref={whatsappRef} name="whatsapp" defaultValue={d.whatsapp} />
        </FormField>
        <FormField label="VLAN">
          <TextInput name="vlan" defaultValue={d.vlan} />
        </FormField>
        <FormField label="SN Perangkat">
          <TextInput name="sn" defaultValue={d.sn} />
        </FormField>
        <FormField label="Teknisi">
          <TextInput name="teknisi" defaultValue={d.teknisi} />
        </FormField>
        <FormField label="Status" required>
          <SelectInput name="status" defaultValue={d.status ?? "belum dikerjakan"}>
            {STATUS_OPTIONS.map((s) => (
              <option key={s.value} value={s.value}>{s.label}</option>
            ))}
          </SelectInput>
        </FormField>
        <FormField label="Link Google Maps">
          <TextInput name="maps_url" defaultValue={d.maps_url} placeholder="https://maps.app.goo.gl/..." />
        </FormField>
        <FormField label="Alamat" required error={err("alamat")} full>
          <TextArea ref={alamatRef} name="alamat" defaultValue={d.alamat} rows={2} />
        </FormField>
        <FormField label="Keluhan" required error={err("keluhan")} full>
          <TextArea name="keluhan" defaultValue={d.keluhan} rows={3} />
        </FormField>
      </div>

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
