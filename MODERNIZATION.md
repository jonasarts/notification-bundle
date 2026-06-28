# Modernization Plan — jonasarts/notification-bundle

> Owner decision 2026-06-27. Status: DROP/DEPRECATE — kein v8.

## Decision

Das Bundle wird **nicht** auf Symfony 8 / PHP 8.4 portiert. Es gibt **kein v8** und **kein 1:1-Ersatz-Bundle**. Konsumenten wechseln auf natives `symfony/mailer` mit `TemplatedEmail` + `mailer.yaml`.

Begründung:

- **Vom Autor selbst aufgegeben.** `docs/changes.md` (V6.0.3): „This bundle will be retired soon." Aktive Entwicklung beschränkt sich seit Jahren auf Kompat-Pflege.
- **0 Tests.** Kein `tests/`, kein PHPUnit in `require-dev`, keine CI. Für einen E-Mail-Versandpfad (kritisch, manuell schwer prüfbar) ein K.O.-Kriterium.
- **SF8-Bruch.** `src/Controller/MailDebugController.php` nutzt `Symfony\Component\Routing\Annotation\Route` (in SF8 entfernt) plus alte `NotificationBundle:entity:...`-Template-Notation → Controller ist unter SF8 nicht lauffähig. `composer.json` pinnt `symfony/* ^6.0|^7.0`.
- **Funktionaler Sender/Reply-To-Bug.** In `MailerNotification::sendMessageA()` werden die lokalen Variablen `$sender`/`$reply_to`/`$return_path` per `!empty(...)` geprüft, aber nie aus `$this->sender` etc. befüllt → konfigurierte Sender/Reply-To/Return-Path-Defaults werden faktisch **nie** auf die Mail gesetzt (toter Code).
- **Kein nativer Mehrwert mehr.** Alle Kernfunktionen deckt SF8 nativ und besser ab (siehe Migration guide).

## Salvage-Analyse für `Hasch\Mailer`

Pro öffentlicher Methode/Feature von `src/Notification/MailerNotification.php`:

