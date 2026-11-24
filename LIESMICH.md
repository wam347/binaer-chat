# Binär-Chat – Rollenspiel (Docker)

Eigenständige Version des Binär-Chats mit Rollenkarten, Besprechung nach jeder Runde und Gesamtübersicht.
Unabhängig von memoone.de: Webseite und Chat-Server laufen zusammen in einem Container (PHP 8.3 + Apache).

```
app/index.html          Rollenspiel (Startseite)
app/update.php          Chat-Server
Dockerfile              Image-Bau
docker-compose.yml      Variante B (mit Image-Bau)
portainer-stack.yml     Variante A (ohne Image-Bau, für den Portainer-Web-Editor)
```

Aufruf danach: `http://<IP-des-Docker-Hosts>:8085`
Lehrkraft-Ansicht mit allen Rollenkarten und der Lösung: `http://<IP>:8085/?lehrer`

---

## Variante A – Portainer-Web-Editor (am einfachsten)

1. Den Ordner `app` auf den Docker-Host kopieren, z. B. nach `/opt/binaerchat/app`
   (per WinSCP, `scp -r app root@<host>:/opt/binaerchat/` o. Ä.).
2. Portainer → **Stacks** → **Add stack** → Name `binaerchat` → **Web editor**.
3. Inhalt von `portainer-stack.yml` einfügen. Falls nötig, den Host-Pfad und den Port (links von `:80`) anpassen.
4. **Deploy the stack**.

Änderungen an `index.html` (z. B. neue Rollenkarten) gelten sofort, nachdem du die Seite im Browser neu geladen hast. Ein Neustart des Containers ist nicht nötig.

## Variante B – Image bauen

**Über die Docker-CLI auf dem Host:** Den ganzen Ordner kopieren und dann ausführen:
```
docker compose up -d --build
```
Der Container erscheint danach auch in Portainer.

**Über Portainer aus einem Git-Repository:** Den Ordner in ein (privates) GitHub-Repo legen.
Dann Portainer → Stacks → Add stack → **Repository** wählen. Repo-URL eintragen, Compose path `docker-compose.yml` → Deploy.

---

## Hinweise

- **Daten:** Die Chat-Nachrichten liegen im Volume `binaerchat-daten`. Pro Kanal gibt es eine kleine JSON-Datei.
  Nachrichten älter als 24 h und Kanäle, die 7 Tage ungenutzt waren, löscht der Server automatisch.
- **Zurücksetzen:** Den Stack stoppen und das Volume `binaerchat-daten` löschen. Alternativ einfach neue Gruppennamen verwenden.
- **Rollenkarten ändern:** In `app/index.html` oben im Block `KONFIGURATION` stehen `KARTEN` sowie die Leitfragen und Tipps (`BESPRECHUNG`).
- **Reverse-Proxy (optional):** Der Container spricht nur HTTP auf Port 80. Für HTTPS oder einen Namen wie `binaerchat.schule.local`
  kannst du ihn hinter einen vorhandenen Proxy (Nginx Proxy Manager, Traefik) hängen.
- **Netz:** Die Schülergeräte müssen den Docker-Host erreichen, also im selben Netz sein oder über eine Portfreigabe.
