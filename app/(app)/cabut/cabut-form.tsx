"use client";

import { useActionState } from "react";
import { FormField, TextInput, SelectInput } from "@/components/ui/form-field";
import type { CabutFormState } from "./actions";
import { ALLOWED_POP } from "./cabut-helpers";

export default function CabutForm({
  action,
}: {
  action: (prevState: CabutFormState, formData: FormData) => Promise<CabutFormState>;
}) {
  const [state, formAction, isPending] = useActionState(action, {});
  const err = (name: string) => state?.fieldErrors?.[name];

  return (
    <form action={formAction} className="space-y-4">
      {state?.error && (
        <div className="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
          {state.error}
        </div>
      )}
      {state?.success && (
        <div className="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700">
          {state.success}
        </div>
      )}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <FormField label="POP" required>
          <SelectInput name="pop" defaultValue={ALLOWED_POP[0]}>
            {ALLOWED_POP.map((p) => (
              <option key={p} value={p}>
                {p}
              </option>
            ))}
          </SelectInput>
        </FormField>
        <FormField label="Nama Pelanggan" required error={err("nama")}>
          <TextInput name="nama" />
        </FormField>
        <FormField label="No. WhatsApp" required error={err("wa")}>
          <TextInput name="wa" placeholder="08xxxxxxxxxx" />
        </FormField>
        <FormField label="Alamat Lengkap" required error={err("alamat")} full>
          <TextInput name="alamat" />
        </FormField>
        <FormField label="Alasan Cabut" required error={err("alasan")} full>
          <TextInput name="alasan" />
        </FormField>
        <FormField label="SN Modem" required error={err("sn_modem")}>
          <TextInput name="sn_modem" />
        </FormField>
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="w-full rounded-md bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 sm:w-auto"
      >
        {isPending ? "Menyimpan..." : "Simpan Tiket (Auto: Belum Selesai)"}
      </button>
    </form>
  );
}
