# نقشه راه تنظیمات سامانه (Admin System Settings)

> سند مرجع برای پیاده‌سازی بخش «تنظیمات سامانه» در پنل ادمین.  
> آخرین بررسی کدبیس: ۱۴۰۴/۰۴/۱۴

---

## وضعیت فعلی

### پیاده‌سازی شده


| مورد             | مسیر / لینک                                           |
| ---------------- | ----------------------------------------------------- |
| کنترل دسترسی API | `admin-api-routes` → `AdminApiRoutes.vue`             |
| منابع سیستم      | `admin-system-resources` → `AdminSystemResources.vue` |


### در منوی ادمین هست، صفحه ندارد

- تنظیمات عمومی (`general-settings`)
- تنظیمات درگاه پرداخت (`payment-settings`)
- تنظیمات SEO (`seo-settings`)

### جاهایی که الان هاردکد شده‌اند


| فایل / محل                     | چه چیزی                                                 |
| ------------------------------ | ------------------------------------------------------- |
| `public/index.html`            | meta tags, favicon, theme-color, Goftino                |
| `public/manifest.json`         | PWA name, icons, colors, shortcuts                      |
| `src/composables/useSEO.js`    | SITE_NAME, SITE_URL, DEFAULT_DESCRIPTION, DEFAULT_IMAGE |
| `src/store/config.js`          | appUrl, apiBaseUrl, reCAPTCHA, Reverb/Pusher            |
| `src/assets/tailwind.css`      | فونت‌ها (YekanBakh, Anjoman)                            |
| `src/translate/locales/*.json` | متن‌های footer، contact، about                          |
| `.env` / `production-env.txt`  | secrets، payment، SMS، OAuth، scores                    |
| `config/*.php`                 | payment, services, scores, search, messenger, upload    |


---

## راهنمای ذخیره‌سازی


| نوع                    | مناسب برای                       | مثال                                     |
| ---------------------- | -------------------------------- | ---------------------------------------- |
| **DB + Cache**         | تنظیمات قابل تغییر از ادمین      | نام سایت، رنگ، لینک شبکه‌ها              |
| **فایل (CDN/static)**  | تصاویر، favicon، لوگو            | logo.png, favicon.svg                    |
| **Env**                | secrets و credentials            | ZARINPAL_TOKEN, SMTP password            |
| **Regenerate on save** | فایل‌هایی که build/serve می‌شوند | manifest.json, robots.txt, CSS variables |


### پیشنهاد معماری دیتابیس

```
site_settings (key-value + type + group)
├── branding.*      → logo, favicon, colors, name
├── seo.*           → meta defaults, robots
├── contact.*       → email, social links
├── auth.*          → registration, captcha toggle
├── payment.*       → default gateway (secrets در .env)
├── integrations.*  → goftino, analytics IDs
└── business.*      → scores, upload limits
```

---

## ۱. هویت و برندینگ (Branding)


| مورد                        | توضیح                                      | ذخیره‌سازی |
| --------------------------- | ------------------------------------------ | ---------- |
| نام سامانه (فارسی/انگلیسی)  | «زنبورک» — `useSEO.js`, `manifest.json`    | DB + Cache |
| عنوان پیش‌فرض صفحات         | `{title} | زنبورک`                         | DB         |
| شعار / Tagline              | توضیح کوتاه سایت                           | DB         |
| لوگوی اصلی (SVG/PNG)        | هدر، فوتر — SVG اینلاین در FooterComponent | فایل + DB  |
| لوگوی لایت / دارک           | نسخه جدا برای هر تم                        | فایل + DB  |
| لوگوی کوچک (Compact)        | سایدبار ادمین، موبایل                      | فایل + DB  |
| Favicon                     | `.svg` / `.ico` — `favicon.svg`            | فایل + DB  |
| Apple Touch Icon            | آیکون iOS                                  | فایل + DB  |
| OG Image پیش‌فرض            | `logo-wide.png` — اشتراک‌گذاری             | فایل + DB  |
| تصویر پیش‌فرض پستر دوره     | `static.zanburak.ir/poster/no-image.png`   | DB         |
| آواتار / کاور پیش‌فرض کاربر | `default.png`                              | DB         |
| Watermark ویدیو             | روی پلیر (اختیاری)                         | فایل + DB  |
| Copyright متن فوتر          | «© 2025 zanburak...»                       | DB         |


