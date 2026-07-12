import Link from "next/link";
import { notFound } from "next/navigation";
import { prisma } from "@/lib/prisma";
import { Badge } from "@/components/ui/badge";
import { updatePelangganMaster } from "../actions";
import PelangganMasterForm, { type PelangganMasterDefaults } from "../pelanggan-master-form";

function sumberLabel(sumber: string): string {
  if (sumber === "mixradius_import") return "Import MixRadius";
  if (sumber === "psb") return "PSB";
  return "Manual";
}

function sumberTone(sumber: string): "blue" | "green" | "gray" {
  if (sumber === "mixradius_import") return "gray";
  if (sumber === "psb") return "green";
  return "blue";
}

export default async function PelangganMasterDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id: idParam } = await params;
  const id = Number(idParam);
  if (!Number.isInteger(id)) notFound();

  const pelanggan = await prisma.pelangganMaster.findUnique({ where: { id } });
  if (!pelanggan) notFound();

  const defaultValues: PelangganMasterDefaults = {
    nama: pelanggan.nama,
    telp: pelanggan.telp,
    alamat: pelanggan.alamat ?? "",
    username: pelanggan.username ?? "",
    email: pelanggan.email ?? "",
    pop: pelanggan.pop ?? "",
  };

  const boundUpdate = updatePelangganMaster.bind(null, id);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <Link href="/pelanggan-master" className="text-sm text-blue-600 hover:underline">
            &larr; Kembali ke daftar
          </Link>
          <h1 className="mt-2 text-2xl font-semibold text-gray-800">{pelanggan.nama}</h1>
          <p className="text-sm text-gray-500">
            #{pelanggan.id} &middot; <Badge tone={sumberTone(pelanggan.sumber)}>{sumberLabel(pelanggan.sumber)}</Badge>
          </p>
        </div>
      </div>

      <div className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6">
        <PelangganMasterForm action={boundUpdate} defaultValues={defaultValues} submitLabel="Simpan Perubahan" />
      </div>
    </div>
  );
}
