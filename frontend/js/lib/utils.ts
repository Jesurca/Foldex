// Une clases de Tailwind sin conflictos (cn). La usan los componentes de ui/.

// Imports: clsx (clases condicionales) y tailwind-merge.
import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
