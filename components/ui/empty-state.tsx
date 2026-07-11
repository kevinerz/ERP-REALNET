// Baris "tidak ada data" yang konsisten untuk semua tabel daftar di app ini.
export function EmptyTableRow({ colSpan, message = "Tidak ada data." }: { colSpan: number; message?: string }) {
  return (
    <tr>
      <td colSpan={colSpan} className="px-4 py-10 text-center text-gray-500">
        {message}
      </td>
    </tr>
  );
}
