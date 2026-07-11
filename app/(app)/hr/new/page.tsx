import Link from "next/link";
import { createKaryawan } from "../actions";
import KaryawanForm from "../karyawan-form";

export default function NewKaryawanPage() {
  return (
    <div>
      <div className="mb-6">
        <Link href="/hr" className="text-sm text-blue-600 hover:underline">
          ← Kembali ke daftar
        </Link>
        <h1 className="mt-2 text-2xl font-semibold text-gray-800">Tambah Karyawan</h1>
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <KaryawanForm action={createKaryawan} submitLabel="Simpan Karyawan" />
      </div>
    </div>
  );
}
