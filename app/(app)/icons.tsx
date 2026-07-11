// Ikon inline (SVG tangan, gaya garis 24x24) -- sengaja tidak pakai library
// ikon eksternal (mis. lucide-react) supaya tidak nambah dependency baru dan
// tidak menambah risiko npm install/build di Hostinger.

type IconProps = { className?: string };

const base = {
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.75,
  strokeLinecap: "round" as const,
  strokeLinejoin: "round" as const,
  viewBox: "0 0 24 24",
};

export function IconGrid({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <rect x="3" y="3" width="7" height="7" rx="1.5" />
      <rect x="14" y="3" width="7" height="7" rx="1.5" />
      <rect x="3" y="14" width="7" height="7" rx="1.5" />
      <rect x="14" y="14" width="7" height="7" rx="1.5" />
    </svg>
  );
}

export function IconUsers({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <circle cx="9" cy="8" r="3.25" />
      <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" />
      <circle cx="17" cy="8.5" r="2.5" />
      <path d="M15.5 14.2c2.5.4 4.5 2.7 4.5 5.8" />
    </svg>
  );
}

export function IconWifi({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <path d="M4 9.5a13 13 0 0 1 16 0" />
      <path d="M7 13a8.5 8.5 0 0 1 10 0" />
      <path d="M10 16.5a4 4 0 0 1 4 0" />
      <circle cx="12" cy="19.5" r="0.9" fill="currentColor" stroke="none" />
    </svg>
  );
}

export function IconTicket({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <path d="M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4Z" />
      <path d="M10 6v12" strokeDasharray="2 2" />
    </svg>
  );
}

export function IconBriefcase({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <rect x="3" y="7.5" width="18" height="12" rx="2" />
      <path d="M8 7.5V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1.5" />
      <path d="M3 12.5h18" />
    </svg>
  );
}

export function IconWallet({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <rect x="3" y="6" width="18" height="13" rx="2" />
      <path d="M3 10h18" />
      <circle cx="16.5" cy="14.5" r="1" fill="currentColor" stroke="none" />
    </svg>
  );
}

export function IconHandshake({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <path d="M2.5 12.5 7 8l3.2 3.1a1.6 1.6 0 0 0 2.2 0l.2-.2a1.6 1.6 0 0 1 2.2 0l3.7 3.6" />
      <path d="M21.5 12.5 17 17l-2.5-2.4" />
      <path d="M9.5 14.5 12 17l1.3-1.2" />
    </svg>
  );
}

export function IconArchive({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <rect x="3" y="4" width="18" height="4.5" rx="1" />
      <path d="M5 8.5V18a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8.5" />
      <path d="M10 13h4" />
    </svg>
  );
}

export function IconMenu({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <path d="M4 6h16" />
      <path d="M4 12h16" />
      <path d="M4 18h16" />
    </svg>
  );
}

export function IconX({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <path d="M6 6l12 12" />
      <path d="M18 6 6 18" />
    </svg>
  );
}

export function IconChevronDown({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <path d="M6 9l6 6 6-6" />
    </svg>
  );
}

export function IconLogOut({ className }: IconProps) {
  return (
    <svg className={className} {...base}>
      <path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3" />
      <path d="M16 17l5-5-5-5" />
      <path d="M21 12H9" />
    </svg>
  );
}
