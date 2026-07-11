import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { updateGangguan } from "../actions";
import GangguanForm, { type GangguanDefaults } from "../gangguan-form";
import DeleteButton from "../delete-button";

export default async function GangguanDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id: idParam } = await params;
  const id = Number(idParam);
  if (!Number.isInteger(id)) notFound();

  const tiket = await prisma.tiketGangguan.findUnique({ where: { id } });
  if (!tiket) notFound();

  const defaultValues: GangguanDefaults = {
    nama_pelanggan: tiket.nama_pelanggan ?? "",
    alamat: tiket.alamat ?? "",
    whatsapp: tiket.whatsapp ?? "",
    pop: tiket.pop ?? "",
    vlan: tiket.vlan ?? "",
    sn: tiket.sn ?? "",
    keluhan: tiket.keluhan ?? "",
    maps_url: tiket.maps_url ?? "",
    teknisi: tiket.teknisi ?? "",
    status: tiket.status,
  };

  const boundUpdate = updateGangguan.bind(null, id);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <Link href="/gangguan" className="text-sm text-blue-600 hover:underline">
            &larr; Kembali ke daftar
          </Link>
          <h1 className="mt-2 text-2xl font-semibold text-gray-800">{tiket.nama_pelanggan}</h1>
          <p className="text-sm text-gray-500">
            Tiket #{tiket.id} &middot; POP {(tiket.pop ?? "-").toUpperCase()}
          </p>
        </div>
        <DeleteButton id={tiket.id} />
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <GangguanForm action={boundUpdate} defaultValues={defaultValues} submitLabel="Simpan Perubahan" />
      </div>
    </div>
  );
}
