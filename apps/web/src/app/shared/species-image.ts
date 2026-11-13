import { Species } from "./models/catalog.models";

/** Fuente única de la imagen usada en las tarjetas y en la ficha de una especie. */
export function speciesImageUrl(species: Species): string {
  return species.image_url?.trim() || "/assets/catalog/placeholder-ave.png";
}
