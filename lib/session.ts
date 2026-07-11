import crypto from "crypto";

// Sesi login sederhana pakai signed cookie (HMAC), tanpa dependency
// tambahan seperti next-auth -- cukup untuk kebutuhan internal ERP ini.

export type SessionPayload = {
  userId: number;
  username: string;
  nama: string;
  divisi: string;
};

const COOKIE_NAME = "erp_session";
const MAX_AGE_SECONDS = 60 * 60 * 8; // 8 jam, sama seperti sesi PHP lama

function getSecret(): string {
  const secret = process.env.AUTH_SECRET;
  if (!secret) {
    throw new Error(
      "AUTH_SECRET belum diset. Isi di .env.local (dev) atau Environment Variables Hostinger (produksi)."
    );
  }
  return secret;
}

function sign(value: string): string {
  return crypto.createHmac("sha256", getSecret()).update(value).digest("hex");
}

export function encodeSession(payload: SessionPayload): string {
  const json = JSON.stringify(payload);
  const base = Buffer.from(json, "utf8").toString("base64url");
  const signature = sign(base);
  return `${base}.${signature}`;
}

export function decodeSession(cookieValue: string | undefined): SessionPayload | null {
  if (!cookieValue) return null;
  const [base, signature] = cookieValue.split(".");
  if (!base || !signature) return null;
  const expected = sign(base);
  // Bandingkan pakai timingSafeEqual supaya tidak rawan timing attack
  const a = Buffer.from(signature);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return null;
  try {
    const json = Buffer.from(base, "base64url").toString("utf8");
    return JSON.parse(json) as SessionPayload;
  } catch {
    return null;
  }
}

export const SESSION_COOKIE_NAME = COOKIE_NAME;
export const SESSION_MAX_AGE = MAX_AGE_SECONDS;
