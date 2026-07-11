import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { updatePelanggan } from "../actions";
import PelangganForm, { type PelangganDefaults } from "../pelanggan-form";
import DeleteButton from "../delete-button";

export default async function PelangganDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id: idParam } = await params;
  const id = Number(idParam);
  if (!Number.isInteger(id)) notFound();

  const pelanggan = await prisma.pelangganInstalasi.findUnique({ where: { id } });
  if (!pelanggan) notFound();

  const defaultValues: PelangganDefaults = {
    nama: pelanggan.nama,
    telp: pelanggan.telp,
    email: pelanggan.email ?? "",
    alamat: pelanggan.alamat,
    url_maps: pelanggan.url_maps,
    paket: pelanggan.paket,
    pop: pelanggan.pop ?? "",
    odp: pelanggan.odp ?? "",
    vlan: pelanggan.vlan ?? "",
    modem: pelanggan.modem,
    dropcore: pelanggan.dropcore,
    sn: pelanggan.sn ?? "",
    user: pelanggan.user,
    userppp: pelanggan.userppp ?? "",
    passwordppp: pelanggan.passwordppp ?? "",
    ktp: pelanggan.ktp ?? "",
    teknisi: pelanggan.teknisi ?? "",
    marketing: pelanggan.marketing ?? "",
    status: pelanggan.status ?? "",
    tanggal: pelanggan.tanggal ? pelanggan.tanggal.toISOString().slice(0, 10) : "",
  };

  const boundUpdate = updatePelanggan.bind(null, id);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <Link href="/pelanggan" className="text-sm text-blue-600 hover:underline">
            ← Kembali ke daftar
          </Link>
          <h1 className="mt-2 text-2xl font-semibold text-gray-800">{pelanggan.nama}</h1>
          <p className="text-sm text-gray-500">ID #{pelanggan.id}</p>
        </div>
        <DeleteButton id={pelanggan.id} />
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <PelangganForm action={boundUpdate} defaultValues={defaultValues} submitLabel="Simpan Perubahan" />
      </div>
    </div>
  );
}
