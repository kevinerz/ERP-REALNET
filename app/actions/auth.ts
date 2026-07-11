"use server";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { verifyPassword } from "@/lib/password";
import { encodeSession, SESSION_COOKIE_NAME, SESSION_MAX_AGE } from "@/lib/session";
import { ALL_ALLOWED_DIVISI, TEKNISI_DIVISI } from "@/lib/auth";

export type LoginState = { error?: string } | null;

export async function loginAction(_prev: LoginState, formData: FormData): Promise<LoginState> {
  const username = String(formData.get("username") ?? "").trim();
  const password = String(formData.get("password") ?? "").trim();

  if (!username || !password) {
    return { error: "Username dan password wajib diisi." };
  }

  let karyawan;
  try {
    karyawan = await prisma.hrKaryawan.findFirst({
      where: { username },
      select: { id: true, nama: true, divisi: true, password: true, status_aktif: true },
    });
  } catch (err) {
    console.error("loginAction: gagal konek/query ke database", err);
    return { error: "Tidak bisa terhubung ke database. Coba lagi sebentar lagi, atau hubungi Administrator." };
  }

  if (!karyawan || !verifyPassword(password, karyawan.password)) {
    return { error: "Username atau password salah." };
  }
  if (!ALL_ALLOWED_DIVISI.includes(karyawan.divisi)) {
    return { error: "Akun Anda tidak memiliki akses ke sistem ini." };
  }
  if (!karyawan.status_aktif) {
    return { error: "Akun Anda tidak aktif. Hubungi Administrator." };
  }

  const cookieStore = await cookies();
  cookieStore.set({
    name: SESSION_COOKIE_NAME,
    value: encodeSession({
      userId: karyawan.id,
      username,
      nama: karyawan.nama,
      divisi: karyawan.divisi,
    }),
    httpOnly: true,
    secure: process.env.NODE_ENV === "production",
    sameSite: "lax",
    maxAge: SESSION_MAX_AGE,
    path: "/",
  });

  redirect(TEKNISI_DIVISI.includes(karyawan.divisi) ? "/dashboard" : "/dashboard");
}

export async function logoutAction() {
  const cookieStore = await cookies();
  cookieStore.delete(SESSION_COOKIE_NAME);
  redirect("/login");
}
