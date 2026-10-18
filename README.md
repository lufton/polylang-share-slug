# Polylang Share Slug

[Deutsch](#deutsch) | [English](#english)

## Deutsch

### Kurzbeschreibung

WordPress-Erweiterung für Polylang, die identische URL-Slugs in unterschiedlichen Sprachen für übersetzbare Beiträge, Seiten und Taxonomie-Begriffe ermöglicht.

### Zweck und Beispiel

Ein Slug ist der lesbare Teil einer URL, der einen Inhalt bezeichnet. Polylang Share Slug ermöglicht, denselben Slug in mehreren Sprachen zu verwenden, statt nur wegen einer anderen Sprachversion einen nummerierten Slug zu benötigen.

Beispiel bei einer entsprechend konfigurierten Sprachverzeichnis-Struktur:

```text
https://example.org/de/team/
https://example.org/en/team/
```

Beide Seiten verwenden `team`; der Sprachkontext unterscheidet sie. Das Plugin übersetzt keine Inhalte und richtet die Sprachverzeichnisse nicht selbst ein.

### Funktionsumfang

- Sprachabhängige Slug-Prüfung für von Polylang als übersetzbar behandelte Beiträge, Seiten und benutzerdefinierte Beitragstypen.
- Sprachabhängige Auflösung von Beiträgen und hierarchischen Seitenpfaden.
- Behandlung gemeinsamer Slugs für Begriffe übersetzbarer Taxonomien, etwa Kategorien und Schlagwörter, einschließlich Verwaltungsoberflächen-Hooks.
- Anpassung des Slugs beim Erstellen einer Medienübersetzung über Polylang.
- Kein eigener Einstellungsbildschirm; die Initialisierung erfolgt über `pll_init`.

Gleiche Slugs in verschiedenen Sprachen setzen keine Übersetzungsverknüpfung zwischen den Inhalten voraus. Innerhalb derselben Sprache bleiben die im Code vorgesehenen Kollisionsprüfungen bestehen; ein belegter Slug wird nicht beliebig wiederverwendbar.

### Voraussetzungen und Kompatibilität

Die Initialisierung setzt laut Quellcode Folgendes voraus:

1. WordPress mit aktivem Polylang und mindestens einer konfigurierten Sprache.
2. Eine nicht leere Permalink-Struktur, also keine einfachen Standard-Permalinks.
3. Eine Polylang-URL-Konfiguration mit aktiviertem Sprachkontext: Die interne Option `force_lang` muss einen von null verschiedenen Wert haben.
4. Die betreffenden Beitragstypen beziehungsweise Taxonomien müssen in Polylang übersetzbar sein.

Der Plugin-Header nennt Version `1.0.0`, aber keine Mindestversionen für WordPress, PHP oder Polylang. Der Code greift auf interne Polylang-Klassen, Eigenschaften und Modellmethoden zu. Diese Dokumentation ist keine Bestätigung der Kompatibilität mit aktuellen Versionen oder mit Polylang Pro. Vor dem produktiven Einsatz die konkrete Kombination auf einer Testinstallation prüfen.

### Installation

1. Polylang installieren, aktivieren und die Sprachen sowie die URL-Struktur konfigurieren.
2. Dieses Repository als ZIP herunterladen und entpacken.
3. Einen Ordner `polylang-share-slug` mit der Datei `polylang-share-slug.php` nach `wp-content/plugins/` kopieren.
4. Im WordPress-Backend unter Plugins die Erweiterung Polylang Share Slug aktivieren.
5. Die oben genannten Voraussetzungen kontrollieren.

Erwartete Dateistruktur:

```text
wp-content/plugins/polylang-share-slug/
├── polylang-share-slug.php
└── README.md
```

### Verwendung und Funktionsprüfung

1. Eine Seite in einer Sprache anlegen, ihr eine Sprache zuweisen und beispielsweise den Slug `team` speichern.
2. Eine weitere Seite in einer anderen Sprache anlegen, die Sprache zuweisen und ebenfalls `team` als Slug speichern. Falls erforderlich, nach der Sprachzuweisung den Slug erneut bearbeiten und speichern.
3. Beide sprachspezifischen URLs aufrufen und kontrollieren, dass jeweils der richtige Inhalt erscheint.
4. Falls verwendet, denselben Ablauf für Beiträge, hierarchische Seiten, eigene Beitragstypen sowie Kategorien und Schlagwörter prüfen.
5. Zusätzlich eine Slug-Kollision innerhalb derselben Sprache testen.

Bereits vorhandene nummerierte Slugs werden nicht durch eine allgemeine Migration bereinigt. Gewünschte Slugs müssen bei den betreffenden Inhalten bearbeitet werden.

### Grenzen und Betriebshinweise

- Vor Änderungen an bestehenden URLs eine Datenbanksicherung erstellen und auf einer Testinstallation prüfen.
- Das Plugin verändert die Slug-Prüfung und Inhaltsabfragen; beim Speichern von Taxonomie-Begriffen aktualisiert es außerdem direkt die WordPress-Term-Tabelle.
- Eigene Abfragen, Importprozesse und zusätzliche Plugins können andere Ausführungspfade verwenden. Deren Verhalten muss gesondert geprüft werden.
- Eine Deaktivierung ist keine Rückmigration: Es gibt im vorliegenden Code keine Routine, die bereits gemeinsam verwendete Slugs wieder eindeutig umbenennt.
- Automatische Weiterleitungen für geänderte URLs gehören nicht zum hier implementierten Funktionsumfang.
- Im vorliegenden Repository sind keine automatisierten Tests oder eine Kompatibilitätsmatrix enthalten. Die obigen Schritte sind eine manuelle Prüfanleitung, kein Bericht ausgeführter Tests.

### Herkunft, Beiträge und Lizenzhinweis

Originalprojekt: [lufton/polylang-share-slug](https://github.com/lufton/polylang-share-slug). Der Plugin-Header nennt Lufton als Autor. Dieser Fork: [ThomasKujawa/polylang-share-slug](https://github.com/ThomasKujawa/polylang-share-slug).

Der Quellcode verweist unter anderem auf WordPress, AndyDeGroo, Ulrich Pogson sowie die Projekte `grappler/polylang-slug` und `mirsch/polylang-slug`. Bestehende Hinweise im Quellcode bleiben unverändert.

Verbesserungen möglichst als nachvollziehbare, thematisch begrenzte Pull Requests an das Originalprojekt richten. Fehlerberichte sollten die WordPress-, PHP- und Polylang-Versionen, URL-Konfiguration und konkrete Schritte zur Reproduktion enthalten.

Im vorliegenden Repository gibt es keine separate Lizenzdatei; der Plugin-Header enthält ebenfalls keine Lizenzangabe. Diese README legt keine Lizenz fest. Die geltenden Nutzungs- und Weitergaberechte vor einer Weiterverteilung mit dem ursprünglichen Autor klären.

---

## English

### Short description

A WordPress extension for Polylang that enables identical URL slugs across different languages for translatable posts, pages, and taxonomy terms.

### Purpose and example

A slug is the readable part of a URL identifying a piece of content. Polylang Share Slug allows the same slug to be used in multiple languages without requiring a numbered slug solely because another language version exists.

Example with a suitably configured language-directory URL structure:

```text
https://example.org/de/team/
https://example.org/en/team/
```

Both pages use `team`; the language context distinguishes them. The plugin neither translates content nor configures language directories itself.

### Features

- Language-aware slug checks for posts, pages, and custom post types treated as translatable by Polylang.
- Language-aware lookup of posts and hierarchical page paths.
- Shared-slug handling for terms in translatable taxonomies, such as categories and tags, including administration hooks.
- Slug adjustment when a media translation is created through Polylang.
- No dedicated settings screen; initialization runs through `pll_init`.

Content sharing a slug across languages does not have to be linked as translations. The collision checks implemented for content within the same language still apply; an occupied slug does not become freely reusable.

### Requirements and compatibility

The source code requires the following for initialization:

1. WordPress with active Polylang and at least one configured language.
2. A non-empty permalink structure, rather than plain default permalinks.
3. A Polylang URL configuration with language context enabled: the internal `force_lang` option must have a non-zero value.
4. The relevant post types or taxonomies must be translatable in Polylang.

The plugin header declares version `1.0.0` but specifies no minimum WordPress, PHP, or Polylang versions. The code accesses internal Polylang classes, properties, and model methods. This documentation does not certify compatibility with current versions or with Polylang Pro. Test your specific combination on a staging installation before production use.

### Installation

1. Install and activate Polylang, then configure languages and the URL structure.
2. Download this repository as a ZIP archive and extract it.
3. Copy a `polylang-share-slug` directory containing `polylang-share-slug.php` into `wp-content/plugins/`.
4. Activate Polylang Share Slug on the Plugins screen in the WordPress administration area.
5. Check the requirements listed above.

Expected file structure:

```text
wp-content/plugins/polylang-share-slug/
├── polylang-share-slug.php
└── README.md
```

### Usage and verification

1. Create a page in one language, assign its language, and save a slug such as `team`.
2. Create another page in a different language, assign its language, and save `team` as its slug too. If necessary, edit and save the slug again after assigning the language.
3. Open both language-specific URLs and verify that each displays the correct content.
4. Where applicable, repeat this check for posts, hierarchical pages, custom post types, categories, and tags.
5. Also test a slug collision within the same language.

There is no general migration that cleans up existing numbered slugs. Edit the relevant content to set the desired slugs.

### Limitations and operational notes

- Back up the database and test on staging before changing existing URLs.
- The plugin modifies slug checks and content queries; when saving taxonomy terms, it also updates the WordPress terms table directly.
- Custom queries, import processes, and other plugins may use different execution paths. Verify their behavior separately.
- Deactivation is not a reverse migration: the supplied code has no routine to rename existing shared slugs back to globally unique values.
- Automatic redirects for changed URLs are outside the functionality implemented here.
- The supplied repository contains no automated tests or compatibility matrix. The steps above are a manual verification guide, not a report of tests performed.

### Origin, contributions, and license notice

Original project: [lufton/polylang-share-slug](https://github.com/lufton/polylang-share-slug). The plugin header credits Lufton as its author. This fork: [ThomasKujawa/polylang-share-slug](https://github.com/ThomasKujawa/polylang-share-slug).

Source comments reference WordPress, AndyDeGroo, Ulrich Pogson, and the `grappler/polylang-slug` and `mirsch/polylang-slug` projects, among others. Existing source-code notices remain unchanged.

Where possible, submit focused, clearly explained improvements to the original project as pull requests. Bug reports should include WordPress, PHP, and Polylang versions, URL configuration, and precise reproduction steps.

The supplied repository has no separate license file, and the plugin header does not declare a license. This README does not assign one. Clarify applicable usage and redistribution rights with the original author before redistributing the code.
