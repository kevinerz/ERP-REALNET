import Link from "next/link";
import { createPelanggan } from "../actions";
import PelangganForm from "../pelanggan-form";

export default function NewPelangganPage() {
  return (
    <div>
      <div className="mb-6">
        <Link href="/pelanggan" className="text-sm text-blue-600 hover:underline">
          ← Kembali ke daftar
        </Link>
        <h1 className="mt-2 text-2xl font-semibold text-gray-800">Tambah Pelanggan</h1>
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <PelangganForm action={createPelanggan} submitLabel="Simpan Pelanggan" />
      </div>
    </div>
  );
}
