"use client";

import { useActionState } from "react";
import type { PelangganFormState } from "./actions";

type FieldDef = { name: string; label: string; required?: boolean; type?: string };

const FIELDS: FieldDef[] = [
  { name: "nama", label: "Nama Pelanggan", required: true },
  { name: "telp", label: "No. Telepon / WhatsApp", required: true },
  { name: "email", label: "Email" },
  { name: "alamat", label: "Alamat", required: true, type: "textarea" },
  { name: "url_maps", label: "Link Google Maps", required: true },
  { name: "paket", label: "Paket", required: true },
  { name: "pop", label: "POP" },
  { name: "odp", label: "ODP" },
  { name: "vlan", label: "VLAN" },
  { name: "modem", label: "Modem (SN/Tipe)", required: true },
  { name: "dropcore", label: "Dropcore", required: true },
  { name: "sn", label: "SN Perangkat" },
  { name: "user", label: "Username PPPoE", required: true },
  { name: "userppp", label: "User PPP (alt)" },
  { name: "passwordppp", label: "Password PPP", type: "password" },
  { name: "ktp", label: "No. KTP" },
  { name: "teknisi", label: "Teknisi Pemasang" },
  { name: "marketing", label: "Marketing" },
  { name: "status", label: "Status" },
  { name: "tanggal", label: "Tanggal Instalasi", type: "date" },
];

export type PelangganDefaults = Partial<Record<string, string>>;

export default function PelangganForm({
  action,
  defaultValues,
  submitLabel,
}: {
  action: (prevState: PelangganFormState, formData: FormData) => Promise<PelangganFormState>;
  defaultValues?: PelangganDefaults;
  submitLabel: string;
}) {
  const [state, formAction, isPending] = useActionState(action, {});

  return (
    <form action={formAction} className="space-y-5">
      {state?.error && (
        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
          {state.error}
        </div>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        {FIELDS.map((field) => {
          const errorMsg = state?.fieldErrors?.[field.name];
          const defaultValue = defaultValues?.[field.name] ?? "";
          const isFull = field.type === "textarea";

          return (
            <div key={field.name} className={isFull ? "sm:col-span-2" : ""}>
              <label className="mb-1 block text-sm font-medium text-gray-700">
                {field.label}
                {field.required && <span className="text-red-500"> *</span>}
              </label>
              {field.type === "textarea" ? (
                <textarea
                  name={field.name}
                  defaultValue={defaultValue}
                  rows={3}
                  className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                />
              ) : (
                <input
                  type={field.type ?? "text"}
                  name={field.name}
                  defaultValue={defaultValue}
                  className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                />
              )}
              {errorMsg && <p className="mt-1 text-xs text-red-600">{errorMsg}</p>}
            </div>
          );
        })}
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
