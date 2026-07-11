"use server";

import { prisma } from "@/lib/prisma";
import { requireSession } from "@/lib/auth";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

export type GangguanFormState = {
  error?: string;
  fieldErrors?: Record<string, string>;
};

const REQUIRED_FIELDS = ["nama_pelanggan", "alamat", "pop", "keluhan"] as const;

function buildData(formData: FormData) {
  const get = (key: string) => (formData.get(key)?.toString().trim() ?? "");

  const fieldErrors: Record<string, string> = {};
  for (const field of REQUIRED_FIELDS) {
    if (!get(field)) fieldErrors[field] = "Wajib diisi";
  }

  const data = {
    nama_pelanggan: get("nama_pelanggan"),
    alamat: get("alamat"),
    whatsapp: get("whatsapp") || null,
    pop: get("pop"),
    vlan: get("vlan") || null,
    sn: get("sn") || null,
    keluhan: get("keluhan"),
    maps_url: get("maps_url") || null,
    teknisi: get("teknisi") || null,
    status: get("status") || "belum dikerjakan",
  };

  return { data, fieldErrors };
}

export async function createGangguan(
  _prev: GangguanFormState,
  formData: FormData
): Promise<GangguanFormState> {
  await requireSession();
  const { data, fieldErrors } = buildData(formData);

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  let newId: number;
  try {
    const created = await prisma.tiketGangguan.create({ data });
    newId = created.id;
  } catch (err) {
    console.error("createGangguan error:", err);
    return { error: "Gagal menyimpan tiket gangguan. Coba lagi." };
  }

  revalidatePath("/gangguan");
  redirect(`/gangguan/${newId}`);
}

export async function updateGangguan(
  id: number,
  _prev: GangguanFormState,
  formData: FormData
): Promise<GangguanFormState> {
  await requireSession();
  const { data, fieldErrors } = buildData(formData);

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  // Samakan persis dengan edit_gangguan.php: kalau status baru diubah jadi
  // 'selesai', set tanggal_selesai = sekarang. Kalau status direvisi DARI
  // 'selesai' ke status lain, reset tanggal_selesai jadi NULL. Selain dua
  // kondisi itu, tanggal_selesai yang sudah ada dipertahankan apa adanya.
  const existing = await prisma.tiketGangguan.findUnique({
    where: { id },
    select: { status: true, tanggal_selesai: true },
  });

  let tanggal_selesai: Date | null;
  if (data.status === "selesai") {
    tanggal_selesai = new Date();
  } else if (existing?.status === "selesai" && data.status !== "selesai") {
    tanggal_selesai = null;
  } else {
    tanggal_selesai = existing?.tanggal_selesai ?? null;
  }

  try {
    await prisma.tiketGangguan.update({
      where: { id },
      data: { ...data, tanggal_selesai },
    });
  } catch (err) {
    console.error("updateGangguan error:", err);
    return { error: "Gagal menyimpan perubahan. Coba lagi." };
  }

  revalidatePath("/gangguan");
  revalidatePath(`/gangguan/${id}`);
  redirect(`/gangguan/${id}`);
}

export async function deleteGangguan(id: number): Promise<void> {
  await requireSession();
  await prisma.tiketGangguan.delete({ where: { id } });
  revalidatePath("/gangguan");
  redirect("/gangguan");
}
