import Link from "next/link";
import { createGangguan } from "../actions";
import GangguanForm from "../gangguan-form";

export default function NewGangguanPage() {
  return (
    <div>
      <div className="mb-6">
        <Link href="/gangguan" className="text-sm text-blue-600 hover:underline">
          &larr; Kembali ke daftar
        </Link>
        <h1 className="mt-2 text-2xl font-semibold text-gray-800">Tambah Tiket Gangguan</h1>
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <GangguanForm action={createGangguan} submitLabel="Simpan Tiket" />
      </div>
    </div>
  );
}
