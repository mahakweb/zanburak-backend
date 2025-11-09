# راهنمای سریع Submit Sitemap

## 🎯 پاسخ مستقیم به سوال شما

### در Google Search Console و Bing Webmaster Tools چه آدرسی وارد کنم؟

**پاسخ:** فقط این آدرس را وارد کنید:

```
https://zanburak.ir/sitemap.xml
```

**نه این:**
```
❌ https://zanburak.ir/api/sitemap.xml
❌ http://your-backend-url/api/sitemap.xml
```

---

## 📋 چرا این آدرس؟

1. **شما در Apache/Nginx تنظیم کرده‌اید:**
   - وقتی کسی `https://zanburak.ir/sitemap.xml` را می‌خواند
   - Apache/Nginx به صورت خودکار آن را به `http://your-backend-url/api/sitemap.xml` proxy می‌کند

2. **Google و Bing باید آدرس عمومی را ببینند:**
   - آنها باید آدرس frontend را ببینند (نه backend)
   - این آدرس در robots.txt هم ذکر شده است

3. **این آدرس در robots.txt است:**
   ```
   Sitemap: https://zanburak.ir/sitemap.xml
   ```

---

## 🔍 مراحل دقیق

### Google Search Console:

1. به https://search.google.com/search-console بروید
2. سایت خود را انتخاب کنید
3. در منوی سمت چپ، روی **"Sitemaps"** کلیک کنید
4. در قسمت **"Add a new sitemap"**، فقط این را وارد کنید:
   ```
   sitemap.xml
   ```
   یا کامل:
   ```
   https://zanburak.ir/sitemap.xml
   ```
5. روی **"Submit"** کلیک کنید

**نکته:** Google معمولاً فقط `/sitemap.xml` را هم می‌پذیرد (بدون دامنه کامل)

---

### Bing Webmaster Tools:

1. به https://www.bing.com/webmasters بروید
2. سایت خود را انتخاب کنید
3. در منوی بالا، روی **"Sitemaps"** کلیک کنید
4. روی **"Submit Sitemap"** کلیک کنید
5. این آدرس را وارد کنید:
   ```
   https://zanburak.ir/sitemap.xml
   ```
6. روی **"Submit"** کلیک کنید

---

## ✅ تست قبل از Submit

قبل از submit کردن، مطمئن شوید که این آدرس کار می‌کند:

```bash
# در مرورگر یا terminal
curl https://zanburak.ir/sitemap.xml
```

باید XML content ببینید (نه 404).

---

## 🚨 مشکلات رایج

### مشکل: "Sitemap could not be read"
**علت:** Apache/Nginx تنظیم نشده یا backend در دسترس نیست
**راه حل:** 
1. مطمئن شوید rewrite rules در Apache/Nginx درست است
2. تست کنید: `curl https://zanburak.ir/sitemap.xml`

### مشکل: "Sitemap is empty"
**علت:** Backend هیچ محتوایی ندارد
**راه حل:** مطمئن شوید که در دیتابیس دوره/اپیزود/سوال دارید

### مشکل: "Sitemap URL is not allowed"
**علت:** آدرس اشتباه وارد شده
**راه حل:** فقط `https://zanburak.ir/sitemap.xml` را وارد کنید

---

## 📊 بعد از Submit

بعد از submit:
- **Google:** معمولاً در عرض چند ساعت پردازش می‌شود
- **Bing:** معمولاً در عرض 24 ساعت پردازش می‌شود

می‌توانید وضعیت را در dashboard ببینید:
- تعداد صفحات ایندکس شده
- خطاها (اگر وجود داشته باشد)
- آخرین به‌روزرسانی

---

## 🔄 به‌روزرسانی خودکار

Sitemap شما داینامیک است، پس:
- هر بار که محتوای جدید اضافه می‌شود، sitemap به‌روز می‌شود
- Google/Bing معمولاً هر چند روز یکبار sitemap را دوباره می‌خوانند
- می‌توانید در Google Search Console، "Request indexing" کنید

---

## ✅ چک‌لیست نهایی

- [ ] Apache/Nginx تنظیم شده است
- [ ] `https://zanburak.ir/sitemap.xml` کار می‌کند (تست شده)
- [ ] در Google Search Console: `https://zanburak.ir/sitemap.xml` submit شده
- [ ] در Bing Webmaster Tools: `https://zanburak.ir/sitemap.xml` submit شده
- [ ] وضعیت sitemap در dashboard بررسی شده

---

**خلاصه:** فقط `https://zanburak.ir/sitemap.xml` را در Google و Bing وارد کنید! 🎯

