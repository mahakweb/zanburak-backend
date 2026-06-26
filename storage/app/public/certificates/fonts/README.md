# Certificate fonts (bundled)

Font files in this folder are **tracked in git** and ship with the project.
After deploy, run `php artisan storage:link` once — fonts are served from `/storage/certificates/fonts/`.

## Persian (fa)
| File | Slug | License |
|------|------|---------|
| yekanbakh.woff | yekanbakh | bundled |
| bzar.ttf | bzar | bundled |
| irannastaliq.ttf | irannastaliq | bundled |
| bnazanin.ttf | bnazanin | Vazirmatn OFL (fallback) |
| anjoman.woff | anjoman | bundled |
| btitr.ttf | btitr | Vazirmatn OFL (fallback) |
| vazirmatn.woff2 | vazirmatn | [SIL OFL](https://github.com/rastikerdar/vazirmatn) |
| samim.woff2 | samim | [SIL OFL](https://github.com/rastikerdar/samim-font) |

## English (en)
| File | Slug | License |
|------|------|---------|
| roboto.woff2 | roboto | [SIL OFL](https://fonts.google.com/specimen/Roboto) |
| open_sans.woff2 | open_sans | SIL OFL |
| montserrat.woff2 | montserrat | SIL OFL |
| playfair.woff2 | playfair | SIL OFL |
| merriweather.woff2 | merriweather | SIL OFL |

Admins can replace any font via the template editor upload UI; uploads override bundled files for that slug.