---

## ۲. تم، رنگ و ظاهر (Theme & UI)


| مورد                     | توضیح                            | ذخیره‌سازی         |
| ------------------------ | -------------------------------- | ------------------ |
| رنگ اصلی (Primary)       | `#fed700` / `#ffd124`            | DB → CSS Variables |
| رنگ ثانویه / Accent      | amber-400، yellow-400            | DB                 |
| رنگ Progress bar         | `--progress-color` در index.html | DB                 |
| theme-color مرورگر       | `#fed700`                        | DB                 |
| background-color PWA     | `#e8d82c` در manifest            | DB                 |
| تم پیش‌فرض سایت          | light / dark / system            | DB (پیش‌فرض)       |
| اجبار تم                 | فقط لایت، فقط دارک، یا آزاد      | DB                 |
| فونت اصلی                | YekanBakh — tailwind.css         | DB                 |
| فونت ثانویه              | Anjoman                          | DB                 |
| Border radius / Style    | گردی گوشه‌ها، سایه‌ها            | DB                 |
| رنگ scrollbar            | tailwind.css                     | DB                 |
| رنگ highlight ادیتور     | `#fed700` — EditorComponent      | DB                 |
| رنگ Plyr player          | `--plyr-color-main`              | DB                 |
| DaisyUI theme preset     | tailwind.config.js               | فایل config        |
| Skeleton / Loading style | shimmer animation                | DB                 |


---

## ۳. SEO و متادیتا


| مورد                               | توضیح                      | ذخیره‌سازی |
| ---------------------------------- | -------------------------- | ---------- |
| Meta Description پیش‌فرض           | index.html, useSEO.js      | DB         |
| Meta Keywords                      | index.html                 | DB         |
| Canonical URL base                 | `https://zanburak.ir`      | DB         |
| robots meta پیش‌فرض                | index, follow              | DB         |
| OG:site_name, locale               | fa_IR                      | DB         |
| Twitter handle                     | `@zanburak`                | DB         |
| Google Search Console verification | meta tag                   | DB         |
| Bing Webmaster verification        | meta tag                   | DB         |
| Schema.org Organization            | JSON-LD                    | DB         |
| robots.txt محتوا                   | SeoController              | DB         |
| Sitemap: فعال/غیرفعال بخش‌ها       | courses, articles, discuss | DB         |
| noindex برای staging               |                            | DB         |
| hreflang / زبان جایگزین            | fa / en                    | DB         |


---

## ۴. PWA و اپ موبایل


| مورد                 | توضیح                              | ذخیره‌سازی      |
| -------------------- | ---------------------------------- | --------------- |
| manifest.json کامل   | name, short_name, icons, shortcuts | DB → regenerate |
| آیکون‌های PWA        | 192, 256, 384, 512                 | فایل + DB       |
| Shortcuts            | دوره‌ها، پنل، چت                   | DB              |
| Screenshots PWA      | install prompt                     | فایل + DB       |
| display mode         | standalone / browser               | DB              |
| orientation          | portrait                           | DB              |
| Apple web app title  | «زنبورک»                           | DB              |
| status bar style iOS | black-translucent                  | DB              |


---

## ۵. تماس، شبکه‌های اجتماعی و سازمان


| مورد                       | توضیح                     | ذخیره‌سازی |
| -------------------------- | ------------------------- | ---------- |
| ایمیل تماس                 | `info@zanburak.ir`        | DB         |
| تلگرام                     | `t.me/zanburak`           | DB         |
| اینستاگرام                 |                           | DB         |
| یوتیوب                     |                           | DB         |
| توییتر/X                   | `@zanburakcom`            | DB         |
| لینکدین / گیت‌هاب / فیسبوک | ContactUs.vue             | DB         |
| شماره تلفن / واتساپ        |                           | DB         |
| آدرس فیزیکی                | geo.region IR             | DB         |
| ساعات پاسخگویی             |                           | DB         |
| نقشه / لوکیشن              |                           | DB         |
| Enamad / نماد اعتماد       | ID و کد — FooterComponent | DB         |
| Samandehi / سایر نمادها    |                           | DB         |


