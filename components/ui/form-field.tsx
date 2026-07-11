// Primitive form bersama -- dipakai semua modul (Pelanggan, HRIS, dan modul
// baru nanti) supaya style & struktur form konsisten, dan tidak perlu
// duplikasi definisi "Field" + kelas input di tiap modul seperti sebelumnya.
//
// Cara pakai di modul baru:
//   <FormField label="Nama" required error={err("nama")}>
//     <TextInput name="nama" defaultValue={d.nama} />
//   </FormField>

export const inputBaseClass =
  "w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400";

export function FormField({
  label,
  required,
  error,
  children,
  full,
}: {
  label: string;
  required?: boolean;
  error?: string;
  children: React.ReactNode;
  full?: boolean;
}) {
  return (
    <div className={full ? "sm:col-span-2" : ""}>
      <label className="mb-1 block text-sm font-medium text-gray-700">
        {label}
        {required && <span className="text-red-500"> *</span>}
      </label>
      {children}
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}

type InputProps = React.InputHTMLAttributes<HTMLInputElement>;
export function TextInput(props: InputProps) {
  return <input {...props} className={`${inputBaseClass} ${props.className ?? ""}`} />;
}

type TextAreaProps = React.TextareaHTMLAttributes<HTMLTextAreaElement>;
export function TextArea(props: TextAreaProps) {
  return <textarea rows={2} {...props} className={`${inputBaseClass} ${props.className ?? ""}`} />;
}

type SelectProps = React.SelectHTMLAttributes<HTMLSelectElement>;
export function SelectInput(props: SelectProps) {
  return <select {...props} className={`${inputBaseClass} ${props.className ?? ""}`} />;
}
