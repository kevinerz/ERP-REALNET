"use server";

import { prisma } from "@/lib/prisma";
import { requireSession } from "@/lib/auth";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

export type PelangganFormState = {
  error?: string;
  fieldErrors?: Record<string, string>;
};

const REQUIRED_FIELDS = ["nama", "user", "paket", "url_maps", "alamat", "telp", "modem", "dropcore"] as const;

function buildData(formData: FormData) {
  const get = (key: string) => (formData.get(key)?.toString().trim() ?? "");

  const fieldErrors: Record<string, string> = {};
  for (const field of REQUIRED_FIELDS) {
    if (!get(field)) fieldErrors[field] = "Wajib diisi";
  }

  const tanggalRaw = get("tanggal");

  const data = {
    nama: get("nama"),
    user: get("user"),
    userppp: get("userppp") || null,
    passwordppp: get("passwordppp") || null,
    paket: get("paket"),
    vlan: get("vlan") || null,
    sn: get("sn") || null,
    pop: get("pop") || null,
    odp: get("odp") || null,
    url_maps: get("url_maps"),
    teknisi: get("teknisi") || null,
    alamat: get("alamat"),
    ktp: get("ktp") || null,
    telp: get("telp"),
    email: get("email") || null,
    marketing: get("marketing") || null,
    tanggal: tanggalRaw ? new Date(tanggalRaw) : null,
    status: get("status") || null,
    modem: get("modem"),
    dropcore: get("dropcore"),
  };

  return { data, fieldErrors };
}

export async function createPelanggan(
  _prev: PelangganFormState,
  formData: FormData
): Promise<PelangganFormState> {
  const session = await requireSession();
  const { data, fieldErrors } = buildData(formData);

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  let newId: number;
  try {
    const created = await prisma.pelangganInstalasi.create({
      data: { ...data, last_updated_by: session.username },
    });
    newId = created.id;
  } catch (err) {
    console.error(err);
    return { error: "Gagal menyimpan data pelanggan. Coba lagi." };
  }

  revalidatePath("/pelanggan");
  redirect(`/pelanggan/${newId}`);
}

export async function updatePelanggan(
  id: number,
  _prev: PelangganFormState,
  formData: FormData
): Promise<PelangganFormState> {
  const session = await requireSession();
  const { data, fieldErrors } = buildData(formData);

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  try {
    await prisma.pelangganInstalasi.update({
      where: { id },
      data: { ...data, last_updated_by: session.username },
    });
  } catch (err) {
    console.error(err);
    return { error: "Gagal menyimpan perubahan. Coba lagi." };
  }

  revalidatePath("/pelanggan");
  revalidatePath(`/pelanggan/${id}`);
  redirect(`/pelanggan/${id}`);
}

export async function deletePelanggan(id: number): Promise<void> {
  await requireSession();
  await prisma.pelangganInstalasi.delete({ where: { id } });
  revalidatePath("/pelanggan");
  redirect("/pelanggan");
}
