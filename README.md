# ChocoClear Plugin for October CMS

ChocoClear provides two report widgets to **inspect** and **clean up** cache and superfluous files on disk.

> **Warning**
> Purging files is destructive and cannot be undone. Review the affected paths before running a purge.

---

## Widget 1 — File Cache

Use this widget to view and clear CMS/back-end caches.

**Requirement:** Your app must use the **file** cache driver (serialized cache objects stored on disk).

**Clears (relative to `storage/`):**
- **CMS cache**
    - `cms/cache/`
    - `cms/combiner/`
    - `cms/twig/`
- **Back-end cache**
    - `framework/cache/`

**How to use:** Click **Clear**. The paths above are emptied.

**Widget options**
- **Show without chart** – renders a compact version without the chart.
- **Chart size** – chart radius (default: `200`).

---

## Widget 2 — Purge Files

Use this widget to view sizes and optionally purge generated or redundant files.

**Targets**
- **Images**
    - **Thumbnails** in `storage/app/uploads/public/` matching regex `^thumb_.*`
    - **Resizer cache** in `storage/app/resources/resize/` (cleared by removing folder contents)
- **Uploads** (`storage/app/uploads/*`)
    - **Purgeable uploads**: files present on disk with **no matching row** in `system_files`
    - **Orphans**: rows in `system_files` with **no `attachment_id`** (DB record + file removed)
- **Temp folder**
    - `storage/temp/`

**How to use:** Click **Clear**. What gets purged depends on the widget options you enable.

**Widget options**
- **Show without chart** – renders a compact version without the chart.
- **Chart size** – chart radius (default: `200`).
- **Purge thumbnails** – deletes files whose **filename** matches `^thumb_.*` in `storage/app/uploads/public/`.
- **Purge resizer cache** – empties `storage/app/resources/resize/`.
- **Purge uploaded files** – deletes disk files that **do not exist** in `system_files`.
- **Purge orphaned files** – deletes **orphan records** in `system_files` (no `attachment_id`) and their files (if present).
- **Purge temp folder** – empties `storage/temp/`.

**Notes**
- Clearing of cache, thumbnails, resizer cache, uploaded files and orphaned files happen by performing the `october:util purge` commands with different parameters.
- Clearing of the temp folder is done by removing the entire folder contents.
- Size calculations can take time on very large trees; values are indicative at the moment of rendering.
- Be mindful of possible unintended consequences when clearing resizer cache and thumbnails as it may affect image rendering in the front-end (eg. think of resized images in sent emails that no longer will render).
- Clearing the temp folder should normally be done by the code that places things in there, but this widget provides a way to do it manually.

---

## Locales
- `en`
- `nl`
