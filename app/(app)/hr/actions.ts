"use server";

import { prisma } from "@/lib/prisma";
import { requireSession } from "@/lib/auth";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { combineTempatTanggalLahir, calculateUmur } from "./hr-helpers";

export type KaryawanFormState = {
  error?: string;
  fieldErrors?: Record<string, string>;
};

const REQUIRED_FIELDS = [
  "nama",
  "nik",
  "nomor_kk",
  "jenis_kelamin",
  "tempat_lahir",
  "tanggal_lahir",
  "agama",
  "status_pernikahan",
  "status_kepegawaian",
  "divisi",
  "gaji_pokok",
  "username",
] as const;

function buildData(formData: FormData, opts: { requirePassword: boolean }) {
  const get = (key: string) => (formData.get(key)?.toString().trim() ?? "");

  const fieldErrors: Record<string, string> = {};
  for (const field of REQUIRED_FIELDS) {
    if (!get(field)) fieldErrors[field] = "Wajib diisi";
  }
  if (opts.requirePassword && !get("password")) {
    fieldErrors.password = "Wajib diisi";
  }

  const tanggalLahir = get("tanggal_lahir");
  const numOrZero = (key: string) => {
    const raw = get(key);
    const n = Number(raw);
    return raw && !Number.isNaN(n) ? n : 0;
  };

  const data = {
    nama: get("nama"),
    nik: get("nik"),
    nomor_kk: get("nomor_kk"),
    tipe_nomor_sim: get("tipe_nomor_sim") || null,
    jenis_kelamin: get("jenis_kelamin"),
    tempat_tanggal_lahir: tanggalLahir ? combineTempatTanggalLahir(get("tempat_lahir"), tanggalLahir) : "",
    umur: tanggalLahir ? calculateUmur(tanggalLahir) : 0,
    agama: get("agama"),
    status_pernikahan: get("status_pernikahan"),
    no_telp: get("no_telp") || null,
    email: get("email") || null,
    alamat: get("alamat") || null,
    status_kepegawaian: get("status_kepegawaian"),
    tanggal_masuk: get("tanggal_masuk") ? new Date(get("tanggal_masuk")) : null,
    status_aktif: get("status_aktif") === "0" ? false : true,
    divisi: get("divisi"),
    jabatan: get("jabatan") || null,
    tipe_petugas: get("tipe_petugas") || "Lainnya",
    // id_pop_penempatan sengaja tidak diisi dari form -- jaringan_pop belum
    // punya data (modul Pengaturan/POP belum dibangun), jadi field ini akan
    // selalu NULL sampai modul itu ada. FK fk_karyawan_pop akan menolak
    // angka apapun yang tidak benar-benar ada di jaringan_pop.
    gaji_pokok: numOrZero("gaji_pokok"),
    tunjangan_jabatan: numOrZero("tunjangan_jabatan"),
    tunjangan_operasional: numOrZero("tunjangan_operasional"),
    bank: get("bank") || null,
    rekening: get("rekening") || null,
    username: get("username"),
  };

  return { data, fieldErrors };
}

export async function createKaryawan(
  _prev: KaryawanFormState,
  formData: FormData
): Promise<KaryawanFormState> {
  await requireSession();
  const { data, fieldErrors } = buildData(formData, { requirePassword: true });

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  const password = formData.get("password")?.toString().trim() ?? "";

  let newId: number;
  try {
    const created = await prisma.hrKaryawan.create({
      data: { ...data, password },
    });
    newId = created.id;
  } catch (err: any) {
    if (err?.code === "P2002") {
      const target = Array.isArray(err?.meta?.target) ? err.meta.target.join(", ") : "";
      return {
        error: `Data tidak bisa disimpan: ${target.includes("username") ? "username" : "NIK"} sudah dipakai karyawan lain.`,
      };
    }
    console.error("createKaryawan error:", err);
    return { error: "Gagal menyimpan data karyawan. Coba lagi." };
  }

  revalidatePath("/hr");
  redirect(`/hr/${newId}`);
}

export async function updateKaryawan(
  id: number,
  _prev: KaryawanFormState,
  formData: FormData
): Promise<KaryawanFormState> {
  await requireSession();
  const { data, fieldErrors } = buildData(formData, { requirePassword: false });

  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Ada isian wajib yang belum lengkap.", fieldErrors };
  }

  const newPassword = formData.get("password")?.toString().trim() ?? "";

  try {
    await prisma.hrKaryawan.update({
      where: { id },
      data: { ...data, ...(newPassword ? { password: newPassword } : {}) },
    });
  } catch (err: any) {
    if (err?.code === "P2002") {
      const target = Array.isArray(err?.meta?.target) ? err.meta.target.join(", ") : "";
      return {
        error: `Data tidak bisa disimpan: ${target.includes("username") ? "username" : "NIK"} sudah dipakai karyawan lain.`,
      };
    }
    console.error("updateKaryawan error:", err);
    return { error: "Gagal menyimpan perubahan. Coba lagi." };
  }

  revalidatePath("/hr");
  revalidatePath(`/hr/${id}`);
  redirect(`/hr/${id}`);
}

export async function deleteKaryawan(id: number): Promise<void> {
  await requireSession();
  await prisma.hrKaryawan.delete({ where: { id } });
  revalidatePath("/hr");
  redirect("/hr");
}
