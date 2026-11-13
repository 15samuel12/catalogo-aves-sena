import { Component, inject, signal } from "@angular/core";
import { RouterLink, RouterLinkActive, RouterOutlet } from "@angular/router";
import { SessionService } from "./core/session.service";

@Component({
  standalone: true,
  selector: "app-root",
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: "./app.component.html",
  styleUrl: "./app.component.scss",
})
/** Contenedor compartido por cada feature diferida; expone el estado de sesión a la navegación. */
export class AppComponent {
  readonly session = inject(SessionService);
  /** Estado local del menú de la sesión actual; no modifica la sesión ni los permisos. */
  readonly accountMenuOpen = signal(false);

  /** Finaliza sólo el estado del navegador; revocar el refresh en servidor será una acción explícita futura. */
  logout(): void {
    this.accountMenuOpen.set(false);
    this.session.end();
  }

  toggleAccountMenu(): void {
    this.accountMenuOpen.update((open) => !open);
  }

  closeAccountMenu(): void {
    this.accountMenuOpen.set(false);
  }

  /** Sólo estos roles reciben los accesos administrativos en la interfaz. */
  isAdministrator(role: string): boolean {
    return role === "admin" || role === "superadmin";
  }
}
