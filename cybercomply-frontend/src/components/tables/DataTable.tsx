import { ReactNode } from "react";

type Column<T> = {
  key: keyof T | string;
  header: string;
  render?: (row: T) => ReactNode;
};

type Props<T> = {
  columns: Array<Column<T>>;
  data: T[];
};

export function DataTable<T extends { id?: string | number }>({ columns, data }: Props<T>) {
  return (
    <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
      <table className="min-w-full text-sm">
        <thead className="bg-slate-50 text-slate-700">
          <tr>
            {columns.map((col) => (
              <th key={String(col.key)} className="px-4 py-3 text-left font-semibold">
                {col.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.length === 0 && (
            <tr>
              <td className="px-4 py-6 text-slate-500" colSpan={columns.length}>
                Sem resultados.
              </td>
            </tr>
          )}
          {data.map((row, index) => (
            <tr key={String(row.id ?? index)} className="border-t border-slate-200">
              {columns.map((col) => (
                <td key={String(col.key)} className="px-4 py-3 text-slate-700">
                  {col.render ? col.render(row) : String((row as Record<string, unknown>)[String(col.key)] ?? "")}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
