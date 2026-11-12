// Preparación de Vitest: matchers de Testing Library y un localStorage en memoria.

import "@testing-library/jest-dom/vitest";

// Node 25+ trae un `localStorage` global sin archivo de respaldo que pisa el de jsdom
// y no guarda nada. Para las pruebas usamos uno en memoria.
class MemoriaStorage implements Storage {
  private datos = new Map<string, string>();
  get length() {
    return this.datos.size;
  }
  clear() {
    this.datos.clear();
  }
  getItem(clave: string) {
    return this.datos.get(clave) ?? null;
  }
  key(i: number) {
    return [...this.datos.keys()][i] ?? null;
  }
  removeItem(clave: string) {
    this.datos.delete(clave);
  }
  setItem(clave: string, valor: string) {
    this.datos.set(clave, String(valor));
  }
}

const almacen = new MemoriaStorage();
Object.defineProperty(window, "localStorage", { configurable: true, value: almacen });
Object.defineProperty(globalThis, "localStorage", { configurable: true, value: almacen });

Object.defineProperty(window, "scrollTo", {
  writable: true,
  value: () => {},
});

Object.defineProperty(window, "matchMedia", {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => {},
  }),
});
