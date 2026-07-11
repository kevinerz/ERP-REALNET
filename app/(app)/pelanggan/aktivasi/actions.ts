"use server";

import { prisma } from "@/lib/prisma";
import { requireSession } from "@/lib/auth";
import { revalidatePath } from "next/cache";
import { sendWhatsAppToPop } from "@/lib/notifications";
import { buildActivationMessage } from "@/lib/notifications/templates/activation";

function getStr(formData: FormData, key: string): string {
  return formData.get(key)?.toString().trim() ?? "";
}

/**
 * Samakan persis dengan handler `do_activate` di aktivasi_pelanggan.php lama:
 * simpan userppp/passwordppp/vlan/paket, set status='aktivasi', lalu kirim
 * notifikasi WhatsApp ke grup POP terkait.
 */
export async function activatePelanggan(id: number, formData: FormData): Promise<void> {
  const session = await requireSession();

  const userppp = getStr(formData, "userppp");
  const passwordppp = getStr(formData, "passwordppp");
  const vlan = getStr(formData, "vlan");
  const paket = getStr(formData, "paket");

  if (!userppp || !passwordppp || !vlan || !paket) {
    throw new Error("Data aktivasi tidak lengkap (paket, username, password, dan VLAN wajib diisi).");
  }

  const updated = await prisma.pelangganInstalasi.update({
    where: { id },
    data: { userppp, passwordppp, vlan, paket, status: "aktivasi", last_updated_by: session.username },
  });

  const paketId = Number(paket);
  const paketData = Number.isFinite(paketId)
    ? await prisma.jaringanPaket.findFirst({ where: { id_paket: paketId } })
    : null;

  const message = buildActivationMessage({
    id: updated.id,
    nama: updated.nama,
    alamat: updated.alamat,
    userppp: updated.userppp,
    passwordppp: updated.passwordppp,
    vlan: updated.vlan,
    telp: updated.telp,
    pop: updated.pop,
    paket: paketData
      ? { nama_paket: paketData.nama_paket, kecepatan: paketData.kecepatan, harga: paketData.harga.toString() }
      : null,
  });
  await sendWhatsAppToPop(updated.pop, message);

  revalidatePath("/pelanggan/aktivasi");
  revalidatePath("/pelanggan");
}

/**
 * Samakan persis dengan handler `cancel_aktivasi` lama: HANYA ubah status
 * jadi 'disimpan', tidak menyentuh field lain -- sengaja begitu di PHP lama
 * juga (dipakai kalau operator belum yakin data aktivasinya, jadi disimpan
 * dulu sebagai draft/pending tanpa mengubah data teknis).
 */
export async function savePendingPelanggan(id: number): Promise<void> {
  const session = await requireSession();
  await prisma.pelangganInstalasi.update({
    where: { id },
    data: { status: "disimpan", last_updated_by: session.username },
  });
  revalidatePath("/pelanggan/aktivasi");
  revalidatePath("/pelanggan");
}

export async function deleteAntrian(id: number): Promise<void> {
  await requireSession();
  await prisma.pelangganInstalasi.delete({ where: { id } });
  revalidatePath("/pelanggan/aktivasi");
  revalidatePath("/pelanggan");
}
