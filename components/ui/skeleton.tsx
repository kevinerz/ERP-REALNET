// Blok skeleton dasar (placeholder loading) -- dipakai lewat loading.tsx
// tiap modul supaya transisi memuat data terasa "cepat & rapi", bukan
// layar putih kosong. Style konsisten dengan kartu/tabel yang sudah ada.

export function Skeleton({ className = "" }: { className?: string }) {
  return <div className={`animate-pulse rounded-md bg-gray-200/80 ${className}`} />;
}

/** Baris kartu statistik (dashboard, gangguan, cabut, dll). */
export function StatCardsSkeleton({ count = 3 }: { count?: number }) {
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="rounded-2xl border border-gray-200 bg-white p-5 shadow-elevated">
          <Skeleton className="h-3 w-24" />
          <Skeleton className="mt-3 h-8 w-16" />
        </div>
      ))}
    </div>
  );
}

/** Skeleton tabel generik: header + N baris x N kolom. */
export function TableSkeleton({ rows = 8, cols = 5 }: { rows?: number; cols?: number }) {
  return (
    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-elevated">
      <div className="border-b border-gray-100 bg-gray-50 px-4 py-3">
        <div className="flex gap-6">
          {Array.from({ length: cols }).map((_, i) => (
            <Skeleton key={i} className="h-3 w-20" />
          ))}
        </div>
      </div>
      <div className="divide-y divide-gray-100">
        {Array.from({ length: rows }).map((_, r) => (
          <div key={r} className="flex items-center gap-6 px-4 py-4">
            {Array.from({ length: cols }).map((_, c) => (
              <Skeleton key={c} className={c === 0 ? "h-4 w-32" : "h-4 w-16"} />
            ))}
          </div>
        ))}
      </div>
    </div>
  );
}

/** Kombinasi umum: header halaman + (opsional) kartu statistik + tabel. */
export function ListPageSkeleton({
  statCount = 0,
  rows = 8,
  cols = 5,
}: {
  statCount?: number;
  rows?: number;
  cols?: number;
}) {
  return (
    <div>
      <div className="mb-6">
        <Skeleton className="h-7 w-56" />
        <Skeleton className="mt-2 h-4 w-72" />
      </div>
      {statCount > 0 && (
        <div className="mb-6">
          <StatCardsSkeleton count={statCount} />
        </div>
      )}
      <Skeleton className="mb-4 h-10 w-full max-w-sm" />
      <TableSkeleton rows={rows} cols={cols} />
    </div>
  );
}
