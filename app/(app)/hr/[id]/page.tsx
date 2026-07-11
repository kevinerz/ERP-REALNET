import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { updateKaryawan } from "../actions";
import KaryawanForm, { type KaryawanDefaults } from "../karyawan-form";
import DeleteButton from "../delete-button";
import { parseTempatTanggalLahir } from "../hr-helpers";

export default async function KaryawanDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id: idParam } = await params;
  const id = Number(idParam);
  if (!Number.isInteger(id)) notFound();

  const karyawan = await prisma.hrKaryawan.findUnique({ where: { id } });
  if (!karyawan) notFound();

  const { tempat, tanggalIso } = parseTempatTanggalLahir(karyawan.tempat_tanggal_lahir);

  const defaultValues: KaryawanDefaults = {
    nama: karyawan.nama,
    nik: karyawan.nik,
    nomor_kk: karyawan.nomor_kk,
    tipe_nomor_sim: karyawan.tipe_nomor_sim ?? "",
    jenis_kelamin: karyawan.jenis_kelamin,
    tempat_lahir: tempat,
    tanggal_lahir: tanggalIso,
    agama: karyawan.agama,
    status_pernikahan: karyawan.status_pernikahan,
    no_telp: karyawan.no_telp ?? "",
    email: karyawan.email ?? "",
    alamat: karyawan.alamat ?? "",
    divisi: karyawan.divisi,
    jabatan: karyawan.jabatan ?? "",
    tipe_petugas: karyawan.tipe_petugas ?? "Lainnya",
    status_kepegawaian: karyawan.status_kepegawaian,
    tanggal_masuk: karyawan.tanggal_masuk ? karyawan.tanggal_masuk.toISOString().slice(0, 10) : "",
    status_aktif: karyawan.status_aktif ? "1" : "0",
    gaji_pokok: karyawan.gaji_pokok.toString(),
    tunjangan_jabatan: karyawan.tunjangan_jabatan.toString(),
    tunjangan_operasional: karyawan.tunjangan_operasional.toString(),
    bank: karyawan.bank ?? "",
    rekening: karyawan.rekening ?? "",
    username: karyawan.username,
  };

  const boundUpdate = updateKaryawan.bind(null, id);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <Link href="/hr" className="text-sm text-blue-600 hover:underline">
            ← Kembali ke daftar
          </Link>
          <h1 className="mt-2 text-2xl font-semibold text-gray-800">{karyawan.nama}</h1>
          <p className="text-sm text-gray-500">
            NIK {karyawan.nik} &middot; {karyawan.divisi}
          </p>
        </div>
        <DeleteButton id={karyawan.id} />
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <KaryawanForm action={boundUpdate} defaultValues={defaultValues} submitLabel="Simpan Perubahan" isEdit />
      </div>
    </div>
  );
}
