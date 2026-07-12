import Link from "next/link";
import { createPelangganMasterManual } from "../actions";
import PelangganMasterForm from "../pelanggan-master-form";

export default function NewPelangganMasterPage() {
  return (
    <div>
      <div className="mb-6">
        <Link href="/pelanggan-master" className="text-sm text-blue-600 hover:underline">
          &larr; Kembali ke daftar
        </Link>
        <h1 className="mt-2 text-2xl font-semibold text-gray-800">Tambah Pelanggan Manual</h1>
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <PelangganMasterForm action={createPelangganMasterManual} submitLabel="Simpan Pelanggan" />
      </div>
    </div>
  );
}