---

## ۶. صفحات ثابت و محتوای CMS


| مورد             | توضیح                        | ذخیره‌سازی     |
| ---------------- | ---------------------------- | -------------- |
| درباره ما        | `/about` — i18n              | DB (Rich text) |
| تماس با ما       | متن hero                     | DB             |
| FAQ              | CRUD جدا در ادمین ✓          | DB ✓           |
| قوانین و مقررات  | لینک footer                  | DB             |
| حریم خصوصی       |                              | DB             |
| VIP چیست         | `/what-is-vip`               | DB             |
| گواهینامه چیست   |                              | DB             |
| همکاری با ما     |                              | DB             |
| صفحه 404         | NotFound                     | DB             |
| بنرهای صفحه اصلی | IndexPage                    | DB             |
| اسلایدر / Hero   |                              | DB             |
| بخش‌های Featured | دوره‌ها، مسیرها              | DB             |
| Newsletter       | فرم footer — UI بدون backend | DB + API       |


---

## ۷. احراز هویت و امنیت


| مورد                  | توضیح                            | ذخیره‌سازی        |
| --------------------- | -------------------------------- | ----------------- |
| ثبت‌نام فعال/غیرفعال  | Fortify Features::registration() | DB                |
| تایید ایمیل اجباری    | الان غیرفعال                     | DB                |
| 2FA اجباری برای ادمین | Fortify 2FA                      | DB                |
| Google reCAPTCHA      | sitekey در config.js             | DB + Env (secret) |
| OAuth Google          | client_id در .env                | Env + DB (toggle) |
| OAuth GitHub          |                                  | Env + DB          |
| OAuth Yahoo           | services.php                     | Env + DB          |
| Session lifetime      | 43200 در production              | DB / Env          |
| Rate limit login      | Fortify limiters                 | DB                |
| حداکثر تلاش OTP       | SMS auth                         | DB                |
| IP whitelist ادمین    |                                  | DB                |
| CORS origins          | CORS_ALLOWED_ORIGINS             | Env + DB          |
| Password policy       | حداقل طول، complexity            | DB                |
| Force HTTPS           |                                  | Env               |


---

## ۸. درگاه پرداخت


| مورد                      | توضیح                         | ذخیره‌سازی   |
| ------------------------- | ----------------------------- | ------------ |
| درگاه پیش‌فرض             | zarinpal — config/payment.php | DB           |
| Zarinpal merchant ID      | ZARINPAL_TOKEN                | Env (secret) |
| Zibal token               |                               | Env          |
| Sandbox / Production mode |                               | DB           |
| Callback URL              |                               | DB           |
| توضیح تراکنش پیش‌فرض      |                               | DB           |
| فعال/غیرفعال هر درگاه     | local, payping, idpay...      | DB           |
| مالیات / VAT درصد         |                               | DB           |
| حداقل مبلغ پرداخت         |                               | DB           |
| ارز                       | تومان / ریال                  | DB           |
| فاکتور خودکار             |                               | DB           |


---

## ۹. ایمیل و پیامک


| مورد                     | توضیح                   | ذخیره‌سازی   |
| ------------------------ | ----------------------- | ------------ |
| SMTP host/port/user      | mail.zanburak.ir        | Env (secret) |
| MAIL_FROM address/name   |                         | DB + Env     |
| قالب ایمیل (header logo) | Blade mail templates    | DB + فایل    |
| Ghasedak API             | SMS                     | Env          |
| MeliPayamak              | OTP template ID         | Env + DB     |
| Provider پیش‌فرض SMS     |                         | DB           |
| OTP expiry time          |                         | DB           |
| Notification templates   | MeliPayamak pattern IDs | DB           |


---

## ۱۰. یکپارچه‌سازی‌های خارجی


