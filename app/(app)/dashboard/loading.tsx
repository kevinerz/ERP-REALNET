import { StatCardsSkeleton } from "@/components/ui/skeleton";

export default function DashboardLoading() {
  return (
    <div>
      <div className="mb-6 h-[132px] animate-pulse rounded-2xl bg-slate-200/60 sm:h-[140px]" />
      <StatCardsSkeleton count={6} />
    </div>
  );
}
