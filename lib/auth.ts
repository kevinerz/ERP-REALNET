import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { decodeSession, SESSION_COOKIE_NAME, type SessionPayload } from "./session";

// Divisi yang diizinkan masuk sistem (dipetakan dari login.php lama)
export const DASHBOARD_DIVISI = ["Admin", "IT", "Manager", "SPV Teknis", "Finance"];
export const TEKNISI_DIVISI = ["Leader Area", "Teknisi"];
export const ALL_ALLOWED_DIVISI = [...DASHBOARD_DIVISI, ...TEKNISI_DIVISI];

export async function getSession(): Promise<SessionPayload | null> {
  const store = await cookies();
  const raw = store.get(SESSION_COOKIE_NAME)?.value;
  return decodeSession(raw);
}

/** Panggil di awal Server Component halaman yang wajib login. */
export async function requireSession(): Promise<SessionPayload> {
  const session = await getSession();
  if (!session) {
    redirect("/login");
  }
  return session;
}