| مورد                       | توضیح                     | ذخیره‌سازی |
| -------------------------- | ------------------------- | ---------- |
| Goftino chat widget        | ID `qss4Su` در index.html | DB         |
| Google Analytics / GTM     |                           | DB         |
| Microsoft Clarity / Hotjar |                           | DB         |
| Meilisearch host/key       | MEILISEARCH_*             | Env        |
| Scout driver               | meilisearch               | Env + DB   |
| Worker upload URL          | worker.zanburak.ir        | Env        |
| Static CDN URL             | static.zanburak.ir        | Env + DB   |
| Download CDN URL           | dl.zanburak.ir            | Env + DB   |
| FFmpeg paths               |                           | Env        |
| Reverb/WebSocket           | key, host, port           | Env        |
| Pusher (notification)      | جدا از Reverb             | Env        |


---

## ۱۱. قوانین کسب‌وکار


| مورد                          | توضیح                       | ذخیره‌سازی |
| ----------------------------- | --------------------------- | ---------- |
| نرخ تبدیل امتیاز              | SCORES_CONVERSION_RATE=1.35 | DB         |
| حداقل امتیاز برداشت           | SCORES_MIN_SCORES=10000     | DB         |
| تخفیف / کوپن                  | CRUD جدا ✓                  | DB ✓       |
| Plan / VIP                    | CRUD جدا ✓                  | DB ✓       |
| Certificate templates         | CRUD جدا ✓                  | DB ✓       |
| Quiz settings                 | per-quiz ✓                  | DB ✓       |
| Comment moderation            | auto-approve / manual       | DB         |
| Discuss: سوالات خصوصی پیش‌فرض |                             | DB         |
| Cart expiry                   |                             | DB         |
| Refund policy days            |                             | DB         |
| Affiliate / referral          |                             | DB         |
| Upload token TTL              | 20 min                      | Env + DB   |


---

## ۱۲. جستجو


| مورد              | توضیح                     | ذخیره‌سازی |
| ----------------- | ------------------------- | ---------- |
| Stop words        | config/search.php         | DB         |
| Slug noise words  |                           | DB         |
| Synonym overrides |                           | DB         |
| Min query length  |                           | DB         |
| Search provider   | Meilisearch / DB fallback | DB         |
| Reindex schedule  |                           | DB         |


---

## ۱۳. پیام‌رسان


| مورد                       | توضیح                | ذخیره‌سازی |
| -------------------------- | -------------------- | ---------- |
| Redis acceleration         | MESSENGER_REDIS      | Env + DB   |
| Stream TTL / poll interval | config/messenger.php | DB         |
| Typing throttle            |                      | DB         |
| Event retention hours      | 48h                  | DB         |
| Max file size in chat      |                      | DB         |
| Allowed file types         |                      | DB         |
| Max group members          |                      | DB         |


---

## ۱۴. اطلاع‌رسانی


| مورد                          | توضیح      | ذخیره‌سازی |
| ----------------------------- | ---------- | ---------- |
| Event groups                  | CRUD جدا ✓ | DB ✓       |
| Events                        | CRUD جدا ✓ | DB ✓       |
| Push notification toggle      |            | DB         |
| Email notification per event  |            | DB         |
| SMS notification per event    |            | DB         |
| In-app notification retention |            | DB         |
| Digest / daily summary        |            | DB         |


---

## ۱۵. آپلود و رسانه


| مورد                      | توضیح                        | ذخیره‌سازی |
| ------------------------- | ---------------------------- | ---------- |
| Max upload size           | PHP ini + backend validation | DB         |
| Allowed MIME types        | video, image, pdf            | DB         |
| Default storage disk      | local / s3 / static          | Env        |
| Image compression quality |                              | DB         |
| Video transcoding preset  | FFmpeg                       | DB         |
| Watermark on upload       |                              | DB         |
| CDN purge on change       |                              | DB         |


---

## ۱۶. پلیر ویدیو


| مورد                      | توضیح       | ذخیره‌سازی |
| ------------------------- | ----------- | ---------- |
| Default quality           | 720p / auto | DB         |
| Autoplay                  |             | DB         |
| Skip intro seconds        |             | DB         |
| Playback speed options    |             | DB         |
| Subtitle default language |             | DB         |
| DRM / download protection |             | DB         |
| Brand color player        | `#fed700`   | DB         |


---

## ۱۷. چندزبانه (i18n)


