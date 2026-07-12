"use client";

import { useActionState } from "react";
import { FormField, TextInput, TextArea } from "@/components/ui/form-field";
import type { PelangganMasterFormState } from "./actions";

export type PelangganMasterDefaults = {
  nama: string;
  telp: string;
  alamat: string;
  username: string;
  email: string;
  pop: string;
};

export default function PelangganMasterForm({
  action,
  defaultValues,
  submitLabel = "Simpan",
}: {
  action: (prevState: PelangganMasterFormState, formData: FormData) => Promise<PelangganMasterFormState>;
  defaultValues?: PelangganMasterDefaults;
  submitLabel?: string;
}) {
  const [state, formAction, isPending] = useActionState(action, {});
  const d = defaultValues;
  const err = (name: string) => state?.fieldErrors?.[name];

  return (
    <form action={formAction} className="space-y-4">
      {state?.error && (
        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
          {state.error}
        </div>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormField label="Nama" required error={err("nama")}>
          <TextInput name="nama" defaultValue={d?.nama} />
        </FormField>
        <FormField label="No. WhatsApp / Telepon" required error={err("telp")}>
          <TextInput name="telp" defaultValue={d?.telp} placeholder="08xxxxxxxxxx" />
        </FormField>
        <FormField label="Username PPP (kalau ada)" error={err("username")}>
          <TextInput name="username" defaultValue={d?.username} />
        </FormField>
        <FormField label="Email" error={err("email")}>
          <TextInput name="email" type="email" defaultValue={d?.email} />
        </FormField>
        <FormField label="POP (kalau tahu)" error={err("pop")}>
          <TextInput name="pop" defaultValue={d?.pop} />
        </FormField>
        <FormField label="Alamat" error={err("alamat")} full>
          <TextArea name="alamat" defaultValue={d?.alamat} rows={3} />
        </FormField>
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
      >
        {isPending ? "Menyimpan..." : submitLabel}
      </button>
    </form>
  );
}
