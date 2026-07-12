"use server";

import { prisma } from "@/lib/prisma";
import { requireSession } from "@/lib/auth";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { searchPelangganMasterRows, type PelangganMasterHit } from "@/lib/pelanggan-master";

export type PelangganMasterFormState = {
  error?: string;
  fieldErrors?: Record<string, string>;
};

/**
 * Server action yang dipanggil langsung dari komponen client PelangganPicker
 * (dipakai di form Gangguan, Cabut, dll) -- cari pelanggan by nama/telp,
 * dibatasi 15 hasil. Read-only, tidak perlu requireSession supaya picker
 * tetap ringan (form-form yang memanggilnya sudah ada di balik halaman yang
 * butuh login).
 */
export async function searchPelanggan(query: string): Promise<PelangganMasterHit[]> {
  return searchPelangganMasterRows(query);
}

const REQUIRED_FIELDS = ["nama", "telp"] as const;

function buildData(formData: FormData) {
  const get = (key: string) => (formData.get(key)?.toString().trim() ?? "");

  const fieldErrors: Record<string, string> = {};
  for (const field of REQUIRED_FIELDS) {
    if (!get(field)) fieldErrors[field] = "Wajib diisi";
  }

  const data = {
    nama: get("nama"),
    telp: get("telp"),
    alamat: get("alamat") || null,
    username: get("username") || null,
    email: get("email") || null,
    pop: get("pop") || null,
  };

  return { data, fieldErrors };
}

export async function createPelangganMasterManual(
  _prev: PelangganMasterFormState,
  formData: FormData
): Promise<PelangganMasterFormState> {
  await requireSession();
  const { data, fieldErrors } = buildData(formData);

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  let newId: number;
  try {
    const created = await prisma.pelangganMaster.create({ data: { ...data, sumber: "manual" } });
    newId = created.id;
  } catch (err) {
    console.error("createPelangganMasterManual error:", err);
    return { error: "Gagal menyimpan data pelanggan. Coba lagi." };
  }

  revalidatePath("/pelanggan-master");
  redirect(`/pelanggan-master/${newId}`);
}

export async function updatePelangganMaster(
  id: number,
  _prev: PelangganMasterFormState,
  formData: FormData
): Promise<PelangganMasterFormState> {
  await requireSession();
  const { data, fieldErrors } = buildData(formData);

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  try {
    await prisma.pelangganMaster.update({ where: { id }, data });
  } catch (err) {
    console.error("updatePelangganMaster error:", err);
    return { error: "Gagal menyimpan perubahan. Coba lagi." };
  }

  revalidatePath("/pelanggan-master");
  revalidatePath(`/pelanggan-master/${id}`);
  redirect(`/pelanggan-master/${id}`);
}