| مورد               | توضیح              | ذخیره‌سازی |
| ------------------ | ------------------ | ---------- |
| زبان پیش‌فرض       | fa                 | DB         |
| زبان‌های فعال      | fa, en             | DB         |
| RTL/LTR per locale |                    | DB         |
| Date format        | Jalali / Gregorian | DB         |
| Currency format    | تومان با جداکننده  | DB         |
| Timezone           | Asia/Tehran        | DB         |


---

## ۱۸. ناوبری و منو


| مورد               | توضیح                 | ذخیره‌سازی |
| ------------------ | --------------------- | ---------- |
| منوی هدر           | لینک‌ها، ترتیب        | DB         |
| منوی فوتر          | FooterComponent       | DB         |
| Admin menu         | adminMenuItems.js     | DB         |
| Breadcrumbs labels | adminBreadcrumbs.json | DB         |
| Maintenance banner | بنر بالای سایت        | DB         |
| Announcement bar   |                       | DB         |


---

## ۱۹. عملیات سیستم


| مورد                    | توضیح               | ذخیره‌سازی   |
| ----------------------- | ------------------- | ------------ |
| Maintenance mode        | Laravel down        | DB + Command |
| Maintenance message     | متن نمایشی          | DB           |
| APP_DEBUG toggle        | فقط super admin     | Env          |
| Cache clear             | config, route, view | Action       |
| Queue status            |                     | Read-only ✓  |
| Log viewer              |                     | Action       |
| Backup trigger          | DB/files            | Action       |
| Health check thresholds | CPU, disk alerts    | DB           |
| API rate limits global  |                     | DB           |
| Feature flags           | beta features       | DB           |


---

## ۲۰. Analytics و گزارش (Admin-only)


| مورد                      | توضیح      | ذخیره‌سازی |
| ------------------------- | ---------- | ---------- |
| Dashboard KPIs visible    |            | DB         |
| Report date range default |            | DB         |
| Export formats            | CSV, Excel | DB         |
| Anonymize user data       | GDPR       | DB         |


---

## اولویت‌بندی پیشنهادی

### فاز ۱ — پرتأثیر و کم‌ریسک

- [ ] نام سامانه + title + meta description
- [ ] لوگو + favicon (لایت/دارک)
- [ ] رنگ primary + theme-color
- [ ] شبکه‌های اجتماعی + ایمیل تماس
- [ ] Enamad / نماد اعتماد

### فاز ۲

- [ ] SEO defaults + robots.txt
- [ ] PWA manifest icons
- [ ] Goftino / Analytics IDs
- [ ] Maintenance mode + announcement bar
- [ ] Default avatar/poster

### فاز ۳

- [ ] Payment gateway toggle (بدون نمایش secret)
- [ ] Score conversion rate
- [ ] Auth toggles (registration, captcha)
- [ ] Upload limits
- [ ] Home page CMS blocks

### فاز ۴ — پیشرفته

- [ ] Theme builder (CSS variables)
- [ ] Menu manager
- [ ] Static pages CMS
- [ ] Feature flags
- [ ] i18n از DB

---

## یادداشت‌های پیاده‌سازی

1. **Secrets هرگز در DB plain-text نباشند** — فقط toggle و reference؛ مقدار در `.env`.
2. **API عمومی `/api/settings/public*`* — فقط branding, contact, theme colors (بدون secret).
3. **Cache invalidation** — بعد از هر update، Redis cache پاک شود.
4. **Frontend bootstrap** — settings در app init لود شود و CSS variables inject شوند.
5. **Audit log** — هر تغییر تنظیمات توسط ادمین ثبت شود.

---

## فایل‌های مرتبط در کدبیس

```
zanburak-frontend/
├── public/index.html
├── public/manifest.json
├── src/composables/useSEO.js
├── src/store/config.js
├── src/config/adminMenuItems.js
├── src/assets/tailwind.css
├── src/views/components/home/FooterComponent.vue
└── src/views/components/DarkMode.vue

zanburak-backend/
├── config/payment.php
├── config/services.php
├── config/scores.php
├── config/search.php
├── config/messenger.php
├── config/upload.php
├── app/Http/Controllers/Api/Seo/SeoController.php
└── production-env.txt
```