| Feature / Methode | Erhaltenswert? | Warum |
|---|---|---|
| `sendTemplateMessage` / `…A` — HTML+TXT-Template **Auto-Pairing** (`<tpl>.html.twig` + `<tpl>.txt.twig` automatisch erkennen & beide setzen) | **Bedingt ja** | Einzige echte Bequemlichkeit. `TemplatedEmail` kann zwar `htmlTemplate()` und `textTemplate()`, aber der Aufrufer muss beide explizit nennen. Eine Helper-Methode, die aus *einem* Basisnamen beide Varianten setzt (sofern vorhanden), spart Boilerplate. Nur als dünner Wrapper über `TemplatedEmail`. |
| `sendTemplateStringMessage` / `…A` — Inline-Twig-Strings | **Nein** | Randfall; nativ trivial via `BodyRenderer` / `createTemplate`. Lohnt keinen Service. |
| Zentrale Default-Header (`from`/`sender`/`reply_to`/`return_path`/`subject_prefix`) | **Nein** | Vollständig durch `mailer.yaml` (`envelope`, globale `headers`) bzw. einen `MessageEvent`-Listener abgedeckt — der natürliche Ort in `Hasch\Mailer`/App-Config, nicht in einer Klasse. |
| Subject-als-Twig (`createTemplate($subject)->render($data)`) | **Nein** | Selten gebraucht, SSTI-Risiko bei User-Input, trivial im Aufrufer. |
| `subject` / `subject_underline` Auto-Variablen | **Nein** | Textmail-Optik-Spezialität; gehört ins Template, nicht in den Service. |
| FPDF/TCPDF-Attachment-Sonderlogik (Reflection auf private Felder) | **Nein** | Fragile Reflection-Hacks. Attachment ist eine Zeile `->attachFromPath()` / `->attach()` beim Aufrufer; PDF-Lib-Wissen gehört nicht in den Mailer. |
| `Swift_Attachment`-Zweig | **Nein** | Tot (SwiftMailer entfernt). |
| `sentMessagesCount` / `resetMessagesCount` | **Nein** | Durch Mailer-Events / Messenger / Profiler abgedeckt. |
| `MailDebugController` | **Nein** | Mailer Web Profiler („Email"-Panel) ersetzt das; zusätzlich Template-Injection-Risiko. |

**Verdikt:** Der **einzige** Salvage-Kandidat ist die **HTML+TXT-Template-Auto-Pairing-Bequemlichkeit**. Empfehlung: als **kleine optionale Helper-Methode** in `Hasch\Mailer` aufheben — z. B. `sendTemplated(string $baseTemplate, …)`, die intern ein `TemplatedEmail` baut, `htmlTemplate("$base.html.twig")` setzt und `textTemplate("$base.txt.twig")` nur, wenn das Template existiert. Dünner Wrapper über `MailerInterface` + `TemplatedEmail`, mit PHPUnit-Test. Alles Übrige: **nichts erhaltenswert, alles nativ**.

## Migration guide (Konsumenten → `TemplatedEmail`)

1. **Dependency entfernen:** `composer remove jonasarts/notification-bundle`; Eintrag aus `config/bundles.php` löschen; `config/packages/notification.yaml` entfernen.
2. **Defaults nach `mailer.yaml`:**
   ```yaml
   # config/packages/mailer.yaml
   framework:
     mailer:
       envelope:
         sender: 'nobody@domain.tld'   # ersetzt notification.from/sender
       headers:
         from: 'Mr. Nobody <nobody@domain.tld>'
         reply-to: '...'               # ersetzt notification.reply_to
   ```
   `subject_prefix` → optional ein `MessageEvent`-Listener, der das Subject präfixt.
3. **Versandcode umstellen** (`NotificationInterface::sendTemplateMessage(...)` →):
   ```php
   use Symfony\Bridge\Twig\Mime\TemplatedEmail;
   use Symfony\Component\Mailer\MailerInterface;

   $email = (new TemplatedEmail())
       ->to($to)
       ->subject($subject)
       ->htmlTemplate('mails/welcome.html.twig')   // <tpl>.html.twig
       ->textTemplate('mails/welcome.txt.twig')    // <tpl>.txt.twig (optional)
       ->context($data);                           // statt $data-Array
   $mailer->send($email);                          // MailerInterface injizieren
   ```
   Optional stattdessen `Hasch\Mailer::sendTemplated('mails/welcome', $to, $subject, $data)` (Salvage-Helper).
4. **Attachments:** `->attachFromPath($path)` bzw. `->attach($content, $name, $mime)` direkt am `TemplatedEmail`; FPDF/TCPDF-Output (`$pdf->Output('', 'S')`) im Aufrufer erzeugen.
5. **Mail-Debug:** `MailDebugController` ersatzlos streichen — Mailer Web Profiler Panel nutzen.
6. **Async (optional, neu):** `symfony/messenger` mit `SendEmailMessage`-Transport für Retry/Queue.

## Work items

- [ ] `composer.json`: `"abandoned": "symfony/mailer"` setzen; v7 als finalen Stand taggen, kein v8.
- [ ] `README.md`: Deprecation-Hinweis ganz oben („Retired. Use `symfony/mailer` `TemplatedEmail` + `mailer.yaml`. See MODERNIZATION.md.") + Platzhaltertext entfernen.
- [ ] Packagist als deprecated/abandoned markieren (folgt aus `abandoned` in composer.json).
- [ ] Konsumenten-Apps identifizieren und gemäß Migration guide umstellen.
- [ ] (Optional Salvage) `Hasch\Mailer`: `sendTemplated()`-Helper (HTML+TXT-Auto-Pairing über `TemplatedEmail`) implementieren **inkl. PHPUnit-Test**.
- [ ] Nach Migration aller Konsumenten: Repo archivieren.

## Definition of Done

- composer.json als `abandoned` markiert, v7 final getaggt, kein v8 veröffentlicht.
- README trägt klaren Deprecation-/Migrationshinweis; Platzhaltertexte weg.
- Alle bekannten Konsumenten nutzen `TemplatedEmail` + `mailer.yaml`; keine Abhängigkeit auf `jonasarts/notification-bundle` mehr.
- Falls Salvage umgesetzt: `Hasch\Mailer::sendTemplated()` existiert, ist getestet und dokumentiert.
- Repo archiviert (read-only).

## Out of scope

- Portierung des Bundles auf SF8 / PHP 8.4 (verworfen).
- Fix des Sender/Reply-To-Bugs in der Altklasse (Bundle wird nicht weitergepflegt).
- Neuschreiben des `MailDebugController` (durch Mailer Profiler ersetzt).
- Multi-Channel-Notifications (SMS/Slack/Push) — wäre `symfony/notifier`, separates Thema, nicht Teil dieses Bundles.
- Async-/Messenger-Setup über den Migrationshinweis hinaus.
